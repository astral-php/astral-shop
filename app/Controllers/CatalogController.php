<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Cache\CatalogReadCache;
use App\Dao\CategoryDao;
use App\Dao\ProductDao;
use Controller\AbstractController;
use Core\Exception\NotFoundException;
use Core\Http\Response;
use Core\View;

final class CatalogController extends AbstractController
{
    /** @param array<string, mixed> $shop */
    public function __construct(
        View $view,
        private CategoryDao $categoryDao,
        private ProductDao $productDao,
        private CatalogReadCache $catalogCache,
        private array $shop = [],
    ) {
        parent::__construct($view);
    }

    public function index(): Response
    {
        $categories = $this->catalogCache->activeCategories();
        $products   = $this->catalogCache->activeProducts();

        return $this->render('shop/index', [
            'title'      => (string) ($this->shop['store_name'] ?? 'Boutique'),
            'shop'       => $this->shop,
            'categories' => $categories,
            'products'   => $products,
            'category'   => null,
        ]);
    }

    public function category(string $slug): Response
    {
        $category = $this->categoryDao->findBySlug($slug);

        if ($category === null || !$category->isActive()) {
            throw new NotFoundException('Catégorie introuvable.');
        }

        $categories = $this->catalogCache->activeCategories();
        $products   = $this->catalogCache->activeProducts($category->id);

        return $this->render('shop/index', [
            'title'      => $category->name,
            'shop'       => $this->shop,
            'categories' => $categories,
            'products'   => $products,
            'category'   => $category,
        ]);
    }

    public function show(string $slug): Response
    {
        // Fiche produit : pas de cache (stock / dispo à jour)
        $product = $this->productDao->findBySlug($slug);

        if ($product === null || !$product->isActive()) {
            throw new NotFoundException('Produit introuvable.');
        }

        return $this->render('shop/show', [
            'title'   => $product->name,
            'shop'    => $this->shop,
            'product' => $product,
        ]);
    }
}
