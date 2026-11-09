<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Règlement de la Consultation</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 18px 34px 96px 34px;
        }
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 13px;
            line-height: 1.55;
            color: #000;
            margin: 0;
            padding: 0;
        }
        .header-table {
            margin-bottom: 4px;
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: middle;
        }
        .header-center {
            text-align: center;
            font-size: 13.5px;
            font-weight: bold;
            line-height: 1.35;
            padding: 0 8px;
        }
        .blue-line {
            border-bottom: 2px solid #000080;
            margin-bottom: 22px;
        }
        .title-box {
            text-align: center;
            margin: 18px 0 28px 0;
        }
        .title-box h1 {
            font-size: 18px;
            font-weight: bold;
            margin: 0;
            display: inline-block;
            border: 2.5px solid #000;
            padding: 9px 28px;
        }
        .ref-box {
            text-align: center;
            font-size: 15px;
            font-weight: bold;
            margin-top: 14px;
        }
        .content {
            margin-top: 24px;
            margin-left: 12px;
            margin-right: 12px;
        }
        .intro-text {
            font-size: 14.5px;
            margin-bottom: 18px;
            font-weight: bold;
        }
        ul.list-no-bullet {
            list-style-type: none;
            padding-left: 0;
            margin-top: 0;
        }
        ul.list-no-bullet > li {
            margin-bottom: 18px;
        }
        ul.list-bullets {
            list-style-type: none;
            padding-left: 20px;
            margin-top: 6px;
        }
        ul.list-bullets > li:before {
            content: "* ";
            font-weight: bold;
            margin-right: 5px;
        }
        ul.list-bullets > li {
            margin-bottom: 6px;
        }
        .checkbox-item {
            margin-left: 56px;
            margin-bottom: 7px;
        }
        .checkbox-box {
            display: inline-block;
            width: 13px;
            height: 13px;
            border: 1.2px solid #000;
            margin-right: 10px;
            vertical-align: middle;
        }
        .letter-list {
            list-style-type: lower-alpha;
            padding-left: 48px;
            margin-top: 8px;
        }
        .letter-list > li {
            margin-bottom: 8px;
        }
    </style>
    @include('documents.partials.bc_typography')
</head>
<body>
    @include('documents.partials.bc_footer')

    <table class="header-table">
        <tr>
            <td style="width: 24%; text-align: left;">
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/logo-onca.png'))) }}" alt="Logo ONCA" style="height: 68px; width: auto;">
            </td>
            <td class="header-center" style="width: 52%;">
                Direction Régionale du Conseil Agricole Rabat-Salé-Kénitra
            </td>
            <td style="width: 24%; text-align: right;">
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/sceau-maroc.png'))) }}" alt="Sceau Maroc" style="height: 68px; width: auto;">
            </td>
        </tr>
    </table>
    <div class="blue-line"></div>

    <div class="title-box">
        <h1>RÈGLEMENT DE LA CONSULTATION</h1>
        <div class="ref-box">
            Consultation N° : {{ $consultation->numero_consultation }}
        </div>
    </div>

    <div class="content">
        <p class="intro-text">La participation à l’offre de prix est soumise aux conditions suivantes :</p>
        
        <ul class="list-no-bullet">
            <li>
                <strong>• L’Offre financière doit être :</strong>
                <ul class="list-bullets">
                    <li>Conforme au modèle de bordereau des prix en annexe,</li>
                    <li>Imprimée sur papier à en-tête du soumissionnaire avec n° de :</li>
                </ul>
                
                <div class="checkbox-item"><span class="checkbox-box"></span>La Taxe professionnelle,</div>
                <div class="checkbox-item"><span class="checkbox-box"></span>L’identifiant fiscal,</div>
                <div class="checkbox-item"><span class="checkbox-box"></span>Le Registre du commerce,</div>
                <div class="checkbox-item"><span class="checkbox-box"></span>L’affiliation à la CNSS,</div>
                <div class="checkbox-item"><span class="checkbox-box"></span>Le RIB,</div>
                <div class="checkbox-item"><span class="checkbox-box"></span>L’Identifiant Commun de l’Entreprise (ICE).</div>
            </li>
            
            <li>
                <strong>•</strong> Fermée dans une enveloppe cachetée portant le nom et l'adresse du candidat, et la mention très apparente : <br>
                <div style="margin-top: 10px; margin-left: 20px; font-weight: bold;">
                    « Offre de prix N° {{ $consultation->numero_consultation }} à n’ouvrir qu’en commission ».
                </div>
            </li>
            
            <li>
                <strong>•</strong> Le jugement des offres est basé sur le montant total de l’offre de prix toutes taxes comprises. L’offre la moins-disante sera retenue.
            </li>
            
            <li>
                <strong>•</strong> L’attributaire sera appelé à présenter un dossier administratif composé des pièces suivantes :
                <ol class="letter-list">
                    <li>Une copie des statuts de la société (ou des pouvoirs conférés),</li>
                    <li>Une copie d'une attestation fiscale pour soumissionnaires aux marchés publics,</li>
                    <li>Une copie d'une attestation d'inscription au registre du commerce,</li>
                    <li>Une copie de l'attestation d'affiliation à la CNSS.</li>
                </ol>
            </li>
        </ul>
    </div>

</body>
</html>
