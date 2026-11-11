# Tests Thunder Client - Dev 1

Définir la variable d'environnement `baseUrl` sur `http://localhost/amariiz/public` et la variable `token` après connexion.

| Test | Requête | Résultat attendu |
|---|---|---|
| Inscription client | `POST {{baseUrl}}/api/v1/auth/register` | `201`, token et rôle `client` |
| Connexion valide | `POST {{baseUrl}}/api/v1/auth/login` | `200`, token Bearer |
| Connexion invalide | mêmes données avec mauvais mot de passe | `401` |
| Route protégée sans token | `GET /api/v1/auth/me` | `401` |
| Ajout au panier | `POST /api/v1/cart/items` | `201`, article présent |
| Modification panier | `PATCH /api/v1/cart/items/{variantId}` | `200`, quantité modifiée |
| Commande sans champ requis | `POST /api/v1/orders` | `422` |
| Commande sans stock | `POST /api/v1/orders` | `422`, aucune décrémentation |
| Commande valide | `POST /api/v1/orders` | `201`, total recalculé et lien WhatsApp |
| Paiement par client | `PATCH /api/v1/orders/{id}/payment` | `403` |
| Paiement par vendeur | même requête avec token vendeur | `200` |
| Limite IP | plus de 10 commandes dans la fenêtre définie | `429` |

Ne jamais utiliser les comptes seed ou les données de test en production.
