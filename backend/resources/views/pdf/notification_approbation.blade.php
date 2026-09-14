@php
    $logoOnca = \App\Support\AooDocumentHelper::embedImage('images/logo-onca.png');
    $sceauMaroc = \App\Support\AooDocumentHelper::embedImage('images/sceau-maroc.png');
    $footerBanner = \App\Support\AooDocumentHelper::embedImage('images/info_DRCA.png');

    $cleanText = function($text) {
        if (!$text) return '';
        $text = str_ireplace(['f?s', 'f?S', 'F?S', 'F?s'], 'FES', $text);
        $replacements = [
            'è' => 'E', 'é' => 'E', 'ê' => 'E', 'ë' => 'E',
            'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'à' => 'A', 'â' => 'A', 'ä' => 'A', 'À' => 'A', 'Â' => 'A', 'Ä' => 'A',
            'î' => 'I', 'ï' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'ô' => 'O', 'ö' => 'O', 'Ô' => 'O', 'Ö' => 'O',
            'ù' => 'U', 'û' => 'U', 'ü' => 'U', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U',
            'ç' => 'C', 'Ç' => 'C'
        ];
        $text = str_replace(array_keys($replacements), array_values($replacements), $text);
        $text = str_replace('?', '', $text);
        return strtoupper(trim($text));
    };

    $qualite = $marche->qualite_gerant ?: ($marche->fournisseur->qualite_representant ?? 'Monsieur le gérant de la société');
    $societe = $cleanText($marche->fournisseur->raison_sociale ?? $marche->titulaire);
    $adresse = $cleanText($marche->fournisseur->adresse ?? ($marche->fournisseur->domicile_elu ?? ''));
    $ville = $cleanText($marche->fournisseur->ville ?? 'CASABLANCA');
    $representant = $cleanText($marche->fournisseur->representant ?? $marche->representant ?? ($marche->titulaire ?? '........................................'));
    
    $numMarche = $marche->num_marche ?: '-';
    $numAoo = $marche->aoo->num_aoo ?? '........................';
    $dateAoo = $marche->date_oa 
        ? \Carbon\Carbon::parse($marche->date_oa)->format('d/m/Y') 
        : ($marche->aoo->date_ouverture 
            ? \Carbon\Carbon::parse($marche->aoo->date_ouverture)->format('d/m/Y') 
            : ($marche->aoo->created_at ? \Carbon\Carbon::parse($marche->aoo->created_at)->format('d/m/Y') : '19/09/2024'));
            
    $dateNotif = $marche->date_notification_marche 
        ? \Carbon\Carbon::parse($marche->date_notification_marche)->format('d/m/Y') 
        : ($marche->date_approbation ? \Carbon\Carbon::parse($marche->date_approbation)->format('d/m/Y') : date('d/m/Y'));
        
    $numDecision = $marche->num_decision ?: ($marche->num_notification ?: ('01/' . ($marche->exercice ?: date('Y')) . '/M' . ($marche->num_marche ? sprintf('%02d', preg_replace('/[^0-9]/', '', explode('/', $marche->num_marche)[0] ?? '1') ?: 1) : '01')));
    
    // Objet pur sans mention du mot LOT
    $rawObjet = $marche->objet_marche ?: ($marche->aoo->objet ?? '');
    $objetText = preg_replace('/,?\s*lot[-\s]*\d+/iu', '', $rawObjet);
    $objetText = preg_replace('/,?\s*en lot unique\.?/iu', '', $objetText);
    $objetText = trim($objetText, " \t\n\r\0\x0B,");
    
    $cautionDefinitive = (float)($marche->caution_definitive ?? $marche->montant_caution_definitive ?? (($marche->montant ?? 0) * 0.03));
@endphp
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Notification de l'approbation</title>
    <style>
        @page {
            margin: 5mm 12mm 22mm 12mm;
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
        
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 2px; }
        .header-table td { vertical-align: middle; border: none; padding: 0; }
        .center-header { text-align: center; font-size: 12.5px; color: #000; font-weight: bold; }

        .table-borderless { width: 100%; border-collapse: collapse; }
        .table-borderless td { border: none; padding: 1.5px 2px; vertical-align: top; }
        
        /* Fixed Footer */
        .footer {
            position: fixed;
            bottom: -18mm;
            left: 0;
            right: 0;
            width: 100%;
            text-align: center;
        }
        .footer-banner {
            width: 100%;
            height: auto;
            max-height: 75px;
        }
    </style>
</head>
<body>

    <!-- FOOTER -->
    <div class="footer">
        @if($footerBanner)
            <img src="{{ $footerBanner }}" class="footer-banner" alt="ONCA DRCA">
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
            <td style="width: 25%; text-align: left;">
                @if($logoOnca)
                    <img src="{{ $logoOnca }}" style="height: 68px;" alt="ONCA">
                @endif
            </td>
            <td class="center-header" style="width: 50%;">
                Direction Régionale du Conseil Agricole<br>Rabat-Salé-Kénitra
            </td>
            <td style="width: 25%; text-align: right;">
                @if($sceauMaroc)
                    <img src="{{ $sceauMaroc }}" style="height: 68px;" alt="Royaume du Maroc">
                @endif
            </td>
        </tr>
    </table>
    
    <hr style="border: 0.8px solid #000; margin-top: 2px; margin-bottom: 6px;">

    <!-- N° & DATE -->
    <table class="table-borderless" style="margin-bottom: 5px;">
        <tr>
            <td style="width: 45%; font-size: 12px; font-weight: bold;">
                N° &nbsp;&nbsp;{{ $numDecision }}
            </td>
            <td style="width: 55%; text-align: right; font-size: 12px;">
                Kénitra le: &nbsp;<strong>{{ $dateNotif }}</strong><br>
                <span style="font-weight: bold; margin-top: 3px; display: inline-block;">Kénitra</span>
            </td>
        </tr>
    </table>

    <!-- TITRE & DESTINATAIRE -->
    <div style="font-size: 13px; font-weight: bold; text-align: center; margin-bottom: 5px;">
        Le Directeur Régional du Conseil Agricole Rabat-Salé-Kénitra
    </div>

    <div style="text-align: center; font-weight: bold; font-size: 12px; line-height: 1.35; margin-bottom: 2px;">
        A<br>
        {{ $qualite }}<br>
        <span style="font-size: 13px; font-weight: bold;">{{ $societe }}</span><br>
        <span style="font-size: 11.5px; font-weight: normal;">{{ $adresse }}</span>
    </div>
    <div style="text-align: right; font-weight: bold; font-size: 12px; padding-right: 30px; margin-bottom: 6px;">
        {{ $ville }}
    </div>

    <!-- OBJET & REFERENCE -->
    <table class="table-borderless" style="font-size: 11.5px; margin-bottom: 8px;">
        <tr>
            <td style="width: 13%; font-weight: bold;">Objet</td>
            <td style="width: 3%; font-weight: bold; text-align: center;">:</td>
            <td style="width: 25%; font-weight: bold;">Ordre de service de</td>
            <td style="width: 59%; font-weight: bold;">Notification de l'approbation</td>
        </tr>
        <tr>
            <td style="font-weight: bold; padding-top: 2px;">Référence</td>
            <td style="font-weight: bold; text-align: center; padding-top: 2px;">:</td>
            <td style="font-weight: bold; padding-top: 2px;">Marché Numéro</td>
            <td style="font-weight: bold; padding-top: 2px;">{{ $numMarche }}</td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td style="font-weight: bold; padding-top: 2px;">Appel d'offre N°</td>
            <td style="font-weight: bold; padding-top: 2px;">
                {{ $numAoo }} &nbsp;&nbsp;&nbsp;&nbsp;en date du &nbsp;&nbsp;{{ $dateAoo }}
            </td>
        </tr>
    </table>

    <!-- CORPS -->
    <div style="font-size: 11.5px; line-height: 1.35; margin-bottom: 4px;">
        Madame/Monsieur,<br>
        J'ai l'honneur de vous informer que le Marché Numéro &nbsp;&nbsp;&nbsp;&nbsp;<strong>{{ $numMarche }}</strong> &nbsp;&nbsp;ayant pour objet<br><br>
        <strong>{{ $objetText }}</strong><br><br>
        a été approuvé(e) par l'autorité competente<br><br>
        A cet effet, je vous invite à signer le présente notification de l'approbation, à enregistrer le marché et à présenter la caution définitive relative au marché sus-cité dans les délais réglementaires
    </div>

    <!-- MAITRE D'OUVRAGE & OBSERVATION -->
    <table style="width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 8px;">
        <tr>
            <td style="width: 55%; vertical-align: top; font-size: 11.5px;">
                <div style="font-weight: bold; text-decoration: underline; margin-bottom: 2px;">Observation</div>
                <div>Le montant de la caution definitive à presenter est de (en DH)</div>
                <div style="font-size: 12.5px; font-weight: bold; margin-top: 3px; padding-left: 15px;">
                    {{ number_format($cautionDefinitive, 2, ',', ' ') }}
                </div>
            </td>
            <td style="width: 45%; vertical-align: top; text-align: center; font-size: 12px;">
                <div style="padding-top: 15px;">Le maître d'ouvrage</div>
            </td>
        </tr>
    </table>

    <!-- SEPARATEUR ACCUSE DE RECEPTION -->
    <hr style="border: 1px solid #000; margin-top: 8px; margin-bottom: 8px;">

    <!-- ACCUSE DE RECEPTION -->
    <div style="text-align: center; font-size: 13px; font-weight: bold; margin-bottom: 8px;">
        Accusé de reception
    </div>

    <table style="width: 100%; border-collapse: collapse; font-size: 11.5px; line-height: 1.45; margin-bottom: 4px;">
        <tr>
            <td style="white-space: nowrap; padding: 2px 10px 2px 0; width: 1%;">Je sousigne, Madame/Monsieur</td>
            <td style="font-weight: bold; padding: 2px 0;">{{ $representant }}</td>
        </tr>
        <tr>
            <td style="white-space: nowrap; padding: 2px 10px 2px 0; width: 1%;">Gérant/ agissant au nom et pour le compte de la société :</td>
            <td style="font-weight: bold; padding: 2px 0;">{{ $societe }}</td>
        </tr>
        <tr>
            <td style="white-space: nowrap; padding: 2px 10px 2px 0; width: 1%;">Faisant élection de domicile à :</td>
            <td style="font-weight: bold; padding: 2px 0;">{{ $adresse }}</td>
        </tr>
    </table>

    <div style="font-size: 11.5px; line-height: 1.35; margin-top: 4px;">
        <div>Atteste avoir réçu du directeur régional du conseil agricole Rabat-Salé-Kénitra la notification de l'approbation</div>
        <div style="margin-top: 2px;">du marché: <strong>{{ $numMarche }}</strong></div>
        <div style="margin-top: 3px;">Ayant pour objet :</div>
        <div style="font-weight: bold; margin-top: 4px;">
            {{ $objetText }}
        </div>
    </div>

</body>
</html>
