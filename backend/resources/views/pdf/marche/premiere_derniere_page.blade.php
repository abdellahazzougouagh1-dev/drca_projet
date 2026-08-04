<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>1er &amp; Dernier Feuillet - {{ $doc['num_marche'] }}</title>
    <style>
        @page { margin: 14mm 12mm; size: A4 portrait; }
        * { box-sizing: border-box; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000;
            line-height: 1.35;
            margin: 0;
            padding: 0;
        }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; }

        .header-table { margin-bottom: 8px; }
        .header-table td { border: none; padding: 0 4px; vertical-align: middle; }
        .header-left { width: 28%; text-align: left; }
        .header-center {
            width: 44%;
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            line-height: 1.4;
        }
        .header-right { width: 28%; text-align: right; }
        .header-logo { height: 58px; width: auto; max-width: 130px; }

        .marche-box {
            border: 2px solid #000;
            margin: 10px 0 12px;
            text-align: center;
        }
        .marche-box-title {
            font-size: 14px;
            font-weight: bold;
            padding: 8px 6px;
            border-bottom: 1px solid #000;
        }
        .marche-box-sub {
            font-size: 11px;
            padding: 6px 8px;
        }
        .marche-box-sub strong { font-weight: bold; }

        .text-justify { text-align: justify; margin: 0 0 10px; }
        .section-heading {
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
            margin: 12px 0 8px;
        }
        .dune-part { text-align: right; margin: 6px 0 4px; font-style: italic; }
        .et-label { text-decoration: underline; margin: 8px 0 6px; }

        .titulaire-table { margin: 0 0 16px; }
        .titulaire-table td {
            border: 1px solid #000;
            padding: 5px 7px;
            font-size: 11px;
        }
        .titulaire-table .label-col {
            width: 42%;
            font-weight: normal;
        }
        .titulaire-table .value-col { width: 58%; }

        .footer-phrase {
            text-align: center;
            font-weight: bold;
            font-size: 12px;
            margin: 28px 0 0;
            letter-spacing: 0.3px;
        }
        .page-break { page-break-after: always; }

        .page-title {
            text-align: center;
            font-weight: bold;
            font-size: 13px;
            margin: 4px 0 10px;
            text-decoration: underline;
        }

        .objet-label, .montant-label {
            font-weight: bold;
            text-decoration: underline;
            margin: 10px 0 4px;
        }
        .objet-text { text-align: justify; font-weight: bold; margin-bottom: 12px; }

        .montant-table { margin: 6px 0 20px; }
        .montant-table td {
            border: 1px solid #000;
            padding: 6px 8px;
        }
        .montant-table .lbl { width: 22%; font-weight: bold; }
        .montant-table .sub { width: 18%; font-weight: bold; text-align: center; }
        .montant-table .val { font-weight: bold; }

        .signature-grid { margin-top: 24px; }
        .signature-grid td {
            border: 1px solid #000;
            height: 90px;
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            padding: 8px 6px 0;
            vertical-align: top;
        }
        .signature-banner td {
            border: 1px solid #000;
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            padding: 10px 6px;
            height: auto;
        }
    </style>
</head>
<body>

{{-- ===================== PAGE 1 : PREMIER FEUILLET ===================== --}}

@include('pdf.marche.partials.contrat_header')

<table class="marche-box">
    <tr>
        <td class="marche-box-title">MARCHE N° : {{ $doc['num_marche'] }}</td>
    </tr>
    <tr>
        <td class="marche-box-sub">
            Issu de l'Appel d'offres N°&nbsp;&nbsp;<strong>{{ $doc['num_aoo'] }}</strong>
            &nbsp;&nbsp;&nbsp;en date du&nbsp;&nbsp;<strong>{{ $doc['date_aoo'] ?: '................' }}</strong>
        </td>
    </tr>
</table>

<p class="text-justify">
    Marché passé par appel d'offre ouvert sur offre de prix, séance publique, en application de l'alinéa 2 paragraphe 1
    article 16 et l'alinéa 3 paragraphe 3 article 17 du Décret n°2-12-349 du 8 joumada I 1433 (20 Mars 2013) relatif aux
    marchés publics publié au bulletin officiel n° 6140 du 4 avril 2013.
</p>

<div class="section-heading">Entre les soussignés</div>

<p class="text-justify">
    L'Office nationale du Conseil Agricole (ONCA), représenté par <strong>{{ $doc['directeur_civilite'] }}</strong>,
    Directeur régional du conseil agricole de la région Rabat-Salé-Kénitra, en sa qualité de maître d'ouvrage, sous
    ordonnateur, agissant au nom et pour le compte de l'ONCA et désigné ci-après par le terme «&nbsp;ONCA&nbsp;» ou
    «&nbsp;Maître d'ouvrage&nbsp;»
</p>

<div class="dune-part">D'une part,</div>
<div class="et-label">Et.</div>

<table class="titulaire-table">
    <tr>
        <td class="label-col">Nom et prénom</td>
        <td class="value-col">{{ $doc['nom_gerant'] ?: '—' }}</td>
    </tr>
    <tr>
        <td class="label-col">Qualité</td>
        <td class="value-col">{{ $doc['qualite_responsable'] ?: '—' }}</td>
    </tr>
    <tr>
        <td class="label-col">Agissant au nom de</td>
        <td class="value-col">{{ $doc['nom_entreprise'] ?: '—' }}</td>
    </tr>
    <tr>
        <td class="label-col">Faisant élection de domicile au</td>
        <td class="value-col">{{ $doc['adresse_entreprise'] ?: '—' }}</td>
    </tr>
    <tr>
        <td class="label-col">Inscrit au Registre de commerce de la ville de</td>
        <td class="value-col">{{ $doc['ville_rc'] ?: '—' }}</td>
    </tr>
    <tr>
        <td class="label-col">Sous le n°</td>
        <td class="value-col">{{ $doc['num_rc'] ?: '—' }}</td>
    </tr>
    <tr>
        <td class="label-col">Patente Sous le n°</td>
        <td class="value-col">{{ $doc['patente'] ?: '—' }}</td>
    </tr>
    <tr>
        <td class="label-col">Affilié à la C.N.S.S. sous le n°</td>
        <td class="value-col">{{ $doc['cnss'] ?: '—' }}</td>
    </tr>
    <tr>
        <td class="label-col">Identification fiscale</td>
        <td class="value-col">{{ $doc['if'] ?: '—' }}</td>
    </tr>
    <tr>
        <td class="label-col">ICE</td>
        <td class="value-col">{{ $doc['ice'] ?: '—' }}</td>
    </tr>
    <tr>
        <td class="label-col">Titulaire d'un compte bancaire ouvert au nom de</td>
        <td class="value-col">{{ $doc['titulaire_compte'] ?: '—' }}</td>
    </tr>
    <tr>
        <td class="label-col">A la Banque</td>
        <td class="value-col">{{ $doc['banque'] ?: '—' }}</td>
    </tr>
    <tr>
        <td class="label-col">Agence</td>
        <td class="value-col">{{ $doc['agence_bancaire'] ?: '—' }}</td>
    </tr>
    <tr>
        <td class="label-col">Sous le n°</td>
        <td class="value-col">{{ $doc['rib'] ?: '—' }}</td>
    </tr>
</table>

<div class="footer-phrase">IL A ÉTÉ ARRÊTÉ ET CONVENU CE QUI SUIT</div>
<div class="page-break"></div>

{{-- ===================== PAGE 2 : DERNIER FEUILLET ===================== --}}

@include('pdf.marche.partials.contrat_header')

<div class="page-title">Dernier Feuillet</div>

<table class="marche-box">
    <tr>
        <td class="marche-box-title">MARCHE N° : {{ $doc['num_marche'] }}</td>
    </tr>
    <tr>
        <td class="marche-box-sub">
            Issu de l'Appel d'offres N°&nbsp;&nbsp;<strong>{{ $doc['num_aoo'] }}</strong>
            &nbsp;&nbsp;&nbsp;en date du&nbsp;&nbsp;<strong>{{ $doc['date_aoo'] ?: '................' }}</strong>
        </td>
    </tr>
</table>

<p class="text-justify">
    Marché passé par appel d'offre ouvert sur offre de prix, séance publique, en application de l'alinéa 2 paragraphe 1
    article 16 et l'alinéa 3 paragraphe 3 article 17 du Décret n°2-12-349 du 8 joumada I 1433 (20 Mars 2013) relatif aux
    marchés publics publié au bulletin officiel n° 6140 du 4 avril 2013.
</p>

<div class="objet-label">Objet du marché</div>
<div class="objet-text">{{ $doc['objet_marche'] ?: '—' }}</div>

<div class="montant-label">Montant du marché</div>
<table class="montant-table">
    <tr>
        <td class="lbl">Montant du marché</td>
        <td class="sub">En Chiffres</td>
        <td class="val">{{ $doc['montant_formate'] }} MAD</td>
    </tr>
    <tr>
        <td class="lbl">&nbsp;</td>
        <td class="sub">En Lettres</td>
        <td class="val">{{ $doc['montant_lettres'] }}</td>
    </tr>
</table>

<table class="signature-grid">
    <tr>
        <td style="width: 50%;">Dressé Par:</td>
        <td style="width: 50%;">Vérifié par:</td>
    </tr>
    <tr>
        <td>Lu et accepté par l'entrepreneur</td>
        <td>Signé par le Maitre d'ouvrage</td>
    </tr>
    <tr class="signature-banner">
        <td colspan="2">
            Approuvé par le Directeur régional du conseil agricole Rabat-Salé-Kénitra
        </td>
    </tr>
</table>

</body>
</html>
