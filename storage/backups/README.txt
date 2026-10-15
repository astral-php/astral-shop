Astral Shop — sauvegardes locales (gitignore)
============================================

Fichiers créés le 2026-10-14 :
  .env.bak-2026-10-14_1101
  starter.sqlite.bak-2026-10-14_1101

RESTAURER l’état QA précédent
-----------------------------
1. Arrêter Apache / php -S si besoin
2. Copier le .env.bak vers la racine du projet :
     copy storage\backups\.env.bak-2026-10-14_1101 .env
3. Copier la BDD :
     copy storage\backups\starter.sqlite.bak-2026-10-14_1101 database\starter.sqlite
4. Recharger https://astral-shop.local

REPARTIR DE ZÉRO (voir procédure chat / §10.5)
---------------------------------------------
1. Sauvegarder si pas déjà fait (comme ci-dessus)
2. copy .env.example .env
3. Renseigner STRIPE_* si tu testes la carte
4. Supprimer database\starter.sqlite
5. composer install
6. php bin/console migrate
7. Ouvrir /install → créer le 1er admin
