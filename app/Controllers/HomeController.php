<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Dao\ProductDao;
use App\Dao\UserDao;
use Controller\AbstractController;
use Core\Http\Response;
use Core\View;

/**
 * Accueil vitrine boutique.
 */
final class HomeController extends AbstractController
{
    /** @param array<string, mixed> $shop */
    public function __construct(
        View $view,
        private UserDao $userDao,
        private ProductDao $productDao,
        private array $shop = [],
    ) {
        parent::__construct($view);
    }

    public function index(): Response
    {
        $featured = $this->productDao->findActive(null, 6);

        return $this->render('home/index', [
            'title'    => (string) ($this->shop['store_name'] ?? 'Astral Shop'),
            'shop'     => $this->shop,
            'hasUsers' => $this->userDao->count() > 0,
            'featured' => $featured,
        ]);
    }
}
