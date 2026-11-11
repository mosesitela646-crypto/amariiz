# Droits des rôles

| Fonction | Client | Vendeur | Admin |
|---|---:|---:|---:|
| S'inscrire et se connecter | Oui | Oui | Oui |
| Gérer son panier | Oui | Oui | Oui |
| Créer une commande | Oui | Oui | Oui |
| Générer un lien WhatsApp | Oui | Oui | Oui |
| Consulter les modes de paiement | Oui | Oui | Oui |
| Modifier le statut de paiement | Non | Oui | Oui |
| Gérer les utilisateurs et paramètres | Non | Non | Oui |

Les routes protégées attendent l'en-tête `Authorization: Bearer <token>`.
