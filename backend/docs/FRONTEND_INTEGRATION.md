# Intégration du frontend

Le frontend appelle l'API JSON par l'intermédiaire de `assets/js/app.js`. L'URL locale par défaut est `http://localhost/Amariiiz/public/api/v1`.

Si Live Server utilise un autre port, régler `CORS_ALLOWED_ORIGIN` dans `.env` avec l'origine exacte, par exemple `http://localhost:5500`. Redémarrer Apache après cette modification.

Le frontend peut aussi définir `window.AMARIIZ_API_BASE` avant de charger `app.js` pour une autre adresse d'hébergement. Les requêtes panier envoient les cookies de session ; les requêtes protégées envoient le token Bearer conservé après la connexion.
