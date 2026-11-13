# API AMARIIZ MILK

Backend REST JSON en PHP 8 et MySQL, compatible XAMPP/Laragon.

## Installation locale

1. Copier `.env.example` vers `.env` et renseigner les identifiants MySQL.
2. Créer la base `amariiz_milk`.
3. Exécuter les migrations dans l'ordre : `101_users_tokens.sql`, `201_catalogue_villes.sql`, `102_orders_payments.sql`, puis `202_reviews.sql`.
4. Exécuter `database/seeds/users_seed.sql`. Le mot de passe temporaire doit être remplacé avant toute mise en production.
5. Configurer le serveur Apache pour faire pointer le DocumentRoot vers `public/`.

## Endpoints Dev 1

| Méthode | URL | Rôle |
|---|---|---|
| POST | `/api/v1/auth/register` | Public |
| POST | `/api/v1/auth/login` | Public |
| POST | `/api/v1/auth/logout` | Authentifié |
| GET | `/api/v1/auth/me` | Authentifié |
| GET/POST | `/api/v1/cart`, `/api/v1/cart/items` | Session PHP |
| PATCH/DELETE | `/api/v1/cart/items/{variantId}` | Session PHP |
| POST | `/api/v1/orders` | Client, vendeur, admin |
| GET | `/api/v1/orders/{id}/whatsapp` | Authentifié |
| GET | `/api/v1/payment-methods` | Authentifié |
| PATCH | `/api/v1/orders/{orderId}/payment` | Vendeur, admin |

Les données de démonstration Dev 2 se trouvent dans `database/seeds/299_catalogue_villes_test.sql`.

La documentation des routes destinées au frontend se trouve dans `docs/API.md`.


## Numéro WhatsApp des commandes

Le lien de confirmation WhatsApp est construit par `modules/commande/WhatsAppService.php`.
Le numéro de destination se configure dans `.env` avec le format international, chiffres uniquement (sans `+` ni espaces).

```ini
WHATSAPP_NUMBER=243999226344
```

Numéro de destination actuellement configuré pour les demandes de commande : **+243 999 226 344**.
