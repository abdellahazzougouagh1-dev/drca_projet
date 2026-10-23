<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Lettre de consultation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            color: #000;
            margin: 20px;
            line-height: 1.5;
        }
        .page-break {
            page-break-after: always;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table {
            margin-bottom: 5px;
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: middle;
        }
        .header-center {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
        }
        .blue-line {
            border-bottom: 2px solid #000080;
            margin-bottom: 20px;
        }
        .top-info {
            width: 100%;
            margin-bottom: 30px;
        }
        .top-info td {
            vertical-align: top;
        }
        .sender-box {
            text-align: center;
            font-weight: bold;
            margin-bottom: 40px;
        }
        .recipient-box {
            margin-left: 50%;
            margin-bottom: 40px;
            line-height: 1.6;
        }
        .subject-box {
            margin-bottom: 30px;
            font-weight: bold;
        }
        .body-text {
            text-align: justify;
            margin-bottom: 20px;
        }
        .objet-text {
            text-align: center;
            font-weight: bold;
            margin: 20px 0;
            font-size: 16px;
        }
        .box-mention {
            border: 2px solid #000;
            padding: 15px;
            text-align: center;
            font-weight: bold;
            margin: 30px auto;
            width: 80%;
            font-size: 15px;
        }
        .deadline-text {
            margin-bottom: 40px;
        }
        .attachments {
            margin-top: 50px;
            font-weight: bold;
            font-size: 13px;
        }
        .attachments ul {
            list-style-type: none;
            padding-left: 10px;
            margin-top: 5px;
        }
        .attachments ul li {
            margin-bottom: 5px;
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
            $baseNum = preg_replace('/^CONV\s*[-_]?\s*/i', '', (string)$baseNum);
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
    $numeroConsultation = data_get($documentData ?? [], 'numero_consultation', $consultation->numero_consultation ?? '');
@endphp

@foreach($lettresList as $lettre)
    <div class="{{ !$loop->last ? 'page-break' : '' }}">
        @php
            $baseLettreNum = data_get($documentData ?? [], 'numero_lettre', $doc['numero_lettre'] ?? '............');
            $baseLettreNum = preg_replace('/^CONV\s*[-_]?\s*/i', '', (string)$baseLettreNum);
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
                $lettreNumero = preg_replace('/^CONV\s*[-_]?\s*/i', '', (string)$lettreNumero);
            }
            $societe = data_get($lettre, 'societe') ?: (data_get($lettre, 'nom') ?: (data_get($documentData ?? [], 'societe', '.........................')));
            $destinataireNom = data_get($lettre, 'destinataire_nom') ?: (data_get($documentData ?? [], 'destinataire_nom', 'Mr Le Gérant de la Société'));
            $destinataireAdresse = data_get($lettre, 'destinataire_adresse') ?: (data_get($lettre, 'adresse') ?: (data_get($documentData ?? [], 'destinataire_adresse', '.........................')));
            $ville = data_get($lettre, 'ville', '');
            if ($ville && !str_contains(strtolower($destinataireAdresse), strtolower($ville))) {
                $destinataireAdresse = trim($destinataireAdresse) . ', ' . $ville;
            }
        @endphp
        
        <table class="header-table">
            <tr>
                <td style="width: 25%;">
                    @if($logoOnca)
                        <img src="{{ $logoOnca }}" alt="Logo ONCA" style="height: 70px; width: auto;">
                    @endif
                </td>
                <td class="header-center" style="width: 50%;">
                    Direction Régionale du Conseil Agricole Rabat-Salé-Kénitra
                </td>
                <td style="width: 25%; text-align: right;">
                    @if($sceauMaroc)
                        <img src="{{ $sceauMaroc }}" alt="Sceau Maroc" style="height: 70px; width: auto;">
                    @endif
                </td>
            </tr>
        </table>
        <div class="blue-line"></div>

        <table class="top-info">
            <tr>
                <td style="width: 50%;">
                    N° : {{ $lettreNumero }}
                </td>
                <td style="width: 50%; text-align: right;">
                    Kénitra le: {{ \Carbon\Carbon::parse($dateLettre)->format('d/m/Y') }}
                </td>
            </tr>
        </table>

        <div class="sender-box">
            Le Directeur Régional du Conseil Agricole<br>
            Rabat-Salé-Kénitra, kénitra
        </div>

        <div class="recipient-box">
            <strong>A</strong><br>
            <strong>{{ $destinataireNom }} {{ $societe }}</strong><br>
            {{ $destinataireAdresse }}
        </div>

        <div class="subject-box">
            Objet : Demande d'offre de prix<br>
            Référence : Consultation N°: {{ $numeroConsultation }}
        </div>

        <div class="body-text">
            Monsieur,
            <br><br>
            J'ai l'honneur de vous informer que la Direction Régionale du Conseil Agricole de Rabat Salé Kénitra lance une consultation pour :
        </div>

        <div class="objet-text">
            {{ $objetConsultation }}
        </div>

        <div class="body-text">
            A cet effet, je vous prie de bien vouloir nous faire parvenir votre meilleure offre de prix sous pli fermé, à l'adresse sus indiquée portant la mention :
        </div>

        <div class="box-mention">
            « Offre de Prix N° {{ $numeroConsultation }} » à n'ouvrir qu'en commission
        </div>

        <div class="deadline-text">
            au plus tard le : <strong>{{ \Carbon\Carbon::parse($dateLimite)->format('d/m/Y') }}</strong> à <strong>{{ $heureLimite }}</strong>.
        </div>

        @php
            $pjItems = array_filter(preg_split('/\r\n|\r|\n/', (string)$piecesJointes), function($p) {
                return trim($p) !== '';
            });
        @endphp
        @if(!empty($pjItems))
        <div class="attachments">
            Pièces Jointes :
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
