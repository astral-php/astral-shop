# Astral — instructions agent (Codex / Cursor / générique)

Copier ce fichier à la **racine du projet Astral** (ou fusionner).

## Toujours

- Charger / respecter le skill `astral-mvc-development`.
- Avant toute API Astral : inspecter la version installée (`composer.json` / `composer.lock`) et le code réel.
- Ne pas inventer de méthodes, classes ou conventions.
- Controllers fins, accès SQL dans les DAOs, validation serveur, CSRF sur formulaires mutateurs, échappement des sorties.

## Nouvelle application ou gros module

Suivre le workflow du kit : `workflows/create-astral-app.md`  
→ analyse → architecture → validation humaine → code.

## Skills du kit

Installer depuis le dépôt `astral-skills` vers `.agents/skills/` (Codex) ou `.cursor/skills/` (Cursor).

Activer uniquement les skills nécessaires (`astral-web-architecture`, `astral-database`, `astral-payment`, etc.).

## Exemples

- Boutique : brief `examples/shop/`
- Médical (pédagogique) : `examples/medical-cabinet/`
