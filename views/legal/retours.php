<?php
/** @var array<string, mixed> $legal */
/** @var array<string, mixed> $shop */
$company = htmlspecialchars((string) ($legal['company_name'] ?? 'la Boutique'), ENT_QUOTES, 'UTF-8');
$address = nl2br(htmlspecialchars((string) ($legal['address'] ?? ''), ENT_QUOTES, 'UTF-8'));
$storeAddress = trim((string) ($shop['store_address'] ?? ''));
$returnAddrHtml = $storeAddress !== ''
    ? nl2br(htmlspecialchars($storeAddress, ENT_QUOTES, 'UTF-8'))
    : ($address !== '' ? $address : '[Adresse de retour à renseigner]');
$email = htmlspecialchars((string) ($legal['email'] ?? ''), ENT_QUOTES, 'UTF-8');
$returnDays = (int) ($legal['return_days'] ?? 14);
$contact = $email !== ''
    ? '<a href="mailto:' . $email . '">' . $email . '</a>'
    : '[e-mail boutique]';
require __DIR__ . '/_layout_start.php';
?>
    <h2 style="color:var(--text);font-size:1.1rem">1. Droit de rétractation</h2>
    <p>Pour les achats à distance concernés, vous disposez d’un délai de
       <strong style="color:var(--text)"><?= $returnDays ?> jours</strong> calendaires
       à compter de la réception du bien (ou du retrait en boutique) pour vous rétracter,
       sans avoir à justifier de motif, sous réserve des exceptions prévues par le Code de la consommation
       (produits personnalisés, denrées rapidement périssables, biens descellés pour des raisons d’hygiène, etc.).</p>
    <p>Pour exercer ce droit, déposez une <strong style="color:var(--text)">demande de remboursement</strong>
       depuis votre espace client (détail de la commande), en indiquant le motif.
       Pour un paiement par virement ou chèque, un RIB est demandé afin d’effectuer le remboursement.</p>

    <h2 style="color:var(--text);font-size:1.1rem;margin-top:1.25rem">2. Conditions du bien retourné</h2>
    <p>Le bien doit être renvoyé dans un état permettant sa remise en vente :
       emballage d’origine si possible, accessoires, notice, sans traces d’usage excessif.
       Le Client est responsable de la dépréciation du bien résultant de manipulations autres que celles
       nécessaires pour établir la nature, les caractéristiques et le bon fonctionnement du bien.</p>

    <h2 style="color:var(--text);font-size:1.1rem;margin-top:1.25rem">3. Frais de renvoi — à la charge du Client</h2>
    <p><strong style="color:var(--text)">Les frais de renvoi du colis sont à la charge exclusive du Client</strong>
       en cas de rétractation ou de retour pour convenance.</p>
    <p>Exception : si le bien est défectueux, non conforme ou si l’erreur est imputable à <?= $company ?>,
       les frais de retour peuvent être pris en charge par le Vendeur après validation du dossier
       (voir section 6 — Produit endommagé ou non conforme).</p>

    <h2 style="color:var(--text);font-size:1.1rem;margin-top:1.25rem">4. Adresse et modalités de renvoi</h2>
    <p>Sauf indication contraire communiquée par e-mail, renvoyez le colis à :</p>
    <p style="color:var(--text)"><strong><?= $company ?></strong><br><?= $returnAddrHtml ?></p>
    <p>Indiquez clairement la référence de commande <strong style="color:var(--text)">CMD-{n°}</strong>
       dans le colis. Conservez une preuve d’envoi (recommandé : suivi / recommandé).</p>
    <p>Sauf exception (motif accepté sans retour, ou commande jamais expédiée),
       <strong style="color:var(--text)">aucun remboursement n’est effectué tant que le retour n’a pas été reçu</strong>
       et contrôlé par <?= $company ?> dans le délai de <?= $returnDays ?> jours après réception / retrait du bien.</p>

    <h2 style="color:var(--text);font-size:1.1rem;margin-top:1.25rem">5. Remboursement après retour</h2>
    <p>Après validation du retour (ou d’une exception sans retour) :</p>
    <ul style="margin:0.5rem 0 0.75rem 1.25rem;padding:0">
        <li>paiement par carte : crédit via Stripe sur la carte utilisée ;</li>
        <li>paiement virement / chèque : virement vers le RIB fourni lors de la demande.</li>
    </ul>
    <p>Un e-mail de confirmation vous est adressé. Les délais bancaires restent ceux de votre établissement.</p>
    <p>En cas de délai de retour dépassé sans réception du colis, le remboursement peut être refusé
       (sauf geste commercial du Vendeur).</p>

    <h2 style="color:var(--text);font-size:1.1rem;margin-top:1.25rem">6. Produit endommagé, défectueux ou non conforme</h2>
    <p>Cette démarche est distincte du simple droit de rétractation pour convenance.</p>
    <ol style="margin:0.5rem 0 0.75rem 1.25rem;padding:0;line-height:1.55">
        <li><strong style="color:var(--text)">À la livraison</strong> — si le colis est visiblement abîmé,
            formulez des réserves auprès du transporteur et conservez les emballages.</li>
        <li><strong style="color:var(--text)">Signalement</strong> — contactez <?= $company ?> sous
            <strong style="color:var(--text)">72 heures</strong> (ou dans les meilleurs délais) à <?= $contact ?>,
            en joignant le numéro de commande, une description du défaut et des photos du produit et de l’emballage.</li>
        <li><strong style="color:var(--text)">Instruction</strong> — le Vendeur indique la suite :
            retour pour expertise, remplacement, remboursement, ou autre solution adaptée.</li>
        <li><strong style="color:var(--text)">Renvoi</strong> — ne renvoyez le produit qu’après accord du Vendeur
            et selon l’adresse / le mode indiqués. En cas de défaut avéré imputable au Vendeur ou au transport
            sous sa responsabilité, les frais de retour peuvent être pris en charge.</li>
        <li><strong style="color:var(--text)">Issue</strong> — réparation, remplacement ou remboursement
            selon la situation et les garanties légales applicables.</li>
    </ol>
    <p>Les garanties légales de conformité et des vices cachés s’appliquent indépendamment de cette procédure
       et du droit de rétractation (voir <a href="/cgv">CGV</a>).</p>

    <h2 style="color:var(--text);font-size:1.1rem;margin-top:1.25rem">7. Exceptions (sans retour physique)</h2>
    <p>Certains cas peuvent permettre un remboursement sans renvoi du colis, après examen par le Vendeur :
       commande annulée avant expédition, produit non livré, ou motif exceptionnel accepté.
       Ces situations sont traitées au cas par cas lors de l’examen de la demande.</p>

    <h2 style="color:var(--text);font-size:1.1rem;margin-top:1.25rem">8. Contact</h2>
    <p><?= $contact ?></p>
    <p style="font-size:0.9rem;margin-top:1rem">Voir aussi : <a href="/cgv">Conditions générales de vente</a>
       · <a href="/mentions-legales">Mentions légales</a></p>
<?php require __DIR__ . '/_layout_end.php'; ?>
