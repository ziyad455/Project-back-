<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification de votre compte AjiKhdam</title>
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
        .otp-container {
            background-color: #E8F5F3;
            border: 1px solid #B4E4DC;
            border-radius: 8px;
            padding: 24px;
            text-align: center;
            margin-bottom: 32px;
        }
        .otp-code {
            font-size: 36px;
            font-weight: 800;
            letter-spacing: 6px;
            color: #093B42;
            font-family: monospace;
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
            <!-- Sleek text/styling for brand logo representation in case images are blocked -->
            <span style="font-size: 24px; font-weight: 800; color: #FFFFFF; letter-spacing: 2px;">AJIKHDAM</span>
        </div>
        <div class="content">
            <h1>Bonjour {{ $firstName }},</h1>
            <p>Merci de vous être inscrit sur AjiKhdam. Pour activer votre compte et finaliser votre inscription, veuillez entrer le code de vérification à 6 chiffres ci-dessous :</p>
            
            <div class="otp-container">
                <div class="otp-code">{{ $otpCode }}</div>
            </div>

            <p style="font-size: 14px; color: #48848D; margin-bottom: 0;">Ce code est valable pendant 10 minutes. Si vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer cet e-mail en toute sécurité.</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} AjiKhdam. Tous droits réservés.</p>
            <p style="margin-top: 8px;">Besoin d'aide ? <a href="mailto:support@ajikhdam.com" class="support-link">Contactez le support</a></p>
        </div>
    </div>
</body>
</html>
