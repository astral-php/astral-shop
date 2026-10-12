# Astral Shop

Boutique e-commerce de démonstration pour l’écosystème [Astral](https://github.com/astral-php) — catalogue, panier, checkout, Stripe, frais de port, facture PDF.

[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php&logoColor=white)](https://www.php.net)
[![Status](https://img.shields.io/badge/status-dev%20(main)-orange)](./CHANGELOG.md)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

> **En développement (`main`).** Pas de version Packagist / `create-project` pour le moment.  
> Pas de **1.0.0** tant que la campagne de tests globaux n’est pas validée.

Écosystème : [Astral MVC](https://github.com/astral-php/astral) · [astral-payment](https://packagist.org/packages/astral-php/astral-payment) · [astral-shipping](https://packagist.org/packages/astral-php/astral-shipping) · [astral-template](https://packagist.org/packages/astral-php/astral-template) · [astral-php.dev](https://astral-php.dev)

## Fonctionnalités (état actuel)

| Zone | Contenu |
|------|---------|
| Catalogue | Catégories, produits TTC, stock, upload image |
| Auth | `AUTH_REGISTRATION=confirm` (défaut boutique), `/install` 1er admin |
| Tunnel | Panier → adresse → **livraison** → carte / virement / chèque |
| Paiement | Stripe (webhook) + offline (IBAN / chèque) |
| Port | `astral-shipping` : forfait FR + retrait + franco (seuil configurable) |
| Commandes | Statut paiement ≠ statut colis ; admin préparation / expédition |
| Facture | PDF Dompdf au paiement, téléchargement espace client |
| UI | Indigo Nova-like (`#4f46e5`), `astral-template` admin |

## Prérequis

- PHP ^8.1, Composer 2  
- SQLite (défaut) ou MySQL  
- Mailpit (ou SMTP) pour les e-mails  
- Compte Stripe test + CLI (`stripe listen`) pour la carte  

## Installation locale

```bash
git clone https://github.com/astral-php/astral-shop.git
cd astral-shop
composer install
cp .env.example .env
php bin/console migrate
composer run serve
# ou vhost Laragon → http://astral-shop.local
```

1. Ouvrir `/install` → créer le compte admin  
2. Admin : catégories + produits  
3. Configurer `STRIPE_*` et éventuellement IBAN / chèque dans `.env`  
4. Webhook local :  
   `stripe listen --forward-to http://astral-shop.local/webhooks/stripe`

## Dépendances Astral (Packagist)

```json
"astral-php/astral-core": "^1.2",
"astral-php/astral-payment": "^0.1.2",
"astral-php/astral-shipping": "^0.1.0",
"astral-php/astral-template": "^0.1"
```

## Configuration utile

```env
AUTH_REGISTRATION=confirm
SHOP_PRIMARY=#4f46e5
SHOP_FLAT_RATE_CENTS=590
SHOP_FREE_SHIPPING_CENTS=6000
SHOP_OFFLINE_PAYMENT_DAYS=7
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
```

Voir `.env.example` pour Stripe, virement et chèque.

Chèque / virement : le stock est réservé à l’engagement jusqu’à `payment_due_at`.  
Expiration + restock (cron / Task Scheduler, ex. 1×/heure) :

```bash
php bin/console orders:expire-offline
```

## Roadmap courte

- Tests échecs paiement Stripe  
- Profils frais de port (Colissimo / Mondial Relay / API) au choix  
- Guide démarrage / onboarding  
- Campagne tests globaux → puis éventuelle **1.0.0**

## Licence

MIT
