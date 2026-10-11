# Prompt de lancement — Astral Shop

À coller dans Cursor / Codex / Claude Code / Antigravity.

Prérequis : skills installés dans ce projet (voir `docs/cdc/SKILLS_PATH.md`).

Kit source (si besoin de mise à jour) :
`D:\laragon\www\components-astral\astral-skills`

---

Suis le workflow `docs/cdc/create-astral-app.md`.

Utilise le brief `docs/cdc/brief.md` comme section 0.
Utilise `docs/cdc/architecture.md` comme hypothèse de départ à valider / corriger.

Skills obligatoires au départ (dossiers locaux) :
- `.cursor/skills/astral-mvc-development` (ou `.agents/skills/…` / `.claude/skills/…`)
- `astral-reference`
- `astral-web-architecture`
- `astral-database`
- `astral-extend-orm`
- `astral-payment`
- `astral-security`

Commence par l'ÉTAPE 1 (inspection du projet et des packages installés).
Ne code rien.
Je veux un **cahier des charges + architecture** (modules, schéma, routes, permissions, plan fichiers, plan tests) avant toute implémentation.

Réutilise les patterns du sandbox Lab / payment du hub si tu y as accès ; n'invente aucune API Astral.
Après validation, on implémentera module par module.
