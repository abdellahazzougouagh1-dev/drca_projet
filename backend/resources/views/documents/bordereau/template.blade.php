<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Bordereau des Prix</title>
    <style>
        @page {
            margin: 100px 50px 80px 50px;
        }
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 12pt;
            line-height: 1.3;
            color: #000;
        }
        header {
            position: fixed;
            top: -60px;
            left: 0px;
            right: 0px;
            height: 50px;
        }
        footer {
            position: fixed; 
            bottom: -50px; 
            left: 0px; 
            right: 0px;
            height: 30px;
            font-size: 9pt;
            font-style: italic;
        }
        .pagenum:before {
            content: counter(page);
        }
        .footer-table {
            width: 100%;
            border: none;
        }
        .footer-table td {
            border: none;
            padding: 0;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }

        .title-doc {
            font-size: 14pt;
            font-weight: bold;
            text-align: center;
            margin-bottom: 30px;
            text-transform: uppercase;
        }
        .subtitle {
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            text-align: justify;
            margin-bottom: 15px;
        }
        .lot-title {
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 30px;
        }
        .table-title {
            font-size: 14pt;
            font-weight: bold;
            text-align: center;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        
        table.bordereau-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        table.bordereau-table th, table.bordereau-table td {
            border: 1px solid #000;
            padding: 6px;
            vertical-align: middle;
        }
        table.bordereau-table th {
            text-align: center;
            font-weight: bold;
        }
        table.bordereau-table td.col-num {
            text-align: center;
            width: 8%;
        }
        table.bordereau-table td.col-designation {
            text-align: justify;
            width: 37%;
        }
        table.bordereau-table td.col-unite {
            text-align: center;
            width: 12%;
        }
        table.bordereau-table td.col-qte {
            text-align: center;
            width: 10%;
        }
        table.bordereau-table td.col-prix {
            text-align: right;
            width: 15%;
        }
        table.bordereau-table td.col-total {
            text-align: right;
            width: 18%;
        }

        .totals-label {
            text-align: right;
            font-weight: bold;
            padding-right: 10px !important;
        }
        .totals-value {
            text-align: right;
            font-weight: bold;
        }

        .signature-zone {
            margin-top: 40px;
            width: 100%;
        }
        .signature-table {
            width: 100%;
            border: none;
        }
        .signature-table td {
            border: none;
            width: 50%;
            text-align: center;
            font-weight: bold;
            vertical-align: top;
        }
        .signature-block {
            margin-left: auto;
            margin-right: auto;
            width: 80%;
            text-align: center;
        }
    </style>
</head>
<body>

    <footer>
        <table class="footer-table">
            <tr>
                <td style="text-align: left; width: 80%;">
                    Appel d'offres national sur offres de prix n° {{ $marche->aoo->numero ?? '..................' }} - Cahier des Prescriptions Spéciales
                </td>
                <td style="text-align: right; width: 20%; font-weight: bold; font-style: normal; font-size: 11pt;">
                    <span class="pagenum"></span>
                </td>
            </tr>
        </table>
    </footer>

    <main>
        <div class="title-doc">
            APPEL D'OFFRES OUVERT NATIONAL SUR OFFRES DE PRIX<br>
            N° {{ $marche->aoo->numero ?? '..................' }}
        </div>

        <div class="subtitle">
            BORDEREAU DES PRIX – DETAIL ESTIMATIF RELATIF A {{ $marche->aoo->objet ?? $marche->objet_marche ?? '....................................................................' }}
        </div>

        @if($relationLot)
        <div class="lot-title">
            LOT {{ $relationLot->numero }} : {{ $relationLot->designation }}
        </div>
        @endif

        <div class="table-title">
            BORDEREAU DES PRIX-DETAIL ESTIMATIF
        </div>

        <table class="bordereau-table">
            <thead>
                <tr>
                    <th rowspan="2">N° de<br>Prix</th>
                    <th rowspan="2">Désignation des prestations</th>
                    <th rowspan="2">Unité de<br>compte</th>
                    <th>Quantité</th>
                    <th>Prix<br>unitaire<br>HT (Dh)</th>
                    <th>Prix Total<br>HT (Dh)</th>
                </tr>
                <tr>
                    <th>(1)</th>
                    <th>(2)</th>
                    <th>3 = (1) x (2)</th>
                </tr>
            </thead>
            <tbody>
                @if(isset($items) && count($items) > 0)
                    @foreach($items as $item)
                    <tr>
                        <td class="col-num">{{ $item->lotItem->numero ?? '' }}</td>
                        <td class="col-designation">{{ $item->lotItem->designation ?? '' }}</td>
                        <td class="col-unite">{{ $item->lotItem->unite ?? '' }}</td>
                        <td class="col-qte">{{ rtrim(rtrim(number_format($item->lotItem->quantite ?? 0, 2, ',', ' '), '0'), ',') }}</td>
                        <td class="col-prix">{{ number_format($item->prix_unitaire_attributaire ?? 0, 2, ',', ' ') }}</td>
                        <td class="col-total">{{ number_format($item->montant_ht ?? 0, 2, ',', ' ') }}</td>
                    </tr>
                    @endforeach
                @else
                    {{-- Lignes vides pour la démo si pas d'items --}}
                    @for($i=1; $i<=2; $i++)
                    <tr>
                        <td class="col-num">{{ $i }}</td>
                        <td class="col-designation">...........................................................................</td>
                        <td class="col-unite">..........</td>
                        <td class="col-qte">..........</td>
                        <td class="col-prix">..........</td>
                        <td class="col-total">..........</td>
                    </tr>
                    @endfor
                @endif

                {{-- Totaux --}}
                @php
                    $tauxTva = $marche->taux_tva ?? 20;
                    $montantHt = $marche->montant_ht ?? 0;
                    $montantTva = $marche->montant_tva ?? 0;
                    $montantTtc = $marche->montant ?? 0;
                @endphp
                <tr>
                    <td colspan="5" class="totals-label">Montant total Hors TVA</td>
                    <td class="totals-value">{{ number_format($montantHt, 2, ',', ' ') }}</td>
                </tr>
                <tr>
                    <td colspan="5" class="totals-label">Montant de la TVA ({{ number_format($tauxTva, 0, ',', '') }}%)</td>
                    <td class="totals-value">{{ number_format($montantTva, 2, ',', ' ') }}</td>
                </tr>
                <tr>
                    <td colspan="5" class="totals-label">Montant total TTC</td>
                    <td class="totals-value">{{ number_format($montantTtc, 2, ',', ' ') }}</td>
                </tr>
            </tbody>
        </table>

        <div class="signature-zone">
            <table class="signature-table">
                <tr>
                    <td></td>
                    <td>
                        <div class="signature-block">
                            Fait à ...................................... le ..........................<br><br><br>
                            (Signature et cachet du concurrent)
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </main>

</body>
</html>
