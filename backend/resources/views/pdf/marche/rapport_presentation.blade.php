<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport de présentation - {{ $doc['num_marche'] }}</title>
    <style>
        @page { margin: 14mm 12mm; size: A4 portrait; }
        * { box-sizing: border-box; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000;
            line-height: 1.45;
            margin: 0;
            padding: 0;
        }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; }

        .header-table td { border: none; padding: 0 4px; vertical-align: middle; }
        .header-center {
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            line-height: 1.35;
        }
        .header-logo { height: 58px; width: auto; max-width: 120px; }
        .header-line { border-bottom: 2px solid #1f4e79; margin: 6px 0 14px; }

        .main-title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            text-decoration: underline;
            border: 1px solid #000;
            padding: 8px 12px;
            margin: 12px auto 18px;
            width: 72%;
        }

        .section { margin-bottom: 12px; }
        .section-title {
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 4px;
        }
        .section-content { text-align: justify; }
        .bold { font-weight: bold; }

        .mode-box {
            border: 2px solid #548235;
            padding: 8px 10px;
            margin: 6px 0;
            text-align: justify;
        }

        .sub-section { margin: 8px 0 8px 16px; }
        .sub-label { font-weight: bold; }

        .budget-table { margin: 10px 0 16px; }
        .budget-table td, .budget-table th {
            border: 1px solid #000;
            padding: 6px 8px;
            text-align: center;
            font-size: 10px;
        }
        .budget-table th { font-weight: bold; background: #f7f7f7; }
        .budget-table .left { text-align: left; }

        .bullets { margin: 6px 0 6px 8px; }
        .bullets p { margin: 4px 0; text-align: justify; }

        .footer-signatures { margin-top: 36px; width: 100%; }
        .footer-signatures td { border: none; padding: 0; vertical-align: top; }
        .sign-box {
            border: 1px solid #000;
            width: 240px;
            height: 70px;
            text-align: center;
            font-weight: bold;
            padding-top: 8px;
        }
        .date-box {
            border: 1px solid #000;
            width: 220px;
            height: 70px;
            text-align: center;
            font-weight: bold;
            padding-top: 8px;
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
            @if($logoOnca)<img src="{{ $logoOnca }}" alt="ONCA" class="header-logo">@endif
        </td>
        <td class="header-center" style="width: 56%;">
            Direction Régionale du Conseil Agricole<br>Rabat-Salé-Kénitra
        </td>
        <td style="width: 22%; text-align: right;">
            @if($sceauMaroc)<img src="{{ $sceauMaroc }}" alt="Royaume du Maroc" class="header-logo">@endif
        </td>
    </tr>
</table>
<div class="header-line"></div>

<div class="main-title">RAPPORT DE PRESENTATION</div>

<div class="section">
    <div class="section-title">1. Marché N° :</div>
    <div class="section-content">
        <span class="bold">Marché N°{{ $doc['num_marche'] }}</span><br>
        Issu de l'appel d'offres n° <span class="bold">{{ $doc['num_aoo'] }}</span>
        du <span class="bold">{{ $doc['date_aoo'] ?: '................' }}</span>
    </div>
</div>

<div class="section">
    <div class="section-title">2. Attributaire du marché</div>
    <div class="section-content">
        <span class="bold">{{ $doc['attributaire'] }}</span><br>
        Sis au : <span class="bold">{{ $doc['attributaire_adresse'] }}</span>
    </div>
</div>

<div class="section">
    <div class="section-title">3. Objet du marché :</div>
    <div class="section-content bold">{{ $doc['objet_marche'] }}</div>
</div>

<div class="section">
    <div class="section-title">4. Mode de passation du marché :</div>
    <div class="mode-box">
        Appel d'offres ouvert simplifié sur offres de prix, séance publique, Passé en application de l'alinéa 2,
        paragraphe 1 de l'article 19 et paragraphe 1 de l'article 20 et alinéa 3, du paragraphe 3 de l'article 20
        du décret n° 2-22-431 du 15 Chaabane 1444 (08 mars 2023) relatif aux marchés publics.
    </div>
</div>

<div class="section">
    <div class="section-title">5. Motifs ayant déterminé le choix du mode de passation :</div>
    <div class="section-content">Une participation massive à la concurrence.</div>
</div>

<div class="section">
    <div class="section-title">6. Exposé de l'économie général du marché :</div>

    <div class="sub-section">
        <span class="sub-label">6.1. Forme du marché :</span> {{ $doc['lot_mention'] }}
    </div>
    <div class="sub-section">
        <span class="sub-label">6.2. Montant de l'estimation du maître d'ouvrage :</span>
        {{ $doc['estimation_ttc_formate'] }} TTC
    </div>
    <div class="sub-section">
        <span class="sub-label">6.3. Montant du Marchés : (en dh)</span><br>
        {{ $doc['montant_marche_ttc_formate'] }} TTC<br>
        <span class="sub-label">En lettres :</span> {{ $doc['montant_marche_lettres'] }}
    </div>
    <div class="sub-section">
        <span class="sub-label">6.4. Caractère des prix :</span> Prix fermes et non révisable.
    </div>
    <div class="sub-section">
        <span class="sub-label">6.5. Délai d'exécution :</span> {{ $doc['delai_execution'] }}
    </div>
    <div class="sub-section">
        <span class="sub-label">6.6. Imputation budgétaire :</span>
        <table class="budget-table">
            <tr>
                <th style="width: 22%;">Budget</th>
                <th colspan="3" style="width: 48%;">Rubrique</th>
                <th style="width: 30%;">Montant</th>
            </tr>
            <tr>
                <th>&nbsp;</th>
                <th style="width: 16%;">{{ $doc['rubrique_1'] ?: '—' }}</th>
                <th style="width: 16%;">{{ $doc['rubrique_2'] ?: '—' }}</th>
                <th style="width: 16%;">{{ $doc['rubrique_3'] ?: '—' }}</th>
                <th>&nbsp;</th>
            </tr>
            <tr>
                <td class="bold">{{ $doc['type_budget'] }}</td>
                <td colspan="3" class="left bold">{{ $doc['intitule_budget'] }}</td>
                <td class="bold">{{ $doc['montant_marche_ttc_formate'] }}</td>
            </tr>
        </table>
    </div>
</div>

<div class="section">
    <div class="section-title">7. Justification du choix des critères de sélection des candidatures et de jugement des offres</div>
    <div class="bullets">
        <p>* le dossier administratif et technique doit refléter des qualités permettant aux concurrents d'honorer leurs engagements dans les bons termes vis-à-vis de l'administration</p>
        <p>* L'offre présentée doit être intérssante, logique et bénéfique à l'administration.</p>
        <p>* S'assurer de la transparence du déroulement de la procédure du marché.</p>
        <p>* S'assurer de l'égalité d'accès aux commandes publiques et de l'effecacité de la dépense publique</p>
    </div>
</div>

<div class="section">
    <div class="section-title">8. Justification du choix de l'attributaire</div>
    <div class="bullets">
        <p>* Le dossier administratif et technique de l'attributaire déposé ne présente aucune défaillance.</p>
        <p>* L'offre retenue par l'administration est jugée économiquement la plus avantageuse.</p>
    </div>
</div>

<table class="footer-signatures">
    <tr>
        <td style="width: 50%;">
            <div class="sign-box">Signature du maitre d'ouvrage</div>
        </td>
        <td style="width: 50%; text-align: right;">
            <div class="date-box">Fait à Kénitra le : {{ $doc['date_document'] }}</div>
        </td>
    </tr>
</table>

</body>
</html>
