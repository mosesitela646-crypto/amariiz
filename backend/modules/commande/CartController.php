<?php
declare(strict_types=1);
namespace Modules\Commande;
use Core\Response; use Core\Request; use Core\Validator; use PDO;
final class CartController {
 public function __construct(private PDO $pdo) { if(session_status()!==PHP_SESSION_ACTIVE) session_start(); $_SESSION['cart'] ??= []; }
 public function show(): never { Response::success($this->details(),'Panier récupéré.'); }
 public function add(): never { $d=(new Validator(Request::json()))->integer('product_variant_id')->integer('quantity')->validated(); $id=(int)$d['product_variant_id']; $_SESSION['cart'][$id]=($_SESSION['cart'][$id]??0)+(int)$d['quantity']; Response::success($this->details(),'Produit ajouté au panier.',201); }
 public function update(array $p): never { $d=(new Validator(Request::json()))->integer('quantity')->validated(); $id=(int)$p['variantId']; if(!isset($_SESSION['cart'][$id])) Response::error('Article introuvable dans le panier.',404); $_SESSION['cart'][$id]=(int)$d['quantity']; Response::success($this->details(),'Panier mis à jour.'); }
 public function delete(array $p): never { $id=(int)$p['variantId']; unset($_SESSION['cart'][$id]); Response::success($this->details(),'Article supprimé du panier.'); }
 public function lines(): array { return $this->details()['items']; }
 private function details(): array { $cart=$_SESSION['cart']; if(!$cart) return ['items'=>[],'total'=>0]; $ids=array_keys($cart); $holders=implode(',',array_fill(0,count($ids),'?')); $s=$this->pdo->prepare("SELECT pv.id,p.name,pv.composition,pv.flavor,pv.format,pv.price,pv.stock_qty,pv.is_available FROM product_variants pv JOIN products p ON p.id=pv.product_id WHERE pv.id IN ($holders) AND p.is_active=1"); $s->execute($ids); $found=[]; foreach($s->fetchAll() as $v){$q=(int)$cart[$v['id']]; $found[]=['product_variant_id'=>(int)$v['id'],'name'=>$v['name'],'composition'=>$v['composition'],'flavor'=>$v['flavor'],'format'=>$v['format'],'quantity'=>$q,'unit_price'=>(float)$v['price'],'subtotal'=>(float)$v['price']*$q,'available'=>(bool)$v['is_available']&&(int)$v['stock_qty']>=$q];} return ['items'=>$found,'total'=>array_sum(array_column($found,'subtotal'))]; }
}
