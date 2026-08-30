<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $docTitle }}</title>
    <style>
        @page { margin: 115px 40px 70px 40px; }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12px;
            color: #000;
            line-height: 1.4;
            margin: 0;
        }
        .header-logo { height: 45px; width: auto; }
        .header-line {
            border-bottom: 2px solid #000080;
            margin-bottom: 18px;
        }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: middle; }
        .page { position: relative; }
        

        .right { text-align: right; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .underline { text-decoration: underline; }

        .meta-header {
            text-align: right;
            font-weight: bold;
            font-size: 11px;
            margin-top: 2px;
            margin-bottom: 8px;
        }

        .title-box {
            border: 2px solid #000;
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            padding: 5px;
            margin-bottom: 10px;
            background: #fff;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .info-table td {
            border: 1px solid #000;
            padding: 4px 6px;
            font-size: 10.5px;
            vertical-align: middle;
        }
        .info-label {
            font-weight: bold;
            width: 12%;
            background: #ffffff;
        }
        .info-val {
            width: 48%;
        }
        .imputation-cell {
            width: 40%;
            padding: 0 !important;
            vertical-align: top !important;
        }
        .imputation-subtable {
            width: 100%;
            border-collapse: collapse;
        }
        .imputation-subtable td {
            border: none;
            border-bottom: 1px solid #000;
            border-left: 1px solid #000;
            padding: 3px;
            text-align: center;
            font-size: 10.5px;
        }
        .imputation-subtable tr:last-child td {
            border-bottom: none;
        }
        .imputation-subtable .sub-label {
            width: 40%;
            font-weight: bold;
            border-left: none;
        }

        .section-title-box {
            border: 1px solid #000;
            border-bottom: none;
            width: 45%;
            font-weight: bold;
            font-size: 11px;
            padding: 3px 6px;
            background: #fff;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .data-table th, .data-table td {
            border: 1px solid #000;
            padding: 5px 6px;
            font-size: 10.5px;
        }
        .data-table th {
            text-align: center;
            font-weight: bold;
            background: #ffffff;
        }

        .totals-table {
            width: 360px;
            margin-left: auto;
            margin-bottom: 12px;
            border-collapse: collapse;
        }
        .totals-table td {
            border: 2px solid #000;
            padding: 5px 8px;
            font-size: 11px;
            font-weight: bold;
        }

        .date-box {
            border: 1px solid #000;
            padding: 4px 8px;
            width: 230px;
            font-weight: bold;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    @include('documents.partials.bc_header')
    @include('documents.partials.bc_footer')
<div class="page">

    <div class="meta-header">
        Exercice {{ $consultation->annee ?? date('Y') }}<br>
        Budget {{ $consultation->type_budget ?? 'Investissement' }}
    </div>

    <div class="title-box">
        Bon de commande N° : {{ $doc['numero_bc'] }}
    </div>

    <table class="info-table">
        <tr>
            <td class="info-label center" style="width: 12%;">Objet</td>
            <td class="info-val" style="width: 48%;">{{ $doc['objet'] }}</td>
            <td class="center bold" style="width: 20%;" rowspan="3">Imputation</td>
            <td class="center bold" style="width: 10%;">ART</td>
            <td class="center" style="width: 10%;">{{ $doc['art'] }}</td>
        </tr>
        <tr>
            <td class="info-label">Titulaire</td>
            <td>{{ $doc['titulaire_nom'] }}</td>
            <td class="center bold">PAR</td>
            <td class="center">{{ $doc['par'] }}</td>
        </tr>
        <tr>
            <td class="info-label">Adresse</td>
            <td>{{ $doc['adresse'] ?? $doc['adresse_societe'] ?? '' }}</td>
            <td class="center bold">LIG</td>
            <td class="center">{{ $doc['lig'] }}</td>
        </tr>
        <tr>
            <td class="info-label">Patente</td>
            <td>{{ $doc['patente'] }}</td>
            <td colspan="3" class="center bold underline">Intitulé</td>
        </tr>
        <tr>
            <td class="info-label">I.F</td>
            <td>{{ $doc['identifiant_fiscal'] ?? '25487963' }}</td>
            <td colspan="3" class="center underline">Prestation de même nature</td>
        </tr>
        <tr>
            <td class="info-label">CNSS</td>
            <td>{{ $doc['cnss'] }}</td>
            <td colspan="3" rowspan="3" class="center" style="padding: 6px; font-size: 10px; line-height: 1.3;">{{ $doc['intitule'] }}</td>
        </tr>
        <tr>
            <td class="info-label">ICE</td>
            <td>{{ $doc['ice'] }}</td>
        </tr>
        <tr>
            <td class="info-label">RIB</td>
            <td>{{ $doc['rib'] }}</td>
        </tr>
    </table>

    <div class="section-title-box">
        Détail du bon de commande
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">N°</th>
                <th style="width: 45%; text-align: left;">Désignation</th>
                <th style="width: 12%;">Unité</th>
                <th style="width: 8%;">Qt</th>
                <th style="width: 15%; text-align: right;">P.U HT</th>
                <th style="width: 15%; text-align: right;">Montant HT</th>
            </tr>
        </thead>
        <tbody>
            @forelse($doc['articles'] as $key => $article)
                <tr>
                    <td class="center">{{ $key + 1 }}</td>
                    <td style="text-align: left;">{{ $article->designation }}</td>
                    <td class="center">{{ $article->unite ?? 'Unité' }}</td>
                    <td class="center">{{ $article->quantite ?? 1 }}</td>
                    <td class="right">{{ number_format($article->prix_unitaire_ht ?? 0, 2, ',', ' ') }}</td>
                    <td class="right">{{ number_format(($article->quantite ?? 1) * ($article->prix_unitaire_ht ?? 0), 2, ',', ' ') }}</td>
                </tr>
            @empty
                <tr>
                    <td class="center">1</td>
                    <td style="text-align: left;">{{ $doc['objet'] }}</td>
                    <td class="center">Unité</td>
                    <td class="center">1</td>
                    <td class="right">{{ number_format($doc['total_ht'], 2, ',', ' ') }}</td>
                    <td class="right">{{ number_format($doc['total_ht'], 2, ',', ' ') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td style="width: 60%;">Total Hors Taxe</td>
            <td class="right" style="width: 40%;">{{ number_format($doc['total_ht'], 2, ',', ' ') }}</td>
        </tr>
        <tr>
            <td>Montant TVA ({{ number_format($doc['tva_rate'] * 100, 0) }}%)</td>
            <td class="right">{{ number_format($doc['tva'], 2, ',', ' ') }}</td>
        </tr>
        <tr>
            <td>Total TTC</td>
            <td class="right">{{ number_format($doc['total_ttc'], 2, ',', ' ') }}</td>
        </tr>
    </table>

    <div style="margin-top: 10px; font-size: 11px;">
        <span class="bold">Le présent Bon de Commande est arrêté à la somme de :</span>
        <span class="bold" style="margin-left: 25px;">{{ number_format($doc['total_ttc'], 2, ',', ' ') }}</span>
    </div>
    <div style="margin-top: 4px; font-size: 11px;">
        <span>En lettres :</span>
        <span class="bold" style="margin-left: 20px;">{{ $doc['montant_en_lettres'] }}</span>
    </div>

    <div class="right bold" style="margin-top: 20px; padding-right: 30px; font-size: 11px;">
        Le Sous-Ordonnateur
    </div>

    <div class="date-box">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="border: none; font-weight: bold; width: 30%;">Date :</td>
                <td style="border: none; text-align: center; font-weight: bold;">{{ $doc['date_document'] }}</td>
            </tr>
        </table>
    </div>
</div>
</body>
</html>
