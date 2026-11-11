<?php
declare(strict_types=1);
namespace Modules\Paiement;
use Core\Auth; use Core\Request; use Core\Response; use Core\Validator; use PDO;
final class PaymentController {
 public function __construct(private PDO $pdo, private Auth $auth) {}
 public function methods(): never { $this->auth->requireRoles(['client','vendeur','admin']); Response::success(['cash'=>'Espèces','mobile_money'=>'Mobile Money','virement'=>'Virement bancaire'],'Modes de paiement disponibles.'); }
 public function update(array $p): never { $user=$this->auth->requireRoles(['vendeur','admin']); $d=(new Validator(Request::json()))->required('status')->in('status',['en_attente','paye','a_payer_livraison'])->validated(); $s=$this->pdo->prepare('UPDATE payments SET status=:status WHERE order_id=:order_id');$s->execute(['status'=>$d['status'],'order_id'=>(int)$p['orderId']]);if($s->rowCount()===0)Response::error('Paiement introuvable.',404); Response::success(['order_id'=>(int)$p['orderId'],'status'=>$d['status'],'changed_by'=>(int)$user['id']],'Statut de paiement mis à jour.'); }
}
