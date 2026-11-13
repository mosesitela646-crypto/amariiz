# API REST — AMARIIZ MILK

Base : `/api/v1`. Toutes les réponses sont JSON : `success`, `data`, `message`, `errors`. Les routes protégées reçoivent `Authorization: Bearer <token>`.

## Catalogue et villes (public)

| Méthode | Route | Paramètres |
|---|---|---|
| GET | `/catalogue/products` | `composition`, `flavor`, `format` facultatifs |
| GET | `/catalogue/products/{id}` | — |
| GET | `/catalogue/products/{id}/flavors` | — |
| GET | `/cities` | — |
| GET | `/promotions` | Codes promotionnels actifs et valides |

Chaque variante du catalogue fournit : `variant_id`, `composition`, `flavor`, `format`, `price` et `stock_qty`.

## Administration (Bearer admin)

| Méthode | Route | Corps JSON |
|---|---|---|
| POST | `/admin/products` | `name`, `description?`, `is_active?` |
| PATCH | `/admin/products/{id}` | `name?`, `description?`, `is_active?` |
| POST | `/admin/products/{id}/photo` | `multipart/form-data`, champ `photo` |
| POST | `/admin/variants` | `product_id`, `composition`, `flavor`, `format`, `price`, `stock_qty`, `low_stock_threshold?`, `is_available?` |
| PATCH | `/admin/variants/{id}` | `price?`, `stock_qty?`, `low_stock_threshold?`, `is_available?` |
| POST | `/admin/cities` | `name`, `delivery_fee`, `delivery_delay_hours`, `is_active?` |
| PATCH | `/admin/cities/{id}` | `name?`, `delivery_fee?`, `delivery_delay_hours?`, `is_active?` |
| DELETE | `/admin/cities/{id}` | Désactive la ville |
| GET | `/admin/clients` | Liste des clients et activité, sans données de mot de passe |
| GET | `/admin/promotions` | Toutes les promotions |
| POST | `/admin/promotions` | `code`, `title`, `discount_type`, `discount_value`, `starts_at`, `ends_at`, `description?`, `max_uses?` |
| PATCH | `/admin/promotions/{id}` | Modification partielle, dont `is_active` |
| GET | `/admin/stock/low` | — |
| GET | `/admin/dashboard?from=AAAA-MM-JJ&to=AAAA-MM-JJ` | Période facultative |
| GET | `/admin/reviews` | — |

## Gestion vendeur (Bearer vendeur ou admin)

| Méthode | Route | Usage |
|---|---|---|
| GET | `/seller/orders` | Filtres : `status`, `city_id`, `from`, `to`, `q` |
| GET | `/seller/orders/search` | Même filtres que la liste |
| GET | `/seller/orders/{id}` | Détail et historique |
| PATCH | `/seller/orders/{id}/status` | `{ "status": "en_livraison" }` |

## Avis (Bearer client)

`POST /orders/{id}/review` avec `{ "rating": 5, "comment": "Très bon." }`. La commande doit être livrée et appartenir au numéro de téléphone du compte connecté. Les avis sont consultables par l'administrateur via `/admin/reviews`.

La création d'une commande exige un compte connecté (Bearer client, vendeur ou admin). Le corps peut inclure `promotion_code` ; le backend vérifie sa validité et enregistre la remise avec la commande.

## Exemple de réponse

```json
{
  "success": true,
  "data": [],
  "message": "Catalogue disponible.",
  "errors": null
}
```
