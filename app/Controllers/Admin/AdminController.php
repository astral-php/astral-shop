<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use Controller\AbstractController;
use Core\Http\Response;

/**
 * Base back-office : layout astral-template `admin` + navigation dédiée.
 */
abstract class AdminController extends AbstractController
{
    /**
     * @return list<array{href: string, label: string}>
     */
    protected function adminNav(): array
    {
        return [
            ['href' => '/admin/orders', 'label' => 'Commandes'],
            ['href' => '/admin/products', 'label' => 'Produits'],
            ['href' => '/admin/categories', 'label' => 'Catégories'],
            ['href' => '/admin/users', 'label' => 'Comptes'],
            ['href' => '/', 'label' => '← Boutique'],
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function render(string $view, array $data = []): Response
    {
        $this->view->setLayout('admin');
        $data['adminNav'] = $data['adminNav'] ?? $this->adminNav();

        return parent::render($view, $data);
    }
}
