<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>OS de Commencement - {{ $num_marche }}</title>
    <style>
        @page {
            margin: 8mm 12mm 12mm 12mm;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.25;
            margin: 0;
            padding: 0;
            color: #000;
        }
        .header-table { width: 100%; margin-bottom: 10px; }
        .header-table td { vertical-align: middle; border: none; padding: 0; }
        .center-header { text-align: center; font-size: 11px; font-weight: bold; }
        .ref-bar { width: 100%; margin-bottom: 15px; font-size: 12px; }
        .destinataire-block { text-align: center; margin-bottom: 15px; font-size: 13px; }
        .subject-table { width: 100%; margin-bottom: 15px; }
        .subject-table td { padding: 2px 0; vertical-align: top; }
        .paragraph { text-align: justify; margin-bottom: 10px; }
        
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
        
        .accuse-block { margin-top: 20px; border-top: 2px solid #000; padding-top: 15px; }
        .accuse-title { text-align: center; font-size: 14px; font-weight: bold; margin-bottom: 15px; }
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

    <!-- Ref Bar -->
    <table class="ref-bar">
        <tr>
            <td><strong>N° {{ $num_os ?: '03/2026' }}/M10</strong></td>
            <td style="text-align: right;"><strong>Kénitra le : {{ $date_os }}</strong></td>
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
            <td style="width: 85%; font-weight: bold;">Ordre de service de Commencement de l'exécution</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Référence :</td>
            <td>
                Marché Numéro <strong>{{ $num_marche }}</strong><br>
                Appel d'offre N° <strong>{{ $num_aoo }}</strong> en date du <strong>{{ $date_ouverture }}</strong>
            </td>
        </tr>
    </table>

    <div style="font-weight: bold; margin-bottom: 12px;">Monsieur,</div>

    <div class="paragraph">
        J'ai l'honneur de vous demander de commencer l'exécution du marché: <strong>{{ $num_marche }}</strong> ayant pour objet :<br>
        <strong>{{ $marche->aoo->objet ?? $objet_marche }}</strong><br>
        @if($marche->lot && strtolower(trim($marche->lot)) !== 'lot unique' && strtolower(trim($marche->lot)) !== 'unique')
        <strong>{{ stripos($marche->lot, 'lot') !== false ? $marche->lot : 'Lot ' . $marche->lot }} : {{ $objet_marche }}</strong>
        @endif
    </div>

    <div class="date-effet-box">
        Le commencement de l'exécution du dit marché prendra effet -suite à son approbation par l'autorité compétente- à compter du : {{ $date_effet_commencement }}
    </div>

    <div style="text-align: right; margin-top: 30px; font-weight: bold;">
        Le Directeur Régional
    </div>

    <!-- Accusé de réception -->
    <div class="accuse-block">
        <div class="accuse-title">Accusé de réception</div>
        
        <div class="paragraph">
            Je soussigné, Monsieur <strong>{{ $representant_nom }}</strong><br>
            Gérant/ agissant au nom et pour le compte de la société : <strong>{{ $titulaire_nom }}</strong><br>
            Faisant élection de domicile à : <strong>{{ $adresse }} {{ !empty($ville) ? '- ' . $ville : '' }}</strong>
        </div>

        <div class="paragraph">
            Atteste avoir reçu du directeur régional du conseil agricole Rabat-Salé-Kénitra l'ordre de service de commencement de l'exécution du marché: <strong>{{ $num_marche }}</strong><br>
            Ayant pour objet :<br>
            <strong>{{ $marche->aoo->objet ?? $objet_marche }}</strong><br>
            @if($marche->lot && strtolower(trim($marche->lot)) !== 'lot unique' && strtolower(trim($marche->lot)) !== 'unique')
            <strong>{{ stripos($marche->lot, 'lot') !== false ? $marche->lot : 'Lot ' . $marche->lot }} : {{ $objet_marche }}</strong>
            @endif
        </div>

        <div class="paragraph" style="font-style: italic;">
            J'accuse, par la présente, la réception de l'ordre de service de commencement de l'exécution du marché sus-cité.
        </div>

        <table style="width: 100%; margin-top: 25px;">
            <tr>
                <td>Fait à ............................, le ............................</td>
                <td style="text-align: right; font-weight: bold;">Signature et Cachet du Titulaire</td>
            </tr>
        </table>
    </div>

</body>
</html>
