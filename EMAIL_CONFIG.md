# Configuration Email - AjiKhdam

## Status Actuel : LOG (Développement)
Le mailer est actuellement en mode `log`. Les emails ne sont pas envoyés,
mais apparaissent dans `storage/logs/laravel.log` pour faciliter les tests.

## Pour activer les vrais emails (Production)

Modifiez les variables suivantes dans le fichier `.env` :

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=votre_email@gmail.com
MAIL_PASSWORD=votre_app_password_gmail
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@ajikhdam.ma
MAIL_FROM_NAME="AjiKhdam"
```

### Obtenir un App Password Gmail
1. Allez sur myaccount.google.com
2. Sécurité → Vérification en deux étapes → Mots de passe d'applications
3. Générez un mot de passe pour "Mail"

## Ce que les emails envoient
Quand une mission est postée, les talents reçoivent :
- ⭐ Les Top 10 talents (par rating) : email immédiat
- 📢 Les autres talents : email après 5 minutes si mission toujours ouverte

## Vérifier les emails en mode log
```bash
tail -f storage/logs/laravel.log | grep -A 20 "Message-ID"
```
