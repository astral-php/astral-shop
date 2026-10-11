<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\CartService;
use Controller\AbstractController;
use Core\Http\Response;
use Core\Request;
use Core\Session;
use Core\View;

final class CartController extends AbstractController
{
    /** @param array<string, mixed> $shop */
    public function __construct(
        View $view,
        private Request $request,
        private Session $session,
        private CartService $cart,
        private array $shop = [],
    ) {
        parent::__construct($view);
    }

    public function show(): Response
    {
        return $this->render('cart/show', [
            'title' => 'Panier',
            'shop'  => $this->shop,
            'lines' => $this->cart->lines(),
            'total' => $this->cart->totalCents(),
            'count' => $this->cart->count(),
        ]);
    }

    public function add(): Response
    {
        $productId = (int) $this->request->input('product_id', 0);
        $qty       = max(1, (int) $this->request->input('qty', 1));
        $redirect  = $this->safeRedirect((string) $this->request->input('redirect', '/panier'));

        $error = $this->cart->add($productId, $qty);
        if ($error !== null) {
            $this->session->flash('error', $error);
            return $this->redirect($redirect);
        }

        $this->session->flash('success', 'Produit ajouté au panier.');
        $this->session->flash('cart_bump', '1');

        return $this->redirect($redirect === '/panier' ? '/panier' : $redirect);
    }

    public function update(): Response
    {
        $productId = (int) $this->request->input('product_id', 0);
        $qty       = (int) $this->request->input('qty', 0);

        $error = $this->cart->update($productId, $qty);
        if ($error !== null) {
            $this->session->flash('error', $error);
        } else {
            $this->session->flash('success', 'Panier mis à jour.');
        }

        return $this->redirect('/panier');
    }

    public function remove(): Response
    {
        $productId = (int) $this->request->input('product_id', 0);
        $this->cart->remove($productId);
        $this->session->flash('success', 'Article retiré du panier.');

        return $this->redirect('/panier');
    }

    private function safeRedirect(string $url): string
    {
        if ($url === '' || !str_starts_with($url, '/') || str_starts_with($url, '//')) {
            return '/panier';
        }

        return $url;
    }
}
