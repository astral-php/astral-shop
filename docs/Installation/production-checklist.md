# Astral Shop — Checklist production (1.5)

> À faire **avant** d’ouvrir la boutique au public.  
> Complète `guide-install-pas-a-pas.md` et `ouvrir-une-boutique-fr.md`.

---

## 1. Environnement PHP / `.env`

| Réglage | Dev (Laragon) | Production |
|---------|---------------|------------|
| `APP_ENV` | `development` | **`production`** |
| `APP_DEBUG` | `true` | **`false`** |
| `APP_URL` / `APP_BASE_URL` | `*.local` | HTTPS public réel |
| `AUTH_REGISTRATION` | `confirm` | `confirm` (recommandé) |

Commande d’audit (warnings démo / secrets) :

```bash
php bin/console shop:check-production
```

Code de sortie `1` = au moins un point bloquant ou warning important.

---

## 2. OPcache (recommandé)

Sur le serveur PHP :

1. Extension `opcache` activée (`php -m | findstr opcache` / `php -m | grep opcache`).
2. Dans `php.ini` (prod typique) :

```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.validate_timestamps=0
opcache.revalidate_freq=0
```

> `validate_timestamps=0` : après un déploiement, **redémarrer PHP-FPM / Apache** (ou `opcache_reset`) pour charger le nouveau code.

Laragon local : OPcache optionnel ; `validate_timestamps=1` reste pratique en dév.

---

## 3. Secrets & valeurs démo

**Ne jamais** committer `.env`. Remplacer avant prod :

| Zone | Exemples démo à changer |
|------|-------------------------|
| Légal | `SHOP_LEGAL_SIRET=000…`, adresse Lilas, `[Hébergeur — …]` |
| Offline | IBAN `FR76 3000…`, bénéficiaire « Astral Shop SAS » |
| Mail | `*.local`, Mailpit `1025` → SMTP réel |
| Stripe | clés **live** + webhook secret live |
| PayPal | `PAYPAL_MODE=live` + client live + webhook ID |
| URL | plus de `astral-shop.local` |

Voir aussi `ouvrir-une-boutique-fr.md` §0 (moteur vs marchand).

---

## 4. Cache & Redis

| Variable | Prod typique |
|----------|--------------|
| `CACHE_DRIVER` | `file` (mutualisé) ou `redis` (VPS + **ext-redis**) |
| `CACHE_CATALOG_TTL` | `300` (ou plus) |
| `QUEUE_DRIVER` | **`database`** (défaut) ; `redis` seulement si besoin charge |

**Non caché :** fiche produit, panier, checkout, stock.  
Invalidation : admin produit / catégorie.

Vérifier Redis PHP : `php -m` contient `redis` + `redis-cli ping` → `PONG`.

---

## 5. Worker (cron / Planificateur)

Mails de confirmation et PDF facture partent via la file. Sans worker (et sans `QUEUE_DRIVER=sync`), la file `database` s’accumule.

### Linux / hébergement cron

```cron
* * * * * cd /var/www/astral-shop && php bin/console worker:work --stop-when-empty >> storage/logs/worker.log 2>&1
0 * * * * cd /var/www/astral-shop && php bin/console orders:expire-offline >> storage/logs/expire-offline.log 2>&1
```

Variante quasi temps réel (flock) :

```cron
* * * * * cd /var/www/astral-shop && flock -n /tmp/astral-worker.lock php bin/console worker:work --max-time=55 >> storage/logs/worker.log 2>&1
```

### Windows (Laragon / VPS)

Planificateur de tâches — toutes les minutes :

```bat
php D:\laragon\www\astral-shop\bin\console worker:work --stop-when-empty
```

Expire offline (horaire) :

```bat
php D:\laragon\www\astral-shop\bin\console orders:expire-offline
```

Test manuel :

```bash
php bin/console worker:work --stop-when-empty
php bin/console queue:failed
```

---

## 6. Webhooks & HTTPS

- Stripe / PayPal : endpoints publics HTTPS, secrets corrects.
- Pas de CSRF sur `/webhooks/*` (signature fournisseur).
- Après déploiement : un paiement test sandbox puis bascule live documentée.

---

## 7. Rate-limit paiement

Pas de middleware rate-limit dans Astral core 1.2. En prod, protéger idéalement au niveau **reverse proxy** (nginx `limit_req`, Cloudflare, etc.) les routes :

- `POST /checkout/payment`
- `POST /webhooks/stripe` / `paypal` (déjà signés ; limiter l’abus IP si besoin)

Backlog applicatif possible post-1.5.

---

## 8. Smoke avant ouverture

- [ ] `APP_ENV=production` + `APP_DEBUG=false`
- [ ] `php bin/console shop:check-production` OK (ou warnings assumés)
- [ ] OPcache actif
- [ ] Worker cron OK (mail + PDF après paiement)
- [ ] `orders:expire-offline` planifié
- [ ] Parcours carte / PayPal / offline sur données **réelles** (ou sandbox contrôlée)
- [ ] Mentions / CGV / SIRET marchand (pas la démo)
