# Note — Validator upload (astral-core 2.0)

Astral Shop gère pour l’instant l’upload image produit via `App\Support\ProductImageUploader`
(MIME, taille max 2 Mo, extension sûre).

## Proposition core 2.0

Ajouter une règle Validator du type :

```text
image_file: required|upload|mimes:jpg,png,webp,gif|max_kb:2048
```

Critères attendus :
- présence du fichier (`$_FILES`)
- code `UPLOAD_ERR_OK`
- contrôle MIME réel (`finfo`) + extension whitelist
- taille max configurable
- refus path traversal / double extension

Jusqu’à core 2.0, les apps (shop, etc.) gardent un helper local dédié.
