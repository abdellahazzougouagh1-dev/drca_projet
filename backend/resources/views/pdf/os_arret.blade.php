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

    $societe = $cleanText($marche->fournisseur->raison_sociale ?? $marche->titulaire);
    $adresse = $cleanText($marche->fournisseur->adresse ?? '');
    $ville = $cleanText($marche->fournisseur->ville ?? 'KENITRA');
    $representant = $cleanText($marche->fournisseur->representant ?? $marche->representant ?? '........................................');
@endphp
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
            font-size: 11.5px; 
            line-height: 1.25; 
            margin: 0; 
            padding: 0; 
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .mb-2 { margin-bottom: 4px; }
        .mb-4 { margin-bottom: 8px; }
        
        .header-table { width: 100%; margin-bottom: 4px; }
        .header-table td { vertical-align: middle; border: none; padding: 0; }
        .center-header { text-align: center; font-size: 10.5px; font-weight: bold; }

        .table-borderless { width: 100%; border-collapse: collapse; }
        .table-borderless td { border: none; padding: 1px 3px; vertical-align: top; }

        .content-body {
            text-align: justify;
            margin-bottom: 8px;
        }

        .accuse-title {
            text-align: center;
            font-size: 15px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        /* Fixed Footer */
        .footer {
            position: fixed;
            bottom: -13mm;
            left: 0;
            right: 0;
            width: 100%;
            text-align: center;
        }
        .footer-banner {
            width: 100%;
            height: auto;
            max-height: 58px;
        }
    </style>
</head>
<body>

    <!-- FOOTER -->
    <div class="footer">
        @if($footerBanner)
            <img src="{{ $footerBanner }}" class="footer-banner" alt="ONCA DRCA">
        @endif
    </div>

    <!-- Header Logos & Direction -->
    <table class="header-table">
        <tr>
            <td style="width: 32%; text-align: left;">
                @if($logoOnca)
                    <img src="{{ $logoOnca }}" style="height: 65px;" alt="ONCA">
                @endif
            </td>
            <td class="center-header" style="width: 36%;">
                Direction Régionale du Conseil Agricole<br>Rabat-Salé-Kénitra
            </td>
            <td style="width: 32%; text-align: right;">
                @if($sceauMaroc)
                    <img src="{{ $sceauMaroc }}" style="height: 65px;" alt="Royaume du Maroc">
                @endif
            </td>
        </tr>
    </table>
    
    <hr style="border: 1.5px solid #000; margin-top: 0px; margin-bottom: 12px;">

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
        @if($footerBanner)
            <img src="{{ $footerBanner }}" style="width: 100%; height: auto; max-height: 58px;" alt="ONCA Separator">
        @endif
        <hr style="border: 1.2px solid #000; margin-top: 3px;">
    </div>

    <div class="accuse-title">Accusé de reception</div>

    <div class="content-body" style="font-size: 13px; line-height: 1.5;">
        Je sousigne, Monsieur &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>{{ $representant }}</strong><br>
        Gérant/ agissant au nom et pour le compte de la société : &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>{{ $societe }}</strong><br>
        Faisant élection de domicile à : &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>{{ $adresse }} {{ $ville }}</strong><br>
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
