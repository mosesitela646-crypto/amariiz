# Intégration Frontend ↔ Backend PHP

Le frontend ne se connecte jamais directement à la base de données. Le backend PHP expose des routes et renvoie du JSON. Le frontend les appellera avec fetch().

## Contrat API à valider
- GET /api/products.php — catalogue et disponibilités
- POST /api/orders.php — créer une demande
- GET /api/orders.php — historique du client connecté
- POST /api/auth/login.php — connexion
- POST /api/auth/register.php — inscription
- GET /api/admin/dashboard.php — indicateurs
- GET /api/stock.php — disponibilité des produits

Format JSON proposé : {"success":true,"message":"OK","data":[]}.

À décider avec l'équipe backend : URL de base, noms des routes, sessions ou jetons, rôles, validation serveur et gestion des erreurs.