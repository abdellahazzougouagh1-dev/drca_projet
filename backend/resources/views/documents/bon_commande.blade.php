<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $docTitle }}</title>
    <style>
        @page { size: A4 portrait; margin: 102px 36px 96px 36px; }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12.5px;
            color: #000;
            line-height: 1.45;
            margin: 0;
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
            font-size: 12px;
            line-height: 1.4;
            margin-top: 2px;
            margin-bottom: 6px;
        }

        .title-box {
            border: 2.5px solid #000;
            text-align: center;
            font-weight: bold;
            font-size: 15.5px;
            padding: 6px 14px;
            margin: 0 auto 10px auto;
            width: 82%;
            background: #fff;
            letter-spacing: 0.3px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid #000;
            margin-bottom: 10px;
        }
        .info-table td {
            border: 1px solid #000;
            padding: 5.5px 8px;
            font-size: 11.5px;
            vertical-align: middle;
        }
        .info-label {
            font-weight: bold;
            width: 13%;
            background: #f2f2f2;
        }
        .info-val {
            width: 47%;
        }

        .section-title-box {
            border: 1.5px solid #000;
            border-bottom: none;
            width: 48%;
            font-weight: bold;
            font-size: 12px;
            padding: 5px 10px;
            background: #f2f2f2;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid #000;
            margin-bottom: 10px;
        }
        .data-table th, .data-table td {
            border: 1px solid #000;
            padding: 6px 7px;
            font-size: 11.5px;
        }
        .data-table th {
            text-align: center;
            font-weight: bold;
            background: #f2f2f2;
            text-transform: uppercase;
            font-size: 11px;
        }
        .empty-row td {
            height: 18px;
            padding: 4px 7px;
        }

        .totals-table {
            width: 390px;
            margin-left: auto;
            margin-bottom: 10px;
            border-collapse: collapse;
            border: 2px solid #000;
        }
        .totals-table td {
            border: 1.5px solid #000;
            padding: 6px 10px;
            font-size: 12px;
            font-weight: bold;
        }
        .totals-table .tlabel {
            background: #f2f2f2;
            width: 60%;
        }

        .closing-row {
            width: 100%;
            margin-top: 14px;
            border-collapse: collapse;
            page-break-inside: avoid;
        }
        .closing-row td {
            border: none;
            vertical-align: top;
            padding: 0;
        }
        .date-box {
            border: 1.5px solid #000;
            padding: 6px 10px;
            width: 240px;
            font-weight: bold;
            font-size: 12px;
        }
    </style>
    @include('documents.partials.bc_typography')
</head>
<body>
    @include('documents.partials.bc_header')
    @include('documents.partials.bc_footer')
<div class="page">

    @php
        $isConvention = (!empty($consultation) && (in_array($consultation->mode_engagement, ['Convention', 'Contrat']) || str_starts_with(strtoupper($consultation->numero_consultation ?? ''), 'CONV')))
            || str_starts_with(strtoupper((string)data_get($documentData ?? [], 'numero_consultation', '')), 'CONV');
        $typeBudget = data_get($documentData ?? [], 'type_budget') ?: ($consultation->type_budget ?? 'Investissement');
        $isContrat = $isConvention && strtolower((string)$typeBudget) === 'fonctionnement';
        $cleanNumConv = trim(preg_replace('/\bCONV\s*[-_]?\s*/i', '', (string)($doc['numero_consultation'] ?? ($consultation->numero_consultation ?? ''))));
        $cleanNumConv = trim(preg_replace('/^[-_\s]+|[-_\s]+$/', '', $cleanNumConv));
        $refDocTitle = $isConvention ? (($isContrat ? 'Contrat N° : ' : 'Convention N° : ') . $cleanNumConv) : ('Bon de commande N° : ' . ($doc['numero_bc'] ?? ''));
    @endphp

    <div class="meta-header">
        Exercice {{ $consultation->annee ?? date('Y') }}<br>
        Budget {{ $typeBudget }}
    </div>

    <div class="title-box">
        {{ $refDocTitle }}
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
            <td colspan="3" rowspan="3" class="center" style="padding: 8px; font-size: 11px; line-height: 1.35;">{{ $doc['intitule'] }}</td>
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
        {{ $isConvention ? ($isContrat ? 'Détail du contrat' : 'Détail de la convention') : 'Détail du bon de commande' }}
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
            @php $articleCount = 0; @endphp
            @forelse($doc['articles'] as $key => $article)
                @php $articleCount++; @endphp
                <tr>
                    <td class="center">{{ $key + 1 }}</td>
                    <td style="text-align: left;">{{ $article->designation }}</td>
                    <td class="center">{{ $article->unite ?? 'Unité' }}</td>
                    <td class="center">{{ $article->quantite ?? 1 }}</td>
                    <td class="right">{{ number_format($article->prix_unitaire_ht ?? 0, 2, ',', ' ') }}</td>
                    <td class="right">{{ number_format(($article->quantite ?? 1) * ($article->prix_unitaire_ht ?? 0), 2, ',', ' ') }}</td>
                </tr>
            @empty
                @php $articleCount = 1; @endphp
                <tr>
                    <td class="center">1</td>
                    <td style="text-align: left;">{{ $doc['objet'] }}</td>
                    <td class="center">Unité</td>
                    <td class="center">1</td>
                    <td class="right">{{ number_format($doc['total_ht'], 2, ',', ' ') }}</td>
                    <td class="right">{{ number_format($doc['total_ht'], 2, ',', ' ') }}</td>
                </tr>
            @endforelse
            @for($i = max(0, 6 - $articleCount); $i > 0; $i--)
                <tr class="empty-row">
                    <td></td><td></td><td></td><td></td><td></td><td></td>
                </tr>
            @endfor
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td class="tlabel">Total Hors Taxes (HT)</td>
            <td class="right">{{ number_format($doc['total_ht'], 2, ',', ' ') }}</td>
        </tr>
        <tr>
            <td class="tlabel">Montant TVA ({{ number_format($doc['tva_rate'] * 100, 0) }}%)</td>
            <td class="right">{{ number_format($doc['tva'], 2, ',', ' ') }}</td>
        </tr>
        <tr>
            <td class="tlabel">Total TTC</td>
            <td class="right">{{ number_format($doc['total_ttc'], 2, ',', ' ') }}</td>
        </tr>
    </table>

    @php
        $arreteTexte = $isConvention 
            ? ($isContrat ? 'Le présent contrat est arrêté à la somme de :' : 'La présente convention est arrêtée à la somme de :') 
            : 'Le présent bon de commande est arrêté à la somme de :';
    @endphp

    <div style="margin-top: 8px; font-size: 12.5px;">
        <span class="bold">{{ $arreteTexte }}</span>
        <span class="bold" style="margin-left: 20px;">{{ number_format($doc['total_ttc'], 2, ',', ' ') }} DH TTC</span>
    </div>
    <div style="margin-top: 4px; font-size: 12.5px;">
        <span>En lettres :</span>
        <span class="bold" style="margin-left: 16px;">{{ $doc['montant_en_lettres'] }}</span>
    </div>

    <table class="closing-row">
        <tr>
            <td style="width: 55%; vertical-align: bottom;">
                <div class="date-box">
                    <table style="width: 100%; border: none;">
                        <tr>
                            <td style="border: none; font-weight: bold; width: 32%; font-size: 12px;">Date :</td>
                            <td style="border: none; text-align: center; font-weight: bold; font-size: 12px;">{{ $doc['date_document'] }}</td>
                        </tr>
                    </table>
                </div>
            </td>
            <td style="width: 45%; text-align: right; font-weight: bold; font-size: 13px; padding-right: 25px; padding-bottom: 18px;">
                Le Sous-Ordonnateur
            </td>
        </tr>
    </table>
</div>
</body>
</html>
