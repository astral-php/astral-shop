# Astral Shop — Installation pas à pas (application à vide)

> Guide technique + marchand pour démarrer une boutique neuve.  
> **1.0.0** : configuration via **`.env`**. Les infos légales / adresse en base (admin) → **1.1.0**.

---

## 0. Prérequis

- PHP ^8.1, Composer 2, Laragon (ou équivalent)
- SQLite (défaut) ou MySQL
- Mailpit (ou SMTP) pour les e-mails
- Compte Stripe test (carte) + CLI `stripe` pour le webhook local

---

## 1. Installer le code

```bash
git clone https://github.com/astral-php/astral-shop.git
cd astral-shop
composer install
cp .env.example .env
php bin/console migrate
php bin/console shipping:rates:import database/rates.example.csv --replace
```

Vhost Laragon → ex. `http://astral-shop.local`  
ou `composer run serve`.

**Livraison 1.1 :** barèmes Colissimo / Mondial Relay via CSV (exemple fictif ci-dessus).  
Produits : renseigner le **poids (g)** en admin. Widgets relais officiels → doc `astral-shipping` `widgets-pickup.md` (UI saisie démo dans le checkout).

### Worker (file d’attente — e-mails / factures PDF)

Après migrate, les tables `jobs` / `failed_jobs` existent. Dans `.env` :

| `QUEUE_DRIVER` | Usage |
|----------------|--------|
| `database` | **Défaut shop** — traiter la file : `php bin/console worker:work --stop-when-empty` (cron / terminal) |
| `sync` | Dév / CI — jobs exécutés tout de suite dans la requête (pas de worker) |
| `redis` | Opt-in — activer **extension Redis** Laragon + service Redis ; ne pas activer avant besoin (charge / Phase cache) |

Cron Laragon / Planificateur (ex.) : chaque minute  
`php bin/console worker:work --stop-when-empty`

---

## 2. Premier admin

1. Ouvrir `/install`
2. Créer le compte administrateur (e-mail déjà vérifié)
3. `/install` devient inaccessible ensuite

Auth boutique : **`AUTH_REGISTRATION=confirm`** (ne pas basculer `direct` → `confirm` sur une BDD déjà peuplée).

---

## 3. Que mettre dans `.env` ? (choix 1.0.0)

### Toujours dans `.env` (secrets & infra)

| Variable | Rôle |
|----------|------|
| `APP_URL` / `APP_BASE_URL` | URL publique (HTTPS en prod) |
| `APP_KEY` / secrets session | Sécurité |
| `STRIPE_*` | Clés + webhook secret |
| `MAIL_*` | SMTP / Mailpit |
| `DB_*` | Base de données |
| `QUEUE_DRIVER` | File worker (`database` / `sync` / `redis`) |
| `CACHE_DRIVER` | Cache catalogue (`file` défaut / `redis` opt-in) |
| `CACHE_CATALOG_TTL` | TTL listes home/catalogue (secondes, défaut 300) |
| `REDIS_*` | Host/port si cache ou queue Redis (Laragon :6379) |

### Cache lecture (catalogue / home)

- **Caché :** listes catégories + produits actifs, featured home.  
- **Non caché :** fiche produit (stock), panier, checkout, commandes.  
- Invalidation auto à la création / édition / suppression admin produit ou catégorie.  
- `CACHE_DRIVER=redis` : service Redis + **extension PHP redis** (Laragon → PHP → Extensions → `redis`). Sans ext, fallback **file** (log warning).

### Identité boutique & légal — `.env` en 1.0.0

> **À charge du marchand.** Le logiciel fournit les pages et le mapping `.env` ;  
> **vous** devez renseigner l’identité réelle et adapter les textes avant ouverture.  
> Détail : `ouvrir-une-boutique-fr.md` §0 (moteur vs marchand).

| Variable | Obligatoire avant **prod** |
|----------|----------------------------|
| `SHOP_STORE_NAME` | Recommandé |
| `SHOP_STORE_ADDRESS` | Oui (retrait + adresse retour) |
| `SHOP_LEGAL_COMPANY` | Oui |
| `SHOP_LEGAL_ADDRESS` | Oui |
| `SHOP_LEGAL_EMAIL` | Oui |
| `SHOP_LEGAL_SIRET` | **Oui** (vente pro) |
| `SHOP_LEGAL_RCS` / `SHOP_LEGAL_VAT` | Si applicable |
| `SHOP_LEGAL_DIRECTOR` | Oui (mentions) |
| `SHOP_LEGAL_HOST` | Oui (hébergeur) |
| `SHOP_RETURN_DAYS` | Défaut 14 — à valider pour votre activité |
| `SHOP_NOTIFY_EMAIL` | Alertes (demandes refund) |
| `SHOP_FLAT_RATE_CENTS` / `SHOP_FREE_SHIPPING_CENTS` | Port MVP (montants à fixer) |
| `SHOP_TRANSFER_*` / `SHOP_CHECK_*` | Si offline activé (**vos** coordonnées) |

**Mixe futur (1.1.0) :** secrets restent en `.env` ; adresse / SIRET / textes éditables → admin + BD.

---

## 4. Catalogue

1. Login admin  
2. Créer catégories + produits (prix TTC, stock, image)  
3. Vérifier fiche produit publique  

---

## 5. Paiement

1. Renseigner `STRIPE_SECRET`, `STRIPE_PUBLIC`, `STRIPE_WEBHOOK_SECRET`  
2. Local : `stripe listen --forward-to http://astral-shop.local/webhooks/stripe`  
3. Optionnel : IBAN / chèque dans `.env`  

---

## 6. Légal — avant ouverture (responsabilité marchand)

1. Personnaliser `SHOP_LEGAL_*` et `SHOP_STORE_ADDRESS` (**données réelles**, pas la démo)  
2. Relire et **adapter** `/cgv` `/mentions-legales` `/retours` `/confidentialite`  
3. Vérifier footer + case « J’accepte les CGV » au checkout  
4. Faire valider les textes par un professionnel si besoin  
5. Comprendre : modèles livrés ≠ conformité automatique  

Voir aussi : `ouvrir-une-boutique-fr.md` (§0 partage des responsabilités + §3 cadre légal).

---

## 7. Smoke test minimal

- [ ] `/install` OK puis bloqué  
- [ ] Produit achetable  
- [ ] Checkout : CGV obligatoires  
- [ ] Carte test Stripe → `paid`  
- [ ] Admin : prépa → envoyée  
- [ ] Mentions : SIRET visible  

Checklist QA interne (hub infos) : `astral-informations/astral-shop/Roadmap/test.md`.

---

## 8. Production (1.5+)

Avant ouverture publique : **[production-checklist.md](./production-checklist.md)**

```bash
php bin/console shop:check-production
```

Points clés : `APP_ENV=production`, `APP_DEBUG=false`, OPcache, cron worker + `orders:expire-offline`, remplacer IBAN / SIRET / mail démo, webhooks HTTPS.

---

## 9. Historique versions

- Colissimo / Mondial Relay → **1.1.0**  
- Worker / cache Redis / OPcache / cron → **1.5.0** (hub : `Features/Optimisation`)  

