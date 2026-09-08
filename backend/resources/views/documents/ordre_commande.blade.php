<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $docTitle }}</title>
    <style>
        @page { margin: 115px 40px 70px 40px; }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 14px;
            color: #000;
            line-height: 1.5;
            margin: 0;
        }
        .header-logo { height: 34px; width: auto; }
        .header-line {
            border-bottom: 1.5px solid #000080;
            margin-bottom: 14px;
        }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; }
        .page { position: relative; }

        .bold { font-weight: bold; }
        .center { text-align: center; }
        .right { text-align: right; }
        .justify { text-align: justify; }

        .ref-table td {
            font-size: 12.5px;
            font-weight: bold;
            padding-bottom: 12px;
        }

        .header-title {
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            line-height: 1.4;
            margin-bottom: 16px;
        }

        .recipient-box {
            width: 100%;
            margin-bottom: 16px;
        }
        .recipient-content {
            width: 60%;
            float: right;
            font-size: 12.5px;
            line-height: 1.45;
        }

        .meta-grid {
            width: 100%;
            margin-bottom: 16px;
            font-size: 12.5px;
        }
        .meta-grid td {
            padding: 4px 0;
            font-weight: bold;
        }

        .content-block {
            font-size: 12.5px;
            line-height: 1.55;
            margin-bottom: 16px;
        }

        .divider {
            border: none;
            border-top: 1px solid #000;
            margin: 24px 0 18px 0;
        }

        .accus-title {
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 14px;
            text-transform: uppercase;
        }
    </style>
    @include('documents.partials.bc_typography')
</head>
<body>
    @include('documents.partials.bc_header')
    @include('documents.partials.bc_footer')
<div class="page">

    <!-- N° et Date -->
    <table class="ref-table" style="margin-top: 6px;">
        <tr>
            <td style="width: 60%;">N° {{ $doc['numero_lettre'] }}</td>
            <td style="text-align: right; width: 40%;">Kénitra, le : <span style="margin-left: 25px;">{{ $doc['date_document'] }}</span></td>
        </tr>
    </table>

    <!-- Titre Signataire -->
    <div class="header-title">
        Le Directeur Régional du Conseil Agricole de Rabat-Salé-Kénitra
    </div>

    <!-- Destinataire -->
    <table style="width: 100%; margin-bottom: 12px;">
        <tr>
            <td style="width: 40%;"></td>
            <td style="width: 60%;">
                <div style="text-align: right; font-weight: bold; margin-bottom: 4px; padding-right: 30px;">Kénitra</div>
                <div style="font-weight: bold; text-align: center; margin-bottom: 2px;">À</div>
                <div style="font-weight: bold; text-align: center;">Monsieur le Gérant de la société</div>
                <div style="font-weight: bold; text-align: center;">{{ $doc['societe'] }}</div>
                <div style="font-weight: bold; text-align: center;">{{ $doc['adresse_societe'] }}</div>
                <div style="text-align: right; font-weight: bold; margin-top: 4px; padding-right: 20px;">{{ $doc['ville_societe'] }}</div>
            </td>
        </tr>
    </table>

    <!-- Objet & Référence -->
    <table class="meta-grid">
        <tr>
            <td style="width: 12%;">Objet</td>
            <td style="width: 3%;">:</td>
            <td style="width: 25%;">Ordre de service de :</td>
            <td style="width: 60%; font-weight: bold;">{{ $doc['nature_os'] ?? "Notification de l'approbation" }}</td>
        </tr>
        <tr>
            <td>Référence</td>
            <td>:</td>
            <td>Bon de Commande N° :</td>
            <td style="font-weight: bold;">{{ $doc['numero_bc'] }}</td>
        </tr>
    </table>

    @php
        $nature = $doc['nature_os'] ?? "Commencement de l'exécution";
        $isAjournement = str_contains(strtolower($nature), 'ajournement') || str_contains(strtolower($nature), 'arrêt') || str_contains(strtolower($nature), 'arret');
        $isReprise = str_contains(strtolower($nature), 'reprise');
        $isNotification = str_contains(strtolower($nature), 'notification');
    @endphp

    <!-- Corps de la notification -->
    <div class="content-block">
        <p style="margin: 0 0 6px 0;">Monsieur,</p>

        @if($isAjournement)
            <p style="margin: 0 0 6px 0;">
                J'ai l'honneur de demander d'arrêter l'exécution du bon de commande N° : <span class="bold">{{ $doc['numero_bc'] }}</span>
            </p>
            <p style="margin: 0 0 6px 0;">ayant pour objet :</p>
            <p class="bold justify" style="margin: 0 0 10px 0;">
                {{ $doc['objet'] }}
            </p>
            <p style="margin: 0 0 10px 0;">
                L'arrêt de l'exécution de ce bon de commande prendra effet à compter de la date de la réception du présent ordre de service.
            </p>
            @if(!empty($doc['motif_ajournement']))
                <p style="margin: 10px 0 2px 0;"><span class="bold">Motif de l'ajournement :</span></p>
                <p style="margin: 0 0 10px 0;">{{ $doc['motif_ajournement'] }}</p>
            @endif
        @elseif($isReprise)
            <p style="margin: 0 0 6px 0;">
                J'ai l'honneur de demander de reprendre l'exécution du bon de commande N° : <span class="bold">{{ $doc['numero_bc'] }}</span>
            </p>
            <p style="margin: 0 0 6px 0;">ayant pour objet :</p>
            <p class="bold justify" style="margin: 0 0 10px 0;">
                {{ $doc['objet'] }}
            </p>
            <p style="margin: 0 0 10px 0;">
                La reprise de l'exécution de ce bon de commande prendra effet à compter de la date de la réception du présent ordre de service.
            </p>
        @elseif($isNotification)
            <p style="margin: 0 0 6px 0;">
                J'ai l'honneur de demander de notifier l'approbation du bon de commande N° : <span class="bold">{{ $doc['numero_bc'] }}</span>
            </p>
            <p style="margin: 0 0 6px 0;">ayant pour objet :</p>
            <p class="bold justify" style="margin: 0 0 10px 0;">
                {{ $doc['objet'] }}
            </p>
            <p style="margin: 0 0 10px 0;">
                Je vous informe que ledit bon de commande a été approuvé par l'autorité compétente et je vous invite à signer la présente notification de l'approbation dans les délais réglementaires.
            </p>
        @else
            <p style="margin: 0 0 6px 0;">
                J'ai l'honneur de  vous demander de bien vouloir  proceder au Commencement de l'exécution du bon de commande N° : <span class="bold">{{ $doc['numero_bc'] }}</span>
            </p>
            <p style="margin: 0 0 6px 0;">ayant pour objet :</p>
            <p class="bold justify" style="margin: 0 0 10px 0;">
                {{ $doc['objet'] }}
            </p>
            <p style="margin: 0 0 10px 0;">
                Le commencement de l'exécution de ce bon de commande prendra effet à compter de la date de la réception du présent ordre de service.
            </p>
        @endif
    </div>

    <!-- Ligne de séparation -->
    <hr class="divider">

    <!-- Section Accusé de Réception -->
    <div class="accus-title">
        Accusé de réception
    </div>

    <div class="content-block">
        <p style="margin: 0 0 6px 0;">Je soussigné, Monsieur</p>

        <table style="width: 100%; margin-bottom: 6px;">
            <tr>
                <td style="width: 43%;">Gérant / agissant au nom et pour le compte de la société :</td>
                <td class="bold">{{ $doc['societe'] }}</td>
            </tr>
            <tr>
                <td>Faisant élection de domicile à :</td>
                <td class="bold">{{ $doc['adresse_societe'] }}</td>
            </tr>
        </table>

        @if($isAjournement)
            <p style="margin: 0 0 6px 0;">
                Atteste avoir reçu du directeur régional du conseil agricole Rabat-Salé-Kénitra l'ordre d'arrêt de l'exécution
            </p>
        @elseif($isReprise)
            <p style="margin: 0 0 6px 0;">
                Atteste avoir reçu du directeur régional du conseil agricole Rabat-Salé-Kénitra l'ordre de reprise de l'exécution
            </p>
        @elseif($isNotification)
            <p style="margin: 0 0 6px 0;">
                Atteste avoir reçu du directeur régional du conseil agricole Rabat-Salé-Kénitra la notification de l'approbation de :
            </p>
        @else
            <p style="margin: 0 0 6px 0;">
                Atteste avoir reçu du directeur régional du conseil agricole Rabat-Salé-Kénitra l'ordre de commencement de l'exécution
            </p>
        @endif

        <table style="width: 100%; margin-bottom: 6px;">
            <tr>
                <td style="width: 25%; font-weight: bold;">Bon de Commande :</td>
                <td class="bold">{{ $doc['numero_bc'] }}</td>
            </tr>
        </table>

        <p style="margin: 0 0 4px 0;">Ayant pour objet :</p>
        <p class="bold justify" style="margin: 0 0 10px 0;">
            {{ $doc['objet'] }}
        </p>

        @if($isAjournement)
            <p style="margin: 6px 0 0 0;">
                J'accuse par la présente, la réception de l'ordre de service d'arrêt de l'exécution du Bon de commande sus-cité.
            </p>
        @elseif($isReprise)
            <p style="margin: 6px 0 0 0;">
                J'accuse par la présente, la réception de l'ordre de service de reprise de l'exécution du Bon de commande sus-cité.
            </p>
        @elseif($isNotification)
            <p style="margin: 6px 0 0 0;">
                J'accuse par la présente, la réception de la notification de l'approbation du bon de commande sus-cité.
            </p>
        @else
            <p style="margin: 6px 0 0 0;">
                J'accuse par la présente, la réception de l'ordre de service de commencement de l'exécution du Bon de commande sus-cité.
            </p>
        @endif
    </div>
</div>
</body>
</html>
