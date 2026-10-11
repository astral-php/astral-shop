# Astral Shop — Architecture ébauche v1

> Statut : **validé** (2026-10-09) — prêt pour module Catalog.  
> Skills : `astral-web-architecture`, `astral-database`, `astral-security`, `astral-payment`.  
> Entrée agent : `astral-shop.md`.

---

## 0. Inspection (ÉTAPE 1)

| Élément | Constat |
|---------|---------|
| Projet | `astral-php/astral-starter` **0.1.1** |
| Core | `astral-php/astral-core` **1.2.3** (lock) |
| Packages shop | Aucun (`payment`, `orm`, `vite`, `form` absents) |
| Présent | Auth (login/register/verify/reset), profile, users, admin rôles, layout, events/listeners basiques |
| Absent | Catalogue, panier, commandes métier, paiement, API démo |
| Rôles core | `guest`, `user`, `admin` (`Core\Auth\Role`) — colonne `users.role` défaut `user` |
| Réf. paiement | Package `D:\laragon\www\components-astral\astral-payment` (à `composer require` plus tard) |
| Réf. patterns | Hub `D:\laragon\www\mvc` Lab / payment 1.2.5 |

**Risques**

- Ne pas inventer les tables/API payment : réutiliser le package.
- Rôle CDC « customer » ≠ constante core : **mapper customer → `user`**.
- Image produit sans upload natif Astral.

---

## 1. Acteurs & cas d’usage

| Acteur | Rôle technique | Actions |
|--------|----------------|---------|
| Visiteur | guest | Voir catalogue, fiche, ajouter au panier session |
| Client | `user` | Compte, checkout, payer, voir ses commandes |
| Admin | `admin` | CRUD catégories/produits, lister commandes, support |

Matrice (extrait) :

| Acteur | Action | Données | Résultat | Authz |
|--------|--------|---------|----------|-------|
| guest | GET /boutique | products actifs | liste | public |
| guest | POST /panier | session cart | ligne ajoutée | public + CSRF |
| user | POST /checkout | cart + adresse | order pending + redirect payment | auth |
| — | POST /webhooks/stripe | event Stripe | order paid/failed | signature Stripe |
| user | GET /orders/:id | order | détail si owner | owner |
| admin | CRUD /admin/products | products | persist | admin |

**Cas limites** : stock insuffisant, produit inactif, panier vide, double webhook, paiement annulé, accès order d’autrui, prix modifié entre panier et checkout (re-prix serveur).

---

## 2. Module map

| Module | Responsabilité | Couches |
|--------|----------------|---------|
| **Identity** | Auth existante starter | garder `Auth*`, `User*`, admin users |
| **Theme / Storefront** | Branding adaptable multi-verticales | config + CSS variables + layout boutique |
| **Catalog** | Catégories, produits, stock, image optionnelle | Model/DAO/Controller/views |
| **Cart** | Panier session MVP | service session + controller |
| **Checkout** | Adresse, snapshot lignes, order pending | controller + OrderItem DAO |
| **Payment** | Stripe via package | **réutiliser** payment package |
| **Orders** | Historique client `/orders` | controllers/views shop (package redirige ici) |
| **Admin Catalog/Orders** | Back-office | Admin controllers |
| **Mail** | Confirmation si configuré | listener sur `PaymentSucceeded` (package ou app) |

**Services justifiés** (pas de couche service systématique) :

- `CartService` — lecture/écriture session, totaux, merge éventuel login  
- `CheckoutService` — validation stock, snapshot prix, création order + items (transaction)

---

## 3. Design UI (multi-verticales)

Objectif : une boutique **moderne et neutre**, adaptable habillement / tech / métiers de bouche / etc. sans refonte structurelle.

| Décision | Détail |
|----------|--------|
| Stack | HTML server-rendered ; Tailwind si `astral-vite` ajouté, sinon CSS maison + variables |
| Branding | `config/shop.php` : `store_name`, `tagline`, `primary`, `accent`, `font_display`, `hero_title` |
| Tokens CSS | `--shop-primary`, `--shop-surface`, `--shop-text`, `--shop-radius`, `--shop-font` |
| Layout | Header / nav catalogue / grille produits / fiche / panier / checkout — **pas** de look métier figé |
| Image | Carte produit : photo si présente, sinon **placeholder** SVG/neutre |
| A11y | focus visible, contrastes, formulaires labellisés |
| Hors design MVP | SPA, theme marketplace multi-vendeurs |

---

## 4. Images produit (sans upload Astral)

| Phase | Stratégie |
|-------|-----------|
| **MVP** | Colonne `products.image_path` **nullable** : chemin relatif sous `public/media/products/` **ou** URL absolue `image_url` nullable. Admin : champ texte (URL ou nom de fichier). Fichiers déposés manuellement / FTP. |
| **Affichage** | Helper vue : si image → `<img>` ; sinon placeholder. |
| **Futur** | Package `astral-upload` (ou intégration core) + template admin ; valider MIME/taille ; pas dans le MVP code. |

Ne pas bloquer la vente d’articles sans photo.

---

## 5. Modèle de données

### Existant
- `users` (starter) — `role` : `user` \| `admin`

### Nouveau (shop)

#### `categories`
| Colonne | Type | Notes |
|---------|------|-------|
| id | PK | |
| name | string | |
| slug | string unique | |
| description | text null | |
| sort_order | int default 0 | |
| active | bool/tinyint | |
| created_at / updated_at | | |

#### `products`
| Colonne | Type | Notes |
|---------|------|-------|
| id | PK | |
| category_id | FK null | |
| name | string | |
| slug | string unique | |
| description | text null | neutre multi-métier |
| price_cents | int unsigned | **centimes, prix TTC** (saisi tel quel en admin) |
| currency | char(3) default EUR | mono-devise MVP |
| stock | int unsigned | |
| sku | string null unique | |
| image_path | string null | relatif `public/…` |
| image_url | string null | URL externe alternative |
| active | bool | |
| created_at / updated_at | | |

Règle image : au plus une source affichée — priorité `image_url` puis `image_path`, sinon placeholder.

#### Panier MVP — **session** (pas de tables `carts`)

Structure session proposée :

```text
cart = {
  items: [ { product_id, qty } ],
  updated_at
}
```

Prix / nom **rechargés depuis la DB** à chaque affichage et au checkout (anti-tampering).

*Évolution* : tables `carts` / `cart_items` si panier connecté multi-device requis.

#### `order_items` (shop — en plus du package)

Le package payment fournit typiquement `orders` + `payments`. Le shop ajoute :

| Colonne | Type | Notes |
|---------|------|-------|
| id | PK | |
| order_id | FK | |
| product_id | FK null | null si produit effacé plus tard |
| product_name | string | **snapshot** |
| unit_price_cents | int | snapshot |
| quantity | int | |
| line_total_cents | int | |

Aligner `orders.total_cents` / `currency` / `status` / `stripe_intent_id` / `user_id` sur les **stubs du package** à l’intégration (ne pas dupliquer une 2ᵉ table orders).

#### Payment tables
→ migrations / modèles du package `astral-payment` uniquement.

### Contraintes & transactions

- Checkout : transaction — vérifier stock, créer order + items, décrémenter stock **ou** réserver puis confirmer au webhook (préférer : **réserver/décrémenter au paiement réussi** via listener pour éviter stock perdu si abandon Stripe ; alternative MVP simple : décrément au checkout + restore si failed/cancel — **à valider**, recommandation ébauche : **décrément sur `PaymentSucceeded`** + contrôle stock au checkout sans commit définitif, ou soft-hold).  
  **Décision proposée v1** : au checkout vérifier stock ; **décrément atomique dans le listener `PaymentSucceeded`** (re-check) ; si rupture → order failed / support.  
- Webhook idempotent (package).  
- Index : `products.slug`, `products.active`, `order_items.order_id`, `categories.slug`.

---

## 6. Flux paiement (contrat)

```text
Panier session
  → Checkout (auth) : adresse + validation
  → Order(pending) + order_items (snapshot)
  → astral-payment : PaymentIntent / page checkout package
  → Stripe
  → POST /webhooks/stripe (signé)
  → Order paid|failed + Payment row
  → Event PaymentSucceeded → stock, email, UI success
  → GET /orders, /orders/:id (à créer côté shop)
```

Référence technique : stubs + README `components-astral/astral-payment` + Lab hub.

---

## 7. Routes & permissions

### Public boutique
| Method | Path | Contrôleur (prévu) | Auth |
|--------|------|--------------------|------|
| GET | `/` | Home (vitrine) | — |
| GET | `/boutique` | CatalogController@index | — |
| GET | `/boutique/categorie/{slug}` | CatalogController@category | — |
| GET | `/produit/{slug}` | CatalogController@show | — |
| GET | `/panier` | CartController@show | — |
| POST | `/panier/ajouter` | CartController@add | CSRF |
| POST | `/panier/maj` | CartController@update | CSRF |
| POST | `/panier/retirer` | CartController@remove | CSRF |

### Client
| Method | Path | Authz |
|--------|------|-------|
| GET/POST | `/checkout` | AuthMiddleware |
| GET | `/orders` | Auth + owner list |
| GET | `/orders/{id}` | Auth + **owner** (ou admin) |

### Payment (package)
| Path | Notes |
|------|-------|
| `/payment/checkout`, `/success`, `/cancel` | Auth |
| `POST /webhooks/stripe` | middleware signature, pas CSRF |

### Admin
| Path | Authz |
|------|-------|
| `/admin/categories` CRUD | Admin |
| `/admin/products` CRUD | Admin |
| `/admin/orders` liste/détail | Admin |
| `/admin/users` | existant |

Paths exacts à aligner sur conventions starter au moment du code.

---

## 8. Plan fichiers (contrat d’implémentation — futur)

```text
CREATE (indicatif)
- config/shop.php
- database/migrations/..._create_categories_table.php
- database/migrations/..._create_products_table.php
- database/migrations/..._create_order_items_table.php
- (+ migrations payment via package)
- app/Models/Category.php, Product.php, OrderItem.php
- app/Dao/CategoryDao.php, ProductDao.php, OrderItemDao.php
- app/Services/CartService.php, CheckoutService.php
- app/Controllers/CatalogController.php, CartController.php
- app/Controllers/CheckoutController.php, OrderController.php
- app/Controllers/Admin/CategoryController.php, ProductController.php, OrderController.php
- app/Listeners/...StockOnPaymentSucceeded.php (si pas fourni)
- views/shop/..., views/cart/..., views/checkout/..., views/orders/...
- views/admin/categories/..., products/..., orders/...
- public/media/products/.gitkeep
- public/css/shop-theme.css (ou entry Vite)
- tests/...

MODIFY
- config/routes.php
- config/dependencies.php
- app/Providers/AppServiceProvider.php
- views/layout/main.php (nav boutique)
- composer.json (require astral-payment, optionnel vite/form/orm)
- .env.example (STRIPE_*, shop branding)

REUSE (ne pas réécrire)
- astral-payment Controllers/Dao/Events/stubs
- starter Auth / Admin users
```

---

## 9. Plan de tests

1. Catalogue public (actifs seulement)  
2. Panier add/update/remove + CSRF  
3. Checkout refuse panier vide / stock insuffisant  
4. Order items snapshot prix  
5. Webhook success → paid + stock (idempotence 2e delivery)  
6. Webhook fail / cancel  
7. `GET /orders/{id}` interdit pour non-owner  
8. Admin CRUD produit (avec/sans image)  
9. Placeholder si pas d’image  
10. Smoke routes auth existantes toujours OK  

---

## 10. Sécurité

- CSRF sur tous POST HTML ; webhook exempt + signature  
- Prix / stock côté serveur uniquement  
- Authz objet sur orders  
- Secrets Stripe dans `.env`  
- Échapper sorties ; pas de stack en prod  
- Uploads absents MVP → pas de surface MIME pour l’instant  
- Rate-limit login déjà / à vérifier starter  

---

## 11. Dépendances Composer (quand on code)

| Package | MVP | Motif |
|---------|-----|-------|
| `astral-php/astral-payment` | **oui** | Stripe |
| `astral-php/astral-vite` | recommandé | Tailwind / assets |
| `astral-php/astral-form` | optionnel | formulaires admin |
| `astral-php/astral-extend-orm` | optionnel | fill/casts models |
| `astral-upload` | **non** | futur |

---

## 12. Décisions tranchées dans cette ébauche

| Sujet | Décision |
|-------|----------|
| Rôle client | `user` (pas de rôle `customer` en DB) |
| Panier MVP | Session PHP |
| Devise | EUR only |
| Images | Optionnelles ; URL ou fichier manuel sous `public/media/products/` |
| Upload | Hors MVP → futur package |
| UI | Thème neutre + variables / `config/shop.php` |
| Stock | Vérif checkout ; décrément sur `PaymentSucceeded` |
| **Prix / TVA** | Admin saisit le **prix TTC** (`price_cents`). Pas de champ taux TVA, HT, ni calcul fiscal au MVP. Affichage boutique = ce montant. |
| Orders UI | À créer dans le shop (`/orders`) |
| API REST | Phase 2 |

## 13. Validation

**Validé** par le product owner (2026-10-09), y compris :
- panier session
- décrément stock sur `PaymentSucceeded`
- adresse minimale
- **admin = prix TTC** (pas de moteur TVA)
- `astral-vite` : au choix à l’implémentation Catalog (tokens CSS possibles en premier)

Évolution possible plus tard : champs `tax_rate` / HT si besoin légal — hors MVP.  

---

## 14. Hors scope MVP

multi-devise, variantes (taille/couleur), promos, abonnements, marketplace, SPA, upload intégré, API publique, multi-entrepôt.
