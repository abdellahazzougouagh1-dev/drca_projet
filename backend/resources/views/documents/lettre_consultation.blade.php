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
    // Récupération sécurisée des offres (qui lient la consultation aux fournisseurs)
    $offres = isset($consultation) && $consultation->offres ? $consultation->offres()->with('fournisseur')->get() : collect([]);
    
    // Si la collection est vide (pas encore de fournisseurs assignés), on crée une ligne par défaut
    if($offres->isEmpty()) {
        $offres = collect([
            (object)[
                'id' => '............',
                'date_envoi' => null,
                'fournisseur' => (object)[
                    'raison_sociale' => '.........................',
                    'adresse' => '.........................'
                ]
            ]
        ]);
    }
@endphp

@foreach($offres as $offre)
    <div class="{{ !$loop->last ? 'page-break' : '' }}">
        
        <table class="header-table">
            <tr>
                <td style="width: 25%;">
                    <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/logo-onca.png'))) }}" alt="Logo ONCA" style="height: 70px; width: auto;">
                </td>
                <td class="header-center" style="width: 50%;">
                    Direction Régionale du Conseil Agricole Rabat-Salé-Kénitra
                </td>
                <td style="width: 25%; text-align: right;">
                    <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/sceau-maroc.png'))) }}" alt="Sceau Maroc" style="height: 70px; width: auto;">
                </td>
            </tr>
        </table>
        <div class="blue-line"></div>

        <table class="top-info">
            <tr>
                <td style="width: 50%;">
                    N° : {{ $offre->id ?? "............" }}
                </td>
                <td style="width: 50%; text-align: right;">
                    Kénitra le: {{ $offre->date_envoi ? \Carbon\Carbon::parse($offre->date_envoi)->format('d/m/Y') : \Carbon\Carbon::parse($consultation->date_consultation ?? now())->format('d/m/Y') }}
                </td>
            </tr>
        </table>

        <div class="sender-box">
            Le Directeur Régional du Conseil Agricole<br>
            Rabat-Salé-Kénitra, kénitra
        </div>

        <div class="recipient-box">
            <strong>A</strong><br>
            <strong>Mr Le Gérant de la Société {{ $offre->fournisseur->raison_sociale ?? '.........................' }}</strong><br>
            {{ $offre->fournisseur->adresse ?? '.........................' }}
        </div>

        <div class="subject-box">
            Objet : Demande d'offre de prix<br>
            Référence : Consultation N°: {{ $consultation->numero_consultation }}
        </div>

        <div class="body-text">
            Monsieur,
            <br><br>
            J'ai l'honneur de vous informer que la Direction Régionale du Conseil Agricole de Rabat Salé Kénitra lance une consultation pour :
        </div>

        <div class="objet-text">
            {{ $consultation->objet_consultation }}
        </div>

        <div class="body-text">
            A cet effet, je vous prie de bien vouloir nous faire parvenir votre meilleure offre de prix sous pli fermé, à l'adresse sus indiquée portant la mention :
        </div>

        <div class="box-mention">
            « Offre de Prix N° {{ $consultation->numero_consultation }} » à n'ouvrir qu'en commission
        </div>

        <div class="deadline-text">
            au plus tard le : <strong>{{ \Carbon\Carbon::parse($consultation->date_reunion ?? now())->format('d/m/Y') }}</strong> à <strong>{{ $consultation->heure_reunion ? \Carbon\Carbon::parse($consultation->heure_reunion)->format('H:i') : '10:00' }}</strong>.
        </div>

        <div class="attachments">
            Pièces Jointes :
            <ul>
                <li>- Modele de Projet de Convention</li>
                <li>- Bordereau des prix</li>
            </ul>
        </div>
        
    </div>
@endforeach

</body>
</html>
