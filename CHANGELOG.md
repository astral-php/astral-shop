# Changelog

Toutes les versions notables de ce projet seront documentées ici.

Le format s’inspire de [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/)
et ce projet adhère au [Versionnage Sémantique](https://semver.org/lang/fr/).

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
- Docs métier centralisées : `astral-informations/astral-shop/` (roadmap, tests, install).

### Clarifié (responsabilité marchand)
- Astral Shop livre le **moteur** ; l’identité légale, les textes, les clés paiement,
  les montants de port et l’exploitation sont **à établir par la personne qui ouvre la boutique**.
- Voir `Features/Installation/ouvrir-une-boutique-fr.md` §0 et disclaimer sur les pages légales.

### Hors périmètre (volontaire)
- **1.1.0** — Colissimo / Mondial Relay / Sendcloud ; infos boutique en BD.
- **1.5.0** — Optimisation (worker + cache HTTP/OPcache/Redis).

## [Unreleased]

### Ajouté
- Guides install dans le dépôt : `docs/Installation/` + index unique `docs/README.md` (liens depuis le README principal).

### Notes
- Hérité du squelette starter (auth, layout) — ce changelog ne reprend pas les versions starter.
