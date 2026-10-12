<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste de Présence - AOO N° {{ $aoo->num_aoo }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #000;
            margin: 0;
            padding: 20px;
        }

        table {
            width: 100%;
            border: 1px solid black;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            border: 1px solid black;
            padding: 6px;
            vertical-align: middle;
        }

        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }

        .logos-cell {
            text-align: left;
            width: 25%;
            padding: 5px;
        }

        .logos-cell img {
            display: inline-block;
            height: 50px;
            vertical-align: middle;
        }

        .title-container {
            text-align: center;
            margin: 20px 0;
        }

        .title-box {
            border: 1px solid black;
            display: inline-block;
            padding: 8px 40px;
            font-size: 14px;
        }

        .signature-box {
            height: 40px;
        }
    </style>
</head>
<body>

    <!-- TABLEAU DE L'EN-TÊTE -->
    <table>
        <tbody>
            <!-- LIGNE 1 -->
            <tr>
                <td rowspan="4" class="logos-cell">
                    <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/logo-onca.png'))) }}" alt="Logo ONCA" style="margin-right: 5px;">
                    <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/sceau-maroc.png'))) }}" alt="Royaume du Maroc">
                </td>
                <td class="font-bold text-center">Appel d'offre Numéro</td>
                <td class="font-bold text-center">{{ $aoo->num_aoo }}</td>
                <td class="font-bold text-center">du</td>
                <td class="font-bold text-center">{{ isset($aoo->date_ouverture) ? \Carbon\Carbon::parse($aoo->date_ouverture)->format('d/m/Y') : '' }}</td>
                <td class="text-right">{{ $aoo->heure_ouverture }}</td>
            </tr>
            <!-- LIGNE 2 -->
            <tr>
                <td>Ayant pour Objet</td>
                <td colspan="4" class="font-bold" style="text-transform: uppercase;">{{ $aoo->objet }}</td>
            </tr>
            <!-- LIGNE 3 -->
            <tr>
                <td colspan="5" style="text-align:center; font-weight:bold;">Le matin</td>
            </tr>
            <!-- LIGNE 4 -->
            <tr>
                <td class="font-bold">Journaux :</td>
                <td>Français</td>
                <td class="text-center">{{ (!empty($aoo->journal_fr) && !preg_match('/^\?+$/', trim($aoo->journal_fr))) ? $aoo->journal_fr : 'Le Matin' }}</td>
                <td class="font-bold text-center">Arabe</td>
                <td class="text-center">{{ (!empty($aoo->journal_ar) && !preg_match('/^\?+$/', trim($aoo->journal_ar))) ? $aoo->journal_ar : 'الصحراء المغربية' }}</td>
            </tr>
        </tbody>
    </table>

    <!-- CADRE DU TITRE -->
    <div class="title-container">
        <div class="title-box">
            Liste de prsence au travaux de la commission d'ouverture de l'Appel doffre
        </div>
    </div>

    <!-- TABLEAU DES MEMBRES -->
    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 25%;">Nom et Prénom</th>
                <th class="text-center" style="width: 30%;">Fonction</th>
                <th class="text-center" style="width: 15%;">Qualité</th>
                <th class="text-center" style="width: 15%;">Signature</th>
                <th class="text-center" style="width: 15%;">Observations</th>
            </tr>
        </thead>
        <tbody>
            @if($aoo->membres_commission && is_array($aoo->membres_commission))
                @foreach ($aoo->membres_commission as $membre)
                    <tr>
                        <td class="font-bold">{{ $membre['nom_prenom'] ?? '' }}</td>
                        <td>{{ $membre['fonction'] ?? '' }}</td>
                        <td class="text-center">{{ $membre['qualite'] ?? '' }}</td>
                        <td class="signature-box"></td>
                        <td></td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="5" class="text-center">Aucun membre assigné</td>
                </tr>
            @endif
        </tbody>
    </table>

</body>
</html>
