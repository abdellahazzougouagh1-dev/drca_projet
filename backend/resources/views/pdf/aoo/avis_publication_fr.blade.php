<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Avis de Publication FR</title>
    <style>
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 14px;
            color: #000;
            margin: 0;
            padding: 0;
            line-height: 1.5;
        }
        .page-wrapper {
            margin: 15px;
            padding: 15px;
        }
        .header-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .header-table td {
            vertical-align: middle;
        }
        .header-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
        }
        .main-title-container {
            text-align: center;
            margin: 30px 0;
        }
        .main-title {
            display: inline-block;
            font-size: 16px;
            font-weight: bold;
            text-decoration: underline;
        }
        .sub-title {
            display: block;
            font-size: 16px;
            font-weight: bold;
            text-decoration: underline;
            margin-top: 5px;
            font-style: italic;
        }
        .content-paragraph {
            text-align: justify;
            margin-bottom: 15px;
        }
        .list-items {
            margin-bottom: 15px;
            padding-left: 40px;
        }
        .list-items li {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    @php
        $logoOncaPath = public_path('images/logo-onca.png');
        $logoOncaSrc = file_exists($logoOncaPath) ? 'data:' . mime_content_type($logoOncaPath) . ';base64,' . base64_encode(file_get_contents($logoOncaPath)) : '';
        
        $sceauMarocPath = public_path('images/sceau-maroc.png');
        $sceauMarocSrc = file_exists($sceauMarocPath) ? 'data:' . mime_content_type($sceauMarocPath) . ';base64,' . base64_encode(file_get_contents($sceauMarocPath)) : '';
    @endphp

    <div class="page-wrapper">
        <div class="header-table">
            <table style="width: 100%;">
                <tr>
                    <td style="width: 15%; vertical-align: top;">
                        @if($logoOncaSrc)
                            <img src="{{ $logoOncaSrc }}" height="120" style="display: block; margin-top: -20px;" alt="ONCA">
                        @endif
                    </td>
                    <td style="width: 70%; vertical-align: middle; padding-top: 10px; white-space: nowrap;" class="header-title">
                        <span style="font-size: 18px;">Office National du Conseil Agricole</span><br>
                        <span style="font-size: 18px;">Direction Régionale du Conseil Agricole de Rabat-Salé-Kénitra</span><br>
                        ********************
                    </td>
                    <td style="width: 15%; vertical-align: top; text-align: right;">
                        @if($sceauMarocSrc)
                            <img src="{{ $sceauMarocSrc }}" height="120" style="display: block; margin-left: auto; margin-top: -20px;" alt="Royaume du Maroc">
                        @endif
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="border-bottom: 2px solid #000; padding-top: 5px;"></td>
                </tr>
            </table>
        </div>

        <div class="main-title-container">
            <span class="main-title">AVIS D'APPEL D'OFFRES OUVERT NATIONAL</span><br>
            <span class="main-title">SUR OFFRES DE PRIX N° {{ $aoo->num_aoo }}</span><br>
            <span class="sub-title">(Séance publique)</span>
        </div>
        
        @php
            $dateOuverture = $aoo->date_ouverture ? \Carbon\Carbon::parse($aoo->date_ouverture)->format('d/m/Y') : '..../..../........';
            $heureOuverture = $aoo->heure_ouverture ? \Carbon\Carbon::parse($aoo->heure_ouverture)->format('H') . ' Heures' : '.... Heures';
            $lieuOuverture = $aoo->lieu_ouverture ?: "la salle des réunions au siège de la Direction Régionale de l'Office National du Conseil Agricole de Rabat-Salé-Kénitra, sis à angle avenue Mohamed V et Rue Sebta Kenitra";
        @endphp

        <div class="content-paragraph">
            Le <span style="font-weight: bold;">{{ $dateOuverture }} à {{ $heureOuverture }}</span>, il sera procédé, à {{ $lieuOuverture }} à l'ouverture des plis relatif à l'appel d'offres ouvert national sur offres de prix n°{{ $aoo->num_aoo }}, 
            ayant pour objet {{ lcfirst($aoo->objet) }}
            @if($aoo->lots && $aoo->lots->count() > 0)
                en {{ $aoo->lots->count() }} lots (
                @foreach($aoo->lots as $index => $lot)
                    Lot {{ $index + 1 }} : {{ lcfirst($lot->objet) }}@if(!$loop->last) et @endif
                @endforeach
                ).
            @else
                en lot unique.
            @endif
        </div>

        <div class="content-paragraph">
            Le dossier d'appel d'offres doit être téléchargé à partir du portail des marchés publics accessible à l'adresse : <a href="http://www.marchespublics.gov.ma" style="color:#000; text-decoration: underline;">www.marchespublics.gov.ma</a>
        </div>

        <div class="content-paragraph">
            L'estimation des coûts des prestations établie par le maitre d'ouvrage est fixée à la somme de :
        </div>
        
        <ul class="list-items" style="list-style-type: none;">
            @if($aoo->lots && $aoo->lots->count() > 0)
                @foreach($aoo->lots as $index => $lot)
                    @php
                        $montantTtc = (float)($lot->estimation ?? 0);
                        if ($montantTtc <= 0 && $lot->items && $lot->items->count() > 0) {
                            $totalHt = 0;
                            foreach($lot->items as $item) {
                                $totalHt += ((float)$item->quantite * (float)$item->prix_unitaire_ht);
                            }
                            $montantTtc = $totalHt * 1.20;
                        }
                        if ($montantTtc <= 0 && !empty($aoo->budget)) {
                            $montantTtc = (float)$aoo->budget;
                        }
                        $montantLettres = class_exists('\NumberFormatter') && $montantTtc > 0 ? (new \NumberFormatter('fr', \NumberFormatter::SPELLOUT))->format($montantTtc) : ($montantTtc > 0 ? \App\Helpers\NumberToWordsHelper::toFrenchWords($montantTtc) : 'zéro');
                    @endphp
                    <li>- <span style="font-weight: bold;">@if($aoo->lots->count() > 1) Lot {{ $index + 1 }} : @endif {{ ucfirst($montantLettres) }} ({{ number_format($montantTtc, 2, ',', ' ') }}) Dirhams TTC.</span></li>
                @endforeach
            @else
                @php
                    $montantTtc = (float)($aoo->budget ?? $aoo->estimation_administrative ?? 0);
                    $montantLettres = class_exists('\NumberFormatter') && $montantTtc > 0 ? (new \NumberFormatter('fr', \NumberFormatter::SPELLOUT))->format($montantTtc) : ($montantTtc > 0 ? \App\Helpers\NumberToWordsHelper::toFrenchWords($montantTtc) : 'zéro');
                @endphp
                <li>- <span style="font-weight: bold;">{{ ucfirst($montantLettres) }} ({{ number_format($montantTtc, 2, ',', ' ') }}) Dirhams TTC.</span></li>
            @endif
        </ul>

        <div class="content-paragraph">
            Le cautionnement provisoire est fixé à la somme de : 
        </div>
        <ul class="list-items" style="list-style-type: none;">
            @if($aoo->lots && $aoo->lots->count() > 0)
                @foreach($aoo->lots as $index => $lot)
                    @php
                        $caution = (float)($lot->cautionnement_provisoire ?? 0);
                        if ($caution <= 0 && !empty($aoo->caution_provisoire)) {
                            $caution = (float)$aoo->caution_provisoire;
                        }
                        $cautionLettres = class_exists('\NumberFormatter') && $caution > 0 ? (new \NumberFormatter('fr', \NumberFormatter::SPELLOUT))->format($caution) : ($caution > 0 ? \App\Helpers\NumberToWordsHelper::toFrenchWords($caution) : 'zéro');
                    @endphp
                    <li>- <span style="font-weight: bold;">@if($aoo->lots->count() > 1) Lot {{ $index + 1 }} : @endif {{ ucfirst($cautionLettres) }} ({{ number_format($caution, 2, ',', ' ') }}) Dirhams.</span></li>
                @endforeach
            @else
                @php
                    $caution = (float)($aoo->caution_provisoire ?? 0);
                    $cautionLettres = class_exists('\NumberFormatter') && $caution > 0 ? (new \NumberFormatter('fr', \NumberFormatter::SPELLOUT))->format($caution) : ($caution > 0 ? \App\Helpers\NumberToWordsHelper::toFrenchWords($caution) : 'zéro');
                @endphp
                <li>- <span style="font-weight: bold;">{{ ucfirst($cautionLettres) }} ({{ number_format($caution, 2, ',', ' ') }}) Dirhams.</span></li>
            @endif
        </ul>

        <div class="content-paragraph">
            Le contenu, la présentation ainsi que le dépôt des dossiers des concurrents doivent être conformes aux 
            dispositions des articles 30 à 34 et <span style="font-weight: bold;">135</span> du décret n°2-22-431 du 15 chaabane 1444 (8 mars 2023) relatif aux 
            marchés publics ainsi que les <span style="font-weight: bold;">articles 9 et 12</span> de l'arrêté du Ministre Délégué auprès de la Ministre de l'économie 
            et des finances, chargé du budget n° 1692-23 du 4 hija 1444 (23 juin 2023) relatif à la dématérialisation des 
            procédures, des documents et des pièces relatives aux marchés publics.
        </div>

        <div class="content-paragraph">
            Les concurrents doivent déposer leurs dossiers par voie électronique dans le portail des marchés publics 
            accessible à l'adresse : <a href="http://www.marchespublics.gov.ma" style="color:#000; text-decoration: underline;">www.marchespublics.gov.ma</a>
        </div>

        <div class="content-paragraph">
            Le cautionnement provisoire doit être constitué de <span style="font-weight: bold;">façon dématérialisée</span> et ce, conformément aux dispositions 
            de l'arrêté 1692-23 du 23 juin 2023 susvisé.
        </div>

        <div class="content-paragraph">
            Les pièces justificatives à fournir sont celles prévues par <span style="font-weight: bold;">les articles 08 et 10</span> du règlement de consultation.
        </div>

    </div>
</body>
</html>
