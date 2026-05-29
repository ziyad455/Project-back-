<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation de votre mot de passe</title>
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
            <p>Vous recevez cet e-mail car nous avons reçu une demande de réinitialisation du mot de passe de votre compte AjiKhdam.</p>
            
            <div class="btn-container">
                <a href="{{ $resetUrl }}" class="btn">Réinitialiser le mot de passe</a>
            </div>

            <p style="font-size: 14px; color: #48848D; margin-bottom: 24px;">Ce lien de réinitialisation est valable pendant 60 minutes. Si vous n'avez pas demandé cette réinitialisation, aucune action supplémentaire n'est requise.</p>
            
            <hr style="border: 0; border-top: 1px solid #E8F0EE; margin-bottom: 24px;">
            
            <p style="font-size: 12px; color: #8CA8AD; line-height: 1.5; margin-bottom: 0; word-break: break-all;">Si vous rencontrez des difficultés avec le bouton ci-dessus, copiez et collez l'URL suivante dans votre navigateur :<br><a href="{{ $resetUrl }}" style="color: #77CFBF;">{{ $resetUrl }}</a></p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} AjiKhdam. Tous droits réservés.</p>
            <p style="margin-top: 8px;">Besoin d'aide ? <a href="mailto:support@ajikhdam.com" class="support-link">Contactez le support</a></p>
        </div>
    </div>
</body>
</html>
