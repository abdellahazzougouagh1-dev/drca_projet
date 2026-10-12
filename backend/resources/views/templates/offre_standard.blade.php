<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Offre Standard - Bordereau des Prix</title>
    <style>
        @page {
            margin: 60px 50px 80px 50px;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            color: #000;
            line-height: 1.4;
        }
        .header-title {
            text-align: center;
            font-weight: bold;
            font-size: 15px;
            margin-bottom: 20px;
            text-transform: uppercase;
        }
        .sub-header {
            text-align: center;
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 30px;
        }
        .table-title {
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 20px;
            text-transform: uppercase;
            text-decoration: underline;
        }
        
        /* Table principale */
        table.bordereau {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.bordereau th, table.bordereau td {
            border: 1px solid #000;
            padding: 8px 6px;
            vertical-align: middle;
        }
        table.bordereau th {
            text-align: center;
            font-weight: bold;
        }

        /* Table des totaux (alignée à droite) */
        .totals-container {
            width: 100%;
            margin-bottom: 40px;
        }
        table.totals {
            width: 45%;
            float: right;
            border-collapse: collapse;
        }
        table.totals th, table.totals td {
            border: 1px solid #000;
            padding: 8px 6px;
            vertical-align: middle;
        }
        
        /* Utilitaires */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        
        /* Section Signature */
        .signature-section {
            width: 100%;
            clear: both;
            margin-top: 50px;
        }
        .signature-table {
            width: 100%;
            border: none;
        }
        .signature-table td {
            border: none;
        }
    </style>
</head>
<body>

    <div class="header-title">
        APPEL D'OFFRES OUVERT NATIONAL SUR OFFRES DE PRIX NO {{ $numero_aoo ?? 'AO-02/2026/DRCA-RSK' }}
    </div>

    <div class="sub-header">
        BORDEREAU DES PRIX DETAIL ESTIMATIF RELATIF A : {{ $objet_aoo ?? 'Acquisition de matériel et équipements agricoles au profit de la Direction Régionale de l\'Agriculture de Rabat-Salé-Kénitra' }}
    </div>

    <div class="table-title">
        BORDEREAU DES PRIX - DETAIL ESTIMATIF
    </div>

    <table class="bordereau">
        <thead>
            <tr>
                <th style="width: 8%;">Prix</th>
                <th style="width: 38%;">Désignation des prestations</th>
                <th style="width: 12%;">Unité de<br>compte</th>
                <th style="width: 12%;">Quantité</th>
                <th style="width: 15%;">Prix<br>unitaire HT<br>Dh</th>
                <th style="width: 15%;">Prix Total HT<br>(DH)</th>
            </tr>
        </thead>
        <tbody>
            @if(isset($lignes) && count($lignes) > 0)
                @foreach($lignes as $index => $ligne)
                <tr>
                    <td class="text-center">{{ $ligne->numero_prix ?? ($index + 1) }}</td>
                    <td class="text-left">{{ $ligne->designation }}</td>
                    <td class="text-center">{{ $ligne->unite }}</td>
                    <td class="text-center">{{ number_format($ligne->quantite, 2, '.', ' ') }}</td>
                    <td class="text-center">{{ number_format($ligne->prix_unitaire, 2, ',', ' ') }}</td>
                    <td class="text-center">{{ number_format($ligne->prix_total, 2, ',', ' ') }}</td>
                </tr>
                @endforeach
            @else
                <!-- Lignes fictives pour la prévisualisation si aucune donnée -->
                <tr>
                    <td class="text-center">1</td>
                    <td class="text-left">Tracteur agricole</td>
                    <td class="text-center">U</td>
                    <td class="text-center">2.00</td>
                    <td class="text-center">350 000,00</td>
                    <td class="text-center">700 000,00</td>
                </tr>
                <tr>
                    <td class="text-center">2</td>
                    <td class="text-left">Charrue agricole</td>
                    <td class="text-center">U</td>
                    <td class="text-center">5.00</td>
                    <td class="text-center">40 000,00</td>
                    <td class="text-center">200 000,00</td>
                </tr>
                <tr>
                    <td class="text-center">3</td>
                    <td class="text-left">Semoir agricole</td>
                    <td class="text-center">U</td>
                    <td class="text-center">4.00</td>
                    <td class="text-center">75 000,00</td>
                    <td class="text-center">300 000,00</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="totals-container">
        <table class="totals">
            <tr>
                <td class="text-right font-bold" style="width: 66%;">Total HT</td>
                <td class="text-center font-bold" style="width: 34%;">{{ number_format($montant_ht ?? 1200000, 2, ',', ' ') }}</td>
            </tr>
            <tr>
                <td class="text-right font-bold">TVA</td>
                <td class="text-center font-bold">{{ number_format($montant_tva ?? 240000, 2, ',', ' ') }}</td>
            </tr>
            <tr>
                <td class="text-right font-bold">Total TTC</td>
                <td class="text-center font-bold">{{ number_format($montant_ttc ?? 1440000, 2, ',', ' ') }}</td>
            </tr>
        </table>
    </div>

    <div class="signature-section">
        <table class="signature-table">
            <tr>
                <td class="text-left" style="width: 60%;">
                    Fait à ...................................... le ......................................
                </td>
                <td class="text-right" style="width: 40%; font-style: italic;">
                    <br><br><br>
                    (Signature et cachet du concurrent)
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
