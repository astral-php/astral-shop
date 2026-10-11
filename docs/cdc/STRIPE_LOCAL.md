# Stripe local — Astral Shop

## Problème fréquent

`stripe login` peut autoriser un **sandbox différent** de celui des clés `STRIPE_*` du `.env`.

Les PaymentIntent du shop sont sur le compte des clés `.env`.
Le listen doit utiliser **la même** clé secrète :

```bash
stripe listen --api-key %STRIPE_SECRET_KEY% --forward-to http://astral-shop.local/webhooks/stripe --events payment_intent.succeeded,payment_intent.payment_failed,charge.refunded
```

Sous PowerShell :

```powershell
$sk = (Select-String -Path .env -Pattern '^STRIPE_SECRET_KEY=(.+)$').Matches.Groups[1].Value.Trim()
stripe listen --api-key $sk --forward-to http://astral-shop.local/webhooks/stripe --events payment_intent.succeeded,payment_intent.payment_failed,charge.refunded
```

Coller le `whsec_…` affiché dans `.env` → `STRIPE_WEBHOOK_SECRET=`.

## Renvoi d’un event

```bash
stripe events resend evt_xxx --api-key <STRIPE_SECRET_KEY>
```
