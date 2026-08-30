<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Notification de l'approbation</title>
    <style>
        @page {
            margin: 8mm 12mm 12mm 12mm;
        }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; line-height: 1.25; margin: 0; padding: 0; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .underline { text-decoration: underline; }
        .mb-2 { margin-bottom: 5px; }
        .mb-4 { margin-bottom: 8px; }
        .mt-4 { margin-top: 10px; }
        .title { font-size: 13px; font-weight: bold; text-align: center; margin-bottom: 8px; }
        .section { margin-bottom: 8px; }
        .table-borderless { width: 100%; border-collapse: collapse; }
        .table-borderless td { border: none; padding: 1px 3px; vertical-align: top; }
        .header-table { width: 100%; margin-bottom: 10px; }
        .header-table td { vertical-align: middle; border: none; }
        
        /* Fixed Footer */
        .footer {
            position: fixed;
            bottom: -8mm;
            left: 0;
            right: 0;
            width: 100%;
            font-size: 7px;
            text-align: center;
        }
        .footer-line { border-bottom: 1.5px solid #4a6344; margin: 3px auto; width: 80%; }
    </style>
</head>
<body>

    <!-- FOOTER (must be declared before content in dompdf) -->
    <div class="footer">
        <div style="text-align: center; color: #555; line-height: 1.3;">
            المديرية الجهوية للاستشارة الفلاحية لجهة الرباط سلا القنيطرة، ملتقى زنقة سبتة، شارع محمد الخامس (قرب بنك المغرب) - القنيطرة<br>
            الهاتف : 212537325599+ / الفاكس : 212537361320+ - الموقع الإلكتروني : www.onca.gov.ma<br>
            <div class="footer-line"></div>
            Direction Régionale du Conseil Agricole de la Région de Rabat Salé Kénitra, Angle Rue Sebta - Bd Mohamed V (à côté de Bank Al-Maghreb) - Kénitra<br>
            Tél. : +212 (0) 537 32 55 99 - Fax : +212 (0) 537 36 13 20 - Site web : www.onca.gov.ma
        </div>
    </div>

    <!-- HEADER -->
    <table class="header-table">
        <tr>
            <td style="width: 33%; text-align: left;">
                @if(file_exists(public_path('images/logo-onca.png')))
                    <img src="{{ public_path('images/logo-onca.png') }}" style="height: 65px;" alt="ONCA">
                @endif
            </td>
            <td style="width: 34%; text-align: center; font-size: 11px; font-weight: bold;">
                Direction Régionale du Conseil Agricole<br>Rabat-Salé-Kénitra
            </td>
            <td style="width: 33%; text-align: right;">
                @if(file_exists(public_path('images/sceau-maroc.png')))
                    <img src="{{ public_path('images/sceau-maroc.png') }}" style="height: 65px;" alt="Royaume du Maroc">
                @endif
            </td>
        </tr>
    </table>
    
    <hr style="border: 1px solid #000; margin-top: 0px; margin-bottom: 10px;">

    <table class="table-borderless" style="margin-bottom: 10px;">
        <tr>
            <td style="width: 50%; font-weight: bold;">N° {{ $marche->num_decision ?? '........................' }}</td>
            <td style="width: 50%; text-align: right; font-weight: bold;">Kénitra le: {{ $marche->date_notification_marche ? \Carbon\Carbon::parse($marche->date_notification_marche)->format('d/m/Y') : ($marche->date_approbation ? \Carbon\Carbon::parse($marche->date_approbation)->format('d/m/Y') : date('d/m/Y')) }}</td>
        </tr>
    </table>

    <div class="title" style="font-size: 15px;">Le Directeur Régional du Conseil Agricole Rabat-Salé-Kénitra</div>

    <div class="text-center font-bold mb-4" style="font-size: 14px;">
        A<br>
        Monsieur le gérant de la société<br>
        {{ strtoupper($marche->fournisseur->raison_sociale ?? '........................................') }}<br>
        {{ strtoupper($marche->fournisseur->adresse ?? '........................................') }} {{ strtoupper($marche->fournisseur->ville ?? '') }}
    </div>

    <table class="table-borderless mb-4">
        <tr>
            <td style="width: 15%;" class="font-bold">Objet :</td>
            <td style="width: 85%;" class="font-bold">Notification de l'approbation</td>
        </tr>
        <tr>
            <td class="font-bold">Référence :</td>
            <td class="font-bold">
                Marché Numéro {{ $marche->num_marche }}<br>
                Appel d'offre N° {{ $marche->aoo->num_aoo ?? '........................' }} en date du {{ $marche->aoo->date_ouverture ? \Carbon\Carbon::parse($marche->aoo->date_ouverture)->format('d/m/Y') : '........................' }}
            </td>
        </tr>
    </table>

    <div class="section mb-2">
        Madame/Monsieur,<br><br>
        J'ai l'honneur de vous informer que le Marché Numéro <strong>{{ $marche->num_marche }}</strong> ayant pour objet :<br>
        <strong>{{ $marche->aoo->objet ?? $marche->objet_marche }}</strong><br>
        @if($marche->lot && strtolower(trim($marche->lot)) !== 'lot unique' && strtolower(trim($marche->lot)) !== 'unique')
        <strong>{{ stripos($marche->lot, 'lot') !== false ? $marche->lot : 'Lot ' . $marche->lot }} : {{ $marche->objet_marche }}</strong><br>
        @endif
        a été approuvé(e) par l'autorité competente le {{ $marche->date_approbation ? \Carbon\Carbon::parse($marche->date_approbation)->format('d/m/Y') : '........................' }}.<br>
        A cet effet, je vous invite à signer la présente notification de l'approbation, à enregistrer le marché et à présenter la caution définitive relative au marché sus-cité dans les délais réglementaires.
    </div>

    <div class="section text-center mb-4" style="margin-left: 50%;">
        Le maître d'ouvrage
    </div>

    <div class="section mb-2">
        <strong><u>Observation</u></strong><br>
        Le montant de la caution definitive à presenter est de (en DH)<br>
        <strong>{{ number_format($marche->montant_caution_definitive ?? (($marche->montant ?? 0) * 0.03), 2, ',', ' ') }}</strong>
    </div>

    <hr style="border: 1px solid #000; margin: 15px 0;">

    <div class="title" style="font-size: 15px;">Accusé de reception</div>

    <div class="section mb-2">
        Je soussigné, Madame/Monsieur <strong>{{ strtoupper($marche->fournisseur->representant ?? '........................................') }}</strong><br>
        Gérant/ agissant au nom et pour le compte de la société : <strong>{{ strtoupper($marche->fournisseur->raison_sociale ?? '........................................') }}</strong><br>
        Faisant élection de domicile à : <strong>{{ strtoupper($marche->fournisseur->adresse ?? '........................................') }} {{ strtoupper($marche->fournisseur->ville ?? '') }}</strong><br>
        Atteste avoir réçu du directeur régional du conseil agricole Rabat-Salé-Kénitra la notification de l'approbation du marché: <strong>{{ $marche->num_marche }}</strong><br>
        Ayant pour objet :<br>
        <strong>{{ $marche->aoo->objet ?? $marche->objet_marche }}</strong><br>
        @if($marche->lot && strtolower(trim($marche->lot)) !== 'lot unique' && strtolower(trim($marche->lot)) !== 'unique')
        <strong>{{ stripos($marche->lot, 'lot') !== false ? $marche->lot : 'Lot ' . $marche->lot }} : {{ $marche->objet_marche }}</strong>
        @endif
    </div>

    <table class="table-borderless mt-2">
        <tr>
            <td style="width: 50%;"></td>
            <td style="width: 50%; text-align: center;">
                Fait à {{ strtoupper($marche->fournisseur->ville ?? '........................................') }} le {{ $marche->date_notification_marche ? \Carbon\Carbon::parse($marche->date_notification_marche)->format('d/m/Y') : '........................................' }}<br><br><br>
                <strong>Signature et cachet</strong>
            </td>
        </tr>
    </table>

</body>
</html>
