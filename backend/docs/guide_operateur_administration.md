# Guide opérateur — AMARIIZ MILK

## Catalogue

Connectez-vous avec un compte administrateur. Créez d'abord un produit, puis ses variantes. Chaque variante doit contenir la composition, le parfum, le format, le prix, le stock et le seuil d'alerte. Les prix attendus sont 2 500 FC pour le petit format et 5 000 FC pour le grand format.

Une photo se transmet avec une requête `multipart/form-data`, champ `photo`. Seuls JPEG, PNG et WEBP de 5 Mo maximum sont acceptés. Pour retirer un produit de la vente sans perdre l'historique, mettez `is_active` à `false`. Pour une variante précise, utilisez `is_available` à `false`.

## Villes

Les villes initiales sont Likasi, Lubumbashi et Kolwezi. L'administrateur ajuste les frais et le délai de livraison. Une ville doit être désactivée, et non supprimée, lorsqu'elle ne doit plus recevoir de commandes.

## Commandes

Le vendeur consulte les commandes, peut rechercher par date, ville, statut, nom ou téléphone, puis fait évoluer le statut : `a_preparer`, `en_livraison`, `livree` ou `annulee`. Chaque changement est ajouté automatiquement à l'historique. Avant un changement, vérifiez le client, son adresse et le mode de réception.

## Stock et tableau de bord

L'écran d'alerte liste les variantes dont le stock est inférieur ou égal à leur seuil. Mettez à jour le stock dès qu'un réapprovisionnement est reçu. Le tableau de bord permet de choisir une période et affiche le nombre de commandes, les ventes hors annulations, les annulations, ainsi que les produits et villes les plus demandés.

## Avis

Après une commande livrée, le client peut donner une note de 1 à 5 et un commentaire. Un seul avis est possible par commande. L'administrateur peut consulter les avis dans l'espace d'administration.
