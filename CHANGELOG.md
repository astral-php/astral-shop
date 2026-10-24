# Changelog

Toutes les versions notables de ce projet seront documentées ici.

Le format s’inspire de [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/)
et ce projet adhère au [Versionnage Sémantique](https://semver.org/lang/fr/).

## [1.1.1] — 2026-10-23

### Ajouté
- Paiement **PayPal** (`astral-php/astral-paypal` ^0.1) — Orders v2 redirect + capture.
- `payment_method = paypal` au checkout (si clés sandbox/live configurées).
- Colonnes `paypal_order_id` / `paypal_capture_id` + webhook `POST /webhooks/paypal`.
- Remboursement admin PayPal (capture refund) + sync return URL.

### Notes
- Dépendance Packagist `astral-php/astral-paypal` **^0.1** (QA sandbox paiement + refund OK).
- Hub / news → **1.2.7.1**.

## [1.1.0] — 2026-10-23

### Ajouté
- Dépendance `astral-php/astral-shipping` **^0.5.3** (barèmes Colissimo / Mondial Relay matures).
- Profil livraison Colissimo / Mondial Relay (barèmes DB) + forfait / retrait.
- Colonne produit `weight_g` (admin + calcul panier).
- Checkout : point relais (`pickup_point_*` session + UI démo / hooks widget).
- Commande `shipping:rates:import` + `database/rates.example.csv`.
- Affichage point relais sur fiches commande (client / admin).

### Notes
- PayPal livré en **1.1.1** · Sendcloud / API étiquettes transporteur → plus tard.
- QA manuelle §5.2 / 5.3 (`astral-informations` `Roadmap/test.md`) OK.
- Hérité du squelette starter (auth, layout) — ce changelog ne reprend pas les versions starter.

## [1.0.2] — 2026-10-16

### Corrigé
- Encodage UTF-8 de `.env.example` (chaînes FR hero / commentaires) cassé par un rewrite PowerShell dans 1.0.1.

## [1.0.1] — 2026-10-16

### Retiré
- Pointeur racine `astral-shop.md` (roadmaps → `astral-informations` uniquement).

### Ajouté
- Guides install dans le dépôt : `docs/Installation/` + index `docs/README.md`.

## [1.0.0] — 2026-10-16

### Ajouté
- Boutique : catalogue, panier session, checkout multi-étapes.
- Paiement Stripe (`astral-payment`) + virement / chèque (instructions offline).
- Frais de port `astral-shipping` (forfait FR, retrait, franco configurable).
- Facture PDF (Dompdf) + espace client ; admin prépa / expédition + mails.
- Réservation stock offline + cron `orders:expire-offline`.
- Remboursements admin (Stripe / offline) + demande client + RIB + retours colis.
- Pages légales CGV / mentions / retours / confidentialité + case + badges checkout.
- Auth `confirm` ; `/install` 1er admin ; UI indigo Nova-like.
- Docs métier centralisées : `astral-informations/astral-shop/` (roadmap, tests).

### Clarifié (responsabilité marchand)
- Astral Shop livre le **moteur** ; l’identité légale, les textes, les clés paiement,
  les montants de port et l’exploitation sont **à établir par la personne qui ouvre la boutique**.
- Voir `docs/Installation/ouvrir-une-boutique-fr.md` §0 et disclaimer sur les pages légales.

### Hors périmètre (volontaire)
- **1.1.0** — Colissimo / Mondial Relay / Sendcloud ; infos boutique en BD.
- **1.5.0** — Optimisation (worker + cache HTTP/OPcache/Redis).
