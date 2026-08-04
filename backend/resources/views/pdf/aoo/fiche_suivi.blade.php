<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fiche de Suivi AOO - {{ $aoo->num_aoo }}</title>
    <style>
        @page {
            margin: 12mm 10mm;
            size: A4 portrait;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #000;
            line-height: 1.25;
            margin: 0;
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        td, th {
            border: 1px solid #000;
            padding: 4px 6px;
            vertical-align: middle;
        }

        .no-border {
            border: none !important;
        }

        .header-table td {
            border: none;
            padding: 0 4px;
        }

        .header-center {
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            line-height: 1.35;
        }

        .header-logo {
            height: 58px;
            width: auto;
        }

        .exercice-row {
            text-align: right;
            font-weight: bold;
            font-size: 10px;
            margin: 6px 0 8px;
        }

        .label-blue {
            background-color: #d9e8f5;
            font-weight: bold;
            text-align: center;
        }

        .label-italic {
            font-weight: bold;
            font-style: italic;
            width: 12%;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }

        .objet-cell {
            min-height: 42px;
            text-align: left;
            vertical-align: top;
            word-wrap: break-word;
        }

        .pieces-header {
            font-weight: bold;
            font-style: italic;
            text-align: center;
        }

        .piece-label {
            font-weight: bold;
            font-style: italic;
            text-align: left;
        }

        .manual-cell {
            height: 35px;
        }

        .observations-box {
            border: 1px solid #000;
            min-height: 150px;
            padding: 8px;
            margin-top: 8px;
        }

        .observations-title {
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 6px;
        }

        .section-gap {
            margin-top: 6px;
        }

        .montant {
            white-space: nowrap;
        }
    </style>
</head>
<body>

@php
    $logoOnca = \App\Support\AooDocumentHelper::embedImage('images/logo-onca.png')
        ?? \App\Support\AooDocumentHelper::embedImage('images/logo_onca.png');
    $sceauMaroc = \App\Support\AooDocumentHelper::embedImage('images/sceau-maroc.png')
        ?? \App\Support\AooDocumentHelper::embedImage('images/logo_royaume.png');
@endphp

<table class="header-table">
    <tr>
        <td style="width: 22%; text-align: left;">
            @if($logoOnca)
                <img src="{{ $logoOnca }}" alt="ONCA" class="header-logo">
            @endif
        </td>
        <td class="header-center" style="width: 56%;">
            Direction régionale du Conseil agricole<br>
            Rabat-Salé-Kénitra
        </td>
        <td style="width: 22%; text-align: right;">
            @if($sceauMaroc)
                <img src="{{ $sceauMaroc }}" alt="Royaume du Maroc" class="header-logo">
            @endif
        </td>
    </tr>
</table>

<div class="exercice-row">Exercice&nbsp;&nbsp;{{ $fiche['exercice'] }}</div>

<table>
    <tr>
        <td class="label-blue" style="width: 22%;">APPEL D'OFFRE N°</td>
        <td class="text-center" colspan="3" style="font-weight: bold;">{{ $aoo->num_aoo }}</td>
    </tr>
    <tr>
        <td class="text-center" style="width: 12%; font-weight: bold;">Date</td>
        <td class="text-center" style="width: 28%;">{{ $fiche['date_ouverture'] }}</td>
        <td class="text-center" style="width: 12%; font-weight: bold;">heure</td>
        <td class="text-center" style="width: 48%;">{{ $fiche['heure_ouverture'] }}</td>
    </tr>
</table>

<table class="section-gap">
    <tr>
        <td class="label-italic">Objet</td>
        <td class="objet-cell">{{ $aoo->objet }}</td>
    </tr>
</table>

<table class="section-gap">
    <tr>
        <td class="text-center" style="width: 25%; font-weight: bold;">Nombre de lots</td>
        <td class="text-center" style="width: 15%; font-weight: bold;">{{ $fiche['nombre_lots'] }}</td>
        <td class="text-center" style="width: 60%; font-weight: bold;">Estimations</td>
    </tr>
    @foreach($fiche['lots'] as $lot)
        <tr>
            <td class="text-center" style="font-weight: bold;">{{ $lot['num_lot'] }}</td>
            <td class="text-left">{{ $lot['objet_lot'] }}</td>
            <td class="text-right montant">
                @if($lot['estimation_ttc'] > 0)
                    {{ number_format($lot['estimation_ttc'], 2, ',', ' ') }} DH TTC
                @endif
            </td>
        </tr>
    @endforeach
</table>

<table class="section-gap">
    <tr>
        <td class="pieces-header" style="width: 40%;">Pièces du dossier AOO</td>
        <td class="pieces-header" style="width: 20%;">Execution</td>
        <td class="pieces-header" style="width: 40%;">observations</td>
    </tr>
    @foreach($fiche['pieces_dossier'] as $piece)
        <tr>
            <td class="piece-label">{{ $piece }}</td>
            <td class="manual-cell">&nbsp;</td>
            <td class="manual-cell">&nbsp;</td>
        </tr>
    @endforeach
</table>

<table class="section-gap">
    <tr>
        <td class="text-center" colspan="2" style="font-weight: bold; font-style: italic;">Résultats d'AOO</td>
    </tr>
    @forelse($fiche['resultats'] as $resultat)
        <tr>
            <td style="width: 30%; font-weight: bold; font-style: italic;">
                Société Adjudicataire
                @if(count($fiche['resultats']) > 1)
                    ({{ $resultat['lot'] }})
                @endif
            </td>
            <td>{{ $resultat['societe'] }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; font-style: italic;">Montant</td>
            <td class="montant">
                @if($resultat['montant'])
                    {{ number_format((float) $resultat['montant'], 2, ',', ' ') }} DH
                @endif
            </td>
        </tr>
    @empty
        <tr>
            <td style="width: 30%; font-weight: bold; font-style: italic;">Société Adjudicataire</td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td style="font-weight: bold; font-style: italic;">Montant</td>
            <td>&nbsp;</td>
        </tr>
    @endforelse
</table>

<div class="observations-box">
    <div class="observations-title">Observations:</div>
</div>

</body>
</html>
