<?php
/** @var array<string, mixed> $legal */
$company = htmlspecialchars((string) ($legal['company_name'] ?? 'la Boutique'), ENT_QUOTES, 'UTF-8');
$email = htmlspecialchars((string) ($legal['email'] ?? ''), ENT_QUOTES, 'UTF-8');
require __DIR__ . '/_layout_start.php';
?>
    <h2 style="color:var(--text);font-size:1.1rem">1. Responsable du traitement</h2>
    <p><?= $company ?> traite les données nécessaires à la gestion des comptes, commandes, paiements,
       livraisons et service après-vente.</p>

    <h2 style="color:var(--text);font-size:1.1rem;margin-top:1.25rem">2. Données collectées</h2>
    <p>Identité, coordonnées, adresses, historique de commandes, données de paiement traitées par le prestataire
       (Stripe — la boutique ne stocke pas les numéros de carte complets), et données techniques de connexion.</p>

    <h2 style="color:var(--text);font-size:1.1rem;margin-top:1.25rem">3. Finalités et bases légales</h2>
    <p>Exécution du contrat (commande, livraison, remboursement), obligations légales (facturation),
       intérêt légitime (sécurité, prévention de la fraude) et, le cas échéant, consentement (cookies non essentiels).</p>

    <h2 style="color:var(--text);font-size:1.1rem;margin-top:1.25rem">4. Destinataires</h2>
    <p>Prestataires techniques (hébergement, e-mail, paiement, transporteurs) dans la limite nécessaire au service.</p>

    <h2 style="color:var(--text);font-size:1.1rem;margin-top:1.25rem">5. Durée et droits</h2>
    <p>Conservation limitée aux durées légales et opérationnelles. Vous disposez des droits d’accès, rectification,
       effacement, limitation, opposition et portabilité, dans les conditions prévues par le RGPD.
       Contact : <?php if ($email !== ''): ?><a href="mailto:<?= $email ?>"><?= $email ?></a><?php else: ?>[e-mail]<?php endif ?>.
       Vous pouvez également saisir la CNIL.</p>

    <h2 style="color:var(--text);font-size:1.1rem;margin-top:1.25rem">6. Cookies</h2>
    <p>Le site utilise des cookies techniques nécessaires au panier et à la session.
       D’éventuels cookies analytics / marketing feront l’objet d’un consentement distinct lorsqu’ils seront activés.</p>
<?php require __DIR__ . '/_layout_end.php'; ?>
