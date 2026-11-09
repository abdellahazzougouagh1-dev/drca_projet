<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $docTitle }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 102px 36px 96px 36px;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12.5px;
            color: #000;
            line-height: 1.5;
            margin: 0;
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
            padding-bottom: 10px;
        }

        .header-title {
            text-align: center;
            font-weight: bold;
            font-size: 13.5px;
            line-height: 1.4;
            margin-bottom: 12px;
        }

        .meta-grid {
            width: 100%;
            margin-bottom: 14px;
            font-size: 12.5px;
            border: 1.5px solid #000;
            border-collapse: collapse;
        }
        .meta-grid td {
            border: 1px solid #000;
            padding: 7px 9px;
            font-weight: bold;
            vertical-align: middle;
        }
        .meta-grid .meta-lbl {
            background: #f2f2f2;
        }

        .content-block {
            font-size: 12.5px;
            line-height: 1.55;
            margin-bottom: 14px;
        }

        .divider {
            border: none;
            border-top: 1.5px solid #000;
            margin: 18px 0 14px 0;
        }

        .accuse-box {
            border: 1.5px solid #000;
            padding: 14px 16px 26px 16px;
            margin-top: 12px;
            page-break-inside: avoid;
        }

        .accus-title {
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            text-decoration: underline;
            margin-bottom: 12px;
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
        Chef de SAF (Service Financier et Administratif)
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
            <td class="meta-lbl" style="width: 15%;">Objet</td>
            <td style="width: 26%;">Ordre de service de :</td>
            <td style="width: 59%; font-weight: bold;">{{ $doc['nature_os'] ?? "Notification de l'approbation" }}</td>
        </tr>
        <tr>
            <td class="meta-lbl">Référence</td>
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
                J'ai l'honneur de vous prescrire d'arrêter l'exécution du bon de commande N° : <span class="bold">{{ $doc['numero_bc'] }}</span>
            </p>
            <p style="margin: 0 0 6px 0;">ayant pour objet :</p>
            <p class="bold justify" style="margin: 0 0 10px 0;">
                {{ $doc['objet'] }}
            </p>
            <p style="margin: 0 0 10px 0;">
                L'arrêt de l'exécution de ce bon de commande prendra effet à compter de la date de réception du présent ordre de service.
            </p>
            @if(!empty($doc['motif_ajournement']))
                <p style="margin: 10px 0 2px 0;"><span class="bold">Motif de l'ajournement :</span></p>
                <p style="margin: 0 0 10px 0;">{{ $doc['motif_ajournement'] }}</p>
            @endif
        @elseif($isReprise)
            <p style="margin: 0 0 6px 0;">
                J'ai l'honneur de vous prescrire de reprendre l'exécution du bon de commande N° : <span class="bold">{{ $doc['numero_bc'] }}</span>
            </p>
            <p style="margin: 0 0 6px 0;">ayant pour objet :</p>
            <p class="bold justify" style="margin: 0 0 10px 0;">
                {{ $doc['objet'] }}
            </p>
            <p style="margin: 0 0 10px 0;">
                La reprise de l'exécution de ce bon de commande prendra effet à compter de la date de réception du présent ordre de service.
            </p>
        @elseif($isNotification)
            <p style="margin: 0 0 6px 0;">
                J'ai l'honneur de vous notifier l'approbation du bon de commande N° : <span class="bold">{{ $doc['numero_bc'] }}</span>
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
                J'ai l'honneur de vous prescrire de commencer l'exécution du bon de commande N° : <span class="bold">{{ $doc['numero_bc'] }}</span>
            </p>
            <p style="margin: 0 0 6px 0;">ayant pour objet :</p>
            <p class="bold justify" style="margin: 0 0 10px 0;">
                {{ $doc['objet'] }}
            </p>
            <p style="margin: 0 0 10px 0;">
                Le commencement de l'exécution de ce bon de commande prendra effet à compter de la date de réception du présent ordre de service.
            </p>
        @endif
    </div>

    <!-- Ligne de séparation -->
    <hr class="divider">

    <!-- Section Accusé de Réception encadrée -->
    <div class="accuse-box">
        <div class="accus-title">
            Accusé de réception
        </div>

        <div class="content-block" style="margin-bottom: 0;">
            <p style="margin: 0 0 8px 0;">Je soussigné, Monsieur</p>

            <table style="width: 100%; margin-bottom: 8px;">
                <tr>
                    <td style="width: 45%; padding: 2px 0;">Gérant / agissant au nom et pour le compte de la société :</td>
                    <td class="bold" style="padding: 2px 0;">{{ $doc['societe'] }}</td>
                </tr>
                <tr>
                    <td style="padding: 2px 0;">Faisant élection de domicile à :</td>
                    <td class="bold" style="padding: 2px 0;">{{ $doc['adresse_societe'] }}</td>
                </tr>
            </table>

            @if($isAjournement)
                <p style="margin: 0 0 8px 0;">
                    Atteste avoir reçu du Directeur Régional du Conseil Agricole de Rabat-Salé-Kénitra l'ordre d'arrêt de l'exécution
                </p>
            @elseif($isReprise)
                <p style="margin: 0 0 8px 0;">
                    Atteste avoir reçu du Directeur Régional du Conseil Agricole de Rabat-Salé-Kénitra l'ordre de reprise de l'exécution
                </p>
            @elseif($isNotification)
                <p style="margin: 0 0 8px 0;">
                    Atteste avoir reçu du Directeur Régional du Conseil Agricole de Rabat-Salé-Kénitra la notification de l'approbation de :
                </p>
            @else
                <p style="margin: 0 0 8px 0;">
                    Atteste avoir reçu du Directeur Régional du Conseil Agricole de Rabat-Salé-Kénitra l'ordre de commencement de l'exécution
                </p>
            @endif

            <table style="width: 100%; margin-bottom: 8px;">
                <tr>
                    <td style="width: 26%; font-weight: bold;">Bon de Commande :</td>
                    <td class="bold">{{ $doc['numero_bc'] }}</td>
                </tr>
            </table>

            <p style="margin: 0 0 4px 0;">Ayant pour objet :</p>
            <p class="bold justify" style="margin: 0 0 10px 0;">
                {{ $doc['objet'] }}
            </p>

            @if($isAjournement)
                <p style="margin: 6px 0 16px 0;">
                    J'accuse par la présente, la réception de l'ordre de service d'arrêt de l'exécution du Bon de commande sus-cité.
                </p>
            @elseif($isReprise)
                <p style="margin: 6px 0 16px 0;">
                    J'accuse par la présente, la réception de l'ordre de service de reprise de l'exécution du Bon de commande sus-cité.
                </p>
            @elseif($isNotification)
                <p style="margin: 6px 0 16px 0;">
                    J'accuse par la présente, la réception de la notification de l'approbation du bon de commande sus-cité.
                </p>
            @else
                <p style="margin: 6px 0 16px 0;">
                    J'accuse par la présente, la réception de l'ordre de service de commencement de l'exécution du Bon de commande sus-cité.
                </p>
            @endif

            <table style="width: 100%; margin-top: 12px;">
                <tr>
                    <td style="width: 55%;">Reçu le : ...............................</td>
                    <td style="width: 45%; text-align: right; font-weight: bold;">Signature et Cachet</td>
                </tr>
            </table>
        </div>
    </div>
</div>
</body>
</html>
