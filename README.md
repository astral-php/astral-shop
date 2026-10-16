# Astral Shop

Boutique e-commerce pour l’écosystème [Astral](https://github.com/astral-php) — catalogue, panier, checkout, Stripe, frais de port, facture PDF, remboursements et retours colis.

[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php&logoColor=white)](https://www.php.net)
[![Version](https://img.shields.io/badge/version-1.0.2-blue)](./CHANGELOG.md)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

> **Version 1.0.2** — moteur boutique prêt (gate métier + légale).  
> **Ouvrir une boutique** reste à la charge du marchand : identité, textes, paiements, port, exploitation.  
> → Documentation : **[docs/README.md](./docs/README.md)** · guides dans [`docs/Installation/`](./docs/Installation/)

Écosystème : [Astral MVC](https://github.com/astral-php/astral) · [astral-payment](https://packagist.org/packages/astral-php/astral-payment) · [astral-shipping](https://packagist.org/packages/astral-php/astral-shipping) · [astral-template](https://packagist.org/packages/astral-php/astral-template) · [astral-php.dev](https://astral-php.dev)

## Périmètre 1.0.0

| Zone | Contenu |
|------|---------|
| Catalogue | Catégories, produits TTC, stock, upload image |
| Auth | `AUTH_REGISTRATION=confirm` (défaut boutique), `/install` 1er admin |
| Tunnel | Panier → adresse → **livraison** → carte / virement / chèque + acceptation CGV |
| Paiement | Stripe (webhook) + offline (IBAN / chèque) |
| Port | `astral-shipping` : **forfait FR** + retrait + franco (seuil configurable) |
| Commandes | Statut paiement ≠ statut colis ; admin préparation / expédition |
| Facture | PDF Dompdf au paiement, téléchargement espace client |
| Remboursements | Demande client, RIB offline, accept/refus, Stripe / marquage offline |
| Retours | Délai, réception admin / dispense, refund verrouillé tant que non reçu |
| Légal | Pages modèles CGV / mentions / retours / confidentialité + `SHOP_LEGAL_*` |
| UI | Indigo Nova-like (`#4f46e5`), `astral-template` admin |

**Hors 1.0.0 :** Colissimo / Mondial Relay / Sendcloud → **1.1.0** · worker / cache avancé → **1.5.0**.

## Moteur vs marchand (à être clair)

| Fourni par Astral Shop | À établir par la personne qui ouvre la boutique |
|------------------------|--------------------------------------------------|
| Parcours commande, états, garde-fous retours/refund | Identité légale (SIRET, adresse, hébergeur, contact) |
| Pages légales **modèles** + case CGV checkout | Textes adaptés à l’activité + validation pro si besoin |
| Intégration Stripe / offline / facture PDF | Clés **live**, IBAN/chèque réels, mailer prod |
| Port forfait + franco + retrait | Montants et politique tarifaire |
| Admin, cron expire offline | Catalogue, `APP_URL`, cron, monitoring |

Sans cette configuration, le projet sert de **démo / socle** — pas d’une boutique professionnelle ouverte telle quelle.

Détail et checklist : [`docs/Installation/ouvrir-une-boutique-fr.md`](./docs/Installation/ouvrir-une-boutique-fr.md)

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
3. Configurer `STRIPE_*`, `SHOP_LEGAL_*`, IBAN / chèque si besoin  
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
SHOP_STORE_ADDRESS="Votre adresse magasin / retour colis"
SHOP_LEGAL_COMPANY="Votre Société SAS"
SHOP_LEGAL_SIRET="123 456 789 00012"
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
```

Voir `.env.example` pour Stripe, virement, chèque et l’ensemble `SHOP_LEGAL_*`.

Chèque / virement : le stock est réservé à l’engagement jusqu’à `payment_due_at`.  
Expiration + restock (cron / Task Scheduler, ex. 1×/heure) :

```bash
php bin/console orders:expire-offline
```

## Documentation

| Doc | Chemin |
|-----|--------|
| **Index docs** (entrée unique) | [`docs/README.md`](./docs/README.md) |
| Install pas à pas | [`docs/Installation/guide-install-pas-a-pas.md`](./docs/Installation/guide-install-pas-a-pas.md) |
| Ouvrir une boutique FR | [`docs/Installation/ouvrir-une-boutique-fr.md`](./docs/Installation/ouvrir-une-boutique-fr.md) |
| Roadmap / QA (hub interne) | `astral-informations/astral-shop/Roadmap/` |

## Roadmap

- **1.0.0 → 1.0.2** — livré ; 1.0.1 sans pointeur interne ; 1.0.2 UTF-8 `.env.example`  
- **1.1.0** — Colissimo / Mondial Relay / Sendcloud ; légal en BD  
- **1.5.0** — Optimisation (worker + cache)

## Licence

MIT
