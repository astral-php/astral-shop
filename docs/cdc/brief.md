# Brief — Astral Shop (ébauche v1)

> Cahier des charges. Détail architecture : `docs/cdc/architecture.md`.  
> Entrée agent : `astral-shop.md`.

**PROJECT_NAME** = "Astral Shop"

**PROJECT_TYPE** = "Boutique e-commerce démonstrative / vitrine Astral"

**PROJECT_DESCRIPTION** = "Boutique Astral MVC : catalogue produits multi-verticales (habillement, tech, métiers de bouche, etc.), panier, commande, paiement Stripe via astral-payment, espace client et administration. Design moderne neutre adaptable par branding. Photos produit optionnelles (sans upload natif au MVP)."

**TARGET_USERS** = "Visiteurs (guest), clients (`user`), administrateurs (`admin`)"

**MAIN_OBJECTIVES** =
- Parcours e-commerce complet sur Astral (pas un clone Shopify)
- Thème adaptable à tout type de produit
- Réutiliser astral-payment + patterns hub Lab 1.2.5
- CDC / architecture validés avant code

**MAIN_FEATURES** =
- Catalogue (catégories, produits, stock, image optionnelle)
- Panier session
- Checkout + Stripe (`astral-payment`) + webhooks
- Compte client (`/orders`)
- Admin : catégories, produits, commandes
- Branding via `config/shop.php` + CSS variables
- Emails si mailer configuré

**AUTHENTICATION** = "COMPTE (user + admin) — starter existant"

**ROLES** = "guest, user (= client), admin"

**API_REQUIRED** = "NON au MVP"

**FRONTEND_STYLE** = "Server-rendered ; thème neutre moderne ; Tailwind si astral-vite ; tokens CSS"

**DEPLOYMENT_TARGET** = "LOCAL (Laragon) puis VPS"

**SPECIAL_CONSTRAINTS** =
- Minimal Astral ; DAOs ; pas de surarchitecture
- Montants en centimes ; webhook = vérité paiement
- Image optionnelle : `image_url` / `image_path` ; pas d’upload Astral (futur `astral-upload`)
- Secrets Stripe dans `.env`
- Gate CDC avant code

**EXTERNAL_SERVICES** = "Stripe ; MailPit local optionnel"

**TECH_BASE** = "astral-starter 0.1.1 + astral-core 1.2.3 ; ajouter astral-payment à l’implémentation"
