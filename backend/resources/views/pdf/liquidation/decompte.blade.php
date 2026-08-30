<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Décompte / Situation</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; }
        .header-table { width: 100%; border: none; margin-bottom: 5px; }
        .header-table td { border: none; padding: 2px; }
        .box-title { border: 1px solid #000; text-align: center; font-weight: bold; padding: 5px; margin-bottom: 10px; font-size: 11px; }
        .recap-title { text-align: center; font-weight: bold; font-size: 14px; margin: 15px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; border: 2px solid #000; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
        th { font-weight: bold; text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .no-border-table { border: none; margin-top: 20px; }
        .no-border-table td { border: none; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="width: 25%;">Exercice : {{ $marche->exercice ?? '2026' }}</td>
            <td style="width: 75%; font-weight: bold; font-size: 12px;">MARCHE N° {{ $marche->num_marche }}</td>
        </tr>
    </table>

    <table class="header-table" style="margin-bottom: 15px;">
        <tr>
            <td style="border: 2px solid #000; text-align: center; font-weight: bold; padding: 5px;">
                Ordre de service de commencement de l'éxucution n°{{ $marche->os_numero ?? '................' }} à compter du : {{ $marche->os_date_effet ? \Carbon\Carbon::parse($marche->os_date_effet)->format('d/m/Y') : '................' }}
            </td>
        </tr>
    </table>

    <table style="border: 2px solid #000; margin-top: 0; margin-bottom: 15px;">
        <tr>
            <td style="width: 50%; font-weight: bold; border-right: 2px solid #000; padding: 5px;">
                Décompte {{ strtolower($liquidation->type_decompte) }} N° {{ str_pad($liquidation->num_decompte, 2, '0', STR_PAD_LEFT) }} du : {{ $liquidation->date_decompte ? \Carbon\Carbon::parse($liquidation->date_decompte)->format('d/m/Y') : '................' }}
            </td>
            <td style="width: 25%; font-weight: bold; text-align: right; border-right: 2px solid #000; padding: 5px;">
                Acompte d'un montant de :
            </td>
            <td style="width: 25%; font-weight: bold; text-align: left; padding: 5px;">
                {{ number_format($liquidation->montant_ttc, 2, ',', ' ') }}
            </td>
        </tr>
    </table>

    <div class="recap-title">RECAPITULATION</div>

    <table style="border: 2px solid #000; margin-bottom: 20px;">
        <thead>
            <tr>
                <th style="width: 50%;">NATURE DES DEPENSES</th>
                <th style="width: 20%;">DEPENSES FAITES DH</th>
                <th style="width: 20%;">RETENUE DE GARANTIE</th>
                <th style="width: 10%;">RESTE</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="bold">Prestations terminés</td>
                <td class="right bold">{{ number_format($liquidation->montant_brut_ttc, 2, ',', ' ') }}</td>
                <td class="right bold">{{ $liquidation->retenue_garantie > 0 ? number_format($liquidation->retenue_garantie, 2, ',', ' ') : 'NEANT' }}</td>
                <td class="right bold">{{ number_format($liquidation->montant_brut_ttc - $liquidation->retenue_garantie, 2, ',', ' ') }}</td>
            </tr>
            <tr>
                <td class="bold">Prestations non terminés</td>
                <td class="right bold">{{ number_format(($marche->montant ?? 0) - ($liquidation->montant_brut_ttc ?? 0), 2, ',', ' ') }}</td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td class="bold">Révision de prix</td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td class="bold">Totaux</td>
                <td></td>
                <td class="right bold">{{ $liquidation->retenue_garantie > 0 ? number_format($liquidation->retenue_garantie, 2, ',', ' ') : 'NEANT' }}</td>
                <td class="right bold">{{ number_format($liquidation->montant_brut_ttc - $liquidation->retenue_garantie, 2, ',', ' ') }}</td>
            </tr>
            <tr>
                <td class="bold" colspan="4">A déduire les dépenses imputées sur les exercices antérieurs</td>
            </tr>
            <tr>
                <td class="bold" colspan="4">A déduire le montant des comptes délivrés sur l'exercice en cours</td>
            </tr>
            <tr>
                <td colspan="3"></td>
                <td class="right bold" style="border-top: 2px solid #000;">{{ number_format($liquidation->montant_brut_ttc - $liquidation->retenue_garantie, 2, ',', ' ') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="recap-title" style="margin-top: 40px; margin-bottom: 30px;">RECAPITULATION</div>

    <div style="font-weight: bold; margin-bottom: 20px; line-height: 1.5; font-size: 11px;">
        Certifié par l'agent chargée de suivi de marché<br>
        qui certifie que les prestations ont été réalisées pour les quantités indiquées au décompte ci-dessus,
    </div>

    <table class="no-border-table" style="margin-top: 40px; margin-bottom: 10px;">
        <tr>
            <td style="text-align: center; font-weight: bold; font-size: 11px;">
                Le montant délivré par nous, Le Sous Ordonnateur, est arrêté à la somme de:
            </td>
            <td style="text-align: right; font-weight: bold; font-size: 11px; width: 25%;">
                {{ number_format($liquidation->net_a_payer ?? ($liquidation->montant_brut_ttc - $liquidation->retenue_garantie), 2, ',', ' ') }} TTC
            </td>
        </tr>
    </table>

    <div style="font-weight: bold; font-size: 11px; margin-bottom: 20px; line-height: 1.5;">
        {{ strtoupper(\App\Helpers\NumberToWordsHelper::toFrenchWords($liquidation->net_a_payer ?? ($liquidation->montant_brut_ttc - $liquidation->retenue_garantie))) }} DIRHAMS
    </div>

    <div style="text-align: center; font-weight: bold; margin-top: 10px; font-size: 11px;">
        A Kénitra, le {{ $liquidation->date_decompte ? \Carbon\Carbon::parse($liquidation->date_decompte)->format('d/m/Y') : '..........' }}
    </div>
</body>
</html>
