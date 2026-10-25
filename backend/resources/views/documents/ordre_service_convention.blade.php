<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ordre de service - {{ $doc['numero_consultation'] ?? 'Convention' }}</title>
    <style>
        @page {
            margin: 10mm 15mm 15mm 15mm;
            size: A4 portrait;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000;
            line-height: 1.35;
            margin: 0;
            padding: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        td, th {
            vertical-align: middle;
        }
        .bold {
            font-weight: bold;
        }
        .uppercase {
            text-transform: uppercase;
        }
        .header-table {
            margin-bottom: 2px;
            width: 100%;
        }
        .header-center {
            text-align: center;
            font-size: 12.5px;
            font-weight: bold;
            line-height: 1.3;
        }
        .header-line {
            border-bottom: 1.5px solid #000080;
            margin-bottom: 8px;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 8px;
        }
        .sender-title {
            font-size: 12.5px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 2px;
        }
        .sender-city {
            font-size: 12px;
            font-weight: bold;
            text-align: right;
            padding-right: 40px;
            margin-bottom: 6px;
        }
        .recipient-box {
            text-align: center;
            line-height: 1.35;
            margin-bottom: 8px;
        }
        .grid-bordered {
            width: 100%;
            border: 1px solid #000;
            margin-bottom: 8px;
        }
        .grid-bordered td {
            border: 1px solid #000;
            padding: 3px 6px;
            font-size: 11px;
        }
        .divider {
            border-top: 1.5px solid #000;
            margin: 10px 0 8px 0;
        }
        .accus-title {
            text-align: center;
            font-weight: bold;
            text-decoration: underline;
            font-size: 13px;
            margin-bottom: 6px;
        }
        .accus-table td {
            padding: 2px 0;
            font-size: 11px;
            vertical-align: top;
        }
    </style>
</head>
<body>
@php
    $doc = $doc ?? ($documentData ?? ($data ?? []));
    $documentData = $documentData ?? ($data ?? ($doc ?? []));

    $logoOncaPath = public_path('images/logo-onca.png');
    $sceauMarocPath = public_path('images/sceau-maroc.png');
    $logoOnca = file_exists($logoOncaPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoOncaPath)) : '';
    $sceauMaroc = file_exists($sceauMarocPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($sceauMarocPath)) : '';

    $rawNumeroConsultation = data_get($documentData, 'numero_convention', data_get($documentData, 'numero_consultation', $consultation->numero_consultation ?? ''));
    $numeroConvention = trim(preg_replace('/\bCONV\s*[-_]?\s*/i', '', (string)$rawNumeroConsultation));
    $numeroConvention = trim(preg_replace('/^[-_\s]+|[-_\s]+$/', '', $numeroConvention));

    $numeroOs = data_get($documentData, 'numero_lettre') 
        ?: (data_get($documentData, 'numero_os') 
        ?: ($doc['numero_lettre'] ?? ('01/' . ($consultation->annee ?? date('Y')) . '/DRC-RSK/OS')));

    $dateDoc = data_get($documentData, 'date_document', $doc['date_document'] ?? date('d/m/Y'));
    if ($dateDoc && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateDoc)) {
        $dateDoc = \Carbon\Carbon::parse($dateDoc)->format('d/m/Y');
    }

    $natureOs = data_get($documentData, 'nature_os', data_get($documentData, 'nature_ordre_service', $doc['nature_os'] ?? "Notification de l'approbation"));
    $motifAjournement = data_get($documentData, 'motif_ajournement', $doc['motif_ajournement'] ?? 'Planification et disponibilité des agriculteurs');

    $isNotification = str_contains(strtolower($natureOs), 'notification') || str_contains(strtolower($natureOs), 'approbation');
    $isCommencement = str_contains(strtolower($natureOs), 'commencement');
    $isAjournement = str_contains(strtolower($natureOs), 'ajournement') || str_contains(strtolower($natureOs), 'arrêt') || str_contains(strtolower($natureOs), 'arret');
    $isReprise = str_contains(strtolower($natureOs), 'reprise');

    // Société attributaire (priorité à celle retenue dans pv_ouverture ou documentData)
    $societe = data_get($documentData, 'societe', $doc['societe'] ?? (data_get($doc, 'attributaire', 'GENERATION MULISERVICES')));
    $destinataireNom = data_get($documentData, 'destinataire_nom', 'Monsieur le gérant de la société');
    $adresseSociete = data_get($documentData, 'adresse_societe', $doc['adresse_societe'] ?? ($doc['adresse'] ?? 'AV MOUKAOUAMA DALIA B2/2APPT NO 7 MARRAKECH.'));
    $villeSociete = data_get($documentData, 'ville_societe', $doc['ville_societe'] ?? 'Marrakech');
    $objet = data_get($documentData, 'objet', $doc['objet'] ?? ($consultation->objet_consultation ?? ''));

    $typeBudget = data_get($documentData, 'type_budget') ?: ($consultation->type_budget ?? 'Investissement');
    $isContrat = strtolower((string)$typeBudget) === 'fonctionnement' || ($consultation->mode_engagement ?? '') === 'Contrat';
    $termeDoc = $isContrat ? 'Contrat' : 'Convention';
    $termeDocLower = $isContrat ? 'contrat' : 'convention';
    $termeDocCe = $isContrat ? 'ce contrat' : 'cette convention';
    $termeDocLedit = $isContrat ? 'ledit contrat' : 'ladite convention';
    $termeDocSus = $isContrat ? 'du Contrat sus-cité' : 'de la Convention sus-citée';
@endphp

    <!-- 1. En-tête officiel -->
    <table class="header-table">
        <tr>
            <td style="width: 25%; text-align: left; vertical-align: middle;">
                @if($logoOnca)
                    <img src="{{ $logoOnca }}" alt="Logo ONCA" style="height: 50px; width: auto;">
                @endif
            </td>
            <td class="header-center" style="width: 50%; vertical-align: middle;">
                Direction Régionale du Conseil Agricole RABAT-SALE-KENITRA
            </td>
            <td style="width: 25%; text-align: right; vertical-align: middle;">
                @if($sceauMaroc)
                    <img src="{{ $sceauMaroc }}" alt="Sceau Maroc" style="height: 50px; width: auto;">
                @endif
            </td>
        </tr>
    </table>
    <div class="header-line"></div>

    <!-- 2. N° OS et Date -->
    <table class="meta-table">
        <tr>
            <td style="width: 55%; font-weight: bold; font-size: 12.5px;">
                N° &nbsp;&nbsp; {{ $numeroOs }}
            </td>
            <td style="width: 45%; text-align: right; font-size: 12px;">
                Kénitra le: &nbsp;&nbsp; <span class="bold">{{ $dateDoc }}</span>
            </td>
        </tr>
    </table>

    <!-- 3. Expéditeur -->
    <div class="sender-title">
        Le Directeur Régional du Conseil Agricole RABAT-SALE
    </div>
    <div class="sender-city">
        Kénitra
    </div>

    <!-- 4. Destinataire -->
    <div class="recipient-box">
        <div class="bold" style="font-size: 13px; margin-bottom: 2px;">A</div>
        <div class="bold" style="font-size: 12px; margin-bottom: 1px;">{{ $destinataireNom }}</div>
        <div class="bold uppercase" style="font-size: 12.5px; margin-bottom: 1px;">{{ $societe }}</div>
        <div class="bold" style="font-size: 11.5px; margin-bottom: 1px;">{{ $adresseSociete }}</div>
        @if($villeSociete)
            <div style="text-align: right; font-weight: bold; font-size: 12px; padding-right: 40px;" class="uppercase">
                {{ $villeSociete }}
            </div>
        @endif
    </div>

    <!-- 5. Tableau Objet & Référence -->
    <table class="grid-bordered">
        <tr>
            <td style="width: 12%; font-weight: bold;">Objet</td>
            <td style="width: 25%; font-weight: bold;">Ordre de service de :</td>
            <td style="width: 63%; font-weight: bold;">{{ $natureOs }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Référence</td>
            <td style="font-weight: bold;">{{ $termeDoc }} numéro:</td>
            <td style="font-weight: bold;">{{ $numeroConvention }}</td>
        </tr>
    </table>

    <!-- 6. Corps de l'Ordre de Service -->
    <div style="margin-bottom: 6px;">
        <div style="margin-bottom: 5px;">Monsieur,</div>

        @if($isNotification)
            <div style="margin-bottom: 4px;">
                J'ai l'honneur de demander de notifier l'approbation {{ $isContrat ? 'du Contrat' : 'de la Convention' }} numéro : <span class="bold">{{ $numeroConvention }}</span> ayant pour objet :
            </div>
            <div class="bold" style="margin: 4px 0 6px 0; line-height: 1.3;">
                {{ $objet }}
            </div>
            <div style="line-height: 1.35; margin-bottom: 4px;">
                Je vous informe que {{ $termeDocLedit }} a été approuvé{{ $isContrat ? '' : 'e' }} par l'autorité compétente et je vous invite à signer la présente notification de l'approbation et à enregistrer {{ $termeDocCe }} dans les délais réglementaires
            </div>
        @elseif($isCommencement)
            <div style="margin-bottom: 4px;">
                J'ai l'honneur de demander de commencer l'exécution {{ $isContrat ? 'du Contrat' : 'de la Convention' }} N° : <span class="bold">{{ $numeroConvention }}</span> ayant pour objet
            </div>
            <div class="bold" style="margin: 4px 0 6px 0; line-height: 1.3;">
                {{ $objet }}
            </div>
            <div style="line-height: 1.35; margin-bottom: 4px;">
                Le commencement de l'exécution de {{ $termeDocCe }} prendra effet à compter de la date de la réception du présent ordre de service
            </div>
        @elseif($isAjournement)
            <div style="margin-bottom: 4px;">
                J'ai l'honneur de demander d'arreter l'execution {{ $isContrat ? 'du Contrat' : 'de la Convention' }} N° : <span class="bold">{{ $numeroConvention }}</span> ayant pour objet
            </div>
            <div class="bold" style="margin: 4px 0 6px 0; line-height: 1.3;">
                {{ $objet }}
            </div>
            <div style="line-height: 1.35; margin-bottom: 5px;">
                L'arrêt de l'exécution de {{ $termeDocCe }} prendra effet à compter de la date de la réception du présent ordre de service
            </div>
            <div class="bold" style="margin-top: 4px;">Motif de l'ajournement</div>
            <div class="bold" style="margin-top: 2px;">{{ $motifAjournement }}</div>
        @elseif($isReprise)
            <div style="margin-bottom: 4px;">
                J'ai l'honneur de demander de reprendre l'exécution {{ $isContrat ? 'du Contrat' : 'de la Convention' }} N° : <span class="bold">{{ $numeroConvention }}</span> ayant pour objet
            </div>
            <div class="bold" style="margin: 4px 0 6px 0; line-height: 1.3;">
                {{ $objet }}
            </div>
            <div style="line-height: 1.35; margin-bottom: 4px;">
                La reprise de l'exécution de {{ $termeDocCe }} prendra effet à compter de la date de la réception du présent ordre de service
            </div>
        @else
            <div style="margin-bottom: 4px;">
                J'ai l'honneur de demander l'exécution {{ $isContrat ? 'du Contrat' : 'de la Convention' }} N° : <span class="bold">{{ $numeroConvention }}</span> ayant pour objet
            </div>
            <div class="bold" style="margin: 4px 0 6px 0;">
                {{ $objet }}
            </div>
        @endif
    </div>

    <!-- 7. Ligne de séparation au milieu de page -->
    <div class="divider"></div>

    <!-- 8. Section Accusé de réception -->
    <div class="accus-title">
        Accusé de reception
    </div>

    <div style="margin-bottom: 4px;">Je soussigne, Monsieur</div>

    <table class="accus-table" style="width: 100%; margin-bottom: 4px;">
        <tr>
            <td style="width: 48%;">Gérant/ agissant au nom et pour le compte de la société :</td>
            <td class="bold uppercase" style="width: 52%;">{{ $societe }}</td>
        </tr>
        <tr>
            <td>Faisant élection de domicile à :</td>
            <td class="bold uppercase">{{ $adresseSociete }}</td>
        </tr>
    </table>

    <div style="margin-bottom: 3px;">
        @if($isNotification)
            Atteste avoir reçu du directeur régional du conseil agricole Rabat-Salé-Kénitra la notification de l'approbation de:
        @elseif($isCommencement)
            Atteste avoir reçu du directeur régional du conseil agricole Rabat-Salé-Kénitra l'ordre de commencement de l'exécution
        @elseif($isAjournement)
            Atteste avoir reçu du directeur régional du conseil agricole Rabat-Salé-Kénitra l'ordre d'arrêt de l'execution
        @elseif($isReprise)
            Atteste avoir reçu du directeur régional du conseil agricole Rabat-Salé-Kénitra l'ordre de reprise de l'exécution
        @else
            Atteste avoir reçu du directeur régional du conseil agricole Rabat-Salé-Kénitra l'ordre de service concernant :
        @endif
    </div>

    <table style="width: 100%; margin-bottom: 3px;">
        <tr>
            <td style="width: 24%; font-weight: bold;">{{ $termeDoc }} numéro:</td>
            <td class="bold" style="width: 76%;">{{ $numeroConvention }}</td>
        </tr>
    </table>

    <div style="margin-bottom: 2px;">Ayant pour objet :</div>
    <div class="bold" style="margin-bottom: 6px; line-height: 1.3;">
        {{ $objet }}
    </div>

    <div style="line-height: 1.35; margin-bottom: 8px;">
        @if($isNotification)
            J'accuse par la présente, la réception de la notification de l'approbation {{ $termeDocSus }}
        @elseif($isCommencement)
            J'accuse par la présente, la réception de l'ordre de service de commencement {{ $termeDocSus }}
        @elseif($isAjournement)
            J'accuse par la présente, la réception de l'ordre de service d'arrêt de l'execution {{ $termeDocSus }}
        @elseif($isReprise)
            J'accuse par la présente, la réception de l'ordre de service de reprise de l'exécution {{ $termeDocSus }}
        @else
            J'accuse par la présente, la réception de l'ordre de service {{ $termeDocSus }}
        @endif
    </div>

    <!-- 9. Pied de page officiel ONCA -->
    @include('documents.partials.bc_footer')

</body>
</html>
