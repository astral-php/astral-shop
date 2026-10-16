<?php

declare(strict_types=1);

namespace App\Controllers;

use Controller\AbstractController;
use Core\Exception\NotFoundException;
use Core\Http\Response;
use Core\View;

/**
 * Pages légales publiques (CGV, mentions, retours, confidentialité).
 * Contenu modèle générique e-commerce FR — à personnaliser par le marchand.
 */
final class LegalController extends AbstractController
{
    private const PAGES = [
        'cgv'             => ['view' => 'legal/cgv', 'title' => 'Conditions générales de vente'],
        'mentions'        => ['view' => 'legal/mentions', 'title' => 'Mentions légales'],
        'retours'         => ['view' => 'legal/retours', 'title' => 'Politique de retour et de rétractation'],
        'confidentialite' => ['view' => 'legal/confidentialite', 'title' => 'Politique de confidentialité'],
    ];

    /** @param array<string, mixed> $shop */
    public function __construct(
        View $view,
        private array $shop = [],
    ) {
        parent::__construct($view);
    }

    public function cgv(): Response
    {
        return $this->renderPage('cgv');
    }

    public function mentions(): Response
    {
        return $this->renderPage('mentions');
    }

    public function retours(): Response
    {
        return $this->renderPage('retours');
    }

    public function confidentialite(): Response
    {
        return $this->renderPage('confidentialite');
    }

    public function show(string $page): Response
    {
        return $this->renderPage($page);
    }

    private function renderPage(string $page): Response
    {
        $key = strtolower(trim($page));
        if (!isset(self::PAGES[$key])) {
            throw new NotFoundException('Page introuvable.');
        }

        $meta = self::PAGES[$key];
        /** @var array<string, mixed> $legal */
        $legal = is_array($this->shop['legal'] ?? null) ? $this->shop['legal'] : [];

        return $this->render($meta['view'], [
            'title' => $meta['title'],
            'shop'  => $this->shop,
            'legal' => $legal,
        ]);
    }
}
