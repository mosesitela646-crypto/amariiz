<?php
declare(strict_types=1);

namespace Modules\Villes;

use Core\Auth;
use Core\Request;
use Core\Response;
use Core\Validator;
use PDO;

final class CityController
{
    public function __construct(private PDO $pdo, private Auth $auth) {}

    public function list(): never
    {
        $stmt=$this->pdo->query('SELECT id,name,delivery_fee,delivery_delay_hours FROM cities WHERE is_active=1 ORDER BY name');
        Response::success($stmt->fetchAll(),'Villes disponibles.');
    }

    public function create(): never
    {
        $this->auth->requireRoles(['admin']); $d=(new Validator(Request::json()))->required('name')->required('delivery_fee')->required('delivery_delay_hours')->integer('delivery_delay_hours',0)->validated();
        if(!is_numeric($d['delivery_fee']) || (float)$d['delivery_fee']<0) Response::error('Frais de livraison invalides.',422);
        try {$stmt=$this->pdo->prepare('INSERT INTO cities (name,delivery_fee,delivery_delay_hours,is_active) VALUES (:name,:fee,:delay,:active)');$stmt->execute(['name'=>trim($d['name']),'fee'=>(float)$d['delivery_fee'],'delay'=>(int)$d['delivery_delay_hours'],'active'=>!empty($d['is_active'])?1:0]);} catch(\PDOException){Response::error('Cette ville existe déjà.',422);}
        Response::success(['id'=>(int)$this->pdo->lastInsertId()],'Ville créée.',201);
    }

    public function update(array $params): never
    {
        $this->auth->requireRoles(['admin']); $d=Request::json();$fields=[];$values=['id'=>(int)$params['id']];
        if(array_key_exists('name',$d)){if(trim((string)$d['name'])==='')Response::error('Nom de ville invalide.',422);$fields[]='name=:name';$values['name']=trim((string)$d['name']);}
        if(array_key_exists('delivery_fee',$d)){if(!is_numeric($d['delivery_fee'])||(float)$d['delivery_fee']<0)Response::error('Frais invalides.',422);$fields[]='delivery_fee=:fee';$values['fee']=(float)$d['delivery_fee'];}
        if(array_key_exists('delivery_delay_hours',$d)){if(!filter_var($d['delivery_delay_hours'],FILTER_VALIDATE_INT)|| (int)$d['delivery_delay_hours']<0)Response::error('Délai invalide.',422);$fields[]='delivery_delay_hours=:delay';$values['delay']=(int)$d['delivery_delay_hours'];}
        if(array_key_exists('is_active',$d)){$fields[]='is_active=:active';$values['active']=$d['is_active']?1:0;}if(!$fields)Response::error('Aucune modification valide.',422);
        try{$stmt=$this->pdo->prepare('UPDATE cities SET '.implode(',',$fields).' WHERE id=:id');$stmt->execute($values);}catch(\PDOException){Response::error('Nom de ville déjà utilisé.',422);}if($stmt->rowCount()===0)Response::error('Ville introuvable ou inchangée.',404);Response::success(null,'Ville mise à jour.');
    }

    public function deactivate(array $params): never
    {
        $this->auth->requireRoles(['admin']);$stmt=$this->pdo->prepare('UPDATE cities SET is_active=0 WHERE id=:id');$stmt->execute(['id'=>(int)$params['id']]);if($stmt->rowCount()===0)Response::error('Ville introuvable ou déjà inactive.',404);Response::success(null,'Ville désactivée.');
    }
}
