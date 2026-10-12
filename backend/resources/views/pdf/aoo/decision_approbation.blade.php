<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Notification d'approbation - {{ $num_marche }}</title>
    <style>
        @page {
            margin: 12mm 15mm 15mm 15mm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.45;
            color: #000;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .header-table td {
            vertical-align: top;
            border: none;
            padding: 0;
        }
        .center-header {
            text-align: center;
            font-size: 9.5pt;
            font-weight: bold;
        }
        .ref-bar {
            width: 100%;
            margin-top: 10px;
            margin-bottom: 20px;
            font-size: 10pt;
        }
        .destinataire-block {
            margin-left: 40%;
            text-align: center;
            margin-bottom: 25px;
        }
        .subject-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .subject-table td {
            padding: 3px 0;
            vertical-align: top;
        }
        .paragraph {
            text-align: justify;
            margin-bottom: 14px;
            line-height: 1.5;
        }
        .observation-box {
            border: 1px solid #000;
            padding: 10px 15px;
            margin: 20px 0;
            background-color: #f8fafc;
        }
        .accuse-block {
            margin-top: 30px;
            border-top: 2px dashed #000;
            padding-top: 15px;
        }
        .accuse-title {
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 12px;
        }
    </style>
</head>
<body>

    <!-- Header Logos & Direction -->
    <table class="header-table">
        <tr>
            <td style="width: 35%;">
                @if(file_exists(public_path('images/logo-onca.png')))
                    <img src="{{ public_path('images/logo-onca.png') }}" style="height: 45px;" alt="ONCA">
                @endif
            </td>
            <td class="center-header" style="width: 30%;">
                Direction Régionale du Conseil Agricole Rabat-Salé-Kénitra
            </td>
            <td style="width: 35%; text-align: right;">
                @if(file_exists(public_path('images/sceau-maroc.png')))
                    <img src="{{ public_path('images/sceau-maroc.png') }}" style="height: 45px;" alt="Royaume du Maroc">
                @endif
            </td>
        </tr>
    </table>

    <!-- Ref Bar -->
    <table class="ref-bar">
        <tr>
            <td><strong>N° {{ $num_decision ?: '01/2026' }}/M06</strong></td>
            <td style="text-align: right;"><strong>Kénitra le : {{ $date_approbation }}</strong></td>
        </tr>
    </table>

    <!-- Destinataire -->
    <div class="destinataire-block">
        <div style="font-weight: bold;">Le Directeur Régional du Conseil Agricole Rabat-Salé-Kénitra</div>
        <div style="font-weight: bold; margin: 4px 0;">A</div>
        <div style="font-weight: bold;">Monsieur le gérant de la société</div>
        <div style="font-size: 11pt; font-weight: bold; margin-top: 4px;">{{ $titulaire_nom }}</div>
        <div>{{ $adresse }} {{ !empty($ville) ? '- ' . $ville : '' }}</div>
    </div>

    <!-- Subject -->
    <table class="subject-table">
        <tr>
            <td style="width: 15%; font-weight: bold;">Objet :</td>
            <td style="width: 85%; font-weight: bold;">Ordre de service de Notification de l'approbation</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Référence :</td>
            <td>
                Marché Numéro <strong>{{ $num_marche }}</strong><br>
                Appel d'offre N° <strong>{{ $num_aoo }}</strong> en date du <strong>{{ $date_ouverture }}</strong>
            </td>
        </tr>
    </table>

    <div style="font-weight: bold; margin-bottom: 12px;">Madame/Monsieur,</div>

    <div class="paragraph">
        J'ai l'honneur de vous informer que le Marché Numéro <strong>{{ $num_marche }}</strong> ayant pour objet :<br>
        <strong>{{ $marche->aoo->objet ?? $objet_marche }}</strong><br>
        @if($marche->lot && strtolower(trim($marche->lot)) !== 'lot unique' && strtolower(trim($marche->lot)) !== 'unique')
        <strong>{{ stripos($marche->lot, 'lot') !== false ? $marche->lot : 'Lot ' . $marche->lot }} : {{ $objet_marche }}</strong><br>
        @endif
        a été approuvé par l'autorité compétente.
    </div>

    <div class="paragraph">
        À cet effet, je vous invite à signer la présente notification de l'approbation, à enregistrer le marché et à présenter la caution définitive relative au marché sus-cité dans les délais réglementaires.
    </div>

    <div style="text-align: right; margin-top: 25px; font-weight: bold;">
        Le maître d'ouvrage
    </div>

    <div class="observation-box">
        <strong>Observation :</strong><br>
        Le montant de la caution définitive à présenter est de (en DH) : <strong>{{ number_format($montant_caution_definitive, 2, ',', ' ') }} DH</strong> (3% du montant du marché).
    </div>

    <!-- Accusé de réception -->
    <div class="accuse-block">
        <div class="accuse-title">Accusé de réception</div>
        
        <div class="paragraph">
            Je soussigné, Madame/Monsieur <strong>{{ $representant_nom }}</strong><br>
            Gérant/ agissant au nom et pour le compte de la société : <strong>{{ $titulaire_nom }}</strong><br>
            Faisant élection de domicile à : <strong>{{ $adresse }} {{ !empty($ville) ? '- ' . $ville : '' }}</strong>
        </div>

        <div class="paragraph">
            Atteste avoir reçu du directeur régional du conseil agricole Rabat-Salé-Kénitra la notification de l'approbation du marché: <strong>{{ $num_marche }}</strong><br>
            Ayant pour objet :<br>
            <strong>{{ $marche->aoo->objet ?? $objet_marche }}</strong><br>
            @if($marche->lot && strtolower(trim($marche->lot)) !== 'lot unique' && strtolower(trim($marche->lot)) !== 'unique')
            <strong>{{ stripos($marche->lot, 'lot') !== false ? $marche->lot : 'Lot ' . $marche->lot }} : {{ $objet_marche }}</strong><br>
            @endif
        </div>

        <table style="width: 100%; margin-top: 20px;">
            <tr>
                <td>Fait à ............................, le ............................</td>
                <td style="text-align: right; font-weight: bold;">Signature et Cachet du Titulaire</td>
            </tr>
        </table>
    </div>

</body>
</html>
