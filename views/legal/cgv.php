<?php
/** @var array<string, mixed> $legal */
$company = htmlspecialchars((string) ($legal['company_name'] ?? 'la Boutique'), ENT_QUOTES, 'UTF-8');
$address = nl2br(htmlspecialchars((string) ($legal['address'] ?? ''), ENT_QUOTES, 'UTF-8'));
$email = htmlspecialchars((string) ($legal['email'] ?? ''), ENT_QUOTES, 'UTF-8');
$phone = htmlspecialchars((string) ($legal['phone'] ?? ''), ENT_QUOTES, 'UTF-8');
$siret = htmlspecialchars((string) ($legal['siret'] ?? ''), ENT_QUOTES, 'UTF-8');
$returnDays = (int) ($legal['return_days'] ?? 14);
$contact = $email !== ''
    ? '<a href="mailto:' . $email . '">' . $email . '</a>'
    : '[e-mail boutique]';
require __DIR__ . '/_layout_start.php';
?>
    <h2 style="color:var(--text);font-size:1.1rem">1. Objet et champ d’application</h2>
    <p>Les présentes conditions générales de vente (ci-après « CGV ») s’appliquent, sans restriction ni réserve,
       à l’ensemble des ventes conclues par <strong style="color:var(--text)"><?= $company ?></strong>
       (ci-après « le Vendeur ») auprès de clients consommateurs (ci-après « le Client »)
       via le site de commerce électronique.</p>
    <p>Toute commande implique l’acceptation sans réserve des présentes CGV, que le Client reconnaît avoir lues
       avant de valider sa commande (case à cocher obligatoire au checkout). Les CGV applicables sont celles
       en vigueur au jour de la commande.</p>

    <h2 style="color:var(--text);font-size:1.1rem;margin-top:1.25rem">2. Identité du vendeur</h2>
    <p>
        <strong style="color:var(--text)"><?= $company ?></strong><br>
        <?= $address !== '' ? $address . '<br>' : '[Adresse du siège — à renseigner]<br>' ?>
        SIRET : <?= $siret !== '' ? $siret : '[SIRET à renseigner]' ?><br>
        Contact : <?= $contact ?>
        <?php if ($phone !== ''): ?><br>Téléphone : <?= $phone ?><?php endif ?>
    </p>
    <p>Les mentions légales complètes (hébergeur, directeur de publication, etc.) sont disponibles sur la page
       <a href="/mentions-legales">Mentions légales</a>.</p>

    <h2 style="color:var(--text);font-size:1.1rem;margin-top:1.25rem">3. Produits</h2>
    <p>Les produits proposés à la vente sont ceux figurant sur le site au jour de la consultation,
       dans la limite des stocks disponibles. Les photographies et descriptifs sont aussi fidèles que possible
       mais n’entrent pas dans le champ contractuel. En cas d’indisponibilité après commande,
       le Client en est informé et peut être remboursé sans frais.</p>

    <h2 style="color:var(--text);font-size:1.1rem;margin-top:1.25rem">4. Prix</h2>
    <p>Les prix sont exprimés en euros, toutes taxes comprises (TTC), hors frais de livraison.
       Les frais de livraison sont indiqués avant la validation de la commande et ajoutés au total.
       Le Vendeur peut modifier ses prix à tout moment ; le prix applicable est celui affiché
       lors de la confirmation de commande par le Client.</p>

    <h2 style="color:var(--text);font-size:1.1rem;margin-top:1.25rem">5. Commande</h2>
    <p>Le Client sélectionne les produits, renseigne ses coordonnées et adresses (livraison / facturation),
       choisit un mode de livraison puis un moyen de paiement. Avant validation, un récapitulatif
       (produits, prix, frais de port, total) lui est présenté.</p>
    <p>La validation de la commande (engagement) vaut offre d’achat et acceptation des CGV.
       Un e-mail de confirmation est adressé selon le mode de paiement (immédiat pour la carte après validation,
       ou après enregistrement pour virement / chèque).</p>
    <p>Le Vendeur se réserve le droit de refuser ou d’annuler une commande en cas de litige antérieur,
       de non-paiement, d’adresse erronée, de suspicion de fraude ou de force majeure.</p>

    <h2 style="color:var(--text);font-size:1.1rem;margin-top:1.25rem">6. Paiement</h2>
    <p>Les moyens proposés (selon configuration du site) peuvent être :</p>
    <ul style="margin:0.5rem 0 0.75rem 1.25rem;padding:0">
        <li><strong style="color:var(--text)">Carte bancaire</strong> — via le prestataire Stripe ; le débit intervient selon les règles Stripe / banque ;</li>
        <li><strong style="color:var(--text)">Virement bancaire</strong> — les instructions (IBAN, référence) sont communiquées après engagement ;</li>
        <li><strong style="color:var(--text)">Chèque</strong> — selon les instructions affichées (ordre, adresse d’envoi, référence).</li>
    </ul>
    <p>Pour le virement et le chèque, la commande peut être réservée ; le paiement est considéré comme reçu
       après validation manuelle par le Vendeur. À défaut de paiement dans le délai indiqué sur le site,
       la commande peut être annulée et le stock remis en vente.</p>
    <p>Le Vendeur n’a pas accès aux données complètes de carte bancaire, traitées par Stripe.</p>

    <h2 style="color:var(--text);font-size:1.1rem;margin-top:1.25rem">7. Livraison et retrait</h2>
    <p>Les modes de livraison disponibles (ex. forfait France, retrait en boutique) et les frais associés
       sont présentés au checkout. Les délais indiqués sont indicatifs hors week-ends et jours fériés,
       sauf mention contraire.</p>
    <p>Le transfert des risques intervient à la remise du colis au Client ou selon les règles applicables
       au mode de transport. En cas de colis visiblement endommagé à la livraison, le Client doit formuler
       des réserves auprès du transporteur et contacter le Vendeur sans délai
       (voir aussi <a href="/retours">politique de retour — produit endommagé</a>).</p>

    <h2 style="color:var(--text);font-size:1.1rem;margin-top:1.25rem">8. Droit de rétractation</h2>
    <p>Conformément aux articles L221-18 et suivants du Code de la consommation, le Client dispose d’un délai de
       <strong style="color:var(--text)"><?= $returnDays ?> jours</strong> calendaires à compter de la réception
       du bien (ou du retrait) pour exercer son droit de rétractation, sans avoir à motiver sa décision,
       sous réserve des exceptions légales (produits personnalisés, denrées rapidement périssables,
       biens descellés ne pouvant être renvoyés pour des raisons d’hygiène, etc.).</p>
    <p>Les modalités pratiques (demande, renvoi, frais) sont détaillées dans la
       <a href="/retours">politique de retour et de rétractation</a>.</p>

    <h2 style="color:var(--text);font-size:1.1rem;margin-top:1.25rem">9. Retours et remboursements</h2>
    <p>Sauf exception acceptée par le Vendeur (commande non expédiée, motif particulier),
       aucun remboursement n’est effectué tant que le retour du bien n’a pas été reçu et contrôlé.</p>
    <p><strong style="color:var(--text)">Les frais de renvoi sont à la charge exclusive du Client</strong>
       en cas d’exercice du droit de rétractation ou de retour pour convenance, sauf défaut imputable au Vendeur
       ou disposition légale contraire.</p>
    <p>Après acceptation, le remboursement du prix des produits (et, le cas échéant, des frais de livraison initiaux
       dans les conditions légales) est effectué sur le moyen de paiement d’origine (carte) ou par virement
       vers le RIB fourni (paiements offline), dans un délai raisonnable après réception du retour.
       Les délais d’apparition sur le compte bancaire du Client dépendent de sa banque.</p>

    <h2 style="color:var(--text);font-size:1.1rem;margin-top:1.25rem">10. Garanties légales</h2>
    <p>Le Client bénéficie de la garantie légale de conformité et de la garantie des vices cachés,
       dans les conditions prévues par le Code de la consommation et le Code civil.
       Ces garanties s’appliquent indépendamment du droit de rétractation.
       Pour un produit endommagé ou non conforme à la réception, voir la procédure dédiée sur
       <a href="/retours">/retours</a>.</p>

    <h2 style="color:var(--text);font-size:1.1rem;margin-top:1.25rem">11. Responsabilité</h2>
    <p>Le Vendeur ne saurait être tenu responsable des dommages résultant d’une mauvaise utilisation des produits,
       d’une interruption du réseau, d’un cas de force majeure, ou du fait exclusif du Client ou d’un tiers.
       La responsabilité du Vendeur est limitée au montant de la commande concernée, dans les limites autorisées par la loi.</p>

    <h2 style="color:var(--text);font-size:1.1rem;margin-top:1.25rem">12. Données personnelles</h2>
    <p>Les données collectées sont nécessaires au traitement des commandes et à la relation client.
       Pour plus d’informations : <a href="/confidentialite">Politique de confidentialité</a>.</p>

    <h2 style="color:var(--text);font-size:1.1rem;margin-top:1.25rem">13. Médiation et litiges</h2>
    <p>En cas de litige, le Client est invité à contacter d’abord le Vendeur à l’adresse <?= $contact ?>
       afin de rechercher une solution amiable.</p>
    <p>Conformément au Code de la consommation, le Client peut recourir gratuitement à un médiateur de la consommation
       en vue de la résolution amiable du litige. Les coordonnées du médiateur compétent seront communiquées
       par le Vendeur sur demande (à personnaliser avant mise en production :
       plateforme de médiation / médiateur désigné).</p>
    <p>À défaut d’accord amiable, les tribunaux français compétents seront saisis.
       Les présentes CGV sont soumises au droit français.</p>

    <h2 style="color:var(--text);font-size:1.1rem;margin-top:1.25rem">14. Contact</h2>
    <p><?= $contact ?>
       <?php if ($phone !== ''): ?> — <?= $phone ?><?php endif ?></p>
<?php require __DIR__ . '/_layout_end.php'; ?>
