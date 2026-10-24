<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Lettre de consultation</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm 15mm 24mm 15mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12.5px;
            color: #000;
            margin: 0;
            padding: 0;
            line-height: 1.35;
        }
        .page-break {
            page-break-after: always;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table {
            margin-bottom: 2px;
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: middle;
        }
        .header-center {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            line-height: 1.3;
        }
        .header-line {
            border-bottom: 1.5px solid #000080;
            margin-bottom: 10px;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 10px;
            border-collapse: collapse;
        }
        .sender-section {
            text-align: center;
            margin-bottom: 8px;
        }
        .sender-title {
            font-size: 13px;
            font-weight: bold;
            text-align: center;
        }
        .sender-city {
            font-size: 12.5px;
            font-weight: bold;
            text-align: right;
            padding-right: 50px;
            margin-top: 1px;
        }
        .recipient-section {
            text-align: center;
            margin-bottom: 10px;
            line-height: 1.35;
        }
        .recipient-label {
            font-size: 13.5px;
            font-weight: bold;
            margin-bottom: 1px;
        }
        .recipient-title {
            font-size: 12.5px;
            font-weight: bold;
        }
        .recipient-company {
            font-size: 12.5px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .recipient-address {
            font-size: 12px;
        }
        .recipient-city {
            font-size: 12px;
            font-weight: bold;
            text-align: right;
            padding-right: 50px;
            text-transform: uppercase;
        }
        .reference-table {
            width: 100%;
            margin-bottom: 10px;
            border-collapse: collapse;
            font-size: 12.5px;
        }
        .reference-table td {
            padding: 1px 0;
            vertical-align: top;
        }
        .ref-col-label {
            width: 110px;
        }
        .salutation-text {
            font-size: 12.5px;
            margin-bottom: 6px;
        }
        .body-p {
            font-size: 12px;
            line-height: 1.35;
            text-align: justify;
            margin-bottom: 6px;
        }
        .objet-box {
            text-align: center;
            font-weight: bold;
            font-size: 12.5px;
            margin: 6px 15px;
            line-height: 1.3;
        }
        .framed-instructions {
            border: 1px solid #000;
            padding: 6px 8px;
            font-size: 11.5px;
            line-height: 1.3;
            text-align: justify;
            margin: 6px 0;
        }
        .mention-box-table {
            width: 100%;
            border: 1px solid #000;
            border-collapse: collapse;
            margin: 6px 0;
            font-size: 12px;
        }
        .mention-box-table td {
            padding: 5px 4px;
            border-right: 1px solid #777;
            vertical-align: middle;
        }
        .mention-box-table td:last-child {
            border-right: none;
        }
        .deadline-block-table {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0 10px 0;
            font-size: 12px;
        }
        .deadline-block-table td {
            padding: 2px 0;
        }
        .closing-formula {
            text-align: center;
            font-size: 12px;
            margin: 12px 0 6px 0;
        }
        .attachments {
            margin-top: 8px;
            font-size: 11.5px;
        }
        .attachments ul {
            list-style-type: none;
            padding-left: 10px;
            margin: 2px 0 0 0;
        }
        .attachments ul li {
            margin-bottom: 2px;
        }
    </style>
</head>
<body>

@php
    $logoOncaPath = public_path('images/logo-onca.png');
    $sceauMarocPath = public_path('images/sceau-maroc.png');
    $logoOnca = file_exists($logoOncaPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoOncaPath)) : '';
    $sceauMaroc = file_exists($sceauMarocPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($sceauMarocPath)) : '';

    $rawLettres = data_get($documentData ?? [], 'lettres_consultation')
        ?: (data_get($documentData ?? [], 'lettres')
        ?: (data_get($doc ?? [], 'lettres_consultation') ?: []));

    if (!empty($rawLettres) && is_array($rawLettres)) {
        $lettresList = $rawLettres;
    } else {
        $offres = isset($consultation) && $consultation->offres ? $consultation->offres()->with('fournisseur')->get() : collect([]);
        if ($offres->isEmpty()) {
            $lettresList = [
                [
                    'numero_lettre' => data_get($documentData ?? [], 'numero_lettre', $doc['numero_lettre'] ?? '............'),
                    'societe' => data_get($documentData ?? [], 'societe', '.........................'),
                    'destinataire_nom' => data_get($documentData ?? [], 'destinataire_nom', 'Mr Le Gérant de la Société'),
                    'destinataire_adresse' => data_get($documentData ?? [], 'destinataire_adresse', '.........................'),
                    'ville' => data_get($documentData ?? [], 'ville', ''),
                ]
            ];
        } else {
            $baseNum = data_get($documentData ?? [], 'numero_lettre', $doc['numero_lettre'] ?? '............');
            $baseNum = trim(preg_replace('/^CONV\s*[-_]?\s*/i', '', (string)$baseNum));
            $lettresList = $offres->values()->map(function($offre, $idx) use ($documentData, $doc, $baseNum) {
                $numLettre = $baseNum;
                if ($idx > 0 && preg_match('/^(\D*)(\d+)(.*)$/', $baseNum, $m)) {
                    $padLen = strlen($m[2]);
                    $nextNum = (int)$m[2] + $idx;
                    $formattedNextNum = (str_starts_with($m[2], '0') && $padLen > 1) ? str_pad($nextNum, $padLen, '0', STR_PAD_LEFT) : $nextNum;
                    $numLettre = $m[1] . $formattedNextNum . $m[3];
                }
                return [
                    'numero_lettre' => $numLettre,
                    'societe' => $offre->fournisseur->raison_sociale ?? '.........................',
                    'destinataire_nom' => data_get($documentData ?? [], 'destinataire_nom', 'Mr Le Gérant de la Société'),
                    'destinataire_adresse' => $offre->fournisseur->adresse ?? '.........................',
                    'ville' => $offre->fournisseur->ville ?? '',
                ];
            })->toArray();
        }
    }

    $dateLettre = data_get($documentData ?? [], 'date_document', $consultation->date_consultation ?? now());
    $dateLimite = data_get($documentData ?? [], 'date_limite', $consultation->date_limite_devis ?? now());
    $heureLimite = data_get($documentData ?? [], 'heure_limite', $consultation->heure_limite_devis ?? '10:00');
    $piecesJointes = data_get($documentData ?? [], 'pieces_jointes', '');
    $objetConsultation = data_get($documentData ?? [], 'objet', $consultation->objet_consultation ?? '');

    // Numéro de consultation sans le mot CONV
    $rawNumeroConsultation = data_get($documentData ?? [], 'numero_consultation', $consultation->numero_consultation ?? '');
    $numeroConsultationClean = trim(preg_replace('/\bCONV\s*[-_]?\s*/i', '', (string)$rawNumeroConsultation));
    $numeroConsultationClean = trim(preg_replace('/^[-_\s]+|[-_\s]+$/', '', $numeroConsultationClean));
@endphp

@foreach($lettresList as $lettre)
    <div class="{{ !$loop->last ? 'page-break' : '' }}">
        @php
            $baseLettreNum = data_get($documentData ?? [], 'numero_lettre', $doc['numero_lettre'] ?? '............');
            $baseLettreNum = trim(preg_replace('/^CONV\s*[-_]?\s*/i', '', (string)$baseLettreNum));
            $lettreNumero = data_get($lettre, 'numero_lettre');
            if (empty($lettreNumero) || $lettreNumero === '............') {
                if ($loop->index > 0 && preg_match('/^(\D*)(\d+)(.*)$/', $baseLettreNum, $m)) {
                    $padLen = strlen($m[2]);
                    $nextNum = (int)$m[2] + $loop->index;
                    $formattedNextNum = (str_starts_with($m[2], '0') && $padLen > 1) ? str_pad($nextNum, $padLen, '0', STR_PAD_LEFT) : $nextNum;
                    $lettreNumero = $m[1] . $formattedNextNum . $m[3];
                } else {
                    $lettreNumero = $baseLettreNum;
                }
            } else {
                $lettreNumero = trim(preg_replace('/^CONV\s*[-_]?\s*/i', '', (string)$lettreNumero));
            }
            $societe = data_get($lettre, 'societe') ?: (data_get($lettre, 'nom') ?: (data_get($documentData ?? [], 'societe', '.........................')));
            $destinataireNom = data_get($lettre, 'destinataire_nom') ?: (data_get($documentData ?? [], 'destinataire_nom', 'Mr Le Gérant de la Société'));
            $destinataireAdresse = data_get($lettre, 'destinataire_adresse') ?: (data_get($lettre, 'adresse') ?: (data_get($documentData ?? [], 'destinataire_adresse', '.........................')));
            $ville = data_get($lettre, 'ville', '');
        @endphp
        
        <!-- En-tête -->
        <table class="header-table">
            <tr>
                <td style="width: 25%; text-align: left; vertical-align: middle;">
                    @if($logoOnca)
                        <img src="{{ $logoOnca }}" alt="Logo ONCA" style="height: 60px; width: auto;">
                    @endif
                </td>
                <td class="header-center" style="width: 50%; text-align: center; vertical-align: middle;">
                    Direction Régionale du Conseil Agricole Rabat-Salé-Kénitra
                </td>
                <td style="width: 25%; text-align: right; vertical-align: middle;">
                    @if($sceauMaroc)
                        <img src="{{ $sceauMaroc }}" alt="Sceau Maroc" style="height: 60px; width: auto;">
                    @endif
                </td>
            </tr>
        </table>
        <div class="header-line"></div>

        <!-- Métadonnées N° et Date -->
        <table class="meta-table">
            <tr>
                <td style="width: 50%; font-weight: bold; font-size: 13px;">
                    N° &nbsp;&nbsp; {{ $lettreNumero }}
                </td>
                <td style="width: 50%; text-align: right; font-weight: bold; font-size: 13px;">
                    Kénitra le: {{ \Carbon\Carbon::parse($dateLettre)->format('d/m/Y') }}
                </td>
            </tr>
        </table>

        <!-- Expéditeur -->
        <div class="sender-section">
            <div class="sender-title">Le Directeur Régional du Conseil Agricole Rabat-Salé-Kénitra</div>
            <div class="sender-city">Kénitra</div>
        </div>

        <!-- Destinataire -->
        <div class="recipient-section">
            <div class="recipient-label">A</div>
            <div class="recipient-title">{{ $destinataireNom }}</div>
            <div class="recipient-company">{{ $societe }}</div>
            <div class="recipient-address">{{ $destinataireAdresse }}</div>
            @if($ville)
                <div class="recipient-city">{{ $ville }}</div>
            @endif
        </div>

        <!-- Objet et Référence -->
        <table class="reference-table">
            <tr>
                <td class="ref-col-label"><strong>Objet &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</strong></td>
                <td><strong>Demande d'offre de prix</strong></td>
            </tr>
            <tr>
                <td class="ref-col-label"><strong>Référence :</strong></td>
                <td><strong>Consultation N° : &nbsp;&nbsp; {{ $numeroConsultationClean }}</strong></td>
            </tr>
        </table>

        <!-- Formule d'appel & paragraphe 1 -->
        <div class="salutation-text">Monsieur,</div>

        <div class="body-p">
            J’ai l’honneur de vous informer que la Direction Régionale du Conseil Agricole de Rabat-Salé-Kénitra envisage la réalisation des prestations relatives au:
        </div>

        <!-- Objet de la consultation -->
        <div class="objet-box">
            {{ $objetConsultation }}
        </div>

        <!-- Paragraphe 2 (Annexe) -->
        <div class="body-p">
            Les prestations objet de la présente consultation figurent dans l’annexe ci-joint.
        </div>

        <!-- Encadré d'instructions -->
        <div class="framed-instructions">
            Si vous êtes intéressés par cette offre, je vous demanderais de bien vouloir faire parvenir à la direction régionale du conseil agricole de Rabat-Salé-Kénitra, sise au Angle rue Sebta bd mohamed V (à coteé de bank el maghreb)-Kénitra, votre meilleure offre de prix (toutes taxes comprises) sous pli fermé et cacheté portant mention suivante:
        </div>

        <!-- Tableau mention « Offre de Prix N° ... » -->
        <table class="mention-box-table">
            <tr>
                <td style="width: 28%; text-align: center; text-decoration: underline; font-weight: bold;">
                    «Offre de Prix N°
                </td>
                <td style="width: 36%; text-align: center; text-decoration: underline; font-weight: bold;">
                    {{ $numeroConsultationClean }}
                </td>
                <td style="width: 36%; text-align: center; text-decoration: underline; font-weight: bold;">
                    à n’ouvrir qu’en commission »,
                </td>
            </tr>
        </table>

        <!-- Tableau délai et bordereau -->
        <table class="deadline-block-table">
            <tr>
                <td style="text-align: left; vertical-align: middle;">
                    Et ce, selon le modèle de bordereau des prix ci-joint, au plus tard le :
                </td>
                <td style="width: 170px; text-align: center; vertical-align: middle; border: 1.5px solid #000; padding: 4px 6px; font-weight: bold;">
                    {{ \Carbon\Carbon::parse($dateLimite)->format('d/m/Y') }} à {{ $heureLimite }}
                </td>
            </tr>
        </table>

        <!-- Formule de politesse finale -->
        <div class="closing-formula">
            Veuillez agréer, Monsieur le Directeur, mes salutations distinguées.
        </div>

        @php
            $pjItems = array_filter(preg_split('/\r\n|\r|\n/', (string)$piecesJointes), function($p) {
                return trim($p) !== '';
            });
        @endphp
        @if(!empty($pjItems))
        <div class="attachments">
            <div style="font-weight: bold; text-decoration: underline; margin-bottom: 3px;">Pièces Jointes :</div>
            <ul>
                @foreach($pjItems as $piece)
                    <li>- {{ trim($piece) }}</li>
                @endforeach
            </ul>
        </div>
        @endif
        
        @include('documents.partials.bc_footer')
    </div>
@endforeach

</body>
</html>
