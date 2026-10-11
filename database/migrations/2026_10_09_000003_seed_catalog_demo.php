<?php

declare(strict_types=1);

use Database\Migration\Migration;

/**
 * Jeu de démo catalogue (uniquement si tables vides).
 */
class SeedCatalogDemo extends Migration
{
    public function up(PDO $pdo): void
    {
        $count = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
        if ($count > 0) {
            return;
        }

        $pdo->exec(<<<'SQL'
            INSERT INTO categories (name, slug, description, sort_order, active) VALUES
            ('Général', 'general', 'Produits transverses — adaptable à tout métier.', 1, 1),
            ('Sélection', 'selection', 'Mise en avant vitrine.', 2, 1)
        SQL);

        $pdo->exec(<<<'SQL'
            INSERT INTO products (
                category_id, name, slug, description, price_cents, currency, stock, sku, image_path, image_url, active
            ) VALUES
            (1, 'Produit exemple A', 'produit-exemple-a',
             'Fiche neutre : remplacez nom, prix TTC et image selon votre vertical (mode, tech, food…).',
             2490, 'EUR', 25, 'DEMO-A', NULL, NULL, 1),
            (1, 'Produit exemple B', 'produit-exemple-b',
             'Sans photo : un placeholder s''affiche automatiquement.',
             5990, 'EUR', 10, 'DEMO-B', NULL, NULL, 1),
            (2, 'Produit vitrine', 'produit-vitrine',
             'Utilisez image_url (URL externe) ou déposez un fichier sous public/media/products/.',
             12900, 'EUR', 5, 'DEMO-V', NULL, NULL, 1)
        SQL);
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DELETE FROM products WHERE sku IN ('DEMO-A', 'DEMO-B', 'DEMO-V')");
        $pdo->exec("DELETE FROM categories WHERE slug IN ('general', 'selection')");
    }
}
