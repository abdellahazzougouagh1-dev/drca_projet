<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Ordre de service d'Ajournement</title>
    <style>
        @page {
            margin: 6mm 10mm 15mm 10mm;
        }
        body { 
            font-family: 'DejaVu Sans', sans-serif; 
            font-size: 12px; 
            line-height: 1.3; 
            margin: 0; 
            padding: 0; 
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .mb-2 { margin-bottom: 5px; }
        .mb-4 { margin-bottom: 10px; }
        
        .header-table { width: 100%; margin-bottom: 5px; }
        .header-table td { vertical-align: middle; border: none; padding: 0; }
        .center-header { text-align: center; font-size: 11px; }

        .table-borderless { width: 100%; border-collapse: collapse; }
        .table-borderless td { border: none; padding: 1px 3px; vertical-align: top; }

        .content-body {
            text-align: justify;
            margin-bottom: 10px;
        }

        .accuse-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 15px;
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
                الهاتف : 212537325599+ / الفaكس : 212537361320+ - الموقع الإلكتروني : www.onca.gov.ma<br>
                Direction Régionale du Conseil Agricole de la Région de Rabat Salé Kénitra, Angle Rue Sebta - Bd Mohamed V (à côté de Bank Al-Maghreb) - Kénitra<br>
                Tél. : +212 (0) 537 32 55 99 - Fax : +212 (0) 537 36 13 20 - Site web : www.onca.gov.ma
            </div>
        @endif
    </div>

    <!-- Header Logos & Direction -->
    <table class="header-table">
        <tr>
            <td style="width: 30%; text-align: left;">
                @if(file_exists(public_path('images/logo-onca.png')))
                    <img src="{{ public_path('images/logo-onca.png') }}" style="height: 60px;" alt="ONCA">
                @endif
            </td>
            <td class="center-header" style="width: 40%;">
                Direction Régionale du Conseil Agricole Rabat-Salé-Kénitra
            </td>
            <td style="width: 30%; text-align: right;">
                @if(file_exists(public_path('images/sceau-maroc.png')))
                    <img src="{{ public_path('images/sceau-maroc.png') }}" style="height: 60px;" alt="Royaume du Maroc">
                @endif
            </td>
        </tr>
    </table>
    
    <hr style="border: 1.5px solid #000; margin-top: 0px; margin-bottom: 15px;">

    <table class="table-borderless" style="margin-bottom: 15px;">
        <tr>
            <td style="width: 50%; font-size: 13px;">N° &nbsp;&nbsp;&nbsp;{{ $marche->os_arret_numero ?? '......../2024/M..' }}</td>
            <td style="width: 50%; text-align: right; font-size: 13px;">Kénitra le: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
        </tr>
    </table>

    <div style="font-size: 14px; font-weight: bold; text-align: center; margin-bottom: 15px;">Le Directeur Régional du Conseil Agricole Rabat-Salé-Kénitra</div>

    <div class="text-center font-bold mb-4" style="font-size: 13px; line-height: 1.4;">
        A<br>
        Monsieur le gérant de la société<br>
        {{ strtoupper($marche->fournisseur->raison_sociale ?? '........................................') }}<br>
        {{ strtoupper($marche->fournisseur->adresse ?? '........................................') }}
    </div>
    
    <div class="text-right font-bold mb-4" style="font-size: 13px; padding-right: 40px;">
        {{ strtoupper($marche->fournisseur->ville ?? '........................') }}
    </div>

    <table class="table-borderless mb-4" style="font-size: 13px;">
        <tr>
            <td style="width: 15%; font-weight: bold;">Objet</td>
            <td style="width: 3%;">:</td>
            <td style="width: 82%; font-weight: bold;">Ordre de service de Ajournement de l'execution</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Référence</td>
            <td style="font-weight: bold;">:</td>
            <td style="font-weight: bold;">
                Marché Numéro &nbsp;&nbsp;&nbsp;&nbsp;{{ $marche->num_marche }}<br>
                Appel d'offre N° &nbsp;&nbsp;&nbsp;&nbsp;{{ $marche->aoo->num_aoo ?? '........................' }} &nbsp;&nbsp;en date du&nbsp;&nbsp; {{ $marche->date_oa ? \Carbon\Carbon::parse($marche->date_oa)->format('d/m/Y') : ($marche->aoo->date_ouverture ? \Carbon\Carbon::parse($marche->aoo->date_ouverture)->format('d/m/Y') : '........................') }}
            </td>
        </tr>
    </table>

    <div class="content-body" style="font-size: 13px;">
        Madame/ Monsieur<br><br>
        J'ai l'honneur de vous demander l'arrêt de l'exécution du marché: &nbsp;&nbsp;&nbsp;&nbsp;<strong>{{ $marche->num_marche }}</strong> &nbsp;&nbsp;ayant pour objet<br>
        <strong>{{ strtoupper($marche->objet_marche) }}</strong>
        @if($marche->lot && strtolower(trim($marche->lot)) !== 'lot unique' && strtolower(trim($marche->lot)) !== 'unique')
        <br><strong>LOT : {{ strtoupper($marche->lot) }}</strong>
        @endif
        <br><br>
        L'ordre d'arret de l'éxecution de ce marché prendra effet à compter de {{ $marche->os_arret_date_effet ? \Carbon\Carbon::parse($marche->os_arret_date_effet)->format('d/m/Y') : 'la date de la récéption du présent ordre de service' }}, et il est établi pour le motif suivant:<br>
        <strong>{{ $marche->os_arret_motif ?? '....................................................................................................' }}</strong>
    </div>

    <div style="text-align: right; margin-top: 20px; margin-bottom: 20px; font-size: 13px; padding-right: 40px;">
        Fait à Kénitra le &nbsp;{{ $marche->os_arret_date_signature ? \Carbon\Carbon::parse($marche->os_arret_date_signature)->format('d/m/Y') : '........................' }}
    </div>

    <div style="width: 100%; text-align: center; margin: 8px 0 10px 0;">
        @if(file_exists(public_path('images/info_DRCA.png')))
            <img src="{{ public_path('images/info_DRCA.png') }}" style="width: 100%; height: auto; max-height: 36px;" alt="ONCA Separator">
        @endif
        <hr style="border: 1.2px solid #000; margin-top: 3px;">
    </div>

    <div class="accuse-title">Accusé de reception</div>

    <div class="content-body" style="font-size: 13px; line-height: 1.5;">
        Je sousigne, Monsieur &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>{{ strtoupper($marche->fournisseur->representant ?? '........................................') }}</strong><br>
        Gérant/ agissant au nom et pour le compte de la société : &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>{{ strtoupper($marche->fournisseur->raison_sociale ?? '........................................') }}</strong><br>
        Faisant élection de domicile à : &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>{{ strtoupper($marche->fournisseur->adresse ?? '........................................') }} {{ strtoupper($marche->fournisseur->ville ?? '') }}</strong><br>
        Atteste avoir reçu du directeur régional du conseil agricole Rabat-Salé-Kénitra l'ordre d'arret de l'execution<br>
        du marché: &nbsp;&nbsp;<strong>{{ $marche->num_marche }}</strong><br>
        Ayant pour objet :<br>
        <strong>{{ strtoupper($marche->objet_marche) }}</strong>
        @if($marche->lot && strtolower(trim($marche->lot)) !== 'lot unique' && strtolower(trim($marche->lot)) !== 'unique')
        <br><strong>LOT : {{ strtoupper($marche->lot) }}</strong>
        @endif
        <br><br>
        J'accuse, par la presente, la récéption de l'ordre de service de l'arret de l'execution du marché sus-cité
    </div>

</body>
</html>
