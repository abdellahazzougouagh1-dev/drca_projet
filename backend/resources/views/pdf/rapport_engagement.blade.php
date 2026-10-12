<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport d'engagement</title>
    <style>
        @page { margin: 35px 42px; }
        body { font-family: "Times New Roman", Times, serif; font-size: 12px; color: #000; line-height: 1.45; }
        h1 { text-align: center; font-size: 14px; margin: 0 0 22px; text-transform: uppercase; }
        h2 { font-size: 14px; margin: 18px 0 6px; border-bottom: 1px solid #000; padding-bottom: 3px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        td, th { border: 1px solid #000; padding: 6px 7px; vertical-align: top; }
        th { text-align: left; width: 34%; }
        .center { text-align: center; }
        .amount { text-align: right; }
    </style>
</head>
<body>
    <h1>Rapport d'engagement budgétaire</h1>

    <table>
        <tr><th>Numéro de la fiche d'engagement</th><td>{{ $registre->numero_rubrique }}</td></tr>
        <tr><th>Référence du marché</th><td>{{ $marche->num_marche }}</td></tr>
        <tr><th>Référence de l'appel d'offres</th><td>{{ $registre->reference_2 ?: '-' }}</td></tr>
        <tr><th>Date d'engagement</th><td>{{ optional($registre->date_engagement)->format('d/m/Y') ?: '-' }}</td></tr>
        <tr><th>Forme d'engagement</th><td>{{ $registre->mode_engagement }}</td></tr>
        <tr><th>Budget / exercice</th><td>{{ $registre->budget ?: '-' }} / {{ $marche->exercice ?: '-' }}</td></tr>
    </table>

    <h2>Objet et bénéficiaire</h2>
    <table>
        <tr><th>Objet du marché</th><td>{{ $registre->objet }}</td></tr>
        <tr><th>Bénéficiaire</th><td>{{ $registre->beneficiaire }}</td></tr>
        <tr><th>Imputation</th><td>ART {{ $registre->art ?: '-' }} / PAR {{ $registre->par ?: '-' }} / LIG {{ $registre->lig ?: '-' }}</td></tr>
    </table>

    <h2>Données financières</h2>
    <table>
        <tr><th>Crédit ouvert CP</th><td class="amount">{{ number_format((float) $registre->credit_ouvert_cp, 2, ',', ' ') }} DH</td></tr>
        <tr><th>Crédit ouvert CE</th><td class="amount">{{ number_format((float) $registre->credit_ouvert_ce, 2, ',', ' ') }} DH</td></tr>
        <tr><th>Dépenses engagées antérieurement CP</th><td class="amount">{{ number_format((float) $registre->depenses_anterieures_cp, 2, ',', ' ') }} DH</td></tr>
        <tr><th>Dépenses engagées antérieurement CE</th><td class="amount">{{ number_format((float) $registre->depenses_anterieures_ce, 2, ',', ' ') }} DH</td></tr>
        <tr><th>Montant de la dépense neuve</th><td class="amount">{{ number_format((float) $registre->montant_depense_neuf, 2, ',', ' ') }} DH</td></tr>
        <tr><th>Intérêts moratoires (1 %)</th><td class="amount">{{ number_format((float) $registre->interets_moratoires, 2, ',', ' ') }} DH</td></tr>
        <tr><th>Montant à engager neuf</th><td class="amount"><strong>{{ number_format((float) $registre->montant_engager_neuf, 2, ',', ' ') }} DH</strong></td></tr>
    </table>
</body>
</html>
