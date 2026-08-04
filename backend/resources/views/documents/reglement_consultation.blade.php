<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Règlement de la Consultation</title>
    <style>
        body {
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #000;
            margin: 20px;
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
        .title-box {
            text-align: center;
            margin: 40px 0;
        }
        .title-box h1 {
            font-size: 20px;
            font-weight: bold;
            margin: 0;
            display: inline-block;
            border: 2px solid #000;
            padding: 8px 30px;
        }
        .ref-box {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin-top: 15px;
        }
        .content {
            margin-top: 40px;
            margin-left: 10px;
        }
        .intro-text {
            font-size: 15px;
            margin-bottom: 20px;
        }
        ul.list-no-bullet {
            list-style-type: none;
            padding-left: 0;
            margin-top: 0;
        }
        ul.list-no-bullet > li {
            margin-bottom: 15px;
        }
        ul.list-bullets {
            list-style-type: none;
            padding-left: 20px;
        }
        ul.list-bullets > li:before {
            content: "* ";
            font-weight: bold;
            margin-right: 5px;
        }
        ul.list-bullets > li {
            margin-bottom: 5px;
        }
        .checkbox-item {
            margin-left: 60px;
            margin-bottom: 6px;
        }
        .checkbox-box {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 1px solid #000;
            margin-right: 10px;
            vertical-align: middle;
        }
        .letter-list {
            list-style-type: lower-alpha;
            padding-left: 50px;
        }
        .letter-list > li {
            margin-bottom: 6px;
        }
    </style>
</head>
<body>

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

    <div class="title-box">
        <h1>REGLEMENT DE LA CONSULTATION</h1>
        <div class="ref-box">
            Consultation N°: {{ $consultation->numero_consultation }}
        </div>
    </div>

    <div class="content">
        <p class="intro-text">La participation à l’offre de prix est soumise aux conditions suivantes :</p>
        
        <ul class="list-no-bullet">
            <li>
                <strong>• L’Offre financière doit être :</strong>
                <ul class="list-bullets">
                    <li>Conforme au modèle de bordereau des prix en annexe,</li>
                    <li>Imprimé sur papier à entête du soumissionnaire avec n° de :</li>
                </ul>
                
                <div class="checkbox-item"><span class="checkbox-box"></span>La Taxe professionnelle,</div>
                <div class="checkbox-item"><span class="checkbox-box"></span>L’identifiant fiscale,</div>
                <div class="checkbox-item"><span class="checkbox-box"></span>Le Registre du commerce,</div>
                <div class="checkbox-item"><span class="checkbox-box"></span>L’affiliation à la CNSS,</div>
                <div class="checkbox-item"><span class="checkbox-box"></span>Le RIB,</div>
                <div class="checkbox-item"><span class="checkbox-box"></span>L’Identifiant Commun de l’entreprise (ICE).</div>
            </li>
            
            <li>
                <strong>•</strong> Fermée dans une enveloppe cachetée portant le nom et l'adresse du candidat, et la mention très apparente : <br>
                <div style="margin-top: 10px; margin-left: 20px; font-weight: bold;">
                    « Offre de Prix N° {{ $consultation->numero_consultation }} à n’ouvrir qu’en commission ».
                </div>
            </li>
            
            <li>
                <strong>•</strong> Le jugement des offres est basé sur le montant total de l’offre de prix toutes taxes comprises. L’offre la moins-disante sera retenue.
            </li>
            
            <li>
                <strong>•</strong> L’attributaire sera appelé à présenter un dossier administratif composé des pièces suivantes :
                <ol class="letter-list">
                    <li>Une copie du Statut de la société (ou pouvoirs conférés),</li>
                    <li>Une copie d'une attestation fiscale pour soumissionnaires aux marchés publics,</li>
                    <li>Une copie d'une attestation d'inscription au registre de commerce,</li>
                    <li>Une copie de l'attestation d'affiliation à la CNSS,</li>
                </ol>
            </li>
        </ul>
    </div>

</body>
</html>
