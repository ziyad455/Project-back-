<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription refusée - AjiKhdam</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #F4F3EF;
            color: #093B42;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background: #FFFFFF;
            border-radius: 12px;
            border: 1px solid #D2EEEA;
            box-shadow: 0 4px 16px rgba(9, 59, 66, 0.05);
            overflow: hidden;
        }
        .header {
            background-color: #093B42;
            padding: 32px;
            text-align: center;
        }
        .header img {
            max-height: 40px;
            width: auto;
        }
        .content {
            padding: 40px 32px;
        }
        h1 {
            font-size: 24px;
            font-weight: 700;
            color: #093B42;
            margin-top: 0;
            margin-bottom: 16px;
        }
        p {
            font-size: 16px;
            line-height: 1.6;
            color: #0F616C;
            margin-top: 0;
            margin-bottom: 24px;
        }
        .btn-container {
            text-align: center;
            margin: 32px 0;
        }
        .btn {
            background-color: #77CFBF;
            color: #093B42 !important;
            text-decoration: none;
            padding: 16px 32px;
            font-size: 16px;
            font-weight: 700;
            border-radius: 8px;
            display: inline-block;
            box-shadow: 0 4px 12px rgba(119, 207, 191, 0.2);
            transition: background-color 0.2s ease;
        }
        .btn:hover {
            background-color: #B4E4DC;
        }
        .footer {
            background-color: #FAFAF8;
            border-top: 1px solid #E8F0EE;
            padding: 24px 32px;
            text-align: center;
            font-size: 13px;
            color: #48848D;
        }
        .footer p {
            margin: 0;
            font-size: 13px;
            color: #48848D;
        }
        .support-link {
            color: #77CFBF;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <span style="font-size: 24px; font-weight: 800; color: #FFFFFF; letter-spacing: 2px;">AJIKHDAM</span>
        </div>
        <div class="content">
            <h1>Bonjour {{ $firstName }},</h1>
            <p>Nous sommes désolés de vous informer que votre inscription en tant que talent sur AjiKhdam n'a pas pu être approuvée.</p>
            <p>Les documents que vous avez fournis semblent invalides ou incomplets. Conformément à notre politique de vérification, nous avons supprimé les documents que vous aviez téléchargés.</p>
            <p>Vous pouvez soumettre à nouveau votre demande en vous assurant de fournir des documents valides et complets.</p>

            <div class="btn-container">
                <a href="{{ $retryUrl }}" class="btn">Réessayer</a>
            </div>

            <p style="font-size: 14px; color: #48848D; margin-bottom: 0;">Si vous pensez qu'il s'agit d'une erreur, veuillez contacter notre équipe de support.</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} AjiKhdam. Tous droits réservés.</p>
            <p style="margin-top: 8px;">Besoin d'aide ? <a href="mailto:support@ajikhdam.com" class="support-link">Contactez le support</a></p>
        </div>
    </div>
</body>
</html>
