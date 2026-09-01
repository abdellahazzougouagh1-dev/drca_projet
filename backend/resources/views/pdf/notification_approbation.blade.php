<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Notification de l'approbation</title>
    <style>
        @page {
            margin: 6mm 10mm 15mm 10mm;
        }
        body { 
            font-family: 'DejaVu Sans', sans-serif; 
            font-size: 11.5px; 
            line-height: 1.3; 
            margin: 0; 
            padding: 0; 
            color: #000;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .underline { text-decoration: underline; }
        
        .header-table { width: 100%; margin-bottom: 4px; }
        .header-table td { vertical-align: middle; border: none; padding: 0; }
        .center-header { text-align: center; font-size: 11px; }

        .table-borderless { width: 100%; border-collapse: collapse; }
        .table-borderless td { border: none; padding: 1px 2px; vertical-align: top; }
        
        /* Fixed Footer */
        .footer {
            position: fixed;
            bottom: -10mm;
            left: 0;
            right: 0;
            width: 100%;
            text-align: center;
        }
        .footer-banner {
            width: 100%;
            height: auto;
            max-height: 45px;
        }
    </style>
</head>
<body>

    <!-- FOOTER -->
    <div class="footer">
        @if(file_exists(public_path('images/info_DRCA.png')))
            <img src="{{ public_path('images/info_DRCA.png') }}" class="footer-banner" alt="ONCA DRCA">
        @else
            <div style="text-align: center; color: #555; font-size: 7.5px; line-height: 1.25;">
                المديرية الجهوية للاستشارة الفلاحية لجهة الرباط سلا القنيطرة، ملتقى زنقة سبتة، شارع محمد الخامس (قرب بنك المغرب) - القنيطرة<br>
                الهاتف : 212537325599+ / الفاكس : 212537361320+ - الموقع الإلكتروني : www.onca.gov.ma<br>
                Direction Régionale du Conseil Agricole de la Région de Rabat Salé Kénitra, Angle Rue Sebta - Bd Mohamed V (à côté de Bank Al-Maghreb) - Kénitra<br>
                Tél. : +212 (0) 537 32 55 99 - Fax : +212 (0) 537 36 13 20 - Site web : www.onca.gov.ma
            </div>
        @endif
    </div>

    <!-- HEADER LOGOS -->
    <table class="header-table">
        <tr>
            <td style="width: 30%; text-align: left;">
                @if(file_exists(public_path('images/logo-onca.png')))
                    <img src="{{ public_path('images/logo-onca.png') }}" style="height: 55px;" alt="ONCA">
                @endif
            </td>
            <td class="center-header" style="width: 40%;">
                Direction Régionale du Conseil Agricole Rabat-Salé-Kénitra
            </td>
            <td style="width: 30%; text-align: right;">
                @if(file_exists(public_path('images/sceau-maroc.png')))
                    <img src="{{ public_path('images/sceau-maroc.png') }}" style="height: 55px;" alt="Royaume du Maroc">
                @endif
            </td>
        </tr>
    </table>
    
    <hr style="border: 1.5px solid #000; margin-top: 0px; margin-bottom: 8px;">

    <!-- N° & DATE -->
    <table class="table-borderless" style="margin-bottom: 8px;">
        <tr>
            <td style="width: 50%; font-size: 13px; font-weight: bold;">N° &nbsp;&nbsp;{{ $marche->num_decision ?? $marche->os_numero ?? '01/2024/M06' }}</td>
            <td style="width: 50%; text-align: right; font-size: 13px;">
                Kénitra le: &nbsp;{{ $marche->date_notification_marche ? \Carbon\Carbon::parse($marche->date_notification_marche)->format('d/m/Y') : ($marche->os_date_signature ? \Carbon\Carbon::parse($marche->os_date_signature)->format('d/m/Y') : date('d/m/Y')) }}<br>
                <span style="font-weight: bold; margin-top: 3px; display: inline-block;">Kénitra</span>
            </td>
        </tr>
    </table>

    <!-- TITRE & DESTINATAIRE -->
    <div style="font-size: 13px; font-weight: bold; text-align: center; margin-bottom: 10px;">
        Le Directeur Régional du Conseil Agricole Rabat-Salé-Kénitra
    </div>

    <div style="text-align: center; font-weight: bold; font-size: 12.5px; line-height: 1.35; margin-bottom: 4px;">
        A<br>
        Monsieur le gérant de la société<br>
        <span style="font-size: 13px;">{{ strtoupper($marche->fournisseur->raison_sociale ?? $marche->titulaire) }}</span><br>
        <span style="font-size: 11.5px;">{{ strtoupper($marche->fournisseur->adresse ?? '') }}</span>
    </div>
    <div style="text-align: right; font-weight: bold; font-size: 12.5px; padding-right: 40px; margin-bottom: 10px;">
        {{ strtoupper($marche->fournisseur->ville ?? 'CASABLANCA') }}
    </div>

    <!-- OBJET & REFERENCE -->
    <table class="table-borderless" style="font-size: 12px; margin-bottom: 8px;">
        <tr>
            <td style="width: 14%; font-weight: bold;">Objet</td>
            <td style="width: 3%; font-weight: bold;">:</td>
            <td style="width: 28%; font-weight: bold;">Ordre de service de</td>
            <td style="width: 55%; font-weight: bold;">Notification de l'approbation</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Référence</td>
            <td style="font-weight: bold;">:</td>
            <td style="font-weight: bold;">Marché Numéro</td>
            <td style="font-weight: bold;">{{ $marche->num_marche }}</td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td style="font-weight: bold;">Appel d'offre N°</td>
            <td style="font-weight: bold;">
                {{ $marche->aoo->num_aoo ?? '........................' }} &nbsp;&nbsp;&nbsp;&nbsp;en date du &nbsp;&nbsp;{{ $marche->date_oa ? \Carbon\Carbon::parse($marche->date_oa)->format('d/m/Y') : ($marche->aoo->date_ouverture ? \Carbon\Carbon::parse($marche->aoo->date_ouverture)->format('d/m/Y') : '........................') }}
            </td>
        </tr>
    </table>

    <!-- CORPS -->
    <div style="font-size: 11.5px; line-height: 1.4; margin-bottom: 6px;">
        Madame/Monsieur,<br>
        J'ai l'honneur de vous informer que le Marché Numéro &nbsp;&nbsp;&nbsp;&nbsp;<strong>{{ $marche->num_marche }}</strong> &nbsp;&nbsp;ayant pour objet<br><br>
        <strong>{{ $marche->objet_marche ?? ($marche->aoo->objet ?? '') }}</strong><br><br>
        a été approuvé(e) par l'autorité competente<br><br>
        A cet effet, je vous invite à signer le présente notification de l'approbation, à enregistrer le marché et à présenter la caution définitive relative au marché sus-cité dans les délais réglementaires
    </div>

    <!-- MAITRE D'OUVRAGE -->
    <div style="text-align: right; padding-right: 60px; font-weight: bold; font-size: 12px; margin-top: 15px; margin-bottom: 12px;">
        Le maître d'ouvrage
    </div>

    <!-- OBSERVATION -->
    <div style="font-size: 11.5px; margin-bottom: 12px;">
        <strong><u>Observation</u></strong><br>
        Le montant de la caution definitive à presenter est de (en DH)<br>
        <span style="font-size: 13px; font-weight: bold; margin-left: 20px; display: inline-block; margin-top: 2px;">
            {{ number_format((float)($marche->caution_definitive ?? $marche->montant_caution_definitive ?? (($marche->montant ?? 0) * 0.03)), 2, ',', ' ') }}
        </span>
    </div>

    <div style="width: 100%; text-align: center; margin: 6px 0 8px 0;">
        @if(file_exists(public_path('images/info_DRCA.png')))
            <img src="{{ public_path('images/info_DRCA.png') }}" style="width: 100%; height: auto; max-height: 36px;" alt="ONCA Separator">
        @endif
        <hr style="border: 1.2px solid #000; margin-top: 3px;">
    </div>

    <!-- ACCUSE DE RECEPTION -->
    <div style="text-align: center; font-size: 15px; font-weight: bold; margin-bottom: 8px;">
        Accusé de reception
    </div>

    <div style="font-size: 11.5px; line-height: 1.45;">
        Je sousigne, Madame/Monsieur &nbsp;&nbsp;&nbsp;&nbsp;<strong>{{ strtoupper($marche->fournisseur->representant ?? $marche->representant ?? '........................................') }}</strong><br>
        Gérant/ agissant au nom et pour le compte de la société : &nbsp;&nbsp;&nbsp;&nbsp;<strong>{{ strtoupper($marche->fournisseur->raison_sociale ?? $marche->titulaire) }}</strong><br>
        Faisant élection de domicile à : &nbsp;&nbsp;&nbsp;&nbsp;<strong>{{ strtoupper($marche->fournisseur->adresse ?? '') }} {{ strtoupper($marche->fournisseur->ville ?? '') }}</strong><br>
        Atteste avoir réçu du directeur régional du conseil agricole Rabat-Salé-Kénitra la notification de l'approbation<br>
        du marché: <strong>{{ $marche->num_marche }}</strong><br>
        Ayant pour objet :<br><br>
        <strong>{{ $marche->objet_marche ?? ($marche->aoo->objet ?? '') }}</strong><br><br>
        J'accuse, par la presente, la réception de l'ordre de service de notification de l'approbation du marché sus-cité
    </div>

</body>
</html>
