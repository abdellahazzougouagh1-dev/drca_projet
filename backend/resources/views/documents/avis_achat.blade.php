<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $docTitle }}</title>
    <style>
        @page { size: A4 portrait; margin: 102px 36px 96px 36px; }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 13px;
            color: #000;
            line-height: 1.5;
            margin: 0;
        }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: middle; }
        .page { position: relative; }
        .meta { width: 100%; margin-bottom: 16px; }
        .meta td { vertical-align: middle; padding: 5px 4px; font-size: 13px; }
        .title-banner {
            border: 2px solid #000;
            padding: 8px 10px;
            margin: 6px 0 18px 0;
            font-size: 14px;
            font-weight: bold;
        }
        .box { width: 100%; border-collapse: collapse; border: 1.5px solid #000; margin-bottom: 16px; }
        .box td, .box th {
            border: 1px solid #000;
            padding: 9px 8px;
            vertical-align: middle;
            font-size: 12px;
        }
        .box th {
            background: #f2f2f2;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            font-size: 11.5px;
        }
        .empty-row td {
            height: 30px;
        }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .underline { text-decoration: underline; }
        .mt { margin-top: 18px; }
        .mb { margin-bottom: 18px; }
        .small { font-size: 12px; margin: 14px 0; }
    </style>
    @include('documents.partials.bc_typography')
</head>
<body>
    @include('documents.partials.bc_header')
    @include('documents.partials.bc_footer')
<div class="page">

    <table class="meta mt">
        <tr>
            <td style="width: 7%;">N°</td>
            @php
                $numVal = $doc['numero_consultation'] ?? $doc['numero_avis'] ?? '';
            @endphp
            @if(str_contains($numVal, '/'))
                <td class="bold" style="width: 29%;">{{ $numVal }}</td>
            @else
                <td class="bold" style="width: 5%;">{{ $numVal }}</td>
                <td class="bold" style="width: 24%;">/DRCA-RSK/SA</td>
            @endif
            <td class="center bold">AVIS D'ACHAT SUR BON DE COMMANDE N° {{ $doc['numero_bc'] }}</td>
        </tr>
    </table>

    <table class="box mb">
        <tr>
            <td class="bold center" style="width: 13%;">Objet de la<br>prestation</td>
            <td class="center bold">{{ $doc['objet'] }}</td>
        </tr>
    </table>

    <p class="bold" style="margin-left: 7%;">Bordereau des prix :</p>
    <table class="box">
        <tr>
            <th style="width: 7%;">Prix N°</th>
            <th>Désignation</th>
            <th>Caractéristiques et spécifications</th>
            <th style="width: 12%;">Unité de<br>mesure</th>
            <th style="width: 10%;">Qt</th>
            <th style="width: 8%;">Garanties<br>exigées</th>
        </tr>
        @php $renderedRows = 0; @endphp
        @if(!empty($doc['consistance_lignes']) && is_array($doc['consistance_lignes']))
            @foreach($doc['consistance_lignes'] as $index => $ligne)
                @php $renderedRows++; @endphp
                <tr>
                    <td class="center bold">{{ data_get($ligne, 'numero_prix', $index + 1) }}</td>
                    <td class="center">{{ data_get($ligne, 'designation') ?: $doc['objet'] }}</td>
                    <td class="center">{{ data_get($ligne, 'specification') ?: data_get($ligne, 'designation') ?: $doc['objet'] }}</td>
                    <td class="center">{{ data_get($ligne, 'unite_mesure', 'Unité') }}</td>
                    <td class="center">{{ data_get($ligne, 'quantite', 1) }}</td>
                    <td class="center">{{ data_get($ligne, 'garantie_exigee') ?: '-' }}</td>
                </tr>
            @endforeach
        @else
            @forelse($doc['articles'] as $index => $article)
                @php $renderedRows++; @endphp
                <tr>
                    <td class="center bold">{{ $doc['numero_prix'] ?: ($index + 1) }}</td>
                    <td class="center">{{ $doc['designation'] ?: $article->designation }}</td>
                    <td class="center">{{ $doc['specification'] ?: $article->designation }}</td>
                    <td class="center">{{ $doc['unite_mesure'] ?: ($article->unite ?? 'Unité') }}</td>
                    <td class="center">{{ $doc['quantite'] ?: ($article->quantite ?? 1) }}</td>
                    <td class="center">{{ $doc['garantie_exigee'] ?: ($article->garantie_exigee ?? '-') }}</td>
                </tr>
            @empty
                @php $renderedRows = 1; @endphp
                <tr>
                    <td class="center bold">{{ $doc['numero_prix'] ?: 1 }}</td>
                    <td class="center">{{ $doc['designation'] ?: $doc['objet'] }}</td>
                    <td class="center">{{ $doc['specification'] ?: $doc['objet'] }}</td>
                    <td class="center">{{ $doc['unite_mesure'] ?: 'Unité' }}</td>
                    <td class="center">{{ $doc['quantite'] ?: 1 }}</td>
                    <td class="center">{{ $doc['garantie_exigee'] ?: '-' }}</td>
                </tr>
            @endforelse
        @endif
        @for($i = max(0, 6 - $renderedRows); $i > 0; $i--)
            <tr class="empty-row">
                <td></td><td></td><td></td><td></td><td></td><td></td>
            </tr>
        @endfor
    </table>

    <table class="meta mt">
        <tr><td style="width: 44%;">Lieu d'exécution :</td><td class="bold">{{ $doc['lieu_execution'] }}</td></tr>
        <tr><td>Délai de livraison :</td><td class="bold">{{ $doc['delai_livraison'] ?? '' }}{{ !empty($doc['delai_livraison_jours']) ? ' (' . $doc['delai_livraison_jours'] . ')' : '' }}</td></tr>
        <tr><td>Date et heure limites de réception des devis :</td><td class="right bold">{{ $doc['date_limite'] }} à {{ $doc['heure_limite'] }}</td></tr>
    </table>

    <p class="small">Les plis des concurrents sont déposés par voie électronique sur le portail des marchés publics accessible à l'adresse : www.marchespublics.gov.ma</p>

    <table class="meta" style="margin-top: 28px;">
        <tr>
            <td class="bold">Chef de SAF (Service Financier et Administratif)</td>
            <td class="center bold" style="width: 15%;">Date</td>
            <td class="center bold" style="width: 18%;">{{ $doc['date_document'] }}</td>
        </tr>
    </table>
</div>
</body>
</html>
