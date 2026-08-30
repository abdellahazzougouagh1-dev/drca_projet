<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Règlement de Consultation / CPS</title>
    <style>
        @page { margin: 20mm 15mm 20mm 15mm; }
        body { font-family: 'Times New Roman', Times, serif; font-size: 12pt; line-height: 1.5; text-align: justify; color: #000; }
        .page-break { page-break-before: always; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .underline { text-decoration: underline; }
        h1, h2, h3, h4, h5 { margin: 12px 0; padding: 0; text-align: center; }
        .title-main { font-size: 14pt; font-weight: bold; text-align: center; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 0.5px; }
        .article-title { font-weight: bold; text-decoration: underline; margin-top: 25px; margin-bottom: 10px; font-size: 12pt; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 10px; }
        table.bordered td, table.bordered th { border: 1px solid #000; padding: 6px 8px; vertical-align: top; }
        .header-table { width: 100%; margin-bottom: 20px; border: none; }
        .header-table td { border: none; vertical-align: middle; }
        ul, ol { margin-top: 8px; margin-bottom: 12px; padding-left: 25px; }
        p { margin-top: 0; margin-bottom: 12px; }
        .mb-10 { margin-bottom: 10px; }
        .mt-10 { margin-top: 10px; }
    </style>
</head>
<body>

    <!-- PAGE 1: PAGE DE GARDE -->
    <table class="header-table">
        <tr>
            <td style="width: 33%; text-align: left;">
                @if(file_exists(public_path('images/logo-onca.png')))
                    <img src="{{ public_path('images/logo-onca.png') }}" style="height: 65px;" alt="ONCA">
                @endif
            </td>
            <td style="width: 34%; text-align: center;"></td>
            <td style="width: 33%; text-align: right;">
                @if(file_exists(public_path('images/sceau-maroc.png')))
                    <img src="{{ public_path('images/sceau-maroc.png') }}" style="height: 65px;" alt="Royaume du Maroc">
                @endif
            </td>
        </tr>
    </table>

    <div class="title-main mt-10">DIRECTION REGIONALE DU CONSEIL AGRICOLE<br>DE RABAT SALE KENITRA</div>
    <div class="title-main" style="margin-top: 30px;">APPEL D’OFFRES OUVERT NATIONAL SUR OFFRES DE PRIX<br>N° {{ $marche->aoo->num_aoo ?? '........................' }}</div>

    <div style="border: 2px solid #000; border-radius: 10px; padding: 20px; margin-top: 40px; text-align: center; font-weight: bold; font-size: 14px; text-transform: uppercase;">
        {{ $marche->aoo->objet ?? $marche->objet_marche ?? 'OBJET DU MARCHE' }}
        <br><br>
        @if($marche->lot && strtolower(trim($marche->lot)) !== 'lot unique' && strtolower(trim($marche->lot)) !== 'unique')
            LOT : {{ $marche->lot }} - {{ $marche->objet_marche }}
        @endif
    </div>

    <div class="title-main" style="margin-top: 60px; font-size: 18px; text-decoration: underline;">REGLEMENT DE CONSULTATION</div>
    
    <div class="text-center mt-10" style="font-size: 12px;">
        Passé en application de l'alinéa 1 du paragraphe 1, l'alinéa a) du paragraphe 3 de la partie I) de l'article 19 et du 
        paragraphe 1 de l'article 20 et de l’alinéa b du paragraphe 3 de l’article 20 du décret n° 2-22-431 du 15 
        Chaabane 1444 (08 mars 2023) relatif aux marchés publics.
    </div>

    <!-- PAGE 2: SOMMAIRE -->
    <div class="page-break"></div>
    <div class="title-main" style="text-decoration: underline;">SOMMAIRE</div>
    <div style="line-height: 1.8;">
        ARTICLE 1 : OBJET DU REGLEMENT DE LA CONSULTATION : ...............................................3<br>
        ARTICLE 2 : REPARTITION EN LOTS...............................................................................................3<br>
        ARTICLE 3 : MAITRE D’OUVRAGE..................................................................................................3<br>
        ARTICLE 4 : CONTENU DU DOSSIER D’APPEL D’OFFRES........................................................3<br>
        ARTICLE 5 : MODIFICATION DU CONTENU DU DOSSIER D’APPEL D’OFFRES..................3<br>
        ARTICLE 6 : DEMANDE D’ECLAIRCISSEMENT ET COMMUNICATION .................................4<br>
        ARTICLE 7 : CONDITIONS REQUISES DES CONCURRENTS.....................................................5<br>
        ARTICLE 8 : JUSTIFICATION DES CAPACITES ET LES QUALITES DES CONCURRENTS.5<br>
        ARTICLE 9 : CAUTIONNEMENT PROVISOIRE ............................................................................8<br>
        ARTICLE 10 : CONTENU DES DOSSIERS DES OFFRES DES CONCURRENTS ....................9<br>
        ARTICLE 11 : PRESENTATION DES DOSSIERS DES OFFRES ..................................................10<br>
        ARTICLE 12 : DEPOT ET RETRAIT DES PLIS DES CONCURRENTS.......................................10<br>
        ARTICLE 13 : DELAI DE VALIDITE DES OFFRES........................................................................11<br>
        ARTICLE 14 : LANGUE D’ETABLISSEMENT DES PIECES ET DES OFFRES.........................11<br>
        ARTICLE 15 : OUVERTURE DES PLIS DES CONCURRENTS ...................................................11<br>
        ARTICLE 16 : APPRECIATION DES DOSSIERS ADMINISTRATIFS ........................................12<br>
        ARTICLE 17 : EVALUATION DES OFFRES DES CONCURRENTS............................................12<br>
        ARTICLE 18 : RESULTATS DEFINITIFS DE L’APPEL D’OFFRES............................................15<br>
        ARTICLE 19 : MONNAIE DE FORMULATION DES OFFRES .....................................................15<br>
        ARTICLE 20 : MESURES COERCITIVES.........................................................................................15<br>
        ANNEXES ................................................................................................................................................17<br>
        PIECE N° 1 (DECLARATION SUR L'HONNEUR).............................................................................18<br>
        PIECE N° 2 (ACTE D'ENGAGEMENT)................................................................................................22<br>
        PIECE N° 3 (NOTE MOYENS HUMAINS ET TECHNIQUES)..........................................................25<br>
    </div>

    <!-- PAGES 3-17: CORPS DU DOCUMENT -->
    <div class="page-break"></div>
    <div class="article-title">ARTICLE 1 : OBJET DU REGLEMENT DE LA CONSULTATION :</div>
    <p>Le présent règlement de consultation concerne l’appel d’offres ouvert national sur offres de prix n° <strong>{{ $marche->aoo->num_aoo ?? '...' }}</strong> ayant pour objet <strong>{{ $marche->aoo->objet ?? $marche->objet_marche ?? '...' }}</strong>.</p>
    <p>Il a été établi en vertu des dispositions de l’article 21 du décret n° 2-22-431 du 15 Chaabane 1444 (08 mars 2023) relatif aux marchés publics.</p>
    <p>Les prescriptions du présent règlement ne peuvent en aucune manière déroger ou modifier les conditions et les formes prévues par le décret n° 2-22-431 précité. Toute disposition contraire audit décret est nulle et non avenue.</p>
    <p>Seules sont valables les prescriptions complémentaires conformes aux dispositions de l’article 21 et des autres articles du décret précité.</p>

    <div class="article-title">ARTICLE 2 : REPARTITION EN LOTS</div>
    <p>Le présent appel d’offres concerne un marché {{ $marche->lot && strtolower(trim($marche->lot)) !== 'lot unique' ? 'en plusieurs lots' : 'en lot unique' }}.</p>
    <p>Chaque concurrent peut présenter son offre pour un lot ou les deux lots mais ne peut être attributaire que d’un seul lot.</p>
    <p class="font-bold underline">Pour le mode d’attribution des lots :</p>
    <p>Le maître d’ouvrage procède à l’ouverture, à l’examen des offres de chaque lot et à l’attribution des lots, lot par lot, dans l’ordre de leur énumération dans le dossier d’appel d’offres.</p>

    <div class="article-title">ARTICLE 3 : MAITRE D’OUVRAGE</div>
    <p>Le maître d’ouvrage du marché qui sera passé suite au présent appel d’offres est le Directeur Régional du Conseil Agricole de Rabat-Salé-Kénitra, Monsieur <strong>BOUDRA Abdelaâli</strong>, en sa qualité de sous-ordonnateur, agissant au nom et pour le compte de l’ONCA.</p>

    <div class="article-title">ARTICLE 4 : CONTENU DU DOSSIER D’APPEL D’OFFRES</div>
    <p>Conformément aux dispositions de l’article 22 du décret n° 2-22-431 précité, le dossier d’appel d’offres doit comprendre :</p>
    <ol type="a">
        <li>Une copie de l’avis d’appel d’offres ;</li>
        <li>Un exemplaire du cahier des prescriptions spéciales ;</li>
        <li>Le modèle de l’acte d’engagement ;</li>
        <li>Le modèle du bordereau des prix et du détail estimatif ;</li>
        <li>Le modèle de déclaration sur l’honneur ;</li>
        <li>Le présent règlement de consultation.</li>
    </ol>

    <div class="article-title">ARTICLE 5 : MODIFICATION DU CONTENU DU DOSSIER D’APPEL D’OFFRES</div>
    <p>Conformément aux dispositions des paragraphes 7 de l’article 22 du décret n° 2-22-431 du 15 Chaabane 1444 (08 mars 2023) relatif aux marchés publics, le maître d’ouvrage peut introduire, à titre exceptionnel, des modifications dans le dossier d’appel d’offres sans changer l’objet du marché. Dans ce cas, ces modifications sont communiquées à tous les concurrents ayant téléchargé ledit dossier, et introduites dans les dossiers mis à la disposition des autres concurrents.</p>
    <p>Ces modifications peuvent intervenir à tout moment à l’intérieur du délai initial de publicité et au plus tard sept jours avant la date de la séance d’ouverture des plis.</p>
    <p>Passé ce délai, le maître d’ouvrage doit, par avis rectificatif, reporter la date de la séance d’ouverture des plis.</p>
    <p>Lorsque les modifications introduites dans le dossier d’appel d’offres nécessitent la publication d’un avis rectificatif, celui-ci est publié conformément aux dispositions du premier alinéa du deuxième paragraphe de l’article 23 du décret précité.</p>
    <p>Dans ce cas, la séance d’ouverture des plis ne peut être tenue qu'après l'expiration d'un délai minimum de dix jours. Ce délai court à partir du lendemain de la date de parution de l’avis rectificatif dans le dernier support de publication, sans que la date de la nouvelle séance ne soit antérieure à celle prévue par l’avis de publicité initial.</p>
    <p>Dans tous les cas, le délai de publicité prévu au troisième alinéa du deuxième paragraphe du I) de l’article 23 décret n° 2-22-431 du 08 mars 2023 relatif aux marchés publics doit être respecté.</p>
    <p>Les concurrents ayant téléchargé le dossier d’appel d’offres doivent être informés des modifications qui y ont été apportées et de la nouvelle date d’ouverture des plis, le cas échéant.</p>
    <p>L’avis rectificatif intervient dans l’un des cas suivants :</p>
    <ol type="a">
        <li>lorsque le maître d’ouvrage introduit des modifications dans le dossier d’appel d’offres, sans se conformer au délai de sept jours prévu ci-dessus ;</li>
        <li>lorsque les modifications à introduire dans le dossier d’appel d’offres nécessitent un délai supplémentaire pour la préparation des offres ;</li>
        <li>lorsqu’il s’agit de redresser des erreurs manifestes constatées dans l’avis publié ;</li>
        <li>lorsque le maître d’ouvrage constate, après publication de l’avis, que le délai qui court entre la date de publication de l’avis et la date de la séance d’ouverture des plis n’est pas conforme au délai de publicité prévu à l’article 23 du décret précité.</li>
    </ol>
    <p>Lorsqu’un concurrent estime que le délai prévu par l’avis de publicité pour la préparation des offres n’est pas suffisant au regard de la complexité des prestations objet du marché, il peut, au cours de la première moitié du délai de publicité, demander au maître d’ouvrage, par lettre transmise par tout moyen pouvant donner date certaine, le report de la date de la séance d’ouverture des plis. Cette lettre doit comporter tous les éléments permettant au maître d’ouvrage d’apprécier la demande de report.</p>
    <p>Si le maître d’ouvrage reconnaît le bien-fondé de la demande du concurrent dont il est saisi, il procède au report de la date de la séance d’ouverture des plis. Le report, dont la durée est laissée à l’appréciation du maître d’ouvrage, fait l’objet d’un avis rectificatif qui est publié dans les mêmes formes que l’avis d’appel d’offres. Il ne peut être procédé au report de la date de la séance d’ouverture des plis qu’une seule fois, quel que soit le concurrent qui le demande. Le maître d’ouvrage informe de ce report les concurrents ayant retiré ou téléchargé les dossiers d’appel d’offres.</p>

    <div class="article-title">ARTICLE 6 : DEMANDE D’ECLAIRCISSEMENT ET COMMUNICATION D’INFORMATIONS AUX CONCURRENTS</div>
    <p>Conformément à l’article 25 du décret précité, les demandes d’informations ou renseignements formulées par les concurrents doivent être adressées dans un délai de sept (07) jours au moins avant la date prévue pour la séance d’ouverture des plis via le portail des marchés publics (www.marchespublics.gov.ma), et ce, conformément aux dispositions de l’article 09 l’arrêté du ministre délégué auprès de la ministre de l’économie et des finances, chargé du budget n° 1692-23 du 4 hija 1444 (23 juin 2023) relatif à la dématérialisation des procédures, des documents et des pièces relatifs aux marchés publics.</p>
    <p>Cette demande n’est recevable que si elle parvient au maître d’ouvrage au moins sept jours avant la date prévue pour la séance d’ouverture des plis.</p>
    <p>Le maître d’ouvrage doit répondre, dans les mêmes formes, à toute demande d’information ou d’éclaircissement reçue, au plus tard trois jours avant la date prévue pour la séance d’ouverture des plis.</p>
    <p>Tout éclaircissement ou renseignement fourni par le maître d’ouvrage à un concurrent à la demande de ce dernier doit être communiqué, le même jour et dans les mêmes formes, aux autres concurrents ayant téléchargé le dossier d’appel d’offres et aux membres de la commission d’appel d’offres.</p>
    <p class="font-bold underline">Cet éclaircissement ou renseignement est mis à la disposition de tout concurrent potentiel dans le portail des marchés publics.</p>

    <div class="article-title">ARTICLE 7 : CONDITIONS REQUISES DES CONCURRENTS</div>
    <p>Conformément aux dispositions de l’article 27 du décret n° 2-22-431 précité :</p>
    <p><strong>1. Seules peuvent participer au présent appel d’offres les personnes physiques ou morales qui :</strong></p>
    <ul>
        <li>Justifient des capacités juridiques, techniques et financières requises,</li>
        <li>Sont en situation fiscale régulière, pour avoir souscrit leurs déclarations et réglé les sommes exigibles dûment définitives ou, à défaut de règlement, constitué des garanties jugées suffisantes par le comptable chargé du recouvrement. Et ce, conformément à la législation en vigueur en matière de recouvrement des créances publiques ;</li>
        <li>Sont affiliées à la CNSS ou à un régime particulier de prévoyance sociale, et souscrivent de manière régulière leurs déclarations de salaires et sont en situation régulière auprès de ces organismes.</li>
        <li>Exercent l’une des activités en rapport avec l’objet du marché ;</li>
    </ul>
    <p><strong>2. Ne sont pas admises à participer à la présente consultation :</strong></p>
    <ul>
        <li>Les personnes qui sont en liquidation judiciaire.</li>
        <li>Les personnes qui sont en redressement judiciaire, sauf autorisation spéciale délivrée par l’autorité judiciaire compétente.</li>
        <li>Les personnes ayant fait l’objet d’une exclusion temporaire ou définitive prononcé dans les conditions fixées par l’article 152 dudit décret ;</li>
        <li>Les personnes qui représentent plus d’un concurrent dans un même marché en lot unique ou d’un même lot lorsqu’il s’agit d’un marché alloti ;</li>
        <li>Les prestataires de services ayant contribué à la préparation du dossier de l’appel d’offres concernés ;</li>
        <li>Les titulaires dont les marchés ont fait l’objet de résiliation pour une faute qui leur incombe au titre des marchés d’achèvement y afférents.</li>
    </ul>

    <div class="article-title">ARTICLE 8 : JUSTIFICATION DES CAPACITES ET LES QUALITES DES CONCURRENTS</div>
    <p>Les dossiers présentés par les concurrents doivent comporter, outre le cahier des prescriptions spéciales et le règlement de consultation paraphés et signés, les pièces des dossiers administratifs et techniques conformément aux dispositions de l’article 28 du décret n° 2-22-431 précité. Les pièces constitutives de chaque dossier sont comme suit :</p>
    
    <p><strong>1- LE DOSSIER ADMINISTRATIF</strong></p>
    <p>Ce dossier doit contenir les pièces suivantes :</p>
    <p><strong>1-1-Pour chaque concurrent, au moment de la présentation des offres :</strong></p>
    <ol type="a">
        <li>La ou les pièces justifiant les pouvoirs conférés à la personne agissant au nom du concurrent. Ces pièces varient selon la forme juridique du concurrent :
            <ul>
                <li>S’il s’agit d’un auto-entrepreneur ou d’une personne physique agissant pour son propre compte, aucune pièce n’est exigée ;</li>
                <li>S’il s’agit d’un représentant du concurrent, celui-ci doit présenter, selon le cas :
                    <ul>
                        <li>Une copie certifiée conforme de la procuration légalisée, lorsqu’il agit au nom d’une personne physique ;</li>
                        <li>Un extrait des statuts de la société et/ou copie certifiée conforme à l’original du procès-verbal de l’organe compétent lui conférant le pouvoir d’agir au nom de cette société ;</li>
                        <li>L’acte par lequel la personne habilitée délègue son pouvoir à une tierce personne, le cas échéant.</li>
                    </ul>
                </li>
                <li>S’il s’agit d’une coopérative ou d’une union de coopératives, la ou les pièces justifiant les pouvoirs conférés à la personne agissant au nom de la coopérative ou de l'union de coopératives.</li>
            </ul>
        </li>
        <li>La déclaration sur l’honneur, en un exemplaire unique, qui doit comporter les mentions prévues à l’article 29 du décret n° 2-22-431 du 15 Chaabane 1444 (08 Mars 2023) relatif aux marchés publics tel qu’il a été modifié et complété (pièce n° 1 en annexe) ;</li>
        <li>L’original du récépissé du cautionnement provisoire ou l’attestation de la caution personnelle et solidaire en tenant lieu, le cas échéant ;</li>
        <li>Pour les groupements, la convention constitutive du groupement prévue à l’article 150 du décret n° 2-22-431 ou sa copie certifiée conforme. Cette convention doit être accompagnée d'une note indiquant notamment l'objet de la convention, la nature du groupement, le mandataire, la durée de la convention, le ou les comptes bancaires, la répartition des prestations, le cas échéant ;</li>
    </ol>
    <p><strong>1-2- Pour le concurrent auquel il est envisagé d’attribuer le marché, dans les conditions fixées à l’article 43 du décret n° 2-22-431 du 15 chaabane 1444 (08 Mars 2023) relatif aux marchés publics :</strong></p>
    <ol type="a">
        <li>Une attestation ou sa copie certifiée conforme à l’originale délivrée depuis moins d’un an par l’administration compétente du lieu d’imposition certifiant que le concurrent est en situation fiscale régulière ou à défaut de paiement qu’il a constitué les garanties prévues à l’article 27 du décret n° 2-22-431 du 08/03/2023. Cette attestation doit mentionner l’activité au titre de laquelle le concurrent est imposé ;</li>
        <li>Une attestation ou sa copie certifiée conforme à l’originale délivrée depuis moins d’un an par la Caisse Nationale de Sécurité Sociale ou par tout autre organisme de prévoyance sociale certifiant que le concurrent est en situation régulière envers l’organisme concerné.</li>
        <li>Une copie du certificat d’immatriculation au registre de commerce (modèle 9) délivrée depuis moins d’un an par l’administration compétente pour les personnes assujetties à l’obligation d’immatriculation conformément à la législation en vigueur.</li>
    </ol>
    <p>La date de production des pièces prévues aux b) et c) ci-dessus sert de base pour l'appréciation de leur validité.</p>
    
    <p class="font-bold underline">&gt;Lorsque le concurrent est un établissement public, il doit fournir :</p>
    <p>1- au moment de la présentation de l’offre, outre le dossier technique et les pièces du dossier administratif prévues aux b) et c) de l’alinéa 1 du A du I) de l’article 28 du décret n° 2-22-431, une copie du texte l’habilitant à exercer les missions en relation avec les prestations objet du marché.</p>
    <p>2- S’il est envisagé de lui attribuer le marché :</p>
    <ol type="a">
        <li>une attestation ou sa copie certifiée conforme à l’original délivrée depuis moins d’un an par le percepteur du lieu d’imposition certifiant qu’il est en situation fiscale régulière ou à défaut de paiement qu’il a constitué les garanties tel que prévu par l’article 27 du décret n° 2-22-431.<br>
        Cette attestation doit mentionner l’activité au titre de laquelle le concurrent est imposé.<br>
        L’attestation précitée n’est exigée que des établissements publics soumis à l’impôt ;</li>
        <li>une attestation ou sa copie certifiée conforme à l’original délivrée depuis moins d’un an par la Caisse nationale de sécurité sociale ou tout autre organisme de prévoyance sociale certifiant que le concurrent est en situation régulière envers l’organisme concerné. La date de production, au maître d’ouvrage, des pièces prévues aux a) et b) ci–dessus sert de base pour l’appréciation de leur validité.</li>
    </ol>

    <p class="font-bold underline">&gt;Lorsque le concurrent est une coopérative ou une union de coopératives, il doit fournir :</p>
    <p>1- Au moment de la présentation de l'offre, outre le dossier technique, et en plus des pièces prévues à l'alinéa 1 du I-A de l’article 28 du décret n°2-22-431 précité, <strong>une attestation d'inscription au registre local des coopératives.</strong></p>
    <p>2- S’il est retenu pour être attributaire du marché :</p>
    <ol type="a">
        <li>Une attestation ou sa copie certifiée conforme à l’original délivrée depuis moins d’un an par le percepteur du lieu d’imposition certifiant que le concurrent est en situation fiscale régulière ou à défaut de paiement qu’il a constitué les garanties tel que prévu à l’article 27 du décret n°2-22-431 précité.<br>
        Cette attestation doit mentionner l’activité au titre de laquelle la coopérative ou l’union de coopératives est imposée ;</li>
        <li>Une attestation ou sa copie certifiée conforme à l'originale délivrée depuis moins d'un an par la caisse nationale de sécurité sociale certifiant que la coopérative ou l'union de coopératives est en situation régulière envers cet organisme, conformément aux dispositions prévues à cet effet à l'article 26 du décret n°2-22-431 précité.</li>
    </ol>
    <p class="font-bold">La date de production des pièces prévues aux b) et c) ci-dessus sert de base pour l'appréciation de leur validité.</p>
    
    <p class="font-bold underline">&gt; Lorsque le concurrent est un auto-entrepreneur, il doit fournir :</p>
    <p>1- Au moment de la présentation de l'offre, outre le dossier technique, et en plus des pièces prévues à l'alinéa 1 du I-A de l’article 28 du décret n°2-22-431 précité, <strong>le certificat d'inscription au registre national de l'auto-entrepreneur ou sa copie certifiée conforme à l'original délivré depuis moins d’un an.</strong></p>
    <p>2- S'il est retenu pour être attributaire du marché, une attestation ou sa copie certifiée conforme à l'original délivrée depuis moins d'un an par l'administration compétente du lieu d'imposition certifiant qu'il est en situation fiscale régulière ou à défaut de paiement qu'il a constitué les garanties prévues à l'article 27 du décret n°2-22-431 précité. Cette attestation doit mentionner l’activité au titre de laquelle l’auto–entrepreneur est imposé.</p>
    <p class="font-bold">La date de production des pièces exigées pour l'autoentrepreneur, la coopérative ou l'union des coopératives sert de base pour l'appréciation de leur validité.</p>

    <p><strong>2- LE DOSSIER TECHNIQUE</strong></p>
    <p>Pour les concurrents soumissionnaires, le dossier technique du concurrent comprendra :</p>
    <ul>
        <li>Une note indiquant les moyens humains et techniques du concurrent et mentionnant, le cas échéant, le lieu, la date, la nature et l’importance des prestations qu’il a exécutées ou à l’exécution desquelles le concurrent a participé avec précision de la qualité de sa participation.</li>
        <li>Deux attestations, ou leurs copies certifiées conformes à l'original, délivrées par les maîtres d'ouvrage publics ou privés ou par les hommes de l'art sous la direction desquels le concurrent a exécuté lesdites prestations ou par les titulaires de marchés au titre des prestations sous-traitées et qui doivent être obligatoirement <strong>de même nature que celles du présent appel d’offres</strong>. Chaque attestation précise notamment la nature des prestations, leur montant et l'année de réalisation ainsi que le nom et la qualité du signataire et son appréciation.</li>
    </ul>
    <p>Toute attestation de référence qui ne précise pas les mentions susvisées ou qui n’est pas certifiée conforme à l’original ne sera pas prise en considération quelle que soit son importance.</p>
    <p>La commission est habilitée à se renseigner auprès des autres administrations pour vérifier et contrôler les affirmations du concurrent. Toute omission ou insuffisance majeure d'information peut être considérée par la commission, comme une fausse déclaration visant à tromper les membres de la commission et pourrait conduire à l'élimination de l'offre du concurrent.</p>

    <div class="article-title">ARTICLE 9 : CAUTIONNEMENT PROVISOIRE</div>
    <ol>
        <li>Le candidat fournira un cautionnement de soumission (ou cautionnement provisoire) qui fera partie intégrante de sa soumission. Ce cautionnement est fixé au niveau des CPS et indiqué au niveau des avis de l'appel d'offres.</li>
        <li>Le concurrent est tenu de constituer et présenter son cautionnement provisoire auprès des organismes agrées visés à l’article 15 de l’arrêté du ministre délégué auprès de la ministre de l’économie et des finances, chargé du budget n° 1692-23 du 23 juin 2023 relatif à la dématérialisation des procédures, des documents et des pièces relatifs aux marchés publics, <strong>obligatoirement</strong> par voie électronique via le portail des marchés publics (<a href="http://www.marchespublics.gov.ma">www.marchespublics.gov.ma</a>). <strong>A défaut, ladite pièce est considérée comme nulle et non avenue.</strong></li>
        <li>Le cautionnement provisoire est nécessaire pour protéger le maitre d'ouvrage contre les risques de désistement du candidat avant l'expiration du délai de validité des offres et qui justifierait la saisie de la caution ;</li>
        <li>Le cautionnement provisoire sera libellé en Dirham Marocain et sera émis par un établissement agréé par le Ministère chargés des finances, dans la forme prévue par la législation en vigueur.</li>
        <li>En application des dispositions du paragraphe 8 de l’article 39 du décret n° 2-22-431 précité, toute offre non accompagnée de l'original du récépissé du cautionnement provisoire ou de l'attestation de la caution personnelle et solidaire en tenant lieu, non conforme sera écartée par la commission d'appel d'offres comme ne satisfaisant pas aux conditions de l'appel d'offres. Sera également écartée de la concurrence, toute offre dont l'objet du cautionnement provisoire n'est pas conforme à celui de l’appel d'offres ou dont le montant est inférieur à la somme demandée ou qui comporte des réserves ou des restrictions.</li>
        <li>L'original du récépissé du cautionnement provisoire ou l'attestation de la caution personnelle et solidaire en tenant lieu de tout candidat dont l'offre n'est pas retenue à la suite du jugement final lui sera restitué conformément à l’article ci-dessous relatif aux résultats définitifs de l’appel d’offres.</li>
        <li>En application des dispositions de l'article 143 du décret n° 2-22-431 précité, si la notification de l'approbation du marché n'est pas intervenue dans le délai initial de validité des offres ou, le cas échéant, dans le délai prorogé de validité des offres, l'attributaire est libéré de son engagement vis-à-vis du maître d'ouvrage. Dans ce cas, mainlevée lui est donnée de son cautionnement provisoire.</li>
        <li>Le cautionnement provisoire de l'attributaire du marché sera libéré après approbation et notification du marché, et après dépôt du cautionnement définitif.</li>
        <li>Le cautionnement provisoire reste acquis à la Direction Régionale du Conseil Agricole de Rabat-Salé-Kénitra dans l’un des cas suivants :
            <ul>
                <li>Si l’offre du concurrent est écartée pour les motifs suivants :
                    <ul>
                        <li>Ne répond pas dans le délai qui lui est imparti ;</li>
                        <li>Ne produit pas les pièces exigées ou produits des pièces non conformes ;</li>
                        <li>Ne confirme pas des rectifications des erreurs matérielles relevées ;</li>
                        <li>Ne régularise pas des discordances constatées entre les diverses pièces de son dossier.</li>
                    </ul>
                </li>
                <li>Si le concurrent retire son offre pendant le délai de validité des offres ;</li>
                <li>Si l'attributaire refuse de signer le marché ;</li>
                <li>Si le titulaire refuse d’accuser réception de l’approbation du marché qui lui a été notifiée dans le délai fixé à l’article 143 du décret n° 2-22-431 précité ;</li>
                <li>Si le titulaire ne réalise pas le cautionnement définitif dans le délai de 30 jours qui suivent la notification de l'approbation du marché.</li>
            </ul>
        </li>
    </ol>

    <div class="article-title">ARTICLE 10 : CONTENU DES DOSSIERS DES OFFRES DES CONCURRENTS</div>
    <p>Les dossiers présentés par les concurrents doivent comporter :</p>
    <ol>
        <li><strong>Le cahier de prescriptions spéciales</strong> et le <strong>Règlement de consultation</strong> paraphés sur toutes les pages et signés en dernière page par le concurrent ou la personne habilitée par lui à cet effet,</li>
        <li><strong>Le dossier administratif</strong> prévu à l'article 8 ci-dessus,</li>
        <li><strong>Le dossier technique</strong> prévu à l'article 8 ci-dessus,</li>
        <li><strong>L'offre financière</strong> comprenant :
            <ol type="a">
                <li><strong>L’acte d'engagement</strong> par lequel le concurrent s'engage à réaliser les prestations objet du marché conformément aux conditions prévues aux cahiers des charges et moyennant un prix qu'il propose, établi en un seul exemplaire selon le modèle en annexe.<br>
                <strong>L’acte d'engagement signé par le concurrent ou son représentant dûment habilité, doit comporter l’ensemble des indications requises y compris le relevé d’identité bancaire (RIB).</strong><br>
                Lorsque l’acte d’engagement est souscrit par un groupement tel qu’il est défini à l’article 150 du décret n° 2-22-431 du 8 mars 2023 relatif aux marchés public, il doit être signé soit par chacun des membres du groupement, soit seulement par le mandataire, si celui-ci justifie des habilitations sous forme de procurations légalisées pour représenter les membres du groupement lors de la procédure de passation du marché. L’acte d’engagement du groupement concerné doit également préciser le montant correspondant à la part revenant à chacun des membres dudit groupement.</li>
                <li><strong>Le bordereau des prix-détail estimatif</strong>, établi conformément au modèle figurant au dossier d'appel d'offres.</li>
            </ol>
        </li>
    </ol>
    <p>Le montant total de l'acte d'engagement doit être libellé en chiffres et en toutes lettres.</p>
    <p>Les prix unitaires et le montant total du bordereau des prix-détail estimatif doivent être libellés en chiffres.</p>
    <p>En cas de discordance entre le montant total de l'acte d'engagement et du bordereau des prix-détail estimatif, le montant de ce dernier document est tenu pour bon pour établir le montant réel de l'acte d'engagement.</p>
    <p class="font-bold">Les pièces de l’offre financière, lorsqu’elles sont souscrites par un groupement tel qu'il est défini à l'article 150 du décret n° 2-22-431 précité, elles doivent être signées soit par chacun des membres du groupement ; soit seulement par le mandataire si celui-ci justifie des habilitations sous forme de procurations légalisées pour représenter les membres du groupement lors de la procédure de passation du marché.</p>
    <p class="font-bold underline">Avertissement :</p>
    <p>Toute omission ou insuffisance majeure d’information peut être considérée par la commission comme une fausse déclaration visant à tromper les membres de la commission et pourrait conduire à l’élimination de l’offre du candidat.</p>
    <p>Toute photocopie devra obligatoirement être certifiée conforme par l'autorité compétente. Il est à signaler que l'absence, d'une des pièces citées ci-dessus au niveau des dossiers administratifs et techniques, pourra entraîner le rejet pur et simple de l'offre sans possibilité de contestations de la part du ou des candidats.</p>

    <div class="article-title">ARTICLE 11 : PRESENTATION DES DOSSIERS DES OFFRES DES CONCURRENTS</div>
    <p>Conformément aux dispositions de l’article 32 du décret n° 2-22-431 précité et de l’Arrêté du Ministre de l’Economie et des Finances N° 1692-23 du 4 Hija 1444 (23 juin 2023) relatif à la dématérialisation des procédures, des documents et des pièces relatives aux marchés publics, chaque concurrent doit déposer un pli électronique contenant deux enveloppes électroniques distinctes, comprenant pour chacune :</p>
    <ul>
        <li><strong>La première enveloppe</strong> : contient, outre les pièces des dossiers administratif et technique, le cahier des prescriptions spéciales et le règlement de consultation paraphés et signés et portant la mention « lu et accepté » par le concurrent ou son représentant dûment habilité ;</li>
        <li><strong>La deuxième enveloppe</strong> : contient l’offre financière du concurrent.</li>
    </ul>
    <p>Conformément aux dispositions de l’article 12 de l’arrêté du Ministre Délégué auprès de la Ministre de l’Economie et des Finances, chargé du budget n° 1692-23 précité, chaque concurrent est tenu de présenter son offre en respectant la présentation exigée par le Portail des Marchés Publics, ainsi, chacune des pièces constituant la réponse du concurrent à la consultation, est insérée, individuellement, dans l’enveloppe électronique la concernant, et est signée, électroniquement, par le concurrent ou la personne dûment habilitée à le représenter.</p>
    <p>Cette signature électronique des pièces et documents s’effectue, à travers le Portail des Marchés Publics, au moyen d’un certificat de signature électronique conformément aux dispositions des textes législatifs et réglementaires en vigueur et aux conditions d’utilisation du Portail des Marchés Publics.</p>

    <div class="article-title">ARTICLE 12 : DEPOT ET RETRAIT DES PLIS DES CONCURRENTS</div>
    <p>Conformément aux dispositions de l’article 34, 35 et 135 du décret 2-22-431 et de l’arrêté du ministre délégué auprès de la ministre de l’économie et des finances, chargé du budget n° 1692-23 du 4 hija 1444 (23 juin 2023) relatif à la dématérialisation des procédures, des documents et des pièces relatifs aux marchés publics, le dépôt et le retrait des plis et des offres des concurrents s’effectuent <strong>obligatoirement par voie électronique via le portail des marchés publics</strong> (<a href="http://www.marchespublics.gov.ma">www.marchespublics.gov.ma</a>).</p>
    <p>Chacune des pièces constituant la réponse du concurrent à la consultation, est insérée, individuellement, dans l’enveloppe électronique la concernant.</p>
    <p>Conformément aux conditions d’utilisation du portail des marchés publics, chaque pièce est signée, électroniquement, par le concurrent ou la personne dûment habilitée à le représenter, à l’exception des pièces dématérialisées.</p>
    <p>Lorsqu’il s’agit d’un groupement, ces pièces sont signées, soit par l’ensemble des membres du groupement, soit uniquement par le mandataire conformément aux dispositions du paragraphe C) de l’article 150 du décret précité n° 2-22-431.</p>
    <p>Tout pli électronique déposé postérieurement à la date limite de remise des plis est automatiquement rejeté par le portail des marchés publics.</p>
    <p>Tout pli déposé peut être retiré par le concurrent antérieurement au jour et à l’heure fixés pour la séance d’ouverture des plis.</p>
    <p>Le retrait de tout pli s’effectue au moyen du même certificat électronique ayant servi au dépôt de ce pli.</p>
    <p>Les informations relatives audit retrait sont enregistrées automatiquement sur le registre de dépôt des plis. Les concurrents ayant retiré leurs plis peuvent présenter de nouveaux plis dans les conditions prévues au présent chapitre et avant la date limite de remise des plis.</p>

    <div class="article-title">ARTICLE 13 : DELAI DE VALIDITE DES OFFRES</div>
    <p>Conformément aux dispositions de l’article 36 du décret n° 2-22-431 précité, les concurrents restent engagés par leurs offres pendant un délai de <strong>soixante (60) jours</strong> qui commence à courir à compter de la date de la séance d’ouverture des plis.</p>
    <p>Toutefois, lorsque la commission d’appel d’offres considère qu’elle n’est pas en mesure d’effectuer son choix pendant le délai de validité des offres prévu, le maître d’ouvrage saisit par lettre recommandée avec accusé de réception, les concurrents concernés, avant l’expiration de ce délai, en vue de leur demander une prorogation du délai de validité des offres d’une durée supplémentaire qu’il fixe conformément à l’article 36 du décret susvisé.</p>
    <p>A cet effet, le maître d’ouvrage fixe aux concurrents concernés une date limite pour faire connaître leurs réponses. Dans ce cas :</p>
    <ol type="a">
        <li>Les concurrents ayant donné, dans les mêmes formes, leur accord à la demande de prorogation, avant la date limite de réponse fixée par le maître d’ouvrage, restent engagés pendant le délai supplémentaire convenu ;</li>
        <li>Les concurrents qui n’ont pas donné leur accord à la demande de prorogation ou qui n’ont pas répondu dans le délai qui leur est imparti sont libérés de leurs engagements vis–à–vis du maître d’ouvrage et mainlevée leur est donnée de leur cautionnement provisoire, au plus tard quarante-huit heures à compter de la date limite de réponse fixée par le maître d’ouvrage ;</li>
        <li>Dans le cas où aucun des concurrents n’a donné son accord à la demande de prorogation ou n’a répondu dans le délai qui lui est imparti, mainlevée lui est donnée de son cautionnement provisoire, au plus tard quarante-huit heures à compter de la date limite de réponse fixée par le maître d’ouvrage. Dans ce cas, il est procédé à l’annulation de la procédure.</li>
    </ol>

    <div class="article-title">ARTICLE 14 : LANGUE D’ETABLISSEMENT DES PIECES ET DES OFFRES</div>
    <p>L'offre préparée par le candidat, ainsi que toute correspondance et tous documents concernant l'offre, échangés entre le candidat et le maître d’ouvrage seront rédigés en langue Française, étant entendu que tout document imprimé fourni par le candidat peut être rédigé en une autre langue, dès lors qu'il sera accompagné par une traduction en langue Française des passages intéressant l’offre. Dans ce cas, et aux fins de l'interprétation de l’offre, seule la traduction française fera foi.</p>

    <div class="article-title">ARTICLE 15 : OUVERTURE DES PLIS DES CONCURRENTS EN SEANCE PUBLIQUE</div>
    <p>La séance d'ouverture des plis des concurrents est publique. Elle se tient au lieu, au jour et à l'heure prévus par l’avis d’appel d’offres ; si ce jour est déclaré férié ou chômé, la réunion se tient le jour ouvrable suivant à la même heure.</p>

    <div class="article-title">ARTICLE 16 : APPRECIATION DES DOSSIERS ADMINISTRATIFS ET TECHNIQUES DES CONCURRENTS</div>
    <p class="font-bold underline">Première phase : Examen des dossiers administratifs et techniques</p>
    <p>La commission d’appel d’offres procédera à l’ouverture et l’examen des dossiers administratifs, et technique, conformément aux dispositions de l'article 136 du décret n° 2-22-431 précité et aux dispositions de l’arrêté du ministre délégué auprès de la ministre de l’économie et des finances, chargé du budget n° 1692-23 du 4 Hija 1444 (23 juin 2023) relatif à la dématérialisation des procédures, des documents et des pièces relatif aux marchés publics.</p>
    <p>La commission apprécie les capacités juridiques, techniques et financières des concurrents ainsi que leurs références professionnelles, au vu des éléments contenus dans leurs dossiers administratif et technique.</p>
    <p>Sont déclarés admissibles les concurrents dont le dossier administratif et le dossier technique satisfont aux exigences du décret n° 2-22-431 du 15 Chaabane (08 Mars 2023) relatif aux marchés publics et aux prescriptions du présent règlement de consultation.</p>
    <p class="font-bold">Seront écartés de la concurrence :</p>
    <ul>
        <li>Les concurrents qui sont en liquidation judiciaire ;</li>
        <li>Les concurrents qui sont en redressement judiciaire, sauf autorisation spéciale délivrée par l’autorité judiciaire compétente ;</li>
        <li>Les concurrents ayant fait l’objet d’une exclusion temporaire ou définitive ;</li>
        <li>Les concurrents qui n'ont pas respecté les prescriptions du paragraphe 2 de l'article 32 du décret n° 2-22-431 précité et rappelées en article 11 ci-dessus, en matière de présentation de leurs dossiers ;</li>
        <li>Les concurrents qui n'ont pas présenté les pièces exigées ;</li>
        <li>Les concurrents qui sont représentés par la même personne dans le cadre du marché ;</li>
        <li>Les concurrents qui ont produit des récépissés non originaux ou l'attestation de la caution personnelle et solidaire en tenant lieu non originale ou dont l'objet n'est pas conforme à celui de l'appel d'offres ou dont le montant est inférieur à la somme demandée ou qui comporte des réserves ou des restrictions ;</li>
        <li>Les concurrents, dont les capacités financières et techniques, sont jugés insuffisantes au regard des pièces présentées.</li>
    </ul>
    <p>Lorsque la commission constate des erreurs matérielles ou des discordances dans les pièces du dossier administratif, elle admet l'offre du concurrent concerné, sous réserve de l'introduction des rectifications nécessaires dans les conditions prévues à l'article 43 du décret n° 2-22-431 précité.</p>
    <p>A l'issue de l'examen des dossiers administratifs et techniques des concurrents, la commission arrête la liste des concurrents admissibles, et procède à l’ouverture de leurs offres financières.</p>

    <div class="article-title">ARTICLE 17 : EVALUATION DES OFFRES DES CONCURRENTS ET CHOIX DE L’OFFRE ECONOMIQUEMENT LA PLUS AVANTAGEUSE</div>
    <p><strong>I/ Evaluation des offres financières des concurrents à huis clos :</strong></p>
    <p>1) La commission écarte les concurrents dont les offres financières :</p>
    <ol type="a">
        <li>Ne sont pas conformes à l'objet du marché ;</li>
        <li>Ne sont pas signées ;</li>
        <li>Sont signées par une personne non habilitée à les engager au regard de la ou des pièces justifiant les pouvoirs conférés ;</li>
        <li>Expriment des restrictions ou des réserves ;</li>
        <li>Présentent des différences dans les libellés des prix, l'unité de compte ou les quantités par rapport aux données prévues dans le descriptif technique, dans le bordereau des prix-détail estimatif.</li>
    </ol>
    <p>2) La commission vérifie, ensuite, le résultat des opérations arithmétiques des offres financières des concurrents retenus. Elle rectifie, le cas échéant, les erreurs de calcul et rétablit les montants exacts des offres concernées.</p>
    <p>3) La commission écarte, selon les modalités et dans les conditions prévues à l’article 44 décret n° 2-22-431 du 08 Mars 2023, les offres financières jugées excessives et les offres financières jugées anormalement basses par rapport au montant de l’estimation établi par le maître d’ouvrage.</p>
    <p>4) La commission détermine, ensuite, le prix de référence des offres financières des concurrents conformément aux dispositions de l’article 44 du décret n° 2-22-431 du 08 Mars 2023 :</p>
    
    <p><strong>II/ Détermination du prix de référence, de l’offre excessive et de l’offre anormalement basse</strong></p>
    <p>Après avoir écarté les offres jugées excessives et anormalement basses, la commission détermine le prix de référence.</p>
    <p>Le prix de référence des offres est égal à la moyenne arithmétique résultant de l’estimation du coût des prestations établie par le maître d’ouvrage et de la moyenne des offres financières des concurrents retenus.</p>
    <p>Ce prix de référence est calculé selon la formule suivante :</p>
    <div style="border: 1px solid #000; padding: 10px; text-align: center; width: 60%; margin: 0 auto;">
        P = (E + Somme des offres financières / Nombre des offres financières) / 2
    </div>
    <p>Où :</p>
    <ul>
        <li>P : Prix de référence ;</li>
        <li>E : Estimation du coût des prestations établie par le maître d’ouvrage ;</li>
    </ul>
    <p>La commission procède ensuite au classement des offres des concurrents conformément aux dispositions de l’article 43 du décret des marchés publics au regard du prix de référence ainsi déterminé.</p>

    <p><strong>III) Choix de l’offre économiquement la plus avantageuse :</strong></p>
    <p>La commission procède au classement des offres des concurrents retenus, au regard du prix de référence ainsi déterminé.</p>
    <p>L’offre la mieux–disante, à proposer au maître d’ouvrage, est celle qui est la plus proche du prix de référence par défaut.</p>
    <p>En cas d’absence d’offres inférieures au prix de référence, l’offre la mieux–disante est celle qui est la plus proche par excès de ce prix.</p>
    <p>Dans le cas où plusieurs offres jugées économiquement les plus avantageuses sont tenues pour équivalentes, tous éléments considérés, la commission procède à un tirage au sort pour départager les concurrents concernés.</p>
    <p>Toutefois :</p>
    <ul>
        <li>Lorsque l’un des concurrents concernés est une coopérative, une union de coopératives ou un auto-entrepreneur, une préférence est accordée à l’offre présentée par celui–ci ;</li>
        <li>Lorsque deux ou plusieurs concurrents concernés sont une coopérative, une union de coopératives ou un auto-entrepreneur, une préférence est accordée aux offres présentées par ceux–ci. Dans ce cas, la commission procède à un tirage au sort pour les départager.</li>
    </ul>
    
    <p>La commission demande électroniquement, via le portail des marchés publics, le concurrent ayant présenté l’offre économiquement la plus avantageuse à :</p>
    <ul>
        <li>Produire, selon le cas, les pièces du dossier administratif visées à l’alinéa 2) du paragraphe A du I) ou à l’alinéa 2) du II) de l’article 28 du décret ;</li>
        <li>Confirmer les rectifications des erreurs matérielles relevées, le cas échéant ;</li>
        <li>Régulariser les discordances constatées entre les diverses pièces de son dossier administratif, le cas échéant ;</li>
        <li>Justifier le ou les prix unitaires principaux jugés anormalement bas ou excessifs conformément aux dispositions de l’article 44 du présent décret.</li>
    </ul>
    <p>A cet effet, la commission lui fixe un délai qui ne peut être inférieur à sept (07) jours à compter de la date de réception de la demande susvisée.</p>
    <ol>
        <li>Les éléments de réponse du concurrent auquel il est envisagé d’attribuer le marché doivent être produits dans un pli fermé accompagné. Ce pli <strong>doit être déposé électroniquement</strong> via le portail des marchés publics.</li>
        <li>La commission se réunit au lieu, au jour et à l’heure fixés. Toutefois, le président peut inviter les membres de la commission à reprendre ses travaux dès la réception de la réponse du concurrent concerné.</li>
        <li>La commission s’assure de l’existence moyen ayant servi à l’invitation du concurrent concerné, procède à la vérification des pièces et de la réponse reçue. Elle examine, ensuite, les pièces et la réponse reçue.</li>
    </ol>
    <p>A l’issue de cet examen, la commission décide :</p>
    <p>A) soit de proposer au maître d’ouvrage de retenir l’offre du concurrent concerné, lorsqu’il s’avère, selon le cas, que :</p>
    <ol type="a">
        <li>les pièces produites au titre du complément du dossier administratif répondent aux conditions du deuxième paragraphe de l’article 28 du décret ° 2-22-431 du 08 Mars 2023 ;</li>
        <li>les rectifications demandées ont été confirmées ;</li>
        <li>les discordances constatées ont été régularisées ;</li>
        <li>Les justifications du prix ou des prix unitaires principaux excessifs ou anormalement bas sont convaincantes.</li>
    </ol>
    <p>B) soit d’écarter le concurrent concerné, lorsque celui–ci :</p>
    <ol type="a">
        <li>Ne répond pas dans le délai qui lui est imparti ;</li>
        <li>Ne produit pas les pièces exigées ou produit des pièces non conformes ;</li>
        <li>Ne confirme pas les rectifications des erreurs matérielles relevées ;</li>
        <li>Ne régularise pas les discordances constatées entre les diverses pièces de son dossier ;</li>
        <li>Fournit des justifications non convaincantes en ce qui concerne le ou les prix unitaires principaux jugés excessifs ou anormalement bas.</li>
    </ol>
    <p>Dans le cas où le concurrent ayant présenté l’offre économiquement la plus avantageuse est écarté conformément aux dispositions du B) ci-dessus, la commission invite, dans les conditions prévues au paragraphe 4 du II) de l'article 43 du décret ° 2-22-431 du 08 Mars 2023, le concurrent dont l’offre est classée deuxième.</p>
    <p>Elle procède à l’examen des pièces et de la réponse reçue et décide soit de le retenir, soit de l’écarter dans les conditions fixées ci-dessus.</p>
    <p>Si la commission ne retient pas le concurrent concerné, elle invite le concurrent dont l’offre est classée la suivante et examine les pièces et la réponse reçues dans les conditions fixées ci-dessus, jusqu’à l’aboutissement de la procédure ou la déclaration de l’appel d’offres infructueux.</p>

    <div class="article-title">ARTICLE 18 : RESULTATS DEFINITIFS DE L’APPEL D’OFFRES</div>
    <p>Les résultats définitifs de l’appel d’offres ouvert sont annoncés conformément à l’article 47 du décret n° 02-22-431 précité et de l’article 9 de l’arrêté du ministre délégué auprès de la ministre de l’économie et des finances, chargé du budget n° 1692-23 du 23 juin 2023 relatif à la dématérialisation des procédures, des documents et des pièces relatives aux marchés publics.</p>
    <p>Le maître d’ouvrage informe électroniquement par voie du portail des marchés publics et conformément à l’article 9 susvisé, l’attributaire de l’acceptation de son offre dans un délai n’excédant pas le troisième jour suivant la date d’achèvement des travaux de la commission d’appel d’offres.</p>
    <p>Dans le même délai, il informe électroniquement, par voie du portail des marché publics, les concurrents éliminés, en leur indiquant les motifs de rejet de leurs offres.</p>
    <p>Toutefois, les pièces ayant été à l’origine de l’écartement des concurrents sont conservées par le maître d’ouvrage pendant un délai minimum de cinq ans, à l’exception de l’original du récépissé du cautionnement provisoire ou de l’attestation de la caution personnelle et solidaire en tenant lieu qui est restitué aux concurrents écartés, dans les quarante-huit heures suivant la date d’envoi de la lettre électronique visée au troisième alinéa du présent article.</p>
    <p>Aucun concurrent ne peut prétendre à indemnité si son offre n’a pas été acceptée.</p>
    <p>Le choix arrêté par la commission d’appel d’offres ne peut, en aucun cas, être modifié par le maître d’ouvrage ou l’autorité compétente.</p>

    <div class="article-title">ARTICLE 19 : MONNAIE DE FORMULATION DES OFFRES</div>
    <p>Conformément aux dispositions de l’article 21 paragraphe 3 du décret n° 2.22.431 précité, le dirham est la monnaie dans laquelle doivent être exprimés les prix des offres présentées par les soumissionnaires.</p>
    <p>Lorsque le concurrent n’est pas installé au Maroc, son offre doit être exprimée en monnaie étrangère convertible. Dans ce cas, pour être évaluées et comparées, les montants des offres exprimées en monnaie étrangère doivent être convertis en dirham. Cette conversion doit s’effectuer sur la base du cours vendeur du dirham en vigueur, donné par Bank Al-Maghreb, le premier jour ouvrable de la semaine précédant celle du jour d’ouverture des plis.</p>

    <div class="article-title">ARTICLE 20 : MESURES COERCITIVES</div>
    <p>En cas de présentation d’une déclaration sur l’honneur inexacte ou de pièces falsifiées ou lorsque des actes frauduleux ou de corruption, des infractions réitérées aux conditions de travail ou des manquements graves aux engagements contractuels conclus ont été relevés à la charge d’un concurrent, d’un attributaire ou d’un titulaire, selon le cas, des sanctions ou l’une d’entre elles seulement, sans préjudice, le cas échéant, des poursuites judiciaires, sont prises :</p>
    <p>a) par décision de l’autorité gouvernementale concernée prise, après avis de la commission nationale de la commande publique, l’exclusion temporaire ou définitive du concurrent concerné de la participation aux marchés lancés par les services relevant de son autorité et/ou aux marchés de l’établissement ou des établissements publics dont elle assure la tutelle ;</p>
    <p>b) par décision du président de l’organe délibérant de la personne morale de droit public visée à l’article 2 du présent décret, après avis de la commission nationale de la commande publique, l’exclusion temporaire ou définitive du concurrent concerné de la participation aux marchés lancés par les services relevant de ladite personne morale ; La mesure d’exclusion prévue au a) ci–dessus peut être étendue aux marchés lancés par l’ensemble des administrations de l’Etat et des établissements publics par décision du Chef du gouvernement, prise sur proposition du ministre concerné et après avis de la commission nationale de la commande publique.</p>
    <p>c) par décision du ministre chargé de l’intérieur prise après avis de la commission nationale de la commande publique, l’exclusion temporaire ou définitive du concurrent concerné de la participation aux marchés lancés par les collectivités territoriales. Cette mesure d’exclusion peut être étendue aux marchés lancés par l’Etat, les établissements publics et les autres personnes morales de droit public, par décision du Chef du gouvernement, prise sur proposition du ministre chargé de l’intérieur et après avis de la commission nationale de la commande publique.</p>
    <p>d) par décision de l’autorité compétente, la résiliation du marché, suivie ou non de la passation d’un nouveau marché, aux frais et risques du titulaire. Les excédents de dépenses résultant de la passation d’un nouveau marché après résiliation sont prélevés sur les sommes qui peuvent être dues au titulaire défaillant sans préjudice des droits à exercer contre lui en cas d’insuffisance. Les diminutions éventuelles des dépenses restent acquises au maître d’ouvrage</p>
    <p>Dans les cas prévus aux a), b) et c) ci–dessus, le concurrent, l’attributaire ou le titulaire, auxquels sont communiqués les griefs qui leur sont reprochés, sont invités, au préalable, à présenter leurs observations dans le délai imparti par le maître d’ouvrage. Ce délai ne peut, en aucun cas, être inférieur à quinze jours. Les décisions prévues aux a), b) et c) doivent être motivées, notifiées au concurrent, à l’attributaire ou au titulaire défaillant, selon le cas, et publiées sur le portail des marchés publics.</p>
    
    <br><br>
    <table class="bordered mt-10">
        <tr>
            <td style="width: 50%; text-align: center; height: 120px; vertical-align: top;">
                <strong>LE MAITRE D’OUVRAGE</strong>
            </td>
            <td style="width: 50%; text-align: center; height: 120px; vertical-align: top;">
                <strong>SIGNATURE ET CACHET DU<br>CONCURRENT</strong><br>
                « Précédé par la mention Lu et Accepté »
            </td>
        </tr>
        <tr>
            <td>A …………………….…….,<br>le……………………………</td>
            <td>A…………………….………,<br>le…………………………</td>
        </tr>
    </table>

    <!-- ANNEXES -->
    <div class="page-break"></div>
    <div class="title-main" style="text-decoration: underline;">ANNEXES</div>
    <p class="font-bold mt-10" style="margin-top: 50px;">PIECE N° 1 : MODELE DE DECLARATION SUR L'HONNEUR</p>
    <p class="font-bold mt-10">PIECE N° 2 : MODELE DE L'ACTE D'ENGAGEMENT</p>
    <p class="font-bold mt-10">PIECE N° 3 : NOTE SUR LES MOYENS HUMAINS ET TECHNIQUES DU CONCURRENT</p>

    <div class="page-break"></div>
    <div class="title-main underline">PIECE N° 1</div>
    <div class="title-main">DECLARATION SUR L'HONNEUR <sup>(1)</sup></div>
    
    <p>Appel d’offres ouvert national sur offres de prix n°<strong>{{ $marche->aoo->num_aoo ?? '...' }}</strong> du {{ $marche->aoo->date_ouverture ? \Carbon\Carbon::parse($marche->aoo->date_ouverture)->format('d/m/Y à H:i') : '...' }} mn.</p>
    <p><strong>Objet du marché : </strong>{{ $marche->aoo->objet ?? $marche->objet_marche ?? '...' }}</p>
    
    <p class="font-bold">A - Pour les personnes physiques</p>
    <p><strong>1) Cas des personnes physiques agissant pour leur propre compte :</strong></p>
    <p>
        Je, soussigné ........................................................................(prénom, nom et qualité) agissant en mon nom personnel et pour mon propre compte.<br>
        N° du tél …………………………………….<br>
        Numéro de fax ………………………………………<br>
        Adresse électronique : ………………………………………………………………………………….<br>
        Adresse du domicile élu : ......................................................................................................................<br>
        Affilié à la CNSS<sup>(2)</sup> sous le n° : ...................................................................................................<br>
        Inscrit au Registre de Commerce de………………….. (Localité) sous le n° .....................<br>
        Inscrite à la taxe professionnelle sous le numéro :……………………………………………<br>
        Numéro de l’identifiant commun de l’entreprise :…………………………………………………<br>
        Relevé d’identité bancaire……………..( postal bancaire ou à la TGR) <sup>(3)</sup>numéro<sup>(4)</sup>………………………….……<br>
        En vertu des pouvoirs qui me sont conférés ;
    </p>

    <p><strong>2) Cas de l’auto-entrepreneur :</strong></p>
    <p>
        Je, soussigné ........................................................................(prénom, nom), agissant en mon nom personnel et pour mon propre compte.<br>
        N° du tél …………………………………….<br>
        Numéro de fax ………………………………………<br>
        Adresse électronique : ………………………………………………………………………………….<br>
        Adresse du domicile élu : ......................................................................................................................<br>
        Inscrit au Registre national de l’auto-entrepreneur sous le numéro : ...............................................<br>
        Numéro de l’identifiant commun de l’entreprise :…………………………………………………<br>
        Relevé d’identité bancaire……………..( postal bancaire ou à la TGR) <sup>(5)</sup> numéro<sup>(6)</sup>………………………….……<br>
        En vertu des pouvoirs qui me sont conférés ;
    </p>

    <div class="page-break"></div>
    <p class="font-bold">B - Pour les personnes morales</p>
    <p><strong>1) Cas des sociétés :</strong></p>
    <p>
        Je, soussigné : <strong>{{ $marche->fournisseur->representant ?? '.......................' }}</strong> (Prénom, nom et qualité), agissant au nom et pour le compte de <strong>{{ $marche->fournisseur->raison_sociale ?? '.......................' }}</strong> (raison sociale et forme juridique), au capital social de........................................................................<br>
        N° du tél …………………………………….<br>
        Numéro de fax ………………………………………<br>
        Adresse électronique : ………………………………………………………………………………<br>
        Adresse du siège social de la société : <strong>{{ $marche->fournisseur->adresse ?? '.......................' }}</strong><br>
        Adresse du domicile élu : ............................................................................................................<br>
        Affiliée à la CNSS sous le n°<sup>(7)</sup> : <strong>{{ $marche->fournisseur->cnss ?? '.......................' }}</strong><br>
        Inscrite au registre de commerce <strong>{{ $marche->fournisseur->ville ?? '.......................' }}</strong> sous le numéro <strong>{{ $marche->fournisseur->rc ?? '.......................' }}</strong><br>
        Inscrit à la taxe professionnelle sous le numéro <strong>{{ $marche->fournisseur->patente ?? '.......................' }}</strong><br>
        Numéro de l’identifiant commun de l’entreprise : <strong>{{ $marche->fournisseur->ice ?? '.......................' }}</strong><br>
        Relevé d’identité bancaire <strong>{{ $marche->fournisseur->banque ?? '.......................' }}</strong> (postal, bancaire ou à la TGR) <sup>(8)</sup>, numéro<sup>(9)</sup> : <strong>{{ $marche->fournisseur->rib ?? '.......................' }}</strong><br>
        En vertu des pouvoirs qui me sont conférés ;
    </p>

    <p><strong>2) Cas des établissements publics :</strong></p>
    <p>
        Je soussigné.....................(nom, prénom et qualité) agissant au nom et pour le compte de (dénomination de l'établissement).<br>
        Numéro téléphone: ......................................................................................................................<br>
        Numéro du fax: ............................................................................................................................<br>
        Adresse électronique: ..................................................................................................................<br>
        Adresse du siège: .........................................................................................................................<br>
        Affiliée à<sup>(10)</sup>...........sous le numéro: ..............................................................................................<br>
        Inscrit au registre du commerce de<sup>(11)</sup>………..(localité) sous le numéro: ....................................<br>
        Numéro de l’identifiant commun de l’entreprise<sup>(7)</sup>:......................................................................<br>
        Inscrite à la taxe professionnelle sous le numéro<sup>(7)</sup>: .....................................................................<br>
        Références du texte l’habilitant à exercer les missions objet du marché: ...................................<br>
        Relevé d’identité bancaire............(postal, bancaire ou à la TGR) <sup>(12)</sup> numéro<sup>(13)</sup>: .....................<br>
        En vertu des pouvoirs qui me sont conférés ;
    </p>

    <p><strong>3) Cas des coopératives ou union des coopératives :</strong></p>
    <p>
        Je soussigné………….. (nom, prénom et qualité) agissant au nom et pour le compte de.....................................(raison sociale et forme juridique de la coopérative ou union des coopératives), au capital social de................................................................................................<br>
        Numéro de téléphone: .................................................................................................................<br>
        Numéro du fax: ............................................................................................................................<br>
        Adresse électronique: ..................................................................................................................<br>
        Adresse du siège social de la coopérative ou union des coopératives: .......................................<br>
        Adresse du domicile élu: .............................................................................................................<br>
        Inscrite au registre local des coopératives, sous le numéro..........................................................<br>
        Affiliée à la CNSS sous le numéro<sup>(5)</sup>: ...........................................................................................<br>
        Inscrite à la taxe professionnelle sous le numéro: .......................................................................<br>
        Numéro de l’identifiant commun de l’entreprise: .......................................................................<br>
        Relevé d’identité bancaire............(postal, bancaire ou à la TGR)<sup>(14)</sup> numéro<sup>(15)</sup>: ........................<br>
        En vertu des pouvoirs qui me sont conférés ;
    </p>

    <p class="font-bold underline mt-10">Déclare sur l’honneur :</p>
    <ol>
        <li>que je remplis les conditions prévues à l'article 27 du décret relatif aux marchés publics;</li>
        <li>m'engager à couvrir, dans les conditions fixées dans le cahier des charges, par une police d'assurance, les risques découlant de mon activité professionnelle;</li>
        <li>m'engage, si j'envisage de recourir à la sous-traitance :
            <ul>
                <li>à veiller à ce que celle-ci ne dépasse pas cinquante pour cent (50%) du montant du marché et qu’elle ne porte pas sur le lot ou le corps d’état principal du marché ;</li>
                <li>à m’assurer que les sous-traitants auxquels je recours remplissent les conditions prévues à l’article 27 du décret n° 2-22-431 du 8 mars 2023.</li>
            </ul>
        </li>
        <li>atteste que je dispose des autorisations requises pour l’exécution des prestations telles que prévues par la législation et la réglementation en vigueur ;</li>
        <li>atteste que je ne suis pas en liquidation judiciaire ou redressement judiciaire ;</li>
        <li>étant en redressement judiciaire, j'atteste que je suis autorisé par l'autorité judiciaire compétente à participer aux appels d’offres ; <sup>(16)</sup></li>
        <li>je m’engage à ne pas recourir par moi-même ou par personne interposée à des pratiques de fraude ou de corruption des personnes qui interviennent, à quelque titre que ce soit, dans les procédures de passation, de gestion et d’exécution du marché ;</li>
        <li>je m’engage à ne pas faire, par moi-même ou par personne interposée, de promesses, de dons ou de présents, en vue d’influer sur la procédure de conclusion du marché et de son exécution ;</li>
        <li>J’atteste que je ne suis pas en situation de conflit d’intérêts ;</li>
        <li>J’atteste que je n'ai pas participé à la préparation du dossier de l’appel d’offres considéré ;</li>
    </ol>
    <p>Je certifie l’exactitude des renseignements contenus dans la présente déclaration sur l’honneur et dans les pièces fournies dans mon dossier de candidature, sous peine de l’application des mesures coercitives prévues à l’article 152 du décret n° 2-22-431 du 8 mars 2023.</p>

    <div style="text-align: right; margin-top: 30px;">
        <strong>Fait à ……..……………..le ………………</strong><br><br>
        <strong>Signature et cachet du concurrent</strong>
    </div>

    <!-- PIECE N° 2 -->
    <div class="page-break"></div>
    <div class="title-main underline">PIECE N° 2</div>
    <div class="title-main">MODELE D'ACTE D'ENGAGEMENT</div>
    
    <p class="font-bold underline">A - Partie réservée à l’Administration :</p>
    <p>Appel d’offres ouvert national sur offres de prix n° <strong>{{ $marche->aoo->num_aoo ?? '...' }}</strong> du {{ $marche->aoo->date_ouverture ? \Carbon\Carbon::parse($marche->aoo->date_ouverture)->format('d/m/Y à H:i') : '...' }} mn.</p>
    <p><strong>Objet du marché : </strong>{{ $marche->aoo->objet ?? $marche->objet_marche ?? '...' }}</p>
    <p>Passé en application de l'alinéa 1 du paragraphe 1, l'alinéa a) du paragraphe 3 de la partie I) de l'article 19 et du paragraphe 1 de l'article 20 et de l’alinéa b du paragraphe 3 de l’article 20 du décret n° 2-22-431 du 15 Chaabane 1444 (08 mars 2023) relatif aux marchés publics.</p>

    <p class="font-bold underline text-center mt-10">B – Partie réservée au concurrent</p>
    
    <p class="font-bold">a) Pour le concurrent agissant à titre individuel</p>
    <p>Je [2], soussigné : ....……………………........................................................................ [Prénom, nom et qualité]<br>
    Agissant en mon nom personnel et pour mon propre compte, <br>
    Adresse du domicile élu : ..………………...........................................................................................................<br>
    Affilié à la Caisse Nationale de Sécurité Sociale (CNSS) sous le n°: …….....…………………….…...... [3] et [4] <br>
    Inscrit au registre du commerce de : .................. [Localité], sous le n° : ...………………………........... [3] et [4] <br>
    N° de la taxe professionnelle : ……………………………………….………………………………………………..………. [3] et [4] <br>
    Numéro de l’identifiant commun de l’entreprise : ......................................................................................</p>

    <p class="font-bold">b) Pour les personnes morales</p>
    <p>Je [2], soussigné : <strong>{{ $marche->fournisseur->representant ?? '.......................' }}</strong> [Prénom, nom et qualité au sein de l'entreprise]<br>
    Agissant au nom et pour le compte de : <strong>{{ $marche->fournisseur->raison_sociale ?? '.......................' }}</strong> [Raison sociale et forme juridique de la société]<br>
    Au capital de : ....……………………………...........................................................................................................<br>
    Adresse du siège social de la société : <strong>{{ $marche->fournisseur->adresse ?? '.......................' }}</strong><br>
    Adresse du domicile élu : ………………………..………………………………………………………………………….………<br>
    Affiliée à la Caisse Nationale de Sécurité Sociale (CNSS) sous le n° : <strong>{{ $marche->fournisseur->cnss ?? '.......................' }}</strong> [3] et [4]<br>
    Inscrite au registre du commerce de : <strong>{{ $marche->fournisseur->ville ?? '.......................' }}</strong> [Localité], sous le n° : <strong>{{ $marche->fournisseur->rc ?? '.......................' }}</strong> [3] et [4]<br>
    N° de la taxe professionnelle : <strong>{{ $marche->fournisseur->patente ?? '.......................' }}</strong> [3] et [4]<br>
    Numéro de l’identifiant commun de l’entreprise: <strong>{{ $marche->fournisseur->ice ?? '.......................' }}</strong></p>

    <p class="font-bold">c) Pour les concurrents membres d'un groupement :</p>
    <p>Nous soussignés:(6)<br>
    – Membre n° 1: ............................................................................................................................<br>
    – Membre n° 2: ............................................................................................................................ <br>
    – Membre n° n: ............................................................................................................................</p>

    <p class="font-bold underline">En vertu des pouvoirs qui me sont conférés :</p>
    <p>Après avoir pris connaissance du dossier d'appel d'offres concernant les prestations précisées en objet de la partie A ci-dessus ;</p>
    <p>Après avoir apprécié à mon point de vue et sous ma responsabilité la nature et les difficultés que comportent ces prestations :</p>
    <ol>
        <li>Remets (remettons), revêtu de ma (nos) signature (s) un bordereau de prix, un détail estimatif et/ou la décomposition du montant global) établi (s) conformément aux modèles figurant au dossier d'appel d’offres ;</li>
        <li>M'engage (nous nous engageons) à exécuter lesdites prestations conformément au cahier des prescriptions spéciales et moyennant les prix que j'ai (nous avons) établi moi-même (nous-mêmes), lesquels font ressortir :</li>
    </ol>
    
    @php
        $montantHT = $marche->montant_ht ?? 0;
        $montantTVA = $marche->montant_tva ?? 0;
        $montantTTC = $marche->montant ?? 0;
        $tauxTVA = $marche->taux_tva ?? 20;
    @endphp

    <ul>
        <li>Montant Hors T.V.A : <strong>{{ number_format($montantHT, 2, ',', ' ') }} MAD ({{ \App\Helpers\NumberToWordsHelper::toFrenchWords($montantHT) }})</strong></li>
        <li>Taux de la T.V.A : <strong>{{ $tauxTVA }}%</strong></li>
        <li>Montant de la T.V.A : <strong>{{ number_format($montantTVA, 2, ',', ' ') }} MAD</strong></li>
        <li>Montant T.V.A Comprise : <strong>{{ number_format($montantTTC, 2, ',', ' ') }} MAD ({{ \App\Helpers\NumberToWordsHelper::toFrenchWords($montantTTC) }})</strong></li>
    </ul>

    <p>La DRCA Rabat-Salé-Kénitra se libérera des sommes dues par lui en faisant donner crédit au compte <strong>{{ $marche->fournisseur->banque ?? '.......................' }}</strong> (à la Trésorerie Générale du Royaume, bancaire ou Postal) [4], ouvert…………………………………………...(en mon nom ou au nom de la Société) [4], à <strong>{{ $marche->fournisseur->agence_bancaire ?? '.......................' }}</strong> [Localité], sous relevé d’identification bancaire (RIB) numéro : <strong>{{ $marche->fournisseur->rib ?? '.......................' }}</strong></p>

    <div style="text-align: right; margin-top: 30px;">
        <strong>Fait à ......................le ……………..</strong><br><br>
        <strong>[Signature et cachet du concurrent] [5]</strong>
    </div>

    <!-- PIECE N° 3 -->
    <div class="page-break"></div>
    <div class="title-main underline">PIECE N° 3</div>
    <div class="title-main">NOTE SUR LES MOYENS HUMAINS ET TECHNIQUES DU CONCURRENT (*)</div>

    <p><strong>1. Nom ou raison sociale :</strong> {{ $marche->fournisseur->raison_sociale ?? '.......................' }}</p>
    <p><strong>2. Implantation au Maroc :</strong></p>
    <ul>
        <li>Siège (adresse, téléphone, télex) ; <strong>{{ $marche->fournisseur->adresse ?? '.......................' }}</strong></li>
        <li>Succursale (adresse, téléphone, télex).</li>
    </ul>

    <p><strong>3. Moyens humains :</strong></p>
    <ul>
        <li>Effectifs en personnel :</li>
        <li>Personnel Administratif ;</li>
        <li>Personnel Technique</li>
    </ul>

    <p><strong>4 - Moyens matériels :</strong></p>
    <p>Le concurrent dressera la liste complète du matériel de l’entreprise en distinguant :<br>
    Le matériel de transport de liaison<br>
    Le matériel logistique….</p>

    <p><strong>5 - Références techniques :</strong></p>
    <p>Domaine de spécialisation du prestataire :<br>
    Liste de prestations réalisées (similaires ou en rapport avec l’objet de l’appel d’offres) :</p>

    <table class="bordered">
        <thead>
            <tr>
                <th rowspan="2">Désignation des prestations</th>
                <th colspan="2">Importance des prestations</th>
                <th rowspan="2">Année d'exécution</th>
                <th rowspan="2">Lieu d'exécution</th>
                <th rowspan="2">Date de réception provisoire</th>
                <th rowspan="2">Date de réception définitive</th>
                <th rowspan="2">Maître d'ouvrage</th>
            </tr>
            <tr>
                <th>N° du marché</th>
                <th>Montant du marché</th>
            </tr>
        </thead>
        <tbody>
            <tr><td style="height: 30px;"></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
            <tr><td style="height: 30px;"></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
        </tbody>
    </table>

    <div style="text-align: center; margin-top: 30px;">
        <strong>Fait à ………………le …………………..</strong><br><br>
        <strong>(Signature et cachet du concurrent)</strong>
    </div>

</body>
</html>
