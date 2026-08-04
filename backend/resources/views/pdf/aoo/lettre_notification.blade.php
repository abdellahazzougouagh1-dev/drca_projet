<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $lettre['objet_lettre'] }} - {{ $lettre['societe'] }}</title>
    <style>
        @page {
            margin: 14mm 12mm;
            size: A4 portrait;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #000;
            margin: 0;
            padding: 0;
            line-height: 1.45;
            background: #fff;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }

        .header-table td {
            vertical-align: middle;
            border: none;
        }

        .header-center {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            line-height: 1.35;
        }

        .header-logo {
            height: 58px;
            width: auto;
        }

        .letter-content {
            margin: 0 8px;
        }

        .destinataire-block {
            margin-left: 48%;
            margin-bottom: 28px;
            font-weight: bold;
            font-size: 12px;
            line-height: 1.55;
            text-align: left;
        }

        .reference-block {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 22px;
        }

        .reference-block td {
            padding: 4px 0;
            vertical-align: top;
        }

        .ref-label {
            width: 95px;
            font-weight: bold;
            text-decoration: underline;
        }

        .ref-value {
            font-weight: bold;
        }

        .salutation {
            font-weight: bold;
            margin-bottom: 14px;
        }

        .corps-texte {
            text-align: justify;
            font-size: 12px;
        }

        .motif-block {
            margin-top: 10px;
            margin-bottom: 14px;
            min-height: 36px;
            font-weight: bold;
        }

        .pieces-fournir {
            margin-top: 18px;
        }

        .nb-title {
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 2px;
        }

        .nb-subtitle {
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 8px;
        }

        .pieces-fournir ul {
            margin: 0;
            padding-left: 18px;
        }

        .pieces-fournir li {
            margin-bottom: 8px;
            text-align: justify;
        }

        .politesse {
            margin-top: 28px;
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>
<body>

@include('pdf.aoo.partials.lettre_notification_body', ['lettre' => $lettre, 'aoo' => $aoo])

</body>
</html>
