<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Estimation Administrative</title>
    <style>
        body {
            font-family: "Calibri", "Arial", sans-serif;
            font-size: 14px;
            color: #000;
            margin: 0;
            padding: 0;
            line-height: 1.4;
        }
        .page-wrapper {
            margin: 10px;
            padding: 10px;
        }
        .header-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .header-title {
            text-align: center;
            font-size: 16px;
        }
        .title-box {
            border: 2px solid #000;
            padding: 8px;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin: 20px 0 30px 0;
            width: 80%;
            margin-left: auto;
            margin-right: auto;
        }
        .info-section {
            margin-bottom: 15px;
        }
        .info-section p {
            margin: 5px 0;
        }
        .obj-section {
            text-transform: uppercase;
            font-weight: bold;
            margin-top: 10px;
            margin-bottom: 20px;
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
        }
        table.data-table th {
            font-weight: bold;
            background-color: #fff;
        }
        .totals-table {
            width: 50%;
            border-collapse: collapse;
            float: right;
            margin-bottom: 30px;
        }
        .totals-table td {
            border: 1px solid #000;
            padding: 6px;
            font-weight: bold;
        }
        .totals-table td.label {
            text-align: left;
        }
        .totals-table td.value {
            text-align: center;
        }
        .clear {
            clear: both;
        }
        .footer-info {
            margin-top: 20px;
        }
        .footer-info p {
            margin: 5px 0;
        }
        .signature {
            text-align: right;
            margin-top: 40px;
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
        <div class="header-table">
            <table style="width: 100%;">
                <tr>
                    <td style="width: 15%; vertical-align: top;">
                        @if($logoOncaSrc)
                            <img src="{{ $logoOncaSrc }}" height="120" style="display: block; margin-top: -20px;" alt="ONCA">
                        @endif
                    </td>
                    <td style="width: 70%; vertical-align: middle; text-align: center; font-size: 16px; font-weight: bold; padding-top: 10px; white-space: nowrap;" class="header-title">
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
        </div>

        <div class="title-box">
            ESTIMATION budgetaire 
        </div>

        @php
            $rawDate = $aoo->date_preparation ?: ($aoo->created_at ?: now());
            try {
                $datePrep = \Carbon\Carbon::parse($rawDate)->format('d/m/Y');
            } catch (\Exception $e) {
                $datePrep = date('d/m/Y');
            }
            $numAoo = $aoo->num_aoo ?? '......................';
        @endphp

        <div class="info-section">
            Appel d'offre Numéro: &nbsp;&nbsp;&nbsp; <strong>{{ $numAoo }}</strong> &nbsp;&nbsp;&nbsp; en date du <strong>{{ $datePrep }}</strong> 
            @if($aoo->lots && $aoo->lots->count() > 0)
                (Lot)
            @else
                lot unique
            @endif
            <br>
            Ayant pour Objet<br>
            <div class="obj-section">
                {{ $aoo->objet }}
            </div>
        </div>

        <div style="margin-bottom: 20px;">
            <strong>Le maître d'Ouvrage :</strong><br>
            Direction Régionale du Conseil Agricole Rabat-Salé-Kénitra
        </div>

        @if($aoo->lots && $aoo->lots->count() > 0)
            @foreach($aoo->lots as $lot)
            <div style="font-weight: bold; margin-top: 10px; margin-bottom: 10px;">Lot {{ $loop->iteration }} : {{ $lot->objet }}</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 10%;">N°</th>
                        <th style="width: 40%;">Designation</th>
                        <th style="width: 10%;">Unité</th>
                        <th style="width: 15%;">Quantité</th>
                        <th style="width: 10%;">PU HT</th>
                        <th style="width: 15%;">Montant HT</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalHt = 0;
                    @endphp
                    @if($lot->items && $lot->items->count() > 0)
                        @foreach($lot->items as $item)
                            @php
                                $montant = $item->quantite * $item->prix_unitaire_ht;
                                $totalHt += $montant;
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td style="text-align: left;">{{ $item->designation }}</td>
                                <td>{{ $item->unite }}</td>
                                <td>{{ $item->quantite }}</td>
                                <td>{{ number_format($item->prix_unitaire_ht, 2, ',', ' ') }}</td>
                                <td>{{ number_format($montant, 2, ',', ' ') }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 20px;">Aucun article renseigné</td>
                        </tr>
                    @endif
                </tbody>
            </table>
            @php
                $tva = $totalHt * 0.20;
                $totalTtc = $totalHt + $tva;
                $montantLettres = class_exists('\NumberFormatter') ? (new \NumberFormatter('fr', \NumberFormatter::SPELLOUT))->format($totalTtc) : '';
            @endphp
            <table class="totals-table">
                <tr>
                    <td class="label">Total Hors Taxe</td>
                    <td class="value">{{ number_format($totalHt, 2, ',', ' ') }}</td>
                </tr>
                <tr>
                    <td class="label">TVA (20%)</td>
                    <td class="value">{{ number_format($tva, 2, ',', ' ') }}</td>
                </tr>
                <tr>
                    <td class="label">Total TTC</td>
                    <td class="value">{{ number_format($totalTtc, 2, ',', ' ') }}</td>
                </tr>
            </table>
            <div class="clear"></div>

            <div class="footer-info">
                <p>L'estimation du maître d'ouvrage est arrêtée à {{ number_format($totalTtc, 2, ',', ' ') }} DH</p>
                <p style="margin-top: 10px;">En lettres &nbsp;&nbsp;&nbsp;&nbsp; <span style="font-weight: bold; text-transform: uppercase;">{{ $montantLettres }} DH</span></p>
            </div>
            @endforeach
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 10%;">N°</th>
                        <th style="width: 40%;">Designation</th>
                        <th style="width: 10%;">Unité</th>
                        <th style="width: 15%;">Quantité</th>
                        <th style="width: 10%;">PU HT</th>
                        <th style="width: 15%;">Montant HT</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalHt = 0;
                        // On essaie de récupérer les items via le premier lot s'il est caché (bien qu'il ne devrait pas être là),
                        // ou si on a stocké les items directement sur l'AO. 
                        // Normalement dans la structure, les items sont sur des lots.
                        // Si pas de lots mais estimation unifiée :
                        $items = [];
                        if ($aoo->lots && $aoo->lots->count() > 0) {
                            $items = $aoo->lots->first()->items;
                        }
                    @endphp
                    @if(count($items) > 0)
                        @foreach($items as $item)
                            @php
                                $montant = $item->quantite * $item->prix_unitaire_ht;
                                $totalHt += $montant;
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td style="text-align: left;">{{ $item->designation }}</td>
                                <td>{{ $item->unite }}</td>
                                <td>{{ $item->quantite }}</td>
                                <td>{{ number_format($item->prix_unitaire_ht, 2, ',', ' ') }}</td>
                                <td>{{ number_format($montant, 2, ',', ' ') }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 20px;">Aucun article renseigné dans les lots.</td>
                        </tr>
                    @endif
                </tbody>
            </table>

            @php
                $tva = $totalHt * 0.20;
                $totalTtc = $totalHt + $tva;
                
                // Si l'AO n'a pas d'items, on utilise l'estimation globale saisie si elle existe
                if ($totalTtc == 0 && $aoo->estimation_ttc > 0) {
                    $totalTtc = $aoo->estimation_ttc;
                    $totalHt = $totalTtc / 1.20;
                    $tva = $totalTtc - $totalHt;
                }

                $montantLettres = class_exists('\NumberFormatter') ? (new \NumberFormatter('fr', \NumberFormatter::SPELLOUT))->format($totalTtc) : '';
            @endphp
            <table class="totals-table">
                <tr>
                    <td class="label">Total Hors Taxe</td>
                    <td class="value">{{ number_format($totalHt, 2, ',', ' ') }}</td>
                </tr>
                <tr>
                    <td class="label">TVA (20%)</td>
                    <td class="value">{{ number_format($tva, 2, ',', ' ') }}</td>
                </tr>
                <tr>
                    <td class="label">Total TTC</td>
                    <td class="value">{{ number_format($totalTtc, 2, ',', ' ') }}</td>
                </tr>
            </table>
            <div class="clear"></div>

            <div class="footer-info">
                <p>L'estimation du maître d'ouvrage est arrêtée à {{ number_format($totalTtc, 2, ',', ' ') }} DH</p>
                <p style="margin-top: 10px;">En lettres &nbsp;&nbsp;&nbsp;&nbsp; <span style="font-weight: bold; text-transform: uppercase;">{{ $montantLettres }} DH</span></p>
            </div>
        @endif

        <div class="signature">
            Le maître d'Ouvrage
        </div>
    </div>
</body>
</html>
