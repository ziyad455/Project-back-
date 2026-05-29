# Configuration Google OAuth - AjiKhdam

## Étapes pour activer Google OAuth

### 1. Créer un projet Google Cloud Console
1. Allez sur https://console.cloud.google.com/
2. Créez un nouveau projet "AjiKhdam"
3. Allez dans "API & Services" → "Credentials"
4. Cliquez "+ Create Credentials" → "OAuth 2.0 Client ID"
5. Type d'application : **Web Application**

### 2. Configurer les URLs autorisées
- Origines JavaScript autorisées : `http://localhost:5173`
- URIs de redirection autorisées : `http://localhost:8000/api/auth/google/callback`

### 3. Ajouter les credentials dans .env
```env
GOOGLE_CLIENT_ID=votre_client_id_ici.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=votre_client_secret_ici
GOOGLE_REDIRECT_URI=http://localhost:8000/api/auth/google/callback

# URL du frontend pour la redirection post-OAuth
FRONTEND_URL=http://localhost:5173
```

### 4. Tester
1. Démarrez le backend : `php artisan serve`
2. Démarrez le frontend : `npm run dev`
3. Cliquez "Continuer avec Google" sur la page Login
4. Après connexion, vous serez redirigé vers /dashboard

## Flux OAuth
```
Frontend → /api/auth/google/redirect → Google OAuth → /api/auth/google/callback → Frontend /dashboard
```
