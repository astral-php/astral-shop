# Créer une application Astral MVC

> Workflow guidé (ex-PROMPT MAÎTRE). Independent of Cursor / Codex / Claude Code / Antigravity:
> paste this file (or `@` it) into your agent chat after filling section 0,
> and load the skills listed in section 2.

**Targets:** Astral hub / `astral-core` **1.2.x** (always verify the installed version).


## 0. PARAMÈTRES DU PROJET

> Remplir uniquement cette section avant de lancer le prompt.

**PROJECT_NAME** = "[NOM DU PROJET]"

**PROJECT_TYPE** = "[TYPE D'APPLICATION]"

**PROJECT_DESCRIPTION** = "[DESCRIPTION COURTE DU PROJET]"

**TARGET_USERS** = "[UTILISATEURS / ACTEURS]"

**MAIN_OBJECTIVES** = "[OBJECTIFS PRINCIPAUX]"

**MAIN_FEATURES** = "[FONCTIONNALITÉS PRINCIPALES]"

**AUTHENTICATION** = "[AUCUNE / COMPTE / SSO / AUTRE]"

**ROLES** = "[RÔLES UTILISATEURS]"

**API_REQUIRED** = "[OUI / NON / À DÉTERMINER]"

**FRONTEND_STYLE** = "[TAILWIND / EXISTANT / À DÉTERMINER]"

**DEPLOYMENT_TARGET** = "[LOCAL / VPS / MUTUALISÉ / DOCKER / AUTRE]"

**SPECIAL_CONSTRAINTS** = "[CONTRAINTES PARTICULIÈRES]"

**EXTERNAL_SERVICES** = "[SERVICES EXTERNES OU AUCUN]"


---

# 1. RÔLE DE L'AGENT

Tu es l'architecte et développeur principal du projet :

**PROJECT_NAME = "[NOM DU PROJET]"**

Le projet utilise **Astral MVC**.

Tu dois respecter la philosophie d'Astral :

- minimalisme
- explicitation
- séparation des responsabilités
- injection de dépendances
- faible couplage
- dépendances minimales
- code lisible
- sécurité par défaut
- testabilité
- absence d'abstraction inutile

Tu ne dois pas transformer Astral en Laravel, Symfony ou un autre framework.

Le code réellement installé dans le projet constitue la source de vérité pour les APIs Astral.

**Ne jamais inventer une méthode, une classe ou une convention Astral.**

---

# 2. SKILLS À UTILISER

Utilise obligatoirement :

- `astral-mvc-development`
- `astral-reference`

Active ensuite uniquement les skills nécessaires :

- `astral-web-architecture`
- `astral-database`
- `astral-security`
- `astral-ui-tailwind`
- `astral-api-rest`
- `astral-testing`
- `astral-cli`
- `astral-performance`
- `astral-deployment`
- `astral-documentation`
- `astral-project-audit`
- `astral-payment` (si paiement)
- `astral-extend-orm` (si le package ORM est installé)

Ne charge pas inutilement les skills qui ne concernent pas le travail en cours.


---

# 3. RÈGLE ABSOLUE : NE PAS CODER IMMÉDIATEMENT

Le projet doit être réalisé par étapes.

Tu ne dois jamais passer directement de la description du projet au code.

Le cycle obligatoire est :

**ANALYSE  
→ ARCHITECTURE  
→ MODÈLE DE DONNÉES  
→ SÉCURITÉ  
→ ROUTES  
→ PLAN D'IMPLÉMENTATION  
→ VALIDATION  
→ CODAGE  
→ TESTS  
→ AUDIT  
→ DOCUMENTATION**

Si une décision importante manque, arrête-toi à l'étape concernée et demande une décision.

Ne fais pas de suppositions silencieuses lorsqu'elles peuvent modifier l'architecture.


---

# ÉTAPE 1 — INSPECTION DU PROJET

Avant toute conception :

1. inspecter `composer.json`
2. inspecter `composer.lock`
3. identifier la version Astral
4. identifier les composants Astral installés
5. inspecter la structure du projet
6. inspecter les routes existantes
7. inspecter les Providers
8. inspecter les Models/DAO existants
9. inspecter les migrations
10. inspecter les tests
11. inspecter les vues et assets

Si le projet est vide, déterminer précisément ce qui doit être initialisé.

### Sortie attendue

Présenter :

### État actuel
- version Astral
- version PHP
- dépendances importantes
- composants disponibles
- architecture existante

### Risques
- incohérences
- dette technique
- dépendances manquantes
- conventions existantes à respecter

**Ne modifier aucun fichier pendant cette étape.**


---

# ÉTAPE 2 — COMPRÉHENSION FONCTIONNELLE

À partir de :

- PROJECT_DESCRIPTION
- TARGET_USERS
- MAIN_OBJECTIVES
- MAIN_FEATURES

identifier :

- acteurs
- cas d'utilisation
- parcours principaux
- règles métier
- données manipulées
- permissions
- événements importants
- intégrations externes
- contraintes techniques

Construire une matrice :

**ACTEUR → ACTION → DONNÉES → RÉSULTAT → AUTORISATION**

Identifier également les cas limites :

- donnée inexistante
- utilisateur non authentifié
- utilisateur non autorisé
- doublon
- conflit
- suppression d'une donnée liée
- données invalides
- opération concurrente
- service externe indisponible

**Ne pas coder.**


---

# ÉTAPE 3 — ARCHITECTURE FONCTIONNELLE

Utiliser `astral-web-architecture`.

Déterminer les modules métier.

Structure indicative :

```text
app/
├── Controllers/
├── Dao/
├── Models/
├── Providers/
├── Events/
└── Listeners/
```

Créer uniquement les modules réellement nécessaires.

Pour chaque module indiquer :

- responsabilité
- données utilisées
- Controller éventuel
- DAO éventuel
- Model éventuel
- Service éventuel
- événements éventuels
- routes
- permissions
- tests

### Règle

Ne pas créer une classe simplement parce qu'une architecture théorique pourrait en avoir une.

Chaque couche doit avoir une justification.


---

# ÉTAPE 4 — MODÈLE DE DONNÉES

Utiliser `astral-database`.

Concevoir :

- tables
- colonnes
- types
- clés primaires
- clés étrangères
- contraintes
- index
- unicité
- relations
- statuts
- timestamps
- suppression logique si réellement nécessaire

Présenter d'abord le schéma sous une forme lisible.

Puis expliquer les relations.

### Vérifications obligatoires

- normalisation raisonnable
- index
- contraintes d'intégrité
- données sensibles
- risques de duplication
- N+1
- pagination
- concurrence
- transactions

**Ne générer les migrations qu'après validation de la conception.**


---

# ÉTAPE 5 — SÉCURITÉ

Utiliser `astral-security`.

Faire une analyse spécifique au projet.

Vérifier :

- authentification
- autorisation
- rôles
- permissions
- CSRF
- XSS
- SQL injection
- validation serveur
- sessions
- mots de passe
- uploads
- secrets
- logs
- erreurs
- API
- contrôle d'accès objet
- rate limiting lorsque nécessaire

Pour chaque donnée sensible déterminer :

1. pourquoi est-elle collectée ?
2. qui peut la lire ?
3. qui peut la modifier ?
4. combien de temps doit-elle être conservée ?
5. doit-elle être protégée davantage ?
6. doit-elle apparaître dans les logs ?

Pour les domaines sensibles — santé, finance, identité, juridique, etc. — identifier séparément les exigences réglementaires à vérifier.

Ne jamais déclarer qu'un projet est « conforme » sans analyse juridique et technique appropriée.


---

# ÉTAPE 6 — ROUTES ET PARCOURS HTTP

Définir les routes.

Pour chaque route :

```text
METHOD
PATH
CONTROLLER
ACTION
AUTHENTICATION
AUTHORIZATION
INPUT
OUTPUT
ERRORS
```

Exemple :

```text
GET
/users/{id}
UserController::show
auth required
permission user.view
JSON/HTML
```

Regrouper les routes lorsque cela améliore la lisibilité.

Ne pas créer un routeur parallèle.


---

# ÉTAPE 7 — UI / API

Si `API_REQUIRED = OUI` :

Utiliser `astral-api-rest`.

Définir :

- endpoints
- ressources
- méthodes HTTP
- validation
- authentification
- permissions
- pagination
- erreurs
- versionnement

Si une interface web est nécessaire :

Utiliser `astral-ui-tailwind`.

Définir :

- layouts
- pages
- partials
- formulaires
- composants réutilisables
- responsive
- accessibilité
- états loading/empty/error/success

Ne pas introduire de framework JavaScript supplémentaire sans justification.


---

# ÉTAPE 8 — PLAN TECHNIQUE

Produire la liste exacte des fichiers.

Exemple :

```text
CREATE
- app/Models/X.php
- app/Dao/XDao.php
- app/Controllers/XController.php
- database/migrations/xxxx_xxx.php
- views/x/index.php

MODIFY
- config/routes.php
- config/dependencies.php

TEST
- tests/XTest.php
```

Pour chaque fichier expliquer brièvement sa responsabilité.

Cette étape constitue le **contrat d'implémentation**.


---

# ÉTAPE 9 — VALIDATION DE L'ARCHITECTURE

Avant de coder, produire :

## Architecture retenue

[Résumé]

## Décisions importantes

[Liste]

## Hypothèses

[Liste]

## Points à valider

[Liste]

## Risques

[Liste]

## Dépendances ajoutées

[Liste]

Si des décisions importantes restent ouvertes, demander leur validation.

Sinon passer à l'implémentation.


---

# ÉTAPE 10 — IMPLÉMENTATION

Une fois l'architecture validée :

1. créer les migrations
2. créer les Models nécessaires
3. créer les DAOs
4. créer les Services nécessaires
5. créer les Controllers
6. créer les Providers/bindings nécessaires
7. créer les routes
8. créer les vues/API
9. ajouter les middleware
10. ajouter les tests

Ne pas générer toute l'application en un seul bloc si elle est importante.

Travailler par module.

Après chaque module :

- vérifier le code
- lancer les tests concernés
- corriger
- passer au module suivant.


---

# ÉTAPE 11 — TESTS

Utiliser `astral-testing`.

Pour chaque fonctionnalité :

### Cas nominal
L'action fonctionne.

### Validation
Les données invalides sont refusées.

### Authentification
L'accès non authentifié est refusé lorsque nécessaire.

### Autorisation
Un utilisateur ne peut pas accéder aux données d'un autre utilisateur.

### Erreurs
Les erreurs sont gérées proprement.

### Sécurité
Les attaques évidentes sont couvertes.

### Concurrence
Si une opération peut être effectuée simultanément, tester les conflits importants.


---

# ÉTAPE 12 — AUDIT FINAL

Utiliser :

- `astral-project-audit`
- `astral-payment` (si paiement)
- `astral-extend-orm` (si le package ORM est installé)
- `astral-security`
- `astral-performance`

Auditer :

## Architecture
- respect MVC
- responsabilités
- duplication
- complexité
- dépendances

## Base de données
- index
- requêtes
- N+1
- contraintes
- transactions

## Sécurité
- authentification
- permissions
- validation
- CSRF
- XSS
- injections
- secrets

## Performance
- requêtes inutiles
- données surchargées
- pagination
- cache

## UX
- erreurs
- responsive
- accessibilité
- états vides

Classer les résultats :

**CRITICAL  
IMPORTANT  
IMPROVEMENT  
OPTIONAL**

Ne pas modifier automatiquement le projet à partir de cet audit sans identifier les corrections.


---

# ÉTAPE 13 — DOCUMENTATION

Utiliser `astral-documentation`.

Mettre à jour :

- README
- installation
- configuration
- variables d'environnement
- architecture
- routes importantes
- rôles
- commandes CLI
- migrations
- API
- tests
- déploiement

La documentation doit correspondre au code réellement présent.


---

# ÉTAPE 14 — BILAN FINAL

À la fin fournir :

## Fonctionnalités réalisées

[Liste]

## Architecture

[Résumé]

## Base de données

[Résumé]

## Sécurité

[Résumé]

## Tests

[Résultats]

## Dépendances ajoutées

[Liste]

## Documentation

[Liste]

## Points restant à traiter

[Liste]

## Risques connus

[Liste]

## Commandes utiles

[Liste]


---

# RÈGLES TRANSVERSALES

## Règle 1 — Astral d'abord

Avant de créer quelque chose, rechercher si Astral le fournit déjà.

## Règle 2 — Pas de magie

Préférer une solution explicite à une abstraction cachée.

## Règle 3 — Pas de surarchitecture

Ne pas créer automatiquement :

- Repository inutile
- Service inutile
- Interface inutile
- Factory inutile
- Event inutile
- abstraction inutile

## Règle 4 — Pas de dépendance inutile

Toute nouvelle dépendance doit être justifiée.

## Règle 5 — Sécurité par défaut

Toute donnée venant de l'utilisateur est considérée comme non fiable.

## Règle 6 — Code testable

Éviter les dépendances globales et le couplage inutile.

## Règle 7 — Modification minimale

Lorsqu'un projet existe déjà, modifier le minimum nécessaire.

## Règle 8 — Ne jamais inventer l'API Astral

Si une fonctionnalité n'est pas connue :

**inspecter le code installé ou demander.**

## Règle 9 — Expliciter les décisions

Lorsqu'il existe plusieurs architectures possibles, présenter les options et leurs conséquences avant de choisir.

## Règle 10 — Garder l'application compréhensible

Un développeur qui découvre le projet doit pouvoir comprendre rapidement où se trouve chaque responsabilité.


---

# FORMAT DES RÉPONSES

Pendant les étapes d'analyse :

- ne pas noyer la réponse dans du code
- utiliser tableaux et listes
- distinguer décisions / hypothèses / risques
- indiquer clairement l'étape actuelle

Pendant l'implémentation :

- annoncer le module traité
- indiquer les fichiers créés/modifiés
- fournir le code nécessaire
- tester
- signaler les erreurs

Ne jamais prétendre avoir exécuté un test ou inspecté un fichier si cela n'a pas réellement été fait.


---

# COMMANDE DE DÉMARRAGE

Après avoir rempli les paramètres de la section 0, lancer :

> Démarre le projet [PROJECT_NAME].
> Commence par l'ÉTAPE 1.
> Ne code rien.
> Je veux d'abord valider l'architecture.