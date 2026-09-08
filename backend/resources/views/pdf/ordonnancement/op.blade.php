<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ordre de Paiement - {{ $ordonnancement->num_op ?: $ordonnancement->num_ordonnancement }}</title>
    <style>
        @page {
            margin: 25px 30px;
            size: A4 portrait;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11pt;
            color: #1a1a1a;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .header-table td {
            vertical-align: top;
        }
        .org-title {
            font-size: 11pt;
            font-weight: bold;
            color: #0f3e2e;
            text-transform: uppercase;
        }
        .org-subtitle {
            font-size: 9pt;
            color: #4b5563;
        }
        .ref-box {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 10pt;
            margin-bottom: 20px;
        }
        .ref-box table {
            width: 100%;
        }
        .doc-title {
            text-align: center;
            font-size: 16pt;
            font-weight: bold;
            color: #0f3e2e;
            letter-spacing: 1px;
            margin: 15px 0 5px 0;
            text-transform: uppercase;
        }
        .recipient-box {
            text-align: center;
            font-weight: bold;
            font-size: 10.5pt;
            margin-bottom: 20px;
            padding: 8px;
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 6px;
            color: #166534;
        }
        .content-block {
            margin-bottom: 20px;
            text-align: justify;
        }
        .detail-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0 20px 0;
        }
        .detail-table td {
            padding: 6px 10px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 10.5pt;
        }
        .detail-table td.label {
            font-weight: bold;
            width: 32%;
            color: #334155;
            background-color: #f8fafc;
        }
        .amount-highlight {
            font-size: 12pt;
            font-weight: bold;
            color: #0f3e2e;
        }
        .signatures-table {
            width: 100%;
            margin-top: 35px;
            border-collapse: separate;
            border-spacing: 15px 0;
        }
        .sig-box {
            width: 50%;
            border: 1.5px solid #22c55e;
            background-color: #f0fdf4;
            border-radius: 6px;
            padding: 10px 8px;
            text-align: center;
            font-size: 8.5pt;
            font-weight: bold;
            color: #14532d;
            height: 110px;
            vertical-align: top;
        }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td style="width: 20%;">
                <div style="font-size: 13pt; font-weight: bold; color: #15803d;">DRCA-RSK</div>
                <div style="font-size: 8pt; color: #64748b;">Royaume du Maroc</div>
            </td>
            <td style="width: 80%; text-align: right;">
                <div class="org-title">Direction Régionale du Conseil Agricole</div>
                <div class="org-subtitle">Rabat-Salé-Kénitra</div>
            </td>
        </tr>
    </table>

    <div class="ref-box">
        <table>
            <tr>
                <td><strong>Référence :</strong> {{ $ordre->num_ordre ?? $ordonnancement->num_op ?? 'OP N° 38' }} /DRCA-RSK/{{ $ordonnancement->exercice }}</td>
                <td style="text-align: right;"><strong>Date :</strong> {{ \Carbon\Carbon::parse($ordonnancement->date_ordonnancement)->format('d/m/Y') }}</td>
            </tr>
        </table>
    </div>

    <div class="doc-title">ORDRE DE PAIEMENT</div>
    
    <div class="recipient-box">
        À MONSIEUR LE TRÉSORIER GÉNÉRAL DU ROYAUME<br>
        CHEF DE L'AGENCE BANCAIRE DE KÉNITRA
    </div>

    <div class="content-block">
        <p>
            Par le débit de notre compte courant <strong>N° 31033010000022470154780181</strong>, 
            <em>ONCA DR RABAT-SALE-KENITRA {{ strtoupper($ordonnancement->budget_type) }}</em>, ouvert dans vos livres, veuillez payer :
        </p>

        <table class="detail-table">
            <tr>
                <td class="label">La somme de :</td>
                <td>
                    <span class="amount-highlight">{{ number_format($montant, 2, ',', ' ') }} DH</span><br>
                    <small><em>({{ $montant_lettres }})</em></small>
                </td>
            </tr>
            <tr>
                <td class="label">Au profit de :</td>
                <td><strong>{{ $beneficiaire }}</strong></td>
            </tr>
            <tr>
                <td class="label">Titulaire du compte :</td>
                <td>{{ $beneficiaire }}</td>
            </tr>
            <tr>
                <td class="label">RIB :</td>
                <td><strong style="font-family: monospace; font-size: 11pt; letter-spacing: 1px;">{{ $rib ?: '310 810 100 002 470 105 200 152' }}</strong></td>
            </tr>
            <tr>
                <td class="label">Référence :</td>
                <td>{{ $ordonnancement->reference }}</td>
            </tr>
            <tr>
                <td class="label">Imputation :</td>
                <td>ART {{ $ordonnancement->article }} / PAR {{ $ordonnancement->paragraphe }} / LIG {{ $ordonnancement->ligne }} / S/LIG {{ $ordonnancement->sous_ligne }}</td>
            </tr>
            <tr>
                <td class="label">Mode de paiement :</td>
                <td><strong style="color: #15803d;">{{ strtoupper($mode_paiement) }}</strong></td>
            </tr>
        </table>

        <p style="margin-top: 25px; font-size: 10pt;">
            Dans l'attente de votre avis de débit, veuillez agréer, Monsieur le Trésorier Général du Royaume, l'expression de nos salutations distinguées.
        </p>
    </div>

    <table class="signatures-table">
        <tr>
            <td class="sig-box">
                LE DIRECTEUR RÉGIONAL DE L'OFFICE NATIONAL DU CONSEIL AGRICOLE DE RABAT-SALÉ-KÉNITRA
            </td>
            <td class="sig-box">
                LE FONDÉ DE POUVOIRS DU TRÉSORIER PAYEUR DE L'ONCA AUPRÈS DE LA DIRECTION RÉGIONALE DU RABAT-SALÉ-KÉNITRA
            </td>
        </tr>
    </table>

</body>
</html>
