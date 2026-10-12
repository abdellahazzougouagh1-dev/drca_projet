<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Extrait du Procès-Verbal - {{ $num_aoo }}</title>
    <style>
        @page {
            margin-top: 12mm;
            margin-bottom: 22mm;
            margin-left: 15mm;
            margin-right: 15mm;
            footer: html_pageFooter;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 10.5pt;
            line-height: 1.35;
            color: #000000;
        }

        /* HEADER */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .header-table td {
            vertical-align: top;
            border: none;
            padding: 0;
        }

        .main-title-block {
            text-align: center;
            font-weight: bold;
            font-size: 11pt;
            text-transform: uppercase;
            margin-top: 6px;
            margin-bottom: 4px;
        }
        .sub-title-block {
            text-align: center;
            font-weight: bold;
            font-size: 10.5pt;
            margin-bottom: 4px;
        }
        .asterisks {
            text-align: center;
            font-size: 11pt;
            margin-bottom: 14px;
        }

        /* BULLET ITEMS */
        .item-row {
            margin-bottom: 7px;
            text-align: justify;
        }
        .bullet {
            font-family: 'DejaVu Sans', 'Arial', sans-serif;
            font-weight: bold;
            margin-right: 4px;
        }
        .item-title {
            font-weight: bold;
        }
        .sub-list {
            margin-left: 20px;
            margin-top: 3px;
            margin-bottom: 6px;
        }
        .sub-list-item {
            margin-bottom: 2px;
            text-transform: uppercase;
            font-size: 10pt;
        }

        /* TABLES */
        .pv-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            margin-bottom: 10px;
            border: 1px solid #000000;
        }
        .pv-table th, .pv-table td {
            border: 1px solid #000000;
            padding: 4px 6px;
            font-size: 9.5pt;
            vertical-align: middle;
        }
        .pv-table th {
            font-weight: bold;
            background-color: #ffffff;
            text-align: center;
        }
        .pv-table td.col-nom {
            text-align: left;
            text-transform: uppercase;
            font-weight: 500;
            width: 45%;
        }
        .pv-table td.col-montant {
            text-align: right;
            font-family: 'Courier New', Courier, monospace;
            font-weight: bold;
            width: 27%;
            white-space: nowrap;
        }

        /* SIGNATURE & CLOSING */
        .closing-block {
            margin-top: 15px;
            page-break-inside: avoid;
        }
        .closing-city-date {
            text-align: center;
            font-weight: bold;
            font-size: 10.5pt;
            margin-bottom: 12px;
        }
        .closing-title {
            text-align: center;
            font-weight: bold;
            font-size: 10pt;
            margin-bottom: 15px;
        }
        .closing-role {
            text-align: center;
            font-weight: bold;
            font-size: 10.5pt;
            text-transform: uppercase;
        }
    </style>
</head>
<body>

    @php
        $logoOncaPath = public_path('images/logo-onca.png');
        $logoOncaSrc = file_exists($logoOncaPath) ? 'data:' . mime_content_type($logoOncaPath) . ';base64,' . base64_encode(file_get_contents($logoOncaPath)) : '';
        $logoRoyaumePath = public_path('images/sceau-maroc.png');
        $logoRoyaumeSrc = file_exists($logoRoyaumePath) ? 'data:' . mime_content_type($logoRoyaumePath) . ';base64,' . base64_encode(file_get_contents($logoRoyaumePath)) : '';
        $footerImgPath = public_path('images/info_DRCA.png');
        $footerImgSrc = file_exists($footerImgPath) ? 'data:' . mime_content_type($footerImgPath) . ';base64,' . base64_encode(file_get_contents($footerImgPath)) : '';
    @endphp

    <!-- HEADER LOGOS -->
    <table class="header-table">
        <tr>
            <td style="width: 35%; text-align: left;">
                @if($logoOncaSrc)
                    <img src="{{ $logoOncaSrc }}" style="height: 55px;" alt="ONCA">
                @endif
            </td>
            <td style="width: 30%; text-align: center;"></td>
            <td style="width: 35%; text-align: right;">
                @if($logoRoyaumeSrc)
                    <img src="{{ $logoRoyaumeSrc }}" style="height: 55px;" alt="Royaume du Maroc">
                @endif
            </td>
        </tr>
    </table>

    <!-- TITRES OFFICIELS -->
    <div class="main-title-block">
        DIRECTION REGIONALE DU CONSEIL AGRICOLE DE LA REGION RABAT-SALE-KENITRA
    </div>
    <div class="sub-title-block">
        Extrait Du Procès-Verbal De La Séance De L’appel D’offres Ouvert simplifié sur offre de prix N°{{ $num_aoo }}
    </div>
    <div class="asterisks">
        *******
    </div>

    <!-- 1. OBJET -->
    <div class="item-row">
        <span class="bullet">➢</span> <span class="item-title">Objet :</span> {{ $objet }}
    </div>

    <!-- 2. MAÎTRE D'OUVRAGE -->
    <div class="item-row">
        <span class="bullet">➢</span> <span class="item-title">Maître d’ouvrage :</span> Direction Régionale Du Conseil Agricole De La Région Rabat-Sale-Kenitra
    </div>

    <!-- 3. DATE D'OUVERTURE -->
    <div class="item-row">
        <span class="bullet">➢</span> <span class="item-title">Date d'ouverture des plis :</span> {{ $date_ouverture }}
    </div>

    <!-- 4. LIEU D'OUVERTURE -->
    <div class="item-row">
        <span class="bullet">➢</span> <span class="item-title">Lieu d'ouverture des plis :</span> {{ $lieu_ouverture }}
    </div>

    <!-- 5. JOURNAUX -->
    <div class="item-row">
        <span class="bullet">➢</span> <span class="item-title">Journal ou journaux ayant publié l'avis de publicité :</span>
        <div class="sub-list">
            <div class="sub-list-item">- {{ $publication_journaux }}</div>
        </div>
    </div>

    <!-- 6. DATE PUBLICATION PORTAIL -->
    <div class="item-row">
        <span class="bullet">➢</span> <span class="item-title">Date de publication au portail des marchés publics :</span> {{ $date_publication_portail }}
    </div>

    <!-- 7. LISTE DES CONCURRENTS AYANT DÉPOSÉ LEURS PLIS -->
    <div class="item-row">
        <span class="bullet">➢</span> <span class="item-title">Liste des concurrents ayant déposé leurs plis :</span>
        <div class="sub-list">
            @forelse($concurrents_deposes as $c)
                <div class="sub-list-item">- {{ $c }}</div>
            @empty
                <div class="sub-list-item">- Néant</div>
            @endforelse
        </div>
    </div>

    <!-- 8. LISTE DES CONCURRENTS ÉCARTÉS ADMIN/TECH -->
    <div class="item-row">
        <span class="bullet">➢</span> <span class="item-title">Liste des concurrents écartés à l'issue de l'examen des dossiers administratifs et techniques :</span>
        <div class="sub-list">
            @forelse($concurrents_ecartes_admin_tech as $c)
                <div class="sub-list-item">- {{ $c }}</div>
            @empty
                <div class="sub-list-item">- Néant</div>
            @endforelse
        </div>
    </div>

    <!-- 9. LISTE DES CONCURRENTS ADMIS SANS RÉSERVE -->
    <div class="item-row">
        <span class="bullet">➢</span> <span class="item-title">Liste des concurrents admis sans réserve :</span>
        <div class="sub-list">
            @forelse($concurrents_admis_sans_reserve as $c)
                <div class="sub-list-item">- {{ $c }}</div>
            @empty
                <div class="sub-list-item">- Néant</div>
            @endforelse
        </div>
    </div>

    <!-- 10. MONTANT DES ACTES D'ENGAGEMENT -->
    <div class="item-row">
        <span class="bullet">➢</span> <span class="item-title">Montant des actes d'engagement des concurrents :</span>
        <table class="pv-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Concurrents</th>
                    <th style="width: 50%;">Montant de l’Acte d’Engagement (en DH TTC)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($actes_engagement as $act)
                    <tr>
                        <td class="col-nom">{{ $act['nom'] }}</td>
                        <td class="col-montant">{{ $act['montant_ttc_format'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" style="text-align: center;">Néant</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- 11. VÉRIFICATION DES MONTANTS -->
    <div class="item-row">
        <span class="bullet">➢</span> <span class="item-title">Vérification des montants des actes d'engagement des concurrents :</span>
        <table class="pv-table">
            <thead>
                <tr>
                    <th style="width: 40%;">Concurrents</th>
                    <th style="width: 30%;">Montant de l’Acte<br>d’Engagement (en DH TTC)</th>
                    <th style="width: 30%;">Montant de l’Acte d’Engagement<br>réctifié (en DH TTC)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($actes_engagement_rectifies as $act)
                    <tr>
                        <td class="col-nom">{{ $act['nom'] }}</td>
                        <td class="col-montant">{{ $act['montant_ttc_format'] }}</td>
                        <td class="col-montant">{{ $act['montant_rectifie_ttc_format'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" style="text-align: center;">Néant</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- 12. CONCURRENTS ÉCARTÉS FINANCIER (LE CAS ÉCHÉANT) -->
    @if(count($concurrents_ecartes_financier) > 0)
        <div class="item-row">
            <span class="item-title">- Liste des concurrents écartés :</span>
            <div class="sub-list">
                @foreach($concurrents_ecartes_financier as $c)
                    <div class="sub-list-item">- {{ $c }}</div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- 13. CONCURRENT RETENU -->
    <div class="item-row">
        <span class="bullet">➢</span> <span class="item-title">Concurrent retenu :</span>
        <table class="pv-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Concurrent</th>
                    <th style="width: 50%;">Montant de l’acte d’engagement (en DH TTC)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="col-nom">- {{ $concurrent_retenu['nom'] }}</td>
                    <td class="col-montant">{{ $concurrent_retenu['montant_ttc_format'] }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- 14. JUSTIFICATION & DATE ACHÈVEMENT -->
    <div class="item-row">
        <span class="bullet">➢</span> <span class="item-title">Justification du choix de l’attributaire :</span> {{ $justification }}
    </div>

    <div class="item-row">
        <span class="bullet">➢</span> <span class="item-title">Date d'achèvement des travaux de la commission :</span> {{ $date_achevement }}.
    </div>

    <!-- BLOC SIGNATURE & CLÔTURE -->
    <div class="closing-block">
        <div class="closing-city-date">
            Fait à Kénitra le {{ $date_pv }}
        </div>
        <div class="closing-title">
            Extrait Du Procès-Verbal De La Séance<br>
            De L’appel D’offres Ouvert Simplifié sur offre de prix N°{{ $num_aoo }}
        </div>
        <div class="closing-role">
            MAITRE D’OUVRAGE
        </div>
    </div>

    <!-- FOOTER OFFICIEL SUR TOUTES LES PAGES -->
    <htmlpagefooter name="pageFooter">
        <div style="text-align: center; border-top: 1px solid #999; padding-top: 3px;">
            @if($footerImgSrc)
                <img src="{{ $footerImgSrc }}" style="width: 100%; height: auto;" alt="Footer ONCA">
            @endif
        </div>
    </htmlpagefooter>
    <sethtmlpagefooter name="pageFooter" value="on" />

</body>
</html>
