<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Cache\CatalogReadCache;
use App\Dao\CategoryDao;
use App\Support\Slug;
use Core\Exception\NotFoundException;
use Core\Http\Response;
use Core\Request;
use Core\Session;
use Core\View;

final class CategoryController extends AdminController
{
    public function __construct(
        View $view,
        private Request $request,
        private CategoryDao $categoryDao,
        private Session $session,
        private CatalogReadCache $catalogCache,
    ) {
        parent::__construct($view);
    }

    public function index(): Response
    {
        return $this->render('admin/categories/index', [
            'title'      => 'Catégories',
            'categories' => $this->categoryDao->findAllOrdered(),
        ]);
    }

    public function create(): Response
    {
        return $this->render('admin/categories/form', [
            'title'    => 'Nouvelle catégorie',
            'category' => null,
            'errors'   => [],
            'old'      => [],
        ]);
    }

    public function store(): Response
    {
        $data = $this->payload();
        $v    = $this->validate($data, [
            'name' => 'required|min:2|max:150',
            'slug' => 'max:160',
        ]);

        if ($v->fails()) {
            return $this->render('admin/categories/form', [
                'title'    => 'Nouvelle catégorie',
                'category' => null,
                'errors'   => $v->errors(),
                'old'      => $data,
            ]);
        }

        $slug = $data['slug'] !== '' ? Slug::from($data['slug']) : Slug::from($data['name']);
        $slug = $this->uniqueSlug($slug);

        $this->categoryDao->insert([
            'name'        => $data['name'],
            'slug'        => $slug,
            'description' => $data['description'] !== '' ? $data['description'] : null,
            'sort_order'  => $data['sort_order'],
            'active'      => $data['active'],
        ]);

        $this->catalogCache->invalidate();
        $this->session->flash('success', 'Catégorie créée.');
        return $this->redirect('/admin/categories');
    }

    public function edit(string $id): Response
    {
        $category = $this->categoryDao->findById((int) $id);
        if ($category === null) {
            throw new NotFoundException('Catégorie introuvable.');
        }

        return $this->render('admin/categories/form', [
            'title'    => 'Modifier la catégorie',
            'category' => $category,
            'errors'   => [],
            'old'      => [],
        ]);
    }

    public function update(string $id): Response
    {
        $categoryId = (int) $id;
        $category   = $this->categoryDao->findById($categoryId);
        if ($category === null) {
            throw new NotFoundException('Catégorie introuvable.');
        }

        $data = $this->payload();
        $v    = $this->validate($data, [
            'name' => 'required|min:2|max:150',
            'slug' => 'max:160',
        ]);

        if ($v->fails()) {
            return $this->render('admin/categories/form', [
                'title'    => 'Modifier la catégorie',
                'category' => $category,
                'errors'   => $v->errors(),
                'old'      => $data,
            ]);
        }

        $slug = $data['slug'] !== '' ? Slug::from($data['slug']) : Slug::from($data['name']);
        $slug = $this->uniqueSlug($slug, $categoryId);

        $this->categoryDao->update($categoryId, [
            'name'        => $data['name'],
            'slug'        => $slug,
            'description' => $data['description'] !== '' ? $data['description'] : null,
            'sort_order'  => $data['sort_order'],
            'active'      => $data['active'],
        ]);

        $this->catalogCache->invalidate();
        $this->session->flash('success', 'Catégorie mise à jour.');
        return $this->redirect('/admin/categories');
    }

    public function destroy(string $id): Response
    {
        $category = $this->categoryDao->findById((int) $id);
        if ($category === null) {
            throw new NotFoundException('Catégorie introuvable.');
        }

        $this->categoryDao->delete((int) $id);
        $this->catalogCache->invalidate();
        $this->session->flash('success', 'Catégorie supprimée.');
        return $this->redirect('/admin/categories');
    }

    /** @return array{name: string, slug: string, description: string, sort_order: int, active: int} */
    private function payload(): array
    {
        return [
            'name'        => trim((string) $this->request->input('name', '')),
            'slug'        => trim((string) $this->request->input('slug', '')),
            'description' => trim((string) $this->request->input('description', '')),
            'sort_order'  => (int) $this->request->input('sort_order', 0),
            'active'      => $this->request->input('active') ? 1 : 0,
        ];
    }

    private function uniqueSlug(string $base, ?int $exceptId = null): string
    {
        $slug = $base;
        $i    = 2;
        while ($this->categoryDao->slugExists($slug, $exceptId)) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }
}
