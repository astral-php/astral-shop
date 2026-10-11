# Astral MVC — Claude Code

Fusionner ce contenu dans le `CLAUDE.md` du projet (ou le copier tel quel).

## Projet

Application **Astral MVC**. Philosophie : minimal, explicite, OOP, DI, sécurité par défaut.

## Source de vérité

Le code et les packages **installés** dans ce repo. Ne jamais inventer une API Astral ni assumer Laravel/Symfony.

## Skills

Si des skills sont présents dans `.claude/skills/` (kit astral-skills), les utiliser.

Sinon, respecter au minimum les règles du skill cœur :

- structure `app/`, `config/`, `database/migrations/`, `public/`, `views/`, `tests/`
- ne pas modifier `astral-core` pour un besoin applicatif
- DAOs pour la base ; controllers fins
- CSRF, validation, échappement

## Workflow

Pour une nouvelle app ou un lot important : ne pas coder d’abord.  
Suivre le workflow « Créer une application Astral MVC » (kit) jusqu’à validation architecture.

## Astral Shop

Si le projet est la boutique : partir de `examples/shop/brief.md` + architecture, skills payment + ORM.
