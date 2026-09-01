<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Ordre de service de Commencement</title>
    <style>
        @page {
            margin: 6mm 10mm 15mm 10mm; /* Increased bottom margin for footer */
        }
        body { 
            font-family: 'DejaVu Sans', sans-serif; 
            font-size: 11.5px; 
            line-height: 1.2; 
            margin: 0; 
            padding: 0; 
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .underline { text-decoration: underline; }
        .mb-2 { margin-bottom: 3px; }
        .mb-4 { margin-bottom: 5px; }
        .mt-4 { margin-top: 8px; }
        
        .header-table { width: 100%; margin-bottom: 5px; }
        .header-table td { vertical-align: middle; border: none; padding: 0; }
        .center-header { text-align: center; font-size: 10.5px; font-weight: bold; }

        .table-borderless { width: 100%; border-collapse: collapse; }
        .table-borderless td { border: none; padding: 1px 3px; vertical-align: top; }

        .recipient-box {
            margin-left: 50%;
            margin-bottom: 10px;
            font-weight: bold;
            line-height: 1.3;
            font-size: 12px;
        }

        .content-body {
            text-align: justify;
            margin-bottom: 5px;
        }

        .signature-box {
            margin-left: 50%;
            margin-top: 10px;
            margin-bottom: 15px;
            font-weight: bold;
            text-align: center;
        }

        .accuse-box {
            border-top: 2px solid #000;
            padding-top: 8px;
            margin-top: 5px;
        }
        
        .accuse-title {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 8px;
        }

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

    <!-- Header Logos & Direction -->
    <table class="header-table">
        <tr>
            <td style="width: 33%; text-align: left;">
                @if(file_exists(public_path('images/logo-onca.png')))
                    <img src="{{ public_path('images/logo-onca.png') }}" style="height: 65px;" alt="ONCA">
                @endif
            </td>
            <td class="center-header" style="width: 34%;">
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
            <td style="width: 50%; font-weight: bold; font-size: 13px;">N° {{ $marche->os_numero ?? '03/2024/M10' }}</td>
            <td style="width: 50%; text-align: right; font-weight: bold; font-size: 13px;">Kénitra le: {{ $marche->os_date_signature ? \Carbon\Carbon::parse($marche->os_date_signature)->format('d/m/Y') : date('d/m/Y') }}</td>
        </tr>
    </table>

    <div class="title" style="font-size: 14px; font-weight: bold; text-align: center; margin-bottom: 12px;">Le Directeur Régional du Conseil Agricole Rabat-Salé-Kénitra</div>

    <div class="text-center font-bold mb-4" style="font-size: 13px; line-height: 1.4;">
        A<br>
        Monsieur le gérant de la société<br>
        <span style="font-size: 14px;">{{ strtoupper($marche->fournisseur->raison_sociale ?? $marche->titulaire) }}</span><br>
        {{ strtoupper($marche->fournisseur->adresse ?? '') }}<br>
        <div style="text-align: right; margin-right: 40px; margin-top: 4px;">{{ strtoupper($marche->fournisseur->ville ?? 'Kénitra') }}</div>
    </div>

    <table class="table-borderless mb-3" style="font-size: 12.5px;">
        <tr>
            <td style="width: 15%;" class="font-bold">Objet</td>
            <td style="width: 3%;" class="font-bold">:</td>
            <td style="width: 30%;" class="font-bold">Ordre de service de</td>
            <td style="width: 52%;" class="font-bold">Commencement de l'execution</td>
        </tr>
        <tr>
            <td class="font-bold">Référence</td>
            <td class="font-bold">:</td>
            <td class="font-bold">Marché Numéro</td>
            <td class="font-bold">{{ $marche->num_marche }}</td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td class="font-bold">Appel d'offre N°</td>
            <td class="font-bold">{{ $marche->aoo->num_aoo ?? '........................' }} &nbsp;&nbsp;&nbsp;&nbsp;en date du &nbsp;&nbsp;{{ $marche->date_oa ? \Carbon\Carbon::parse($marche->date_oa)->format('d/m/Y') : ($marche->aoo->date_ouverture ? \Carbon\Carbon::parse($marche->aoo->date_ouverture)->format('d/m/Y') : '........................') }}</td>
        </tr>
    </table>

    <div class="content-body" style="font-size: 12px; line-height: 1.45; margin-bottom: 12px;">
        Monsieur,<br><br>
        J'ai l'honneur de vous demander de commencer l'exécution du marché: &nbsp;&nbsp;&nbsp;&nbsp;<strong>{{ $marche->num_marche }}</strong> &nbsp;&nbsp;ayant pour objet<br><br>
        <strong>{{ strtoupper($marche->aoo->objet ?? $marche->objet_marche) }}</strong>
        @if($marche->lot && strtolower(trim($marche->lot)) !== 'lot unique' && strtolower(trim($marche->lot)) !== 'unique')
        <br><strong>{{ stripos($marche->lot, 'lot') !== false ? $marche->lot : 'Lot ' . $marche->lot }} : {{ strtoupper($marche->objet_marche) }}</strong>
        @endif
        <br><br>
        Le commencement de l'éxecution du dit marché prendra effet -suite à son approbation par l'autorité compétente- à compter de <strong>{{ $marche->os_date_effet ? \Carbon\Carbon::parse($marche->os_date_effet)->format('d/m/Y') : 'la date de la récéption du présent ordre de service' }}</strong>.
    </div>

    <div style="width: 100%; text-align: center; margin: 6px 0 8px 0;">
        @if(file_exists(public_path('images/info_DRCA.png')))
            <img src="{{ public_path('images/info_DRCA.png') }}" style="width: 100%; height: auto; max-height: 36px;" alt="ONCA Separator">
        @endif
        <hr style="border: 1.2px solid #000; margin-top: 3px;">
    </div>

    <div class="accuse-title" style="font-size: 14px; font-weight: bold; text-align: center; margin-bottom: 8px;">Accusé de reception</div>

    <div class="content-body" style="font-size: 12px; line-height: 1.4; margin-bottom: 8px;">
        Je sousigne, Monsieur &nbsp;&nbsp;&nbsp;&nbsp;<strong>{{ strtoupper($marche->fournisseur->representant ?? $marche->representant ?? '........................................') }}</strong><br>
        Gérant/ agissant au nom et pour le compte de la société : &nbsp;&nbsp;&nbsp;&nbsp;<strong>{{ strtoupper($marche->fournisseur->raison_sociale ?? $marche->titulaire) }}</strong><br>
        Faisant élection de domicile à : &nbsp;&nbsp;&nbsp;&nbsp;<strong>{{ strtoupper($marche->fournisseur->adresse ?? '') }} {{ strtoupper($marche->fournisseur->ville ?? '') }}</strong><br>
        Atteste avoir reçu du directeur régional du conseil agricole Rabat-Salé-Kénitra l'ordre de service de commencement de l'execution du marché: <strong>{{ $marche->num_marche }}</strong><br>
        Ayant pour objet :<br>
        <strong>{{ strtoupper($marche->aoo->objet ?? $marche->objet_marche) }}</strong>
        @if($marche->lot && strtolower(trim($marche->lot)) !== 'lot unique' && strtolower(trim($marche->lot)) !== 'unique')
        <br><strong>{{ stripos($marche->lot, 'lot') !== false ? $marche->lot : 'Lot ' . $marche->lot }} : {{ strtoupper($marche->objet_marche) }}</strong>
        @endif
        <br><br>
        J'accuse, par la presente, la récéption de l'ordre de service de commencement de l'execution du marché sus-cité
    </div>
</body>
</html>
