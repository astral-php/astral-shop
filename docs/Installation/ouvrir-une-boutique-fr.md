# Ouvrir une boutique en ligne en France — guide marchand

> Document **informatif** fourni avec Astral Shop **1.0.0**. **Ce n’est pas un conseil juridique.**  
> Le marchand reste responsable de sa conformité (CGV, mentions, fiscalité, CNIL, etc.).  
> Faire valider les textes par un professionnel avant mise en production.  
> **Install technique pas à pas :** `guide-install-pas-a-pas.md` (même dossier).

---

## 0. Qui fait quoi — à lire en premier

**Astral Shop = moteur prêt.**  
**Ouvrir une boutique = configurer et assumer** identité, textes, paiements, port, exploitation.

| Fourni par le logiciel (moteur) | À établir par le marchand (obligatoire avant prod) |
|--------------------------------|-----------------------------------------------------|
| Parcours panier → checkout → paiement → colis | Identité légale (raison sociale, SIRET, adresse, contact, hébergeur) |
| Pages CGV / mentions / retours / confidentialité (**modèles**) | Contenu réel des pages + validation juridique |
| Case « J’accepte les CGV » bloquante | Politique commerciale (délais, exceptions, litiges) |
| Demande de remboursement + RIB offline | Clés Stripe **live**, IBAN / chèque réels |
| Retour colis avant refund (délai, réception admin) | Adresse de renvoi (`SHOP_STORE_ADDRESS`), délai (`SHOP_RETURN_DAYS`) |
| Remboursement Stripe / marquage offline | Exécution des virements sortants (offline) |
| Facture PDF à l’encaissement | Statut fiscal / mentions facture conformes à *votre* activité |
| Port MVP : forfait + franco + retrait | Montants forfait / seuil franco ; transporteurs API → **1.1.0** |
| `/install`, admin catalogue, mails | Mailer prod, `APP_URL`, cron `orders:expire-offline`, catalogue |

Sans les éléments de la colonne de droite, le site peut **démontrer** le parcours, mais **ne doit pas** être présenté comme une boutique professionnelle ouverte au public.

---

## 1. Ce que le logiciel fournit

| Élément | Où |
|---------|-----|
| Pages CGV, mentions, retours, confidentialité | `/cgv` `/mentions-legales` `/retours` `/confidentialite` |
| Case « J’accepte les CGV » au checkout | Obligatoire avant engagement de commande |
| Liens footer | Layout boutique |
| Demande de remboursement client (+ RIB offline) | Fiche commande client |
| Retour colis avant remboursement | Délai + réception / dispense admin |
| Remboursement CB (Stripe) / offline (RIB) | Admin commande |

Les pages sont des **modèles génériques** e-commerce FR. Les valeurs affichées viennent de `.env` (`SHOP_LEGAL_*`).  
**Le marchand doit les personnaliser** (identité + rédaction) avant ouverture.

---

## 2. Personnaliser l’identité légale (`.env`)

Copier depuis `.env.example` puis renseigner **vos** données (pas les exemples démo) :

```env
SHOP_LEGAL_COMPANY="Votre Société SAS"
SHOP_LEGAL_ADDRESS="12 rue Exemple, 75001 Paris, France"
SHOP_LEGAL_EMAIL=contact@votredomaine.fr
SHOP_LEGAL_PHONE="01 23 45 67 89"
SHOP_LEGAL_SIRET="123 456 789 00012"
SHOP_LEGAL_RCS="RCS Paris 123 456 789"
SHOP_LEGAL_VAT="FR12345678901"
SHOP_LEGAL_DIRECTOR="Prénom Nom"
SHOP_LEGAL_HOST="Hébergeur — nom, adresse, contact"
SHOP_RETURN_DAYS=14
SHOP_STORE_ADDRESS="Adresse magasin (retrait + retour colis)"
SHOP_NOTIFY_EMAIL=gestion@votredomaine.fr
```

| Variable | Usage | Qui |
|----------|--------|-----|
| `SHOP_LEGAL_*` | Contenu des pages légales | **Marchand** |
| `SHOP_RETURN_DAYS` | Délai retour / rétractation (jours calendaires, défaut 14) | **Marchand** (cadre légal à vérifier) |
| `SHOP_STORE_ADDRESS` | Retrait boutique + adresse de renvoi affichée au client | **Marchand** |
| `SHOP_NOTIFY_EMAIL` | Alertes (ex. nouvelle demande de remboursement) | **Marchand** |
| `STRIPE_*` / `SHOP_TRANSFER_*` / `SHOP_CHECK_*` | Encaissement | **Marchand** |
| `SHOP_FLAT_RATE_CENTS` / `SHOP_FREE_SHIPPING_CENTS` | Port forfait / franco | **Marchand** |

Après modification : pas de rebuild — recharger la page (config lue à chaque requête).

---

## 3. Cadre légal — modèles ≠ conformité

1. Les textes livrés (`/cgv`, `/mentions-legales`, `/retours`, `/confidentialite`) sont un **cadre de départ**.  
2. Chaque page affiche un **avertissement** : modèle à adapter, pas un conseil juridique.  
3. Avant production, le marchand doit au minimum :
   - remplacer tous les placeholders / valeurs démo ;
   - aligner CGV et politique de retour sur **son** activité (exceptions rétractation, frais de renvoi, etc.) ;
   - faire relire / valider par un professionnel si besoin ;
   - vérifier SIRET, hébergeur, directeur de publication sur `/mentions-legales`.

### Obligations courantes (rappel non exhaustif)

1. **Mentions légales** — éditeur, SIRET, contact, hébergeur (LCEN).  
2. **CGV** — prix TTC, commande, livraison, paiement, litiges, rétractation.  
3. **Droit de rétractation** — souvent **14 jours** pour la vente à distance B2C (exceptions possibles).  
4. **Politique de retour** — état du bien, frais de renvoi, délai après réception.  
5. **Confidentialité / cookies** — information des personnes, base légale, contact.  
6. **Facturation** — règles applicables à votre statut (le shop génère un PDF à l’encaissement).  
7. **Paiement** — Stripe (CB) + éventuellement virement / chèque selon config.

### Liens utiles (officiels / institutionnels)

- [service-public.fr — Mentions légales site](https://www.service-public.fr/professionnels-entreprises/vosdroits/F31228)  
- [service-public.fr — Vente à distance / rétractation](https://www.service-public.fr/particuliers/vosdroits/F10485)  
- [economie.gouv.fr — e-commerce](https://www.economie.gouv.fr/entreprises/site-internet-mentions-obligatoires)  
- [CNIL — cookies et traceurs](https://www.cnil.fr/fr/cookies-et-autres-traceurs)  
- [Impôts — facturation / e-commerce](https://www.impots.gouv.fr/) (actualité selon votre statut)

---

## 4. Boucle métier remboursements + retours

```text
Client paie → Admin prépare → Envoi / retrait
  → Client : demande de remboursement (+ RIB si offline)
  → Si colis déjà livré/retiré : retour obligatoire (sauf dispense)
  → Client renvoie à SHOP_STORE_ADDRESS (réf. CMD-{id})
  → Admin : « Retour reçu » (ou « Dispenser du retour »)
  → Admin : accepter / rembourser (Stripe) ou marquer remboursée (offline)
```

Règles produit livrées :

- Pas d’engagement commande sans acceptation CGV.  
- Délai retour = `SHOP_RETURN_DAYS` après expédition / retrait (`shipped_at`).  
- Pas de remboursement si retour requis non reçu (sauf dispense admin).  
- Jamais expédié → retour non requis automatiquement.

---

## 5. Checklist avant ouverture

- [ ] Variables `SHOP_LEGAL_*` et adresse magasin renseignées (**vos** données)  
- [ ] **SIRET** renseigné et visible sur `/mentions-legales`  
- [ ] Relire `/cgv` `/mentions-legales` `/retours` `/confidentialite` et adapter les textes  
- [ ] Case CGV visible et bloquante au checkout  
- [ ] Stripe test → **live** (clés + webhook) si vente réelle  
- [ ] Mailer prod (plus Mailpit) + `SHOP_NOTIFY_EMAIL`  
- [ ] Forfait / franco port adaptés à votre offre  
- [ ] Cron `orders:expire-offline` en place  
- [ ] Tester une boucle complète : commande → envoi → demande → retour → remboursement  
- [ ] Validation juridique / comptable des textes et process  
- [ ] Suivre `guide-install-pas-a-pas.md`  

---

## 6. Références projet

| Fichier | Rôle |
|---------|------|
| `docs/Installation/guide-install-pas-a-pas.md` | Install technique (même dossier) |
| `README.md` | Présentation + périmètre 1.0.0 |
| `.env.example` | Modèle de variables |
| `config/shop.php` | Mapping `.env` → config |
| Hub `astral-informations/…/Roadmap/` | Roadmap + campagne QA (interne) |
