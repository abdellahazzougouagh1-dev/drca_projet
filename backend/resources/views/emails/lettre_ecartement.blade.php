<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $lettre['objet_lettre'] }}</title>
</head>
<body>
    <p>Madame, Monsieur,</p>
    <p>
        Veuillez trouver ci-joint la lettre de notification concernant l'appel d'offres <strong>{{ $lettre['objet_lettre'] }}</strong>.
    </p>
    <p>
        Société : {{ $lettre['societe'] }}<br>
        Lot : {{ $lettre['lot_numero'] }}
    </p>
    <p>
        Merci de prendre connaissance du document et de nous répondre selon les instructions reçues.
    </p>
    <p>Cordialement,</p>
    <p>L'équipe ONCA</p>
</body>
</html>
