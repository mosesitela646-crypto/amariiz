<?php
declare(strict_types=1);

namespace Modules\Commande;

use Core\Auth;
use Core\Env;
use Core\RateLimiter;
use Core\Request;
use Core\Response;
use Core\Validator;
use PDO;
use Throwable;

final class OrderController
{
    public function __construct(private PDO $pdo, private CartController $cart, private Auth $auth) {}

    public function create(): never
    {
        $user = $this->auth->requireRoles(['client','vendeur','admin']);
        RateLimiter::assertAllowed('order:'.Request::ip(), (int)Env::get('ORDER_RATE_LIMIT','10'), (int)Env::get('ORDER_RATE_WINDOW_SECONDS','3600'));
        $payload = Request::json();
        $d = (new Validator($payload))
            ->required('customer_name')
            ->required('phone')->phone('phone')
            ->required('address')
            ->required('city_id')->integer('city_id')
            ->required('reception_mode')->in('reception_mode',['retrait','livraison'])
            ->required('payment_method')->in('payment_method',['cash','mobile_money','virement'])
            ->validated();

        $items = $this->cart->lines();
        if (!$items) Response::error('Le panier est vide.',422);

        try {
            $this->pdo->beginTransaction();
            $cityStmt = $this->pdo->prepare('SELECT id,name,delivery_fee,is_active FROM cities WHERE id=:id FOR UPDATE');
            $cityStmt->execute(['id'=>(int)$d['city_id']]);
            $city = $cityStmt->fetch();
            if (!$city || !(int)$city['is_active']) throw new \RuntimeException('Ville indisponible.');

            $subtotal = 0.0;
            foreach ($items as &$line) {
                $stmt = $this->pdo->prepare('SELECT pv.id,pv.price,pv.stock_qty,pv.is_available,p.is_active FROM product_variants pv JOIN products p ON p.id=pv.product_id WHERE pv.id=:id FOR UPDATE');
                $stmt->execute(['id'=>$line['product_variant_id']]);
                $variant = $stmt->fetch();
                if (!$variant || !(int)$variant['is_available'] || !(int)$variant['is_active'] || (int)$variant['stock_qty'] < (int)$line['quantity']) {
                    throw new \RuntimeException('Stock insuffisant pour un article du panier.');
                }
                $line['unit_price'] = (float)$variant['price'];
                $subtotal += (float)$variant['price'] * (int)$line['quantity'];
            }
            unset($line);

            $discount = 0.0;
            $promotionId = null;
            $promotionCode = strtoupper(trim((string)($payload['promotion_code'] ?? '')));
            if ($promotionCode !== '') {
                $promoStmt = $this->pdo->prepare('SELECT id,code,discount_type,discount_value,max_uses,uses_count FROM promotions WHERE code=:code AND is_active=1 AND starts_at<=NOW() AND ends_at>=NOW() FOR UPDATE');
                $promoStmt->execute(['code'=>$promotionCode]);
                $promotion = $promoStmt->fetch();
                if (!$promotion || ($promotion['max_uses'] !== null && (int)$promotion['uses_count'] >= (int)$promotion['max_uses'])) {
                    throw new \RuntimeException('Code promotionnel invalide, expiré ou déjà épuisé.');
                }
                $promotionId = (int)$promotion['id'];
                $discount = $promotion['discount_type'] === 'percent'
                    ? $subtotal * ((float)$promotion['discount_value'] / 100)
                    : (float)$promotion['discount_value'];
                $discount = min($subtotal, max(0.0, $discount));
                $usageStmt = $this->pdo->prepare('UPDATE promotions SET uses_count=uses_count+1 WHERE id=:id');
                $usageStmt->execute(['id'=>$promotionId]);
            }

            $deliveryFee = $d['reception_mode'] === 'livraison' ? (float)$city['delivery_fee'] : 0.0;
            $total = max(0.0, $subtotal - $discount + $deliveryFee);

            $stmt = $this->pdo->prepare("INSERT INTO orders (customer_name,phone,address,city_id,reception_mode,total,promotion_id,discount_amount,status)
                VALUES (:name,:phone,:address,:city_id,:mode,:total,:promotion_id,:discount,'a_preparer')");
            $stmt->execute([
                'name'=>trim($d['customer_name']),
                'phone'=>trim($d['phone']),
                'address'=>trim($d['address']),
                'city_id'=>(int)$city['id'],
                'mode'=>$d['reception_mode'],
                'total'=>$total,
                'promotion_id'=>$promotionId,
                'discount'=>$discount
            ]);
            $orderId = (int)$this->pdo->lastInsertId();

            foreach ($items as $line) {
                $quantity = (int)$line['quantity'];
                $stmt = $this->pdo->prepare('INSERT INTO order_items (order_id,product_variant_id,quantity,unit_price) VALUES (:order_id,:variant_id,:quantity,:unit_price)');
                $stmt->execute(['order_id'=>$orderId,'variant_id'=>$line['product_variant_id'],'quantity'=>$quantity,'unit_price'=>$line['unit_price']]);
                $stmt = $this->pdo->prepare('UPDATE product_variants SET stock_qty=stock_qty-:quantity WHERE id=:id');
                $stmt->execute(['quantity'=>$quantity,'id'=>$line['product_variant_id']]);
            }

            $stmt = $this->pdo->prepare("INSERT INTO order_status_history (order_id,status,changed_by) VALUES (:order_id,'a_preparer',:changed_by)");
            $stmt->execute(['order_id'=>$orderId,'changed_by'=>$user['id']]);
            $stmt = $this->pdo->prepare("INSERT INTO payments (order_id,status,method) VALUES (:order_id,'en_attente',:method)");
            $stmt->execute(['order_id'=>$orderId,'method'=>$d['payment_method']]);

            $this->pdo->commit();
            $_SESSION['cart'] = [];
            $order = [
                'id'=>$orderId,
                'total'=>$total,
                'discount_amount'=>$discount,
                'promotion_code'=>$promotionCode ?: null,
                'city_name'=>$city['name'],
                'reception_mode'=>$d['reception_mode']
            ];
            Response::success([
                'order'=>$order,
                'whatsapp_url'=>WhatsAppService::link($order,$items)
            ],'Commande créée.',201);
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            if ($e instanceof \RuntimeException) Response::error($e->getMessage(),422);
            throw $e;
        }
    }

    public function whatsapp(array $params): never
    {
        $this->auth->requireRoles(['client','vendeur','admin']);
        $stmt = $this->pdo->prepare('SELECT o.id,o.total,o.discount_amount,o.reception_mode,c.name AS city_name FROM orders o JOIN cities c ON c.id=o.city_id WHERE o.id=:id');
        $stmt->execute(['id'=>(int)$params['id']]);
        $order = $stmt->fetch();
        if (!$order) Response::error('Commande introuvable.',404);
        $stmt = $this->pdo->prepare('SELECT p.name,pv.composition,pv.flavor,pv.format,oi.quantity,oi.unit_price FROM order_items oi JOIN product_variants pv ON pv.id=oi.product_variant_id JOIN products p ON p.id=pv.product_id WHERE oi.order_id=:id');
        $stmt->execute(['id'=>(int)$params['id']]);
        Response::success(['whatsapp_url'=>WhatsAppService::link($order,$stmt->fetchAll())],'Lien WhatsApp généré.');
    }

    public function mine(): never
    {
        $user = $this->auth->requireRoles(['client']);
        $stmt = $this->pdo->prepare('SELECT o.id,o.total,o.discount_amount,o.reception_mode,o.status,o.created_at,c.name AS city_name FROM orders o JOIN cities c ON c.id=o.city_id WHERE o.phone=:phone ORDER BY o.created_at DESC');
        $stmt->execute(['phone'=>$user['phone']]);
        Response::success($stmt->fetchAll(),'Historique des commandes.');
    }
}
