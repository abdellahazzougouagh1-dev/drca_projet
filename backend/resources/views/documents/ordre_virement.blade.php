<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ordre de Virement</title>
    <style>
        @page { margin: 8mm 12mm 8mm 12mm; size: A4 portrait; }
        * { box-sizing: border-box; }
        body { font-family: "DejaVu Sans", Arial, sans-serif; font-size: 10.5px; line-height: 1.35; margin: 0; padding: 0; color: #000; }

        .hdr { width: 100%; border-collapse: collapse; margin-bottom: 3px; }
        .hdr td { vertical-align: middle; padding: 0; }
        .hdr-center { text-align: center; font-size: 11.5px; font-weight: bold; line-height: 1.4; }
        .hdr-divider { border-bottom: 1.5px solid #000; margin: 4px 0 10px 0; }

        .ref-line { font-size: 10.5px; margin-bottom: 20px; }

        .main-title {
            text-align: center; font-size: 18px; font-weight: bold;
            letter-spacing: 1px; margin-bottom: 5px;
            text-decoration: underline;
        }
        .sub-title {
            text-align: center; font-size: 13px; font-weight: bold;
            margin-bottom: 5px; letter-spacing: 0.3px;
        }
        .dest-title {
            text-align: center; font-size: 12.5px; font-weight: bold;
            letter-spacing: 0.5px; text-transform: uppercase;
            margin: 0 auto 20px auto;
        }

        .body-text { font-size: 10.5px; text-align: justify; margin-bottom: 14px; line-height: 1.5; }
        .field-row { display: table; width: 100%; margin-bottom: 8px; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .info-table td { padding: 5px 0; font-size: 10.5px; vertical-align: top; }
        .info-table .flabel { width: 30%; font-weight: normal; }
        .info-table .fsep   { width: 3%; text-align: center; }
        .info-table .fval   { width: 67%; font-weight: bold; }

        .op-ref { font-size: 10.5px; margin-bottom: 14px; }
        .closing-text { font-size: 10.5px; text-align: justify; line-height: 1.55; margin-bottom: 20px; }

        .sig-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .sig-table th {
            background: #4ca84e; color: #fff; font-weight: bold;
            text-align: center; padding: 8px 5px; border: 1px solid #000;
            font-size: 9.5px; text-transform: uppercase; letter-spacing: 0.3px;
            line-height: 1.4;
        }
        .sig-table td { border: 1px solid #000; height: 80px; vertical-align: top; }
    </style>
</head>
<body>

@php
    $logoOncaSrc   = file_exists(public_path('images/logo-onca.png'))   ? 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('images/logo-onca.png')))   : '';
    $sceauMarocSrc = file_exists(public_path('images/sceau-maroc.png')) ? 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('images/sceau-maroc.png'))) : '';

    $exercice    = $consultation->annee ?? date('Y');
    $suffixe     = '/INV/DRCA-RSK/' . $exercice;

    $numOV       = data_get($documentData, 'numero_ov', '');
    $numOP       = data_get($documentData, 'numero_op') ?: '';
    $dateDoc     = data_get($documentData, 'date_document')
                   ? \Carbon\Carbon::parse(data_get($documentData, 'date_document'))->format('d/m/Y')
                   : '';

    $compteCourant  = data_get($documentData, 'compte_courant', '310330100602470154760181');
    $libelleCompte  = data_get($documentData, 'libelle_compte', 'ONCA DR RABAT-SALE-KENITRA INVESTISSEMENT');

    $montantOv      = (float)(data_get($documentData, 'montant_ov') ?: $doc['total_ttc'] ?? 0);
    $montantLettres = \App\Helpers\NumberToWordsHelper::toFrenchMoneyWords($montantOv);

    $beneficiaire   = data_get($documentData, 'societe') ?: ($doc['societe'] ?? '');
    $rib            = data_get($documentData, 'rib') ?: ($doc['rib'] ?? '');
    $reference      = data_get($documentData, 'reference_bc') ?: ($doc['numero_bc'] ?? '');
@endphp

<!-- HEADER -->
<table class="hdr">
    <tr>
        <td style="width:22%; text-align:left;">
            @if($logoOncaSrc)<img src="{{ $logoOncaSrc }}" style="height:55px; width:auto;">@endif
        </td>
        <td style="width:56%;" class="hdr-center">
            Direction régionale du Conseil agricole<br>
            <span style="font-size:12px;">Rabat-Salé-Kénitra</span>
        </td>
        <td style="width:22%; text-align:right;">
            @if($sceauMarocSrc)<img src="{{ $sceauMarocSrc }}" style="height:55px; width:auto;">@endif
        </td>
    </tr>
</table>
<div class="hdr-divider"></div>

<!-- RÉFÉRENCE OV -->
<div class="ref-line">
    /Référence: OV N° &nbsp;&nbsp;
    <strong style="font-size:13px;">{{ $numOV }}</strong>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; {{ $suffixe }}
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    Date: &nbsp;&nbsp; {{ $dateDoc }}
</div>

<!-- TITRE PRINCIPAL -->
<div class="main-title">ORDRE DE VIREMENT</div>
<div class="sub-title">A</div>
<div class="dest-title">
    MONSIEUR LE TRESORIER GENERAL DU ROYAUME<br>
    CHEF DE L'AGENCE BANCAIRE DE KENITRA
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
        <td class="fval" style="font-size:12px;">{{ number_format($montantOv, 2, ',', ' ') }}</td>
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
        <td class="fval" style="font-family:monospace; font-size:10px;">{{ $rib }}</td>
    </tr>
    <tr>
        <td class="flabel">Pour fin de règlement de :</td>
        <td class="fsep"></td>
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
    Monsieur le Trésorier Général Du Royaume, l'expression de nos salutations distinguées.
</div>

<!-- SIGNATURES -->
<table class="sig-table">
    <tr>
        <th style="width:50%;">
            LE DIRECTEUR REGIONAL DE L'OFFICE NATIONALE DU CONSEIL<br>
            AGRICOLE DU Rabat-Salé-Kénitra
        </th>
        <th style="width:50%;">
            LE FONDE DE POUVOIRS DU TRESORIER PAYEUR DE L'ONCA<br>
            AUPRES DE LA DIRECTION REGIONALE DU Rabat-Salé-Kénitra
        </th>
    </tr>
    <tr>
        <td style="height:90px;"></td>
        <td style="height:90px;"></td>
    </tr>
</table>

</body>
</html>
