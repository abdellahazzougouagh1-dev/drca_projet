<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ordre de Virement</title>
    <style>
        @page { margin: 18px 34px 96px 34px; size: A4 portrait; }
        * { box-sizing: border-box; }
        body { font-family: "Times New Roman", Times, serif; font-size: 12px; line-height: 1.48; margin: 0; padding: 0; color: #000; }
        td, th { font-family: "Times New Roman", Times, serif; }

        .hdr { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .hdr td { vertical-align: middle; padding: 0; border: none; }
        .hdr-center { text-align: center; font-size: 13.5px; font-weight: bold; line-height: 1.35; padding: 0 8px; }
        .hdr-divider { border-bottom: 2px solid #000080; margin: 4px 0 16px 0; }

        .ref-table { width: 100%; border-collapse: collapse; margin-bottom: 22px; font-size: 12px; }
        .ref-table td { padding: 0; border: none; vertical-align: middle; }

        .main-title {
            text-align: center; font-size: 16.5px; font-weight: bold;
            letter-spacing: 0.6px; margin-bottom: 8px;
            text-transform: uppercase;
            text-decoration: underline;
        }
        .sub-title {
            text-align: center; font-size: 13.5px; font-weight: bold;
            margin-bottom: 6px;
        }
        .dest-title {
            text-align: center; font-size: 13px; font-weight: bold;
            letter-spacing: 0.4px; text-transform: uppercase;
            line-height: 1.4;
            margin: 0 auto 26px auto;
        }

        .body-text { font-size: 12px; text-align: justify; margin-bottom: 18px; line-height: 1.55; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .info-table td { padding: 7.5px 0; font-size: 12px; vertical-align: top; }
        .info-table .flabel { width: 29%; font-weight: normal; }
        .info-table .fsep   { width: 3%; text-align: center; font-weight: bold; }
        .info-table .fval   { width: 68%; font-weight: bold; }

        .op-ref { font-size: 12px; margin-bottom: 18px; }
        .closing-text { font-size: 12px; text-align: justify; line-height: 1.55; margin-bottom: 26px; }

        .sig-table { width: 100%; border-collapse: collapse; border: 2px solid #000; page-break-inside: avoid; }
        .sig-table th {
            background: #a3e635; color: #000; font-weight: bold;
            text-align: center; padding: 9px 7px; border: 1.5px solid #000;
            font-size: 10px; text-transform: uppercase; letter-spacing: 0.3px;
            line-height: 1.35;
        }
        .sig-table td { border: 1.5px solid #000; height: 150px; vertical-align: top; }
    </style>
    @include('documents.partials.bc_typography')
</head>
<body>

@php
    $logoOncaSrc   = file_exists(public_path('images/logo-onca.png'))   ? 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('images/logo-onca.png')))   : '';
    $sceauMarocSrc = file_exists(public_path('images/sceau-maroc.png')) ? 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('images/sceau-maroc.png'))) : '';

    $exercice    = $consultation->annee ?? date('Y');
    $suffixe     = '/DRCA-RSK/' . $exercice;

    $numOV       = data_get($documentData, 'numero_ov', '');
    $numOP       = data_get($documentData, 'numero_op') ?: '';
    $dateDoc     = data_get($documentData, 'date_document')
                   ? \Carbon\Carbon::parse(data_get($documentData, 'date_document'))->format('d/m/Y')
                   : '';

    $compteCourant  = data_get($documentData, 'compte_courant', '310330100602470154760181');
    $libelleCompte  = data_get($documentData, 'libelle_compte', 'ONCA DR RABAT-SALE-KENITRA INVESTISSEMENT');

    $montantOv      = (float)(data_get($documentData, 'montant_ov') ?: $doc['total_ttc'] ?? 0);
    $montantLettres = \App\Helpers\NumberToWordsHelper::toFrenchMoneyWords($montantOv);

    $isConvention = (!empty($consultation) && ($consultation->mode_engagement === 'Convention' || str_starts_with(strtoupper($consultation->numero_consultation ?? ''), 'CONV')))
        || str_starts_with(strtoupper((string)data_get($documentData, 'numero_consultation', '')), 'CONV');
    $cleanNumConv  = trim(preg_replace('/\bCONV\s*[-_]?\s*/i', '', (string)($doc['numero_consultation'] ?? ($consultation->numero_consultation ?? ''))));
    $defaultRef    = $isConvention ? ('Convention N° ' . $cleanNumConv) : ($doc['numero_bc'] ?? '');
    $beneficiaire   = data_get($documentData, 'societe') ?: ($doc['societe'] ?? ($doc['attributaire'] ?? ''));
    $rib            = data_get($documentData, 'rib') ?: ($doc['rib'] ?? '');
    $reference      = data_get($documentData, 'reference_bc') ?: $defaultRef;
@endphp

@include('documents.partials.bc_footer')

<!-- HEADER -->
<table class="hdr">
    <tr>
        <td style="width:24%; text-align:left;">
            @if($logoOncaSrc)<img src="{{ $logoOncaSrc }}" style="height:68px; width:auto;">@endif
        </td>
        <td style="width:52%;" class="hdr-center">
            Direction Régionale du Conseil Agricole Rabat-Salé-Kénitra
        </td>
        <td style="width:24%; text-align:right;">
            @if($sceauMarocSrc)<img src="{{ $sceauMarocSrc }}" style="height:68px; width:auto;">@endif
        </td>
    </tr>
</table>
<div class="hdr-divider"></div>

<!-- RÉFÉRENCE OV -->
<table class="ref-table">
    <tr>
        <td style="width:65%;">
            Référence : OV N° &nbsp;&nbsp;
            <strong style="font-size:13.5px;">{{ $numOV }}</strong>
            &nbsp;&nbsp; {{ $suffixe }}
        </td>
        <td style="width:35%; text-align:right;">
            Date : &nbsp;&nbsp; <strong>{{ $dateDoc }}</strong>
        </td>
    </tr>
</table>

<!-- TITRE PRINCIPAL -->
<div class="main-title">ORDRE DE VIREMENT</div>
<div class="sub-title">À</div>
<div class="dest-title">
    MONSIEUR LE TRÉSORIER GÉNÉRAL DU ROYAUME<br>
    CHEF DE L'AGENCE BANCAIRE DE KÉNITRA
</div>

<!-- TEXTE INTRODUCTIF -->
<div class="body-text">
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    Par le débit de notre compte courant N°
    <strong>{{ $compteCourant }}</strong>,
    {{ $libelleCompte }}, ouvert dans vos livres, veuillez virer :
</div>

<!-- INFORMATIONS DE VIREMENT -->
<table class="info-table">
    <tr>
        <td class="flabel">La somme de</td>
        <td class="fsep">:</td>
        <td class="fval" style="font-size:13px;">{{ number_format($montantOv, 2, ',', ' ') }} DH</td>
    </tr>
    <tr>
        <td class="flabel">En lettres</td>
        <td class="fsep">:</td>
        <td class="fval" style="font-style:italic; font-weight:normal;">{{ $montantLettres }}</td>
    </tr>
    <tr>
        <td class="flabel">Au profit de</td>
        <td class="fsep">:</td>
        <td class="fval" style="text-transform:uppercase;">{{ $beneficiaire }}</td>
    </tr>
    <tr>
        <td class="flabel">Titulaire du compte</td>
        <td class="fsep">:</td>
        <td class="fval col-num" style="font-size:11.5px;">{{ $rib }}</td>
    </tr>
    <tr>
        <td class="flabel">Pour fin de règlement de</td>
        <td class="fsep">:</td>
        <td class="fval">{{ $reference }}</td>
    </tr>
</table>

<!-- RÉFÉRENCE OP -->
<div class="op-ref">
    &nbsp;&nbsp;&nbsp;&nbsp;
    <strong>OP N° &nbsp;&nbsp; {{ $numOP }} &nbsp;&nbsp; {{ $suffixe }}</strong>
</div>

<!-- TEXTE DE CLÔTURE -->
<div class="closing-text">
    &nbsp;&nbsp;&nbsp;&nbsp;Dans l'attente de votre avis de débit, veuillez agréer,
    Monsieur le Trésorier Général du Royaume, l'expression de nos salutations distinguées.
</div>

<!-- SIGNATURES -->
<table class="sig-table">
    <tr>
        <th style="width:50%;">
            LE DIRECTEUR RÉGIONAL DE L'OFFICE NATIONAL DU CONSEIL<br>
            AGRICOLE DE RABAT-SALÉ-KÉNITRA
        </th>
        <th style="width:50%;">
            LE FONDÉ DE POUVOIR DU TRÉSORIER-PAYEUR DE L'ONCA<br>
            AUPRÈS DE LA DIRECTION RÉGIONALE DE RABAT-SALÉ-KÉNITRA
        </th>
    </tr>
    <tr>
        <td style="height:150px;"></td>
        <td style="height:150px;"></td>
    </tr>
</table>

</body>
</html>
