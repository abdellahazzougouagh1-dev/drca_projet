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
            bottom: -12mm; /* Sit inside the 15mm bottom margin */
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
            <td style="width: 50%; font-weight: bold;">N° {{ $marche->os_numero ?? '......../2024/M10' }}</td>
            <td style="width: 50%; text-align: right; font-weight: bold;">Kénitra le: {{ $marche->os_date_signature ? \Carbon\Carbon::parse($marche->os_date_signature)->format('d/m/Y') : '........................' }}</td>
        </tr>
    </table>

    <div class="title" style="font-size: 15px; font-weight: bold; text-align: center; margin-bottom: 8px;">Le Directeur Régional du Conseil Agricole Rabat-Salé-Kénitra</div>

    <div class="text-center font-bold mb-4" style="font-size: 14px;">
        A<br>
        Monsieur le gérant de la société<br>
        {{ strtoupper($marche->fournisseur->raison_sociale ?? '........................................') }}<br>
        {{ strtoupper($marche->fournisseur->adresse ?? '........................................') }} {{ strtoupper($marche->fournisseur->ville ?? '') }}
    </div>

    <table class="table-borderless mb-4">
        <tr>
            <td style="width: 15%;" class="font-bold">Objet :</td>
            <td style="width: 85%;" class="font-bold">Ordre de service de Commencement de l'exécution</td>
        </tr>
        <tr>
            <td class="font-bold">Référence :</td>
            <td class="font-bold">
                Marché Numéro {{ $marche->num_marche }}<br>
                Appel d'offre N° {{ $marche->aoo->num_aoo ?? '........................' }} en date du {{ $marche->aoo->date_ouverture ? \Carbon\Carbon::parse($marche->aoo->date_ouverture)->format('d/m/Y') : '........................' }}
            </td>
        </tr>
    </table>

    <div class="content-body" style="margin-bottom: 8px;">
        Madame/Monsieur,<br><br>
        J'ai l'honneur de vous demander de commencer l'exécution du marché: <strong>{{ $marche->num_marche }}</strong> ayant pour objet :<br>
        <strong>{{ $marche->objet_marche }}</strong>
        @if($marche->lot && $marche->lot !== 'Lot Unique')
        <br><strong>Lot :</strong> {{ $marche->lot }}
        @endif
        <br><br>
        Le commencement de l'exécution du dit marché prendra effet -suite à son approbation par l'autorité compétente- à compter de <strong>{{ $marche->os_date_effet ? \Carbon\Carbon::parse($marche->os_date_effet)->format('d/m/Y') : '........................' }}</strong>.
    </div>

    <div class="signature-box" style="margin-left: 50%; text-align: center; font-weight: bold; margin-bottom: 15px;">
        Le Directeur Régional
    </div>

    <hr style="border: 1px solid #000; margin: 15px 0;">

    <div class="accuse-title" style="font-size: 15px; font-weight: bold; text-align: center; margin-bottom: 8px;">Accusé de réception</div>

    <div class="content-body" style="margin-bottom: 8px;">
        Je soussigné, Madame/Monsieur <strong>{{ strtoupper($marche->fournisseur->representant ?? '........................................') }}</strong><br>
        Gérant/ agissant au nom et pour le compte de la société : <strong>{{ strtoupper($marche->fournisseur->raison_sociale ?? '........................................') }}</strong><br>
        Faisant élection de domicile à : <strong>{{ strtoupper($marche->fournisseur->adresse ?? '........................................') }} {{ strtoupper($marche->fournisseur->ville ?? '') }}</strong><br>
        Atteste avoir reçu du directeur régional du conseil agricole Rabat-Salé-Kénitra l'ordre de service de commencement de l'exécution du marché: <strong>{{ $marche->num_marche }}</strong><br>
        Ayant pour objet :<br>
        <strong>{{ $marche->objet_marche }}</strong>
        @if($marche->lot && $marche->lot !== 'Lot Unique')
        <br><strong>Lot :</strong> {{ $marche->lot }}
        @endif
        <br>
        J'accuse, par la présente, la réception de l'ordre de service de commencement de l'exécution du marché sus-cité.
    </div>

    <table class="table-borderless mt-2" style="margin-top: 5px;">
        <tr>
            <td style="width: 50%;"></td>
            <td style="width: 50%; text-align: center;">
                Fait à ........................................ le ........................................<br><br><br>
                <strong>Signature et cachet du Titulaire</strong>
            </td>
        </tr>
    </table>
</body>
</html>
