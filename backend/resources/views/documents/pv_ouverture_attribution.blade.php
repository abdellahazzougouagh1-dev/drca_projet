<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $docTitle }}</title>
    <style>
        @page { margin: 115px 40px 70px 40px; }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 14px;
            color: #000;
            line-height: 1.5;
            margin: 0;
        }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: middle; }
        .page { position: relative; }
        .bold { font-weight: bold; }
        .center { text-align: center; }
        .right { text-align: right; }
        .uppercase { text-transform: uppercase; }

        /* HEADER LOGOS STYLING */
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 5px; margin-top: 0; }
        .header-center { text-align: center; font-size: 12px; font-weight: bold; line-height: 1.25; }
        .header-logo { height: 45px; width: auto; }
        .header-line {
            border-bottom: 2px solid #000080;
            margin-bottom: 18px;
        }
        /* FOOTER LOGOS STYLING */
        footer {
            position: fixed;
            bottom: -35px;
            left: 0;
            right: 0;
            border-top: 1px solid #999;
            padding-top: 4px;
            font-size: 8px;
            color: #333;
        }
        .footer-table { width: 100%; border-collapse: collapse; }
        .footer-table td { vertical-align: middle; border: none; }
        .footer-left { width: 20%; }
        .footer-center { width: 60%; text-align: center; }
        .footer-right { width: 20%; text-align: right; }
        .footer-logo { max-height: 30px; width: auto; }
        .footer-green-logo { max-height: 25px; width: auto; }
        .footer-info { max-height: 28px; width: auto; }

        .grid-table {
            width: 100%;
            border: 1.5px solid #000;
            border-collapse: collapse;
            margin-bottom: 9px;
            font-size: 9.5px;
        }
        .grid-table th {
            border: 1px solid #000;
            background-color: #f1f5f9;
            padding: 4px 6px;
            font-weight: bold;
            text-align: center;
        }
        .grid-table td {
            border: 1px solid #000;
            padding: 4px 6px;
            vertical-align: middle;
        }
    </style>
    @include('documents.partials.bc_typography')
</head>
<body>
    @include('documents.partials.bc_header')
    @include('documents.partials.bc_footer')
<div class="page">

    <!-- TITRE PRINCIPAL ET NUMERO D'AVIS -->
    <table style="width: 100%; border: 1.5px solid #000; border-collapse: collapse; text-align: center; margin-top: 4px; margin-bottom: 9px;">
        <tr>
            <td style="padding: 5px; font-size: 12px; font-weight: bold; text-transform: uppercase; border-bottom: 1.5px solid #000;">
                PV d'examen des devis
            </td>
        </tr>
        <tr>
            <td style="padding: 0;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="width: 50%; text-align: center; border-right: 1.5px solid #000; padding: 3px; font-weight: bold; text-transform: uppercase; font-size: 10px;">
                            AVIS D'ACHAT N° :
                        </td>
                        <td style="width: 50%; text-align: center; padding: 3px; font-weight: bold; font-size: 10px;">
                            {{ $doc['numero_consultation'] }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- DATE ET HEURE DE SEANCE -->
    <div style="margin-bottom: 8px; font-weight: bold; font-size: 10px;">
        En date du {{ $doc['date_reunion'] }} &nbsp;&nbsp;&nbsp;&nbsp; : &nbsp;&nbsp;&nbsp;&nbsp; {{ $doc['heure_reunion'] }}
    </div>

    <!-- EN-TETE COMMISSION ET PRESTATION -->
    <table style="width: 100%; border: 1.5px solid #000; border-collapse: collapse; margin-bottom: 9px; font-size: 9.5px;">
        <tr>
            <td style="padding: 5px; border-bottom: 1.5px solid #000;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="vertical-align: top; width: 70%; padding-right: 6px;">
                            Une commission s'est réunie au siège de la Direction Régionale du Conseil Agricole de Rabat-Salé-Kénitra pour procéder au déchiffrement des plis concernant l'avis d'achat sur bon de commande N° :{{ $doc['numero_bc'] }}
                        </td>
                       
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="padding: 4px 6px;">
                <div>relative à la prestation de :</div>
                <div style="text-align: center; font-weight: bold; margin-top: 3px; padding: 0 6px; font-size: 10px;">
                    {{ $doc['objet'] }}
                </div>
            </td>
        </tr>
    </table>

    <!-- DISCOURS INTRODUCTIF -->
    <div style="text-align: center; margin: 6px 0 8px 0; font-size: 9.5px;">
        Le déchiffrement des plis contenant les propositions faites par les concurrents déposés auprès du portail des marchés publics
    </div>

    <!-- TABLEAU 1: CONCURRENTS (N° auto-incrémenté) -->
    <table class="grid-table">
        <thead>
            <tr>
                <th style="width: 8%;">N°</th>
                <th style="width: 52%;">Nom des concurrents</th>
                <th style="width: 40%;">Montant de l'offre (TTC)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($doc['concurrents'] as $index => $c)
            <tr>
                <td class="center">{{ $index + 1 }}</td>
                <td class="center uppercase">{{ data_get($c, 'nom') }}</td>
                <td class="center">{{ number_format((float) data_get($c, 'montant', 0), 2, ',', ' ') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- TABLEAU 2: SOCIETES INVITEES PAR ORDRE D'OFFRE LA PLUS AVANTAGEUSE -->
    <div style="font-weight: bold; margin-bottom: 3px; font-size: 9.5px;">
        Le maître d'ouvrage invite par ordre les sociétés qui présentent l'offre la plus avantageuse :
    </div>
    <table class="grid-table" style="margin-bottom: 9px;">
        <tr>
            <td style="width: 25%; font-weight: bold; vertical-align: top; padding: 4px; border-right: 1px solid #000;">
                Les sociétés :
            </td>
            <td style="padding: 0; vertical-align: top;">
                <table style="width: 100%; border-collapse: collapse;">
                    @foreach($doc['societes_invitees'] as $index => $si)
                    <tr>
                        <td style="border-bottom: {{ $loop->last ? 'none' : '1px solid #000' }}; border-right: 1px solid #000; width: 60%; padding: 3px 6px; text-align: center; font-weight: bold; text-transform: uppercase;">
                            {{ data_get($si, 'nom') }}
                        </td>
                        <td style="border-bottom: {{ $loop->last ? 'none' : '1px solid #000' }}; width: 40%; padding: 3px 6px; text-align: center; font-weight: bold;">
                            {{ number_format((float) data_get($si, 'montant', 0), 2, ',', ' ') }}
                        </td>
                    </tr>
                    @endforeach
                </table>
            </td>
        </tr>
    </table>

    <!-- SECTION 3: SOCIETES AYANT REFUSE ET MOTIF DE REFUS -->
    <div style="font-weight: bold; margin-top: 5px; margin-bottom: 3px; font-size: 9.5px;">
        Les sociétés ayant écarter sant :
    </div>
    <table class="grid-table" style="margin-bottom: 9px;">
        <thead>
            <tr>
                <th style="width: 35%;">Société</th>
                <th style="width: 65%;">Motif du refus</th>
            </tr>
        </thead>
        <tbody>
            @if(!empty($doc['societes_refusees']))
                @foreach($doc['societes_refusees'] as $sr)
                <tr>
                    <td style="font-weight: bold; text-transform: uppercase;">
                        {{ is_array($sr) ? data_get($sr, 'nom') : $sr }}
                    </td>
                    <td>
                        {{ is_array($sr) ? data_get($sr, 'motif', "Refus d'invitation du maître d'ouvrage") : "Refus d'invitation du maître d'ouvrage" }}
                    </td>
                </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="2" class="center">Néant</td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- SECTION 4: DECISION D'ATTRIBUTION ET MONTANT -->
    <div style="margin-top: 5px; margin-bottom: 3px; font-size: 9.5px;">
        Le maître d'ouvrage décide de retenir l'offre qu'il juge la plus avantageuse présentée ici par la société :
    </div>
    <div style="font-weight: bold; margin-bottom: 3px; font-size: 9.5px;">
        ayant confirmé son offre
    </div>
    <div style="text-align: center; font-weight: bold; font-size: 11.5px; text-transform: uppercase; margin: 4px 0;">
        {{ $doc['attributaire'] }}
    </div>
    @if(!empty($doc['motif_attribution']))
    <div style="margin: 4px 0; font-size: 9.5px; text-align: center;">
        <span style="font-weight: bold;">Motif de retenu :</span> 
        <span style="font-style: italic;">{{ $doc['motif_attribution'] }}</span>
    </div>
    @endif
    <div style="margin: 4px 0; font-size: 10px;">
        Pour un montant global (TTC) de &nbsp;&nbsp;&nbsp;&nbsp;<span style="font-weight: bold; font-size: 11px;">{{ number_format((float) $doc['montant_retenu'], 2, ',', ' ') }}</span> &nbsp;&nbsp;&nbsp;&nbsp; <strong>DH</strong>
    </div>
    <div style="margin-bottom: 8px; font-size: 9.5px;">
        En lettres : <span style="font-weight: bold;">{{ $doc['montant_en_lettres'] }}</span>
    </div>

    <!-- SECTION 5: HEURE LEVEE DE SEANCE ET DATE -->
    <table style="width: 100%; margin: 6px 0 9px 0; font-size: 9.5px;">
        <tr>
            <td style="width: 50%;">
                La séance est levée &nbsp;&nbsp;&nbsp;&nbsp; à &nbsp;&nbsp;&nbsp;&nbsp; <span class="bold">{{ $doc['heure_fin'] }}</span>
            </td>
            <td style="width: 50%; text-align: right;">
                Fait à Kénitra, le : <span class="bold">{{ $doc['date_document'] }}</span>
            </td>
        </tr>
    </table>

    <!-- SECTION 6: MEMBRES DE LA COMMISSION -->
    <div style="font-weight: bold; margin-bottom: 4px; font-size: 9.5px;">
        Membres de la commission :
    </div>
    <table style="width: 100%; border: 1.5px solid #000; border-collapse: collapse; margin-bottom: 9px; font-size: 9.5px;">
        <tr style="background: #f8fafc;">
            @foreach($doc['commission'] as $m)
                <td style="border: 1px solid #000; padding: 4px; text-align: center; font-weight: bold; width: {{ 100 / max(1, count($doc['commission'])) }}%;">
                    {{ data_get($m, 'nom') ?: data_get($m, 'nom_prenom') }}
                </td>
            @endforeach
        </tr>
        <tr>
            @foreach($doc['commission'] as $m)
                <td style="border: 1px solid #000; height: 38px;"></td>
            @endforeach
        </tr>
    </table>

    <!-- SECTION 7: MAITRE D'OUVRAGE SIGNATURE -->
    <table style="width: 100%; border: 1.5px solid #000; border-collapse: collapse; page-break-inside: avoid; font-size: 9.5px;">
        <tr style="background: #f8fafc;">
            <td style="border: 1px solid #000; padding: 4px; text-align: center; font-weight: bold;">
                Le maitre d'ouvrage
            </td>
        </tr>
        <tr>
            <td style="border: 1px solid #000; height: 48px;"></td>
        </tr>
    </table>
</div>
</body>
</html>
