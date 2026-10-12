<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Page de Garde - {{ $doc['num_marche'] }}</title>
    <style>
        @page { margin: 10mm 10mm 14mm; size: A4 portrait; }
        * { box-sizing: border-box; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #000;
            line-height: 1.25;
            margin: 0;
            padding: 0;
        }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        td, th { border: 1px solid #000; padding: 4px 6px; vertical-align: middle; }

        .header-table td { border: none; padding: 0 4px; vertical-align: middle; }
        .header-center {
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            line-height: 1.35;
            padding-top: 4px;
        }
        .header-logo { height: 58px; width: auto; max-width: 120px; }
        .header-line { border-bottom: 2px solid #1f4e79; margin: 6px 0 4px; }

        .budget-block {
            text-align: right;
            font-weight: bold;
            font-size: 10px;
            margin: 2px 0 8px;
            line-height: 1.5;
        }

        .label-blue {
            background-color: #d9e8f5;
            font-weight: bold;
            font-style: italic;
            text-align: center;
        }
        .label-italic {
            font-weight: bold;
            font-style: italic;
            width: 14%;
        }
        .value-bold { font-weight: bold; text-align: center; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .objet-cell {
            text-align: left;
            vertical-align: top;
            font-weight: bold;
            word-wrap: break-word;
        }
        .lot-unique {
            font-weight: bold;
            font-style: italic;
            text-align: center;
        }
        .section-gap { margin-top: 0; }

        .pieces-header {
            font-weight: bold;
            font-style: italic;
            text-align: center;
            background-color: #fff;
        }
        .piece-num { width: 6%; text-align: center; font-weight: bold; }
        .piece-label {
            font-weight: bold;
            font-style: italic;
            text-align: left;
        }
        .check-col { width: 14%; height: 22px; }

        .obs-title {
            font-weight: bold;
            text-decoration: underline;
            text-align: center;
            padding: 6px;
        }
        .obs-body { height: 80px; vertical-align: top; }
        .date-suivi {
            font-weight: bold;
            text-align: center;
            padding: 8px;
        }
        .suivi-label {
            width: 18%;
            text-align: center;
            font-weight: bold;
            vertical-align: middle;
        }
        .suivi-area { height: 60px; }

        .page-break { page-break-after: always; }

        /* Page 2 */
        .page2-title { display: none; }
        .marche-blue-box {
            background-color: #bdd7ee;
            border: 1px solid #000;
            text-align: center;
            font-weight: bold;
            font-size: 16px;
            padding: 18px 10px;
            margin: 10px 0 8px;
        }
        .aoo-recap {
            text-align: center;
            font-weight: bold;
            font-style: italic;
            margin-bottom: 12px;
            font-size: 10px;
        }
        .aoo-recap span { font-style: normal; }

        .objet-title-box {
            background-color: #bdd7ee;
            border: 1px solid #000;
            text-align: center;
            font-weight: bold;
            padding: 6px;
            margin-top: 8px;
        }
        .objet-content-box {
            background-color: #bdd7ee;
            border: 1px solid #000;
            border-top: none;
            text-align: center;
            font-weight: bold;
            font-style: italic;
            padding: 10px 12px;
            min-height: 52px;
        }
        .attributaire-row td {
            background-color: #bdd7ee;
            font-weight: bold;
            padding: 8px 10px;
        }
        .attributaire-label { width: 22%; text-align: left; }
        .attributaire-value { text-align: center; font-size: 11px; }

        .marche-annee {
            text-align: center;
            font-weight: bold;
            font-size: 12px;
            padding: 14px 6px;
            border: 1px solid #000;
            margin: 10px 0;
        }
        .montant-row td {
            background-color: #bdd7ee;
            font-weight: bold;
            padding: 8px 10px;
        }
        .montant-label { width: 28%; text-align: left; }
        .montant-value { text-align: center; font-size: 11px; }

        .dotted-table td {
            border: 1px dotted #000;
            padding: 6px 8px;
        }
        .dotted-label {
            font-weight: bold;
            font-style: italic;
            width: 18%;
        }
        .dotted-value {
            text-align: center;
            font-weight: bold;
        }

        footer {
            position: fixed;
            bottom: -8mm;
            left: 0;
            right: 0;
        }
        .footer-table td { border: none; vertical-align: middle; padding: 4px 6px; font-size: 8px; }
        .footer-left { width: 18%; text-align: center; }
        .footer-center { width: 64%; text-align: center; line-height: 1.35; }
        .footer-right { width: 18%; text-align: right; }
        .footer-logo { height: 42px; width: auto; }
        .footer-line { border-bottom: 3px solid #1f4e79; margin-top: 4px; }
        .page2-content { padding-bottom: 70px; }
    </style>
</head>
<body>

@php
    $logoOnca = \App\Support\AooDocumentHelper::embedImage('images/logo-onca.png')
        ?? \App\Support\AooDocumentHelper::embedImage('images/logo_onca.png');
    $sceauMaroc = \App\Support\AooDocumentHelper::embedImage('images/sceau-maroc.png')
        ?? \App\Support\AooDocumentHelper::embedImage('images/logo_royaume.png');
    $logoGreen = \App\Support\AooDocumentHelper::embedImage('images/generation-green.png');
@endphp

{{-- ===================== PAGE 1 : FICHE DE SUIVI & PIÈCES ===================== --}}

<table class="header-table">
    <tr>
        <td style="width: 22%; text-align: left;">
            @if($logoOnca)<img src="{{ $logoOnca }}" alt="ONCA" class="header-logo">@endif
        </td>
        <td style="width: 56%;">&nbsp;</td>
        <td style="width: 22%; text-align: right;">
            @if($sceauMaroc)<img src="{{ $sceauMaroc }}" alt="Royaume du Maroc" class="header-logo">@endif
        </td>
    </tr>
    <tr>
        <td colspan="3" class="header-center">
            Direction régionale du Conseil agricole<br>Rabat-Salé-Kénitra
        </td>
    </tr>
</table>
<div class="header-line"></div>

<div class="budget-block">
    Exercice : {{ $doc['exercice'] }}<br>
    Budget : {{ $doc['type_budget'] }}
</div>

<table>
    <tr>
        <td class="label-blue" style="width: 22%;">MARCHE N° :</td>
        <td class="value-bold" colspan="3">{{ $doc['num_marche'] }}</td>
    </tr>
    <tr>
        <td class="label-blue">ISSU DE L'AOO N° :</td>
        <td class="value-bold" style="width: 28%;">{{ $doc['num_aoo'] }}</td>
        <td class="value-bold" style="width: 8%;">Du</td>
        <td class="value-bold" style="width: 38%;">{{ $doc['date_aoo'] ?: '................' }}</td>
    </tr>
    <tr>
        <td colspan="4" class="lot-unique">{{ $doc['lot_mention'] }}</td>
    </tr>
</table>

<table class="section-gap">
    <tr>
        <td class="label-italic">Code</td>
        <td class="value-bold">{{ $doc['code_budget'] ?: '—' }}</td>
    </tr>
    <tr>
        <td class="label-italic">Intitulé</td>
        <td class="value-bold">{{ $doc['intitule_budget'] ?: '—' }}</td>
    </tr>
    <tr>
        <td class="label-italic">Objet</td>
        <td class="objet-cell">{{ $doc['objet_marche'] ?: '—' }}</td>
    </tr>
    <tr>
        <td class="label-italic">Titulaire</td>
        <td class="text-left" style="font-weight: bold;">{{ $doc['nom_entreprise'] ?: '—' }}</td>
    </tr>
    <tr>
        <td class="label-italic">Montant (TTC)</td>
        <td class="text-right" style="font-weight: bold;">{{ $doc['montant_formate'] }}</td>
    </tr>
</table>

<table class="section-gap">
    <tr>
        <td class="pieces-header" style="width: 6%;">&nbsp;</td>
        <td class="pieces-header" style="width: 52%;">Pièces du dossier</td>
        <td class="pieces-header check-col">Original</td>
        <td class="pieces-header check-col">Copie Conformes</td>
    </tr>
    @foreach($doc['pieces_dossier'] as $piece)
        <tr>
            <td class="piece-num">{{ $piece['num'] }}</td>
            <td class="piece-label">{{ $piece['label'] }}</td>
            <td class="check-col">&nbsp;</td>
            <td class="check-col">&nbsp;</td>
        </tr>
    @endforeach
</table>

<table class="section-gap">
    <tr>
        <td class="obs-title" colspan="4">Observations:</td>
    </tr>
    <tr>
        <td class="obs-body" colspan="4">&nbsp;</td>
    </tr>
    <tr>
        <td class="date-suivi" colspan="4">{{ $doc['date_suivi'] }}</td>
    </tr>
    <tr>
        <td class="suivi-label">Suivi des Paiement</td>
        <td class="suivi-area" colspan="3">&nbsp;</td>
    </tr>
</table>

<div class="page-break"></div>

{{-- ===================== PAGE 2 : GARDE OFFICIELLE ===================== --}}

<footer>
    <table class="footer-table">
        <tr>
            <td class="footer-left">
                @if($logoGreen)
                    <img src="{{ $logoGreen }}" alt="Génération Green" class="footer-logo"><br>
                @endif
                <strong>Génération Green<br>2020-2030</strong>
            </td>
            <td class="footer-center">
                Direction Régionale du Conseil Agricole de la Région de Rabat Salé Kénitra,<br>
                Angle Rue Sebta - Bd Mohamed V (à côté de Bank Al-Maghrib) - Kénitra<br>
                Tél. : +212 (0) 537 32 55 99 - Fax : +212 (0) 537 36 13 20 - Site web : www.onca.gov.ma
            </td>
            <td class="footer-right">
                @if($sceauMaroc)
                    <img src="{{ $sceauMaroc }}" alt="Ministère" class="footer-logo">
                @endif
            </td>
        </tr>
    </table>
    <div class="footer-line"></div>
</footer>

<div class="page2-content">
    <table class="header-table">
        <tr>
            <td style="width: 22%; text-align: left;">
                @if($logoOnca)<img src="{{ $logoOnca }}" alt="ONCA" class="header-logo">@endif
            </td>
            <td style="width: 56%;">&nbsp;</td>
            <td style="width: 22%; text-align: right;">
                @if($sceauMaroc)<img src="{{ $sceauMaroc }}" alt="Royaume du Maroc" class="header-logo">@endif
            </td>
        </tr>
        <tr>
            <td colspan="3" class="header-center">
                Direction régionale du Conseil agricole<br>Rabat-Salé-Kénitra
            </td>
        </tr>
    </table>
    <div class="header-line"></div>

    <div class="budget-block">
        Exercice : {{ $doc['exercice'] }}<br>
        Budget : {{ $doc['type_budget'] }}
    </div>

    <div class="marche-blue-box">MARCHE N° : {{ $doc['num_marche'] }}</div>

    <div class="aoo-recap">
        ISSU DE L'AOO N° : <span>{{ $doc['num_aoo'] }}</span>
        &nbsp;&nbsp;en date du&nbsp;&nbsp;<span>{{ $doc['date_aoo'] ?: '................' }}</span>
    </div>

    <div class="objet-title-box">Objet du Marché</div>
    <div class="objet-content-box">{{ $doc['objet_marche'] ?: '—' }}</div>

    <table style="margin-top: 10px;">
        <tr class="attributaire-row">
            <td class="attributaire-label">Attributaire</td>
            <td class="attributaire-value">{{ $doc['nom_entreprise'] ?: '—' }}</td>
        </tr>
    </table>

    <div class="marche-annee">{{ $doc['marche_annee_label'] }}</div>

    <table>
        <tr class="montant-row">
            <td class="montant-label">Montant du Marché</td>
            <td class="montant-value">{{ $doc['montant_formate'] }} TTC</td>
        </tr>
    </table>

    <table class="dotted-table" style="margin-top: 16px;">
        <tr>
            <td class="dotted-label">Budget</td>
            <td class="dotted-value">{{ $doc['type_budget'] }}</td>
        </tr>
        <tr>
            <td class="dotted-label">Code</td>
            <td class="dotted-value">{{ $doc['code_budget'] ?: '—' }}</td>
        </tr>
        <tr>
            <td class="dotted-label">Intitulé</td>
            <td class="dotted-value">{{ $doc['intitule_budget'] ?: '—' }}</td>
        </tr>
    </table>
</div>

</body>
</html>
