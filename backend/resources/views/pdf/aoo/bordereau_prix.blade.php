<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Bordereau des prix</title>
    <style>
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 13px;
            color: #000;
            margin: 0;
            padding: 0;
            line-height: 1.4;
        }
        .page-wrapper {
            margin: 10px;
            padding: 10px;
        }
        .header-title {
            text-align: center;
            font-size: 15px;
            font-weight: bold;
            margin-bottom: 25px;
            text-transform: uppercase;
        }
        .subtitle {
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .lot-title {
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .table-title {
            text-align: center;
            font-weight: bold;
            font-size: 15px;
            margin-bottom: 5px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #000;
            padding: 6px;
            text-align: center;
            vertical-align: middle;
        }
        table.data-table th {
            font-weight: bold;
            background-color: #fff;
        }
        .totals-row td {
            font-weight: bold;
            text-align: right;
            padding-right: 10px;
        }
        .totals-value {
            text-align: center !important;
        }
        .signature-box {
            margin-top: 40px;
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>
<body>
    @php
        $logoOncaPath = public_path('images/logo-onca.png');
        $logoOncaSrc = file_exists($logoOncaPath) ? 'data:' . mime_content_type($logoOncaPath) . ';base64,' . base64_encode(file_get_contents($logoOncaPath)) : '';
        
        $sceauMarocPath = public_path('images/sceau-maroc.png');
        $sceauMarocSrc = file_exists($sceauMarocPath) ? 'data:' . mime_content_type($sceauMarocPath) . ';base64,' . base64_encode(file_get_contents($sceauMarocPath)) : '';
    @endphp
    <div class="page-wrapper">
        <table style="width: 100%; margin-bottom: 20px;">
            <tr>
                <td style="width: 15%; vertical-align: top;">
                    @if($logoOncaSrc)
                        <img src="{{ $logoOncaSrc }}" height="120" style="display: block; margin-top: -20px;" alt="ONCA">
                    @endif
                </td>
                <td style="width: 70%; vertical-align: middle; text-align: center; font-size: 16px; font-weight: bold; padding-top: 10px; white-space: nowrap;">
                    <span style="font-size: 18px;">Office National du Conseil Agricole</span><br>
                    <span style="font-size: 18px;">Direction Régionale du Conseil Agricole de Rabat-Salé-Kénitra</span><br>
                    ********************
                </td>
                <td style="width: 15%; vertical-align: top; text-align: right;">
                    @if($sceauMarocSrc)
                        <img src="{{ $sceauMarocSrc }}" height="120" style="display: block; margin-left: auto; margin-top: -20px;" alt="Royaume du Maroc">
                    @endif
                </td>
            </tr>
            <tr>
                <td colspan="3" style="border-bottom: 2px solid #000; padding-top: 5px;"></td>
            </tr>
        </table>

        <div class="header-title">
            APPEL D'OFFRES OUVERT NATIONAL SUR OFFRES DE PRIX<br>
            N° {{ $aoo->num_aoo }}
        </div>

        <div class="subtitle">
            BORDEREAU DES PRIX - DETAIL ESTIMATIF RELATIF A {{ $aoo->objet }}
        </div>

        @if($aoo->lots && $aoo->lots->count() > 0)
            @foreach($aoo->lots as $lot)
            
            <div class="lot-title">
                LOT {{ $loop->iteration }} : {{ $lot->objet }}
            </div>

            <div class="table-title">BORDEREAU DES PRIX-DETAIL ESTIMATIF</div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 10%;">N° de Prix</th>
                        <th style="width: 45%;">Désignation des prestations</th>
                        <th style="width: 12%;">Unité de compte</th>
                        <th style="width: 11%;">Quantité<br><br>(1)</th>
                        <th style="width: 11%;">Prix unitaire HT (Dh)<br><br>(2)</th>
                        <th style="width: 11%;">Prix Total HT (Dh)<br><br>3 = (1) x (2)</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalHt = 0;
                    @endphp
                    @if($lot->items && $lot->items->count() > 0)
                        @foreach($lot->items as $item)
                            @php
                                $totalHt += ($item->quantite * $item->prix_unitaire_ht);
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td style="text-align: left;">{{ $item->designation }}</td>
                                <td>{{ $item->unite }}</td>
                                <td>{{ $item->quantite }}</td>
                                @php
                                    $montantTotalLigne = $item->quantite * $item->prix_unitaire_ht;
                                @endphp
                                <td>{{ number_format($item->prix_unitaire_ht, 2, ',', ' ') }}</td>
                                <td>{{ number_format($montantTotalLigne, 2, ',', ' ') }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="6" style="padding: 20px;">Aucun article configuré pour ce lot.</td>
                        </tr>
                    @endif
                    
                    @php
                        $tva = $totalHt * 0.20;
                        $totalTtc = $totalHt + $tva;
                    @endphp
                    
                    <tr class="totals-row">
                        <td colspan="5">Montant total Hors TVA</td>
                        <td class="totals-value">{{ number_format($totalHt, 2, ',', ' ') }}</td>
                    </tr>
                    <tr class="totals-row">
                        <td colspan="5">Montant de la TVA (20%)</td>
                        <td class="totals-value">{{ number_format($tva, 2, ',', ' ') }}</td>
                    </tr>
                    <tr class="totals-row">
                        <td colspan="5">Montant total TTC</td>
                        <td class="totals-value">{{ number_format($totalTtc, 2, ',', ' ') }}</td>
                    </tr>
                </tbody>
            </table>

            <div class="signature-box">
                Fait à ...................................... le ..............................<br><br>
                (Signature et cachet du concurrent)
            </div>

            @if(!$loop->last)
                <div style="page-break-after: always;"></div>
            @endif

            @endforeach
        @else
            <div class="table-title">BORDEREAU DES PRIX-DETAIL ESTIMATIF</div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 10%;">N° de Prix</th>
                        <th style="width: 45%;">Désignation des prestations</th>
                        <th style="width: 12%;">Unité de compte</th>
                        <th style="width: 11%;">Quantité<br><br>(1)</th>
                        <th style="width: 11%;">Prix unitaire HT (Dh)<br><br>(2)</th>
                        <th style="width: 11%;">Prix Total HT (Dh)<br><br>3 = (1) x (2)</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalHt = 0;
                        $items = [];
                        if ($aoo->lots && $aoo->lots->count() > 0) {
                            $items = $aoo->lots->first()->items;
                        }
                    @endphp
                    @if(count($items) > 0)
                        @foreach($items as $item)
                            <tr>
                                <td>{{ $item->numero_prix }}</td>
                                <td style="text-align: left;">{{ $item->designation }}</td>
                                <td>{{ $item->unite }}</td>
                                <td>{{ $item->quantite }}</td>
                                <td></td>
                                <td></td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="6" style="padding: 20px;">Aucun article configuré pour cet Appel d'Offres.</td>
                        </tr>
                    @endif
                    
                    <tr class="totals-row">
                        <td colspan="5">Montant total Hors TVA</td>
                        <td class="totals-value"></td>
                    </tr>
                    <tr class="totals-row">
                        <td colspan="5">Montant de la TVA (20%)</td>
                        <td class="totals-value"></td>
                    </tr>
                    <tr class="totals-row">
                        <td colspan="5">Montant total TTC</td>
                        <td class="totals-value"></td>
                    </tr>
                </tbody>
            </table>

            <div class="signature-box">
                Fait à ...................................... le ..............................<br><br>
                (Signature et cachet du concurrent)
            </div>
        @endif

    </div>
</body>
</html>
