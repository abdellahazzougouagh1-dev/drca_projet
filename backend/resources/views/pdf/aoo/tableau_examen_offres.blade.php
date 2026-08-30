<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau d'Examen des Offres - {{ $num_aoo }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 8pt;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }
        
        /* ENTETE */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            border-bottom: 1.5px solid #0f172a;
            padding-bottom: 4px;
        }
        .header-table td {
            vertical-align: middle;
            border: none;
            padding: 0;
        }
        .header-center {
            text-align: center;
        }
        .main-title {
            font-size: 11pt;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .sub-title {
            font-size: 8pt;
            color: #475569;
            font-weight: bold;
        }

        /* SECTION TITLES */
        .section-header {
            background-color: #0f172a;
            color: #ffffff;
            font-size: 8pt;
            font-weight: bold;
            padding: 3px 6px;
            margin-top: 4px;
            margin-bottom: 3px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        /* IDENTIFICATION BOX */
        .ident-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            border: 1px solid #94a3b8;
        }
        .ident-table td {
            border: 1px solid #cbd5e1;
            padding: 2.5px 5px;
            font-size: 7.5pt;
            vertical-align: top;
        }
        .ident-lbl {
            background-color: #f1f5f9;
            font-weight: bold;
            color: #334155;
            width: 17%;
        }
        .ident-val {
            color: #0f172a;
            width: 33%;
        }

        /* GRILLE D'EXAMEN DES OFFRES */
        .grid-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid #334155;
            margin-top: 2px;
        }
        .grid-table th {
            border: 1px solid #475569;
            background-color: #ede8db;
            color: #0f172a;
            font-size: 7.5pt;
            font-weight: bold;
            text-align: center;
            padding: 3px 2px;
            vertical-align: middle;
        }
        .grid-table th.sub-th {
            font-size: 7pt;
            background-color: #e5dfcf;
            padding: 2px 2px;
        }
        .grid-table td {
            border: 1px solid #94a3b8;
            padding: 3px 3px;
            font-size: 7pt;
            vertical-align: middle;
        }

        /* STATUS BADGES & TEXTS */
        .statut-admis {
            color: #047857;
            font-weight: bold;
        }
        .statut-reserve {
            color: #b45309;
            font-weight: bold;
        }
        .statut-rejet {
            color: #b91c1c;
            font-weight: bold;
        }
        .statut-non-examine {
            color: #64748b;
            font-style: italic;
        }

        .motif-txt {
            font-size: 6.5pt;
            color: #334155;
            line-height: 1.1;
        }
        .motif-reserve-txt {
            font-size: 6.5pt;
            color: #b45309;
            font-weight: bold;
            line-height: 1.1;
        }
        .motif-rejet-txt {
            font-size: 6.5pt;
            color: #b91c1c;
            font-weight: bold;
            line-height: 1.1;
        }
        .montant-val {
            font-weight: bold;
            text-align: right;
            white-space: nowrap;
            color: #0f172a;
        }
    </style>
</head>
<body>

    @php
        $logoOncaPath = public_path('images/logo-onca.png');
        $logoOncaSrc = file_exists($logoOncaPath) ? 'data:' . mime_content_type($logoOncaPath) . ';base64,' . base64_encode(file_get_contents($logoOncaPath)) : '';
        $logoRoyaumePath = public_path('images/royaume-maroc.png');
        $logoRoyaumeSrc = file_exists($logoRoyaumePath) ? 'data:' . mime_content_type($logoRoyaumePath) . ';base64,' . base64_encode(file_get_contents($logoRoyaumePath)) : '';
    @endphp

    <!-- HEADER OFFICIEL -->
    <table class="header-table">
        <tr>
            <td style="width: 25%; text-align: left;">
                @if($logoOncaSrc)
                    <img src="{{ $logoOncaSrc }}" style="height: 38px;" alt="ONCA">
                @else
                    <div style="font-size: 8pt; font-weight: bold; color: #047857;">ROYAUME DU MAROC<br>ONCA</div>
                @endif
            </td>
            <td style="width: 50%; text-align: center;">
                <div class="main-title">Direction Régionale du Conseil Agricole</div>
                <div class="sub-title">Rabat - Salé - Kénitra (DRCA-RSK)</div>
            </td>
            <td style="width: 25%; text-align: right;">
                @if($logoRoyaumeSrc)
                    <img src="{{ $logoRoyaumeSrc }}" style="height: 38px;" alt="Maroc">
                @else
                    <div style="font-size: 7.5pt; color: #475569;">Édition officielle<br><strong>{{ $date_edition }}</strong></div>
                @endif
            </td>
        </tr>
    </table>

    <!-- 1. IDENTIFICATION DE LA CONSULTATION -->
    <div class="section-header">1. Identification de la Consultation</div>
    <table class="ident-table">
        <tr>
            <td class="ident-lbl">Référence :</td>
            <td class="ident-val"><strong>{{ $num_aoo }}</strong></td>
            <td class="ident-lbl">Mode de passation :</td>
            <td class="ident-val">{{ $mode_passation }}</td>
        </tr>
        <tr>
            <td class="ident-lbl">Objet :</td>
            <td class="ident-val" colspan="3">{{ $objet }}</td>
        </tr>
        <tr>
            <td class="ident-lbl">Date & Heure limite :</td>
            <td class="ident-val">{{ $date_heure_limite ?: '-' }}</td>
            <td class="ident-lbl">Lieu d'ouverture :</td>
            <td class="ident-val">{{ $lieu }}</td>
        </tr>
        <tr>
            <td class="ident-lbl">Publication Journaux :</td>
            <td class="ident-val">{{ $publication_journaux }}</td>
            <td class="ident-lbl">Portail Marchés :</td>
            <td class="ident-val">{{ $publication_portail }}</td>
        </tr>
        <tr>
            <td class="ident-lbl">Estimation de l'Admin :</td>
            <td class="ident-val"><strong style="color: #1e3a8a;">{{ $estimation > 0 ? number_format($estimation, 2, ',', ' ') . ' DH HT' : '-' }}</strong></td>
            <td class="ident-lbl">Seuils réglementaires :</td>
            <td class="ident-val">
                Basse (80%) : <strong>{{ $seuil_bas > 0 ? number_format($seuil_bas, 2, ',', ' ') . ' DH' : '-' }}</strong> | 
                Excessive (120%) : <strong>{{ $seuil_haut > 0 ? number_format($seuil_haut, 2, ',', ' ') . ' DH' : '-' }}</strong>
            </td>
        </tr>
    </table>

    <!-- 2. ENREGISTREMENT ET EXAMEN DES OFFRES -->
    <div class="section-header">2. Enregistrement et examen des offres</div>
    <table class="grid-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 17%;">Nom du dépositaire de l'offre</th>
                <th colspan="2" style="width: 18%;">Dossier administratif et technique</th>
                <th colspan="2" style="width: 18%;">Offre technique</th>
                <th colspan="3" style="width: 23%;">Offre financière</th>
                <th colspan="3" style="width: 24%;">Après vérification</th>
            </tr>
            <tr>
                <!-- Dossier Admin -->
                <th class="sub-th" style="width: 9%;">Statut</th>
                <th class="sub-th" style="width: 9%;">Motif / Réserve</th>
                <!-- Offre Tech -->
                <th class="sub-th" style="width: 9%;">Statut</th>
                <th class="sub-th" style="width: 9%;">Motif / Réserve</th>
                <!-- Offre Fin -->
                <th class="sub-th" style="width: 9%;">Montant</th>
                <th class="sub-th" style="width: 7%;">Statut</th>
                <th class="sub-th" style="width: 7%;">Motif</th>
                <!-- Après Vérif -->
                <th class="sub-th" style="width: 9%;">Montant</th>
                <th class="sub-th" style="width: 7%;">Statut</th>
                <th class="sub-th" style="width: 8%;">Motif</th>
            </tr>
        </thead>
        <tbody>
            @forelse($concurrents as $c)
                <tr>
                    <!-- Nom -->
                    <td style="font-weight: bold; color: #0f172a;">
                        {{ $c['nom'] }}
                    </td>

                    <!-- Admin Statut -->
                    <td style="text-align: center;">
                        @if($c['admin_statut'] === 'Admis sans réserve')
                            <span class="statut-admis">Admis sans réserve</span>
                        @elseif($c['admin_statut'] === 'Admis avec réserve')
                            <span class="statut-reserve">Admis avec réserve</span>
                        @elseif($c['admin_statut'] === 'Rejeté' || $c['admin_statut'] === 'Rejetée')
                            <span class="statut-rejet">Rejeté</span>
                        @elseif($c['admin_statut'] === 'Non examiné')
                            <span class="statut-non-examine">Non examiné</span>
                        @else
                            <span class="statut-admis">{{ $c['admin_statut'] }}</span>
                        @endif
                    </td>

                    <!-- Admin Motif -->
                    <td style="text-align: center;">
                        @if($c['admin_statut'] === 'Admis avec réserve' && !empty($c['admin_motif']) && $c['admin_motif'] !== '-')
                            <div class="motif-reserve-txt">Réserve : {{ $c['admin_motif'] }}</div>
                        @elseif($c['admin_statut'] === 'Rejeté' && !empty($c['admin_motif']) && $c['admin_motif'] !== '-')
                            <div class="motif-rejet-txt">Motif : {{ $c['admin_motif'] }}</div>
                        @else
                            <div class="motif-txt">{{ $c['admin_motif'] ?: '-' }}</div>
                        @endif
                    </td>

                    <!-- Tech Statut -->
                    <td style="text-align: center;">
                        @if($c['tech_statut'] === 'Admis sans réserve')
                            <span class="statut-admis">Admis sans réserve</span>
                        @elseif($c['tech_statut'] === 'Admis avec réserve')
                            <span class="statut-reserve">Admis avec réserve</span>
                        @elseif($c['tech_statut'] === 'Rejeté' || $c['tech_statut'] === 'Rejetée')
                            <span class="statut-rejet">Rejeté</span>
                        @elseif($c['tech_statut'] === 'Non examiné')
                            <span class="statut-non-examine">Non examiné</span>
                        @else
                            <span class="statut-admis">{{ $c['tech_statut'] }}</span>
                        @endif
                    </td>

                    <!-- Tech Motif -->
                    <td style="text-align: center;">
                        @if($c['tech_statut'] === 'Admis avec réserve' && !empty($c['tech_motif']) && $c['tech_motif'] !== '-')
                            <div class="motif-reserve-txt">Réserve : {{ $c['tech_motif'] }}</div>
                        @elseif($c['tech_statut'] === 'Rejeté' && !empty($c['tech_motif']) && $c['tech_motif'] !== '-')
                            <div class="motif-rejet-txt">Motif : {{ $c['tech_motif'] }}</div>
                        @else
                            <div class="motif-txt">{{ $c['tech_motif'] ?: '-' }}</div>
                        @endif
                    </td>

                    <!-- Fin Montant -->
                    <td class="montant-val">
                        @if($c['fin_statut'] === 'Non examiné' || is_null($c['montant_ht']) || $c['montant_ht'] == 0)
                            <span style="color: #94a3b8; font-weight: normal;">-</span>
                        @else
                            {{ number_format($c['montant_ht'], 2, ',', ' ') }} DH
                        @endif
                    </td>

                    <!-- Fin Statut -->
                    <td style="text-align: center;">
                        @if($c['fin_statut'] === 'Admis sans réserve')
                            <span class="statut-admis">Admis sans réserve</span>
                        @elseif($c['fin_statut'] === 'Admis avec réserve')
                            <span class="statut-reserve">Admis avec réserve</span>
                        @elseif($c['fin_statut'] === 'Rejeté' || $c['fin_statut'] === 'Rejetée')
                            <span class="statut-rejet">Rejeté</span>
                        @elseif($c['fin_statut'] === 'Non examiné')
                            <span class="statut-non-examine">Non examiné</span>
                        @else
                            <span class="statut-admis">{{ $c['fin_statut'] }}</span>
                        @endif
                    </td>

                    <!-- Fin Motif -->
                    <td style="text-align: center;">
                        @if($c['fin_statut'] === 'Admis avec réserve' && !empty($c['fin_motif']) && $c['fin_motif'] !== '-')
                            <div class="motif-reserve-txt">Réserve : {{ $c['fin_motif'] }}</div>
                        @elseif($c['fin_statut'] === 'Rejeté' && !empty($c['fin_motif']) && $c['fin_motif'] !== '-')
                            <div class="motif-rejet-txt">Motif : {{ $c['fin_motif'] }}</div>
                        @else
                            <div class="motif-txt">{{ $c['fin_motif'] ?: '-' }}</div>
                        @endif
                    </td>

                    <!-- Après Vérif Montant -->
                    <td class="montant-val">
                        @if($c['verif_statut'] === 'Non examiné' || is_null($c['montant_rectifie']) || $c['montant_rectifie'] == 0)
                            <span style="color: #94a3b8; font-weight: normal;">-</span>
                        @else
                            {{ number_format($c['montant_rectifie'], 2, ',', ' ') }} DH
                        @endif
                    </td>

                    <!-- Après Vérif Statut -->
                    <td style="text-align: center;">
                        @if($c['verif_statut'] === 'Admis sans réserve')
                            <span class="statut-admis">Admis sans réserve</span>
                        @elseif($c['verif_statut'] === 'Admis avec réserve')
                            <span class="statut-reserve">Admis avec réserve</span>
                        @elseif($c['verif_statut'] === 'Rejeté' || $c['verif_statut'] === 'Rejetée')
                            <span class="statut-rejet">Rejeté</span>
                        @elseif($c['verif_statut'] === 'Non examiné')
                            <span class="statut-non-examine">Non examiné</span>
                        @else
                            <span class="statut-admis">{{ $c['verif_statut'] }}</span>
                        @endif
                    </td>

                    <!-- Après Vérif Motif -->
                    <td style="text-align: center;">
                        @if($c['verif_statut'] === 'Admis avec réserve' && !empty($c['verif_motif']) && $c['verif_motif'] !== '-')
                            <div class="motif-reserve-txt">Réserve : {{ $c['verif_motif'] }}</div>
                        @elseif($c['verif_statut'] === 'Rejeté' && !empty($c['verif_motif']) && $c['verif_motif'] !== '-')
                            <div class="motif-rejet-txt">Motif : {{ $c['verif_motif'] }}</div>
                        @else
                            <div class="motif-txt">{{ $c['verif_motif'] ?: '-' }}</div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" style="text-align: center; padding: 10px; color: #64748b;">
                        Aucun concurrent enregistré.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
