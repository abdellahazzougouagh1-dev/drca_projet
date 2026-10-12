<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Estimation de l'Administration - {{ $aoo->num_aoo }}</title>
    <style>
        @page {
            margin: 95px 45px 110px 45px;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            color: #000;
            line-height: 1.45;
        }
        
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }
        .header-table td {
            vertical-align: middle;
        }
        .header-center {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            padding: 0 10px;
        }
        .header-logo {
            height: 72px;
            width: auto;
        }
        .header-line {
            border-bottom: 2px solid #000080;
            margin-bottom: 18px;
        }

        footer {
            position: fixed;
            bottom: -95px;
            left: 0;
            right: 0;
            border-top: 1px solid #999;
            padding-top: 8px;
            font-size: 8px;
            color: #333;
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }
        .footer-table td {
            vertical-align: top;
            border: none;
        }
        .footer-left {
            width: 22%;
            font-weight: bold;
            font-size: 9px;
            line-height: 1.3;
        }
        .footer-center {
            width: 56%;
            text-align: center;
            font-size: 8px;
            line-height: 1.35;
        }
        .footer-right {
            width: 22%;
            text-align: right;
        }
        .footer-logo {
            height: 48px;
            width: auto;
        }

        .lot-page {
            page-break-inside: avoid;
        }
        .lot-page-break {
            page-break-after: always;
        }

        .title-box {
            border: 3px solid #000;
            text-align: center;
            padding: 10px 16px;
            margin: 18px auto 22px auto;
            width: 72%;
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-section {
            margin-bottom: 18px;
            font-size: 12px;
        }
        .info-line {
            margin-bottom: 6px;
        }
        .info-objet-label {
            font-weight: bold;
            margin-bottom: 4px;
        }
        .info-objet-text {
            text-transform: uppercase;
            text-align: justify;
            line-height: 1.5;
        }

        .price-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .price-table th,
        .price-table td {
            border: 1px solid #000;
            padding: 7px 6px;
        }
        .price-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
            font-size: 11px;
            text-transform: uppercase;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        
        .empty-row td {
            height: 24px;
        }

        .totals-wrapper {
            width: 100%;
            margin: 8px 0 28px 0;
        }
        .totals-table {
            width: 38%;
            float: right;
            border-collapse: collapse;
        }
        .totals-table td {
            border: 2px solid #000;
            padding: 7px 8px;
            font-weight: bold;
            font-size: 11px;
        }
        .totals-table .label {
            background-color: #f2f2f2;
            text-align: left;
        }
        .totals-table .value {
            text-align: right;
            min-width: 90px;
        }
        
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }

        .closing-layout {
            margin-top: 35px;
            width: 100%;
        }
        .closing-layout-table {
            width: 100%;
            border-collapse: collapse;
        }
        .closing-layout-table td {
            vertical-align: top;
            border: none;
            padding: 0;
        }
        .amount-words {
            font-size: 12px;
            line-height: 1.7;
            text-align: left;
            width: 62%;
            padding-right: 20px;
        }
        .amount-words strong {
            font-weight: bold;
            text-transform: capitalize;
        }
        .signature-box {
            text-align: right;
            font-weight: bold;
            font-size: 12px;
            padding-top: 30px;
        }
    </style>
</head>
<body>

@php
    $dateOuverture = $aoo->date_ouverture
        ? \Carbon\Carbon::parse($aoo->date_ouverture)->format('d/m/Y')
        : '......................';

    $embedImage = function (?string $relativePath): ?string {
        if (!$relativePath) {
            return null;
        }
        $path = public_path($relativePath);
        if (!file_exists($path)) {
            return null;
        }
        $mime = mime_content_type($path) ?: 'image/png';
        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
    };

    $logoOnca = $embedImage('images/logo-onca.png');
    $sceauMaroc = $embedImage('images/sceau-maroc.png');
    $logoGreen = $embedImage('images/generation-green.png');
@endphp

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
    </footer>

    @foreach ($lotsData as $lotData)
        @php
            $lot = $lotData['lot'];
            $items = $lotData['items'];
            $lineCount = $items->count();
            $lotLabel = trim(($lot->num_lot ?? 'LOT 1') . ($lot->objet_lot ? ' : ' . $lot->objet_lot : ''));
        @endphp

        <div class="lot-page {{ !$loop->last ? 'lot-page-break' : '' }}">
            <table class="header-table">
                <tr>
                    <td style="width: 24%;">
                        @if($logoOnca)
                            <img src="{{ $logoOnca }}" alt="Logo ONCA" class="header-logo">
                        @endif
                    </td>
                    <td class="header-center" style="width: 52%;">
                        Direction Régionale du Conseil Agricole Rabat-Salé-Kénitra
                    </td>
                    <td style="width: 24%; text-align: right;">
                        @if($sceauMaroc)
                            <img src="{{ $sceauMaroc }}" alt="Royaume du Maroc" class="header-logo">
                        @endif
                    </td>
                </tr>
            </table>
            <div class="header-line"></div>

            <div class="title-box">ESTIMATION DE L'ADMINISTRATION</div>

        <div class="info-section">
                <div class="info-line">
                    Appel d'offre Numéro : <strong>{{ $aoo->num_aoo }}</strong> en date du {{ $dateOuverture }} Lot : <strong>{{ $lotLabel }}</strong>
            </div>
                <div class="info-objet-label">Ayant pour Objet</div>
                <div class="info-objet-text">{{ $aoo->objet }}</div>
        </div>

        <table class="price-table">
            <thead>
                <tr>
                        <th style="width: 7%;">N°</th>
                        <th style="width: 46%;">Désignation</th>
                        <th style="width: 11%;">Unité</th>
                        <th style="width: 11%;">Quantité</th>
                        <th style="width: 12%;">PU HT</th>
                        <th style="width: 13%;">Montant HT</th>
                </tr>
            </thead>
            <tbody>
                    @forelse ($items as $index => $item)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td class="text-left">{{ $item->designation }}</td>
                            <td class="text-center">{{ $item->unite ?: '-' }}</td>
                            <td class="text-right">{{ number_format((float) $item->quantite, 2, ',', ' ') }}</td>
                            <td class="text-right">{{ number_format((float) $item->prix_unitaire_ht, 2, ',', ' ') }}</td>
                            <td class="text-right">{{ number_format((float) $item->montant_ht, 2, ',', ' ') }}</td>
                        </tr>
                    @empty
                <tr>
                    <td class="text-center">1</td>
                            <td class="text-left">Prestations selon le CPS.</td>
                    <td class="text-center">Forfait</td>
                            <td class="text-right">1,00</td>
                            <td class="text-right">{{ number_format($lotData['total_ht'], 2, ',', ' ') }}</td>
                            <td class="text-right">{{ number_format($lotData['total_ht'], 2, ',', ' ') }}</td>
                </tr>
                        @php $lineCount = 1; @endphp
                    @endforelse
                
                    @for ($i = max(0, 6 - $lineCount); $i > 0; $i--)
                <tr class="empty-row">
                        <td></td><td></td><td></td><td></td><td></td><td></td>
                </tr>
                @endfor
            </tbody>
        </table>

        <div class="totals-wrapper clearfix">
            <table class="totals-table">
                <tr>
                    <td class="label">Total Hors Taxe</td>
                        <td class="value">{{ number_format($lotData['total_ht'], 2, ',', ' ') }}</td>
                </tr>
                <tr>
                    <td class="label">TVA (20%)</td>
                        <td class="value">{{ number_format($lotData['total_tva'], 2, ',', ' ') }}</td>
                </tr>
                <tr>
                    <td class="label">Total TTC</td>
                        <td class="value">{{ number_format($lotData['total_ttc'], 2, ',', ' ') }}</td>
                </tr>
            </table>
        </div>

            <div class="closing-layout">
                <table class="closing-layout-table">
                    <tr>
                        <td class="amount-words">
                            L'estimation du maître d'ouvrage est arrêtée à la somme de :
                            <strong>{{ $lotData['montant_lettres'] }} Dirhams TTC.</strong>
                        </td>
                        <td class="signature-box">
                Le maître d'Ouvrage
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    @endforeach

</body>
</html>
