# Catalogue de prompts courts

À coller dans Cursor, Codex, Claude Code ou Antigravity après avoir installé les skills (voir README).

Remplacer les `[PLACEHOLDERS]`.

---

## 1. Démarrer un projet

Utilise `astral-mvc-development` et `astral-web-architecture`.
Inspecte la version Astral installée, la structure du projet et les composants Composer disponibles.
Je veux créer [DESCRIPTION].
Ne code pas encore : propose l'architecture minimale, les modules, les routes, les rôles, les tables et le plan de tests.

## 2. Créer un module

Utilise `astral-mvc-development`, `astral-database` et `astral-testing`.
Crée le module [MODULE].
Respecte les conventions existantes du projet.
Commence par la liste des fichiers à créer/modifier, puis implémente.
Ajoute la migration, DAO, modèle, contrôleur, routes, vues et tests uniquement si nécessaires.

## 3. Audit

Utilise `astral-project-audit` et `astral-security`.
Analyse le projet sans modifier le code.
Classe les problèmes en CRITICAL / IMPORTANT / IMPROVEMENT / OPTIONAL.
Pour chaque problème, donne le fichier concerné, la cause et la correction proposée.

## 4. Base de données

Utilise `astral-database`.
Voici le besoin : [BESOIN].
Propose d'abord le schéma et les contraintes.
Vérifie les index, unicité, relations, pagination et transactions.
Puis génère les migrations et DAO conformes au projet.

## 5. Interface

Utilise `astral-ui-tailwind`.
Construis l'interface [PAGE].
Réutilise les composants Astral déjà installés.
Prévois responsive, accessibilité, états vide/erreur/succès et validation des formulaires.
Ne crée pas de framework JavaScript supplémentaire.

## 6. API

Utilise `astral-api-rest`, `astral-security` et `astral-testing`.
Ajoute l'API [RESSOURCE].
Inspecte d'abord les mécanismes API existants.
Définis endpoints, auth, permissions, validation, réponses, erreurs et pagination avant de coder.

## 7. Sécurité

Utilise `astral-security`.
Fais un audit de [FONCTIONNALITÉ].
Cherche CSRF, XSS, SQL injection, contrôle d'accès, fuite de données, secrets, logs sensibles et erreurs de production.
Ne modifie rien avant de présenter les corrections.

## 8. Refactoring

Utilise `astral-mvc-development` et `astral-testing`.
Refactore [CODE] sans changer son comportement fonctionnel.
Réduis la complexité et la duplication tout en conservant les conventions Astral.
Ajoute/actualise les tests avant de considérer le refactoring terminé.

## 9. Performance

Utilise `astral-performance` et `astral-database`.
Analyse [FONCTIONNALITÉ] pour les problèmes de requêtes, N+1, pagination, cache et payload.
Ne propose que des optimisations justifiées par un problème concret.

## 10. Production

Utilise `astral-deployment`, `astral-security` et `astral-project-audit`.
Prépare [PROJET] pour la production.
Vérifie debug, secrets, logs, permissions, migrations, HTTPS, sauvegardes et rollback.
Ne prétends pas que l'installation est sécurisée sans identifier les éléments réellement vérifiés.

## 11. Paiement (Stripe / astral-payment)

Utilise `astral-mvc-development`, `astral-payment`, `astral-security` et `astral-testing`.
Inspecte le package `astral-payment` installé et les exemples sandbox.
Pour [BESOIN PAIEMENT] : propose le flux order → provider → webhook → événements avant de coder.
Ne code rien tant que le contrat d'implémentation n'est pas validé.

## 12. ORM (astral-extend-orm)

Utilise `astral-extend-orm` et `astral-database`.
Inspecte la version installée.
Pour [MODÈLE / BESOIN] : propose Model + DAO sans inventer d'API Eloquent.

---

# Modes de pilotage

## Mode analyse
Ne code rien. Analyse d'abord le projet et donne-moi les décisions à prendre.

## Mode implémentation
Après validation de l'architecture, implémente la fonctionnalité par petites étapes et teste chaque étape.

## Mode revue
Relis le code comme un mainteneur Astral : architecture, sécurité, simplicité, tests et dette technique.

## Mode strict
Ne crée aucune nouvelle dépendance et n'introduis aucune abstraction sans justification explicite.

## Mode migration
Modifie le minimum nécessaire et conserve la compatibilité avec le comportement existant.

---

# Rappel workflow complet

Pour une **nouvelle application**, préférer `workflows/create-astral-app.md` (section 0 remplie) plutôt qu'un prompt court.
Voir aussi `examples/` pour des briefs prêts (médical, shop).
