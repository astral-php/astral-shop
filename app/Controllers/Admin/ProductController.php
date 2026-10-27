<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Cache\CatalogReadCache;
use App\Dao\CategoryDao;
use App\Dao\ProductDao;
use App\Support\Money;
use App\Support\ProductImageUploader;
use App\Support\Slug;
use Core\Exception\NotFoundException;
use Core\Http\Response;
use Core\Request;
use Core\Session;
use Core\View;

final class ProductController extends AdminController
{
    /** @param array<string, mixed> $shop */
    public function __construct(
        View $view,
        private Request $request,
        private ProductDao $productDao,
        private CategoryDao $categoryDao,
        private Session $session,
        private ProductImageUploader $uploader,
        private CatalogReadCache $catalogCache,
        private array $shop = [],
    ) {
        parent::__construct($view);
    }

    public function index(): Response
    {
        return $this->render('admin/products/index', [
            'title'    => 'Produits',
            'products' => $this->productDao->findAllWithCategory(),
            'shop'     => $this->shop,
        ]);
    }

    public function create(): Response
    {
        return $this->render('admin/products/form', [
            'title'      => 'Nouveau produit',
            'product'    => null,
            'categories' => $this->categoryDao->findAllOrdered(),
            'errors'     => [],
            'old'        => [],
            'shop'       => $this->shop,
        ]);
    }

    public function store(): Response
    {
        [$data, $priceCents, $priceError] = $this->payload();

        $v = $this->validate($data, [
            'name' => 'required|min:2|max:200',
            'slug' => 'max:220',
        ]);

        $errors = $v->errors();
        if ($priceError !== null) {
            $errors['price'] = [$priceError];
        }

        [$uploadedPath, $uploadError] = $this->handleUpload();
        if ($uploadError !== null) {
            $errors['image_file'] = [$uploadError];
        }
        if ($uploadedPath !== null) {
            $data['image_path'] = $uploadedPath;
        }

        if ($errors !== []) {
            return $this->render('admin/products/form', [
                'title'      => 'Nouveau produit',
                'product'    => null,
                'categories' => $this->categoryDao->findAllOrdered(),
                'errors'     => $errors,
                'old'        => $data,
                'shop'       => $this->shop,
            ]);
        }

        $slug = $data['slug'] !== '' ? Slug::from($data['slug']) : Slug::from($data['name']);
        $slug = $this->uniqueSlug($slug);

        $this->productDao->insert([
            'category_id' => $data['category_id'],
            'name'        => $data['name'],
            'slug'        => $slug,
            'description' => $data['description'] !== '' ? $data['description'] : null,
            'price_cents' => $priceCents,
            'currency'    => (string) ($this->shop['currency'] ?? 'EUR'),
            'stock'       => $data['stock'],
            'weight_g'    => $data['weight_g'],
            'sku'         => $data['sku'] !== '' ? $data['sku'] : null,
            'image_path'  => $data['image_path'] !== '' ? $data['image_path'] : null,
            'image_url'   => $data['image_url'] !== '' ? $data['image_url'] : null,
            'active'      => $data['active'],
        ]);

        $this->catalogCache->invalidate();
        $this->session->flash('success', 'Produit créé (prix TTC enregistré).');
        return $this->redirect('/admin/products');
    }

    public function edit(string $id): Response
    {
        $product = $this->productDao->findById((int) $id);
        if ($product === null) {
            throw new NotFoundException('Produit introuvable.');
        }

        return $this->render('admin/products/form', [
            'title'      => 'Modifier le produit',
            'product'    => $product,
            'categories' => $this->categoryDao->findAllOrdered(),
            'errors'     => [],
            'old'        => [],
            'shop'       => $this->shop,
        ]);
    }

    public function update(string $id): Response
    {
        $productId = (int) $id;
        $product   = $this->productDao->findById($productId);
        if ($product === null) {
            throw new NotFoundException('Produit introuvable.');
        }

        [$data, $priceCents, $priceError] = $this->payload();

        $v = $this->validate($data, [
            'name' => 'required|min:2|max:200',
            'slug' => 'max:220',
        ]);

        $errors = $v->errors();
        if ($priceError !== null) {
            $errors['price'] = [$priceError];
        }

        [$uploadedPath, $uploadError] = $this->handleUpload();
        if ($uploadError !== null) {
            $errors['image_file'] = [$uploadError];
        }
        if ($uploadedPath !== null) {
            $data['image_path'] = $uploadedPath;
        } elseif ($data['image_path'] === '' && $product->image_path) {
            // Conserver l’image existante si aucun nouveau fichier / chemin
            $data['image_path'] = (string) $product->image_path;
        }

        if ($errors !== []) {
            return $this->render('admin/products/form', [
                'title'      => 'Modifier le produit',
                'product'    => $product,
                'categories' => $this->categoryDao->findAllOrdered(),
                'errors'     => $errors,
                'old'        => $data,
                'shop'       => $this->shop,
            ]);
        }

        $slug = $data['slug'] !== '' ? Slug::from($data['slug']) : Slug::from($data['name']);
        $slug = $this->uniqueSlug($slug, $productId);

        $this->productDao->update($productId, [
            'category_id' => $data['category_id'],
            'name'        => $data['name'],
            'slug'        => $slug,
            'description' => $data['description'] !== '' ? $data['description'] : null,
            'price_cents' => $priceCents,
            'stock'       => $data['stock'],
            'weight_g'    => $data['weight_g'],
            'sku'         => $data['sku'] !== '' ? $data['sku'] : null,
            'image_path'  => $data['image_path'] !== '' ? $data['image_path'] : null,
            'image_url'   => $data['image_url'] !== '' ? $data['image_url'] : null,
            'active'      => $data['active'],
        ]);

        $this->catalogCache->invalidate();
        $this->session->flash('success', 'Produit mis à jour.');
        return $this->redirect('/admin/products');
    }

    public function destroy(string $id): Response
    {
        $product = $this->productDao->findById((int) $id);
        if ($product === null) {
            throw new NotFoundException('Produit introuvable.');
        }

        $this->productDao->delete((int) $id);
        $this->catalogCache->invalidate();
        $this->session->flash('success', 'Produit supprimé.');
        return $this->redirect('/admin/products');
    }

    /**
     * @return array{0: array<string, mixed>, 1: int, 2: ?string}
     */
    private function payload(): array
    {
        $priceRaw   = trim((string) $this->request->input('price', ''));
        $priceCents = Money::parseEurosToCents($priceRaw);
        $priceError = null;

        if ($priceCents === null) {
            $priceError = 'Indiquez un prix TTC valide (ex. 24,90).';
            $priceCents = 0;
        }

        $categoryRaw = trim((string) $this->request->input('category_id', ''));

        $data = [
            'name'        => trim((string) $this->request->input('name', '')),
            'slug'        => trim((string) $this->request->input('slug', '')),
            'description' => trim((string) $this->request->input('description', '')),
            'price'       => $priceRaw,
            'category_id' => $categoryRaw === '' ? null : (int) $categoryRaw,
            'stock'       => max(0, (int) $this->request->input('stock', 0)),
            'weight_g'    => max(0, (int) $this->request->input('weight_g', 0)),
            'sku'         => trim((string) $this->request->input('sku', '')),
            'image_path'  => trim((string) $this->request->input('image_path', '')),
            'image_url'   => trim((string) $this->request->input('image_url', '')),
            'active'      => $this->request->input('active') ? 1 : 0,
        ];

        return [$data, $priceCents, $priceError];
    }

    private function uniqueSlug(string $base, ?int $exceptId = null): string
    {
        $slug = $base;
        $i    = 2;
        while ($this->productDao->slugExists($slug, $exceptId)) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }

    /** @return array{0: ?string, 1: ?string} */
    private function handleUpload(): array
    {
        /** @var array<string, mixed>|null $file */
        $file = $_FILES['image_file'] ?? null;
        if (!is_array($file)) {
            return [null, null];
        }

        return $this->uploader->store($file, dirname(__DIR__, 3) . '/public');
    }
}
