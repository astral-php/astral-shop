# Changelog

Toutes les versions notables de ce projet seront documentées ici.

> **Astral Shop est en développement (`main`).**  
> Aucune release Packagist / tag `1.0.0` pour le moment.  
> Les entrées ci-dessous décrivent l’historique de travail en cours, pas des versions publiées.

Le format s’inspire de [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/).

## [Unreleased] — développement

### Ajouté
- Boutique basée sur astral-starter : catalogue, panier session, checkout multi-étapes.
- Paiement Stripe (`astral-payment`) + virement / chèque (instructions offline).
- Frais de port via `astral-shipping` ^0.1.0 (forfait FR, retrait boutique, franco configurable).
- Colonnes commande : `payment_method`, `fulfillment_status`, `subtotal_cents`, `shipping_cents`, `invoice_path`.
- Snapshot livraison (`order_shipments`).
- Facture PDF (Dompdf) à la confirmation de paiement + mention e-mail + téléchargement espace client.
- Admin : marquer payée / préparation / envoyée + mails confirmation et expédition.
- Auth `confirm` par défaut ; `/install` pour le premier admin ; UI indigo Nova-like.
- Dépendances Packagist : payment ^0.1.2, template ^0.1, shipping ^0.1.0, core ^1.2.

### En cours / prévu
- Tests globaux (campagne QA) et correctifs associés.
- Profils de port avancés (Colissimo, Mondial Relay, Sendcloud) au choix.
- Guide de démarrage post-install.
- Tests échecs paiement Stripe.

### Notes
- Hérité du squelette starter (auth, layout, `.htaccess`) — ce changelog ne reprend pas les numéros de version starter comme releases du shop.
