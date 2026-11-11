<?php
declare(strict_types=1);

namespace Modules\Catalogue;

use PDO;

/** Service réutilisable par la commande : aucune écriture n'est effectuée ici. */
final class CatalogueService
{
    public function __construct(private PDO $pdo) {}

    /** Retourne la variante seulement si le produit et la variante sont commandables. */
    public function orderableVariant(int $variantId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT pv.id, pv.price, pv.stock_qty FROM product_variants pv
             JOIN products p ON p.id = pv.product_id
             WHERE pv.id = :id AND pv.is_available = 1 AND p.is_active = 1'
        );
        $stmt->execute(['id' => $variantId]);
        return $stmt->fetch() ?: null;
    }

    public function canBeOrdered(int $variantId, int $quantity): bool
    {
        $variant = $this->orderableVariant($variantId);
        return $variant !== null && (int) $variant['stock_qty'] >= $quantity;
    }
}
