<?php
declare(strict_types=1);

namespace Modules\Gestion;

use Core\Auth;
use Core\Request;
use Core\Response;
use Core\Validator;
use PDO;

final class GestionController
{
    public function __construct(private PDO $pdo, private Auth $auth) {}

    public function orders(): never
    {
        $this->auth->requireRoles(['vendeur','admin']); $where=[];$values=[];
        foreach(['status'=>['a_preparer','en_livraison','livree','annulee']] as $key=>$allowed)if(isset($_GET[$key])&&in_array($_GET[$key],$allowed,true)){$where[]="o.$key=:$key";$values[$key]=$_GET[$key];}
        if(isset($_GET['city_id'])&&ctype_digit((string)$_GET['city_id'])){$where[]='o.city_id=:city_id';$values['city_id']=(int)$_GET['city_id'];}
        if(isset($_GET['from'])&&preg_match('/^\d{4}-\d{2}-\d{2}$/',(string)$_GET['from'])){$where[]='o.created_at>=:from';$values['from']=$_GET['from'].' 00:00:00';}
        if(isset($_GET['to'])&&preg_match('/^\d{4}-\d{2}-\d{2}$/',(string)$_GET['to'])){$where[]='o.created_at<=:to';$values['to']=$_GET['to'].' 23:59:59';}
        if(isset($_GET['q'])&&trim((string)$_GET['q'])!==''){$where[]='(o.customer_name LIKE :q OR o.phone LIKE :q)';$values['q']='%'.trim((string)$_GET['q']).'%';}
        $sql='SELECT o.id,o.customer_name,o.phone,o.total,o.reception_mode,o.status,o.created_at,c.name AS city_name FROM orders o JOIN cities c ON c.id=o.city_id'.($where?' WHERE '.implode(' AND ',$where):'').' ORDER BY o.created_at DESC';
        $stmt=$this->pdo->prepare($sql);$stmt->execute($values);Response::success($stmt->fetchAll(),'Commandes disponibles.');
    }

    public function order(array $params): never
    {
        $this->auth->requireRoles(['vendeur','admin']);$stmt=$this->pdo->prepare('SELECT o.*,c.name AS city_name FROM orders o JOIN cities c ON c.id=o.city_id WHERE o.id=:id');$stmt->execute(['id'=>(int)$params['id']]);$order=$stmt->fetch();if(!$order)Response::error('Commande introuvable.',404);
        $stmt=$this->pdo->prepare('SELECT oi.quantity,oi.unit_price,p.name,pv.composition,pv.flavor,pv.format FROM order_items oi JOIN product_variants pv ON pv.id=oi.product_variant_id JOIN products p ON p.id=pv.product_id WHERE oi.order_id=:id');$stmt->execute(['id'=>$order['id']]);$order['items']=$stmt->fetchAll();
        $stmt=$this->pdo->prepare('SELECT h.status,h.created_at,u.name AS changed_by_name FROM order_status_history h JOIN users u ON u.id=h.changed_by WHERE h.order_id=:id ORDER BY h.created_at');$stmt->execute(['id'=>$order['id']]);$order['history']=$stmt->fetchAll();Response::success($order,'Détail de la commande.');
    }

    public function updateStatus(array $params): never
    {
        $user=$this->auth->requireRoles(['vendeur','admin']);$d=(new Validator(Request::json()))->required('status')->in('status',['a_preparer','en_livraison','livree','annulee'])->validated();
        $this->pdo->beginTransaction();try{$stmt=$this->pdo->prepare('SELECT id,status FROM orders WHERE id=:id FOR UPDATE');$stmt->execute(['id'=>(int)$params['id']]);$order=$stmt->fetch();if(!$order){$this->pdo->rollBack();Response::error('Commande introuvable.',404);}if($order['status']===$d['status']){$this->pdo->rollBack();Response::success(['status'=>$d['status']],'Statut inchangé.');}
        $stmt=$this->pdo->prepare('UPDATE orders SET status=:status WHERE id=:id');$stmt->execute(['status'=>$d['status'],'id'=>$order['id']]);$stmt=$this->pdo->prepare('INSERT INTO order_status_history (order_id,status,changed_by) VALUES (:order_id,:status,:changed_by)');$stmt->execute(['order_id'=>$order['id'],'status'=>$d['status'],'changed_by'=>$user['id']]);$this->pdo->commit();Response::success(['id'=>(int)$order['id'],'status'=>$d['status']],'Statut mis à jour.');}catch(\Throwable $e){if($this->pdo->inTransaction())$this->pdo->rollBack();throw $e;}
    }

    public function clients(): never
    {
        $this->auth->requireRoles(['admin']);
        $stmt = $this->pdo->query("SELECT u.id,u.name,u.phone,u.email,u.created_at,
            COUNT(o.id) AS orders_count,
            COALESCE(SUM(CASE WHEN o.status<>'annulee' THEN o.total ELSE 0 END),0) AS orders_value,
            MAX(o.created_at) AS last_order_at
            FROM users u LEFT JOIN orders o ON o.phone=u.phone
            WHERE u.role='client'
            GROUP BY u.id,u.name,u.phone,u.email,u.created_at
            ORDER BY last_order_at DESC,u.created_at DESC");
        Response::success($stmt->fetchAll(),'Clients et activité des commandes.');
    }

    public function lowStock(): never
    {
        $this->auth->requireRoles(['admin']);$stmt=$this->pdo->query('SELECT pv.id,p.name,pv.composition,pv.flavor,pv.format,pv.stock_qty,pv.low_stock_threshold FROM product_variants pv JOIN products p ON p.id=pv.product_id WHERE pv.stock_qty<=pv.low_stock_threshold ORDER BY pv.stock_qty ASC');Response::success($stmt->fetchAll(),'Alertes de stock faible.');
    }

    public function dashboard(): never
    {
        $this->auth->requireRoles(['admin']);$from=$_GET['from']??date('Y-m-01');$to=$_GET['to']??date('Y-m-d');if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$from)||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$to))Response::error('Période invalide.',422);$p=['from'=>$from.' 00:00:00','to'=>$to.' 23:59:59'];
        $stmt=$this->pdo->prepare("SELECT COUNT(*) AS orders_count,
            COALESCE(SUM(status='a_preparer'),0) AS pending_orders,
            COALESCE(SUM(status='en_livraison'),0) AS delivery_orders,
            COALESCE(SUM(status='livree'),0) AS delivered_orders,
            COALESCE(SUM(status='annulee'),0) AS cancellations,
            COALESCE(SUM(CASE WHEN status<>'annulee' THEN total ELSE 0 END),0) AS sales,
            COALESCE(SUM(discount_amount),0) AS discounts
            FROM orders WHERE created_at BETWEEN :from AND :to");
        $stmt->execute($p);$summary=$stmt->fetch();
        $summary['clients_count']=(int)$this->pdo->query("SELECT COUNT(*) FROM users WHERE role='client'")->fetchColumn();
        $data=['summary'=>$summary];
        $stmt=$this->pdo->prepare("SELECT p.name,COUNT(*) AS quantity FROM order_items oi JOIN orders o ON o.id=oi.order_id JOIN product_variants pv ON pv.id=oi.product_variant_id JOIN products p ON p.id=pv.product_id WHERE o.created_at BETWEEN :from AND :to AND o.status<>'annulee' GROUP BY p.id,p.name ORDER BY quantity DESC LIMIT 5");$stmt->execute($p);$data['top_products']=$stmt->fetchAll();
        $stmt=$this->pdo->prepare("SELECT c.name,COUNT(*) AS orders_count FROM orders o JOIN cities c ON c.id=o.city_id WHERE o.created_at BETWEEN :from AND :to AND o.status<>'annulee' GROUP BY c.id,c.name ORDER BY orders_count DESC LIMIT 5");$stmt->execute($p);$data['top_cities']=$stmt->fetchAll();Response::success($data,'Tableau de bord disponible.');
    }

    public function createReview(array $params): never
    {
        $user=$this->auth->requireRoles(['client','vendeur','admin']);$d=(new Validator(Request::json()))->required('rating')->integer('rating',1)->validated();if((int)$d['rating']>5)Response::error('La note doit être comprise entre 1 et 5.',422);
        $stmt=$this->pdo->prepare('SELECT id FROM orders WHERE id=:id AND status=\'livree\' AND phone=:phone');$stmt->execute(['id'=>(int)$params['id'],'phone'=>$user['phone']]);if(!$stmt->fetch())Response::error('Avis non autorisé pour cette commande.',403);
        try{$stmt=$this->pdo->prepare('INSERT INTO reviews (order_id,rating,comment) VALUES (:order_id,:rating,:comment)');$stmt->execute(['order_id'=>(int)$params['id'],'rating'=>(int)$d['rating'],'comment'=>isset($d['comment'])?trim((string)$d['comment']):null]);}catch(\PDOException){Response::error('Un avis existe déjà pour cette commande.',422);}Response::success(['order_id'=>(int)$params['id']],'Avis enregistré.',201);
    }

    public function reviews(): never
    {
        $this->auth->requireRoles(['admin']);$stmt=$this->pdo->query('SELECT r.id,r.order_id,r.rating,r.comment,o.customer_name,o.phone FROM reviews r JOIN orders o ON o.id=r.order_id ORDER BY r.id DESC');Response::success($stmt->fetchAll(),'Avis disponibles.');
    }
}
