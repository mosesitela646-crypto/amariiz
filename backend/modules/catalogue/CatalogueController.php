<?php
declare(strict_types=1);

namespace Modules\Catalogue;

use Core\Auth;
use Core\Request;
use Core\Response;
use Core\Validator;
use PDO;

final class CatalogueController
{
    public function __construct(private PDO $pdo, private Auth $auth) {}

    public function list(): never
    {
        $where = ['p.is_active = 1', 'pv.is_available = 1']; $params = [];
        foreach (['composition' => ['avec_sucre', 'sans_sucre'], 'flavor' => ['banane', 'vanille'], 'format' => ['petit', 'grand']] as $field => $allowed) {
            $value = $_GET[$field] ?? null;
            if ($value !== null && in_array($value, $allowed, true)) { $where[] = "pv.{$field} = :{$field}"; $params[$field] = $value; }
        }
        $sql = 'SELECT p.id AS product_id,p.name,p.description,p.photo_url,pv.id AS variant_id,pv.composition,pv.flavor,pv.format,pv.price,pv.stock_qty
                FROM products p JOIN product_variants pv ON pv.product_id=p.id WHERE ' . implode(' AND ', $where) . ' ORDER BY p.name,pv.format,pv.flavor';
        $stmt = $this->pdo->prepare($sql); $stmt->execute($params);
        Response::success($stmt->fetchAll(), 'Catalogue disponible.');
    }

    public function show(array $params): never
    {
        $stmt = $this->pdo->prepare('SELECT id,name,description,photo_url FROM products WHERE id=:id AND is_active=1');
        $stmt->execute(['id' => (int) $params['id']]); $product = $stmt->fetch();
        if (!$product) Response::error('Produit introuvable.', 404);
        $stmt = $this->pdo->prepare('SELECT id,composition,flavor,format,price,stock_qty FROM product_variants WHERE product_id=:id AND is_available=1 ORDER BY format,flavor');
        $stmt->execute(['id' => $product['id']]); $product['variants'] = $stmt->fetchAll();
        Response::success($product, 'Produit disponible.');
    }

    public function flavors(array $params): never
    {
        $stmt = $this->pdo->prepare('SELECT DISTINCT flavor FROM product_variants WHERE product_id=:id AND is_available=1 ORDER BY flavor');
        $stmt->execute(['id' => (int) $params['id']]); Response::success($stmt->fetchAll(PDO::FETCH_COLUMN), 'Parfums disponibles.');
    }

    public function createProduct(): never
    {
        $this->auth->requireRoles(['admin']); $d = (new Validator(Request::json()))->required('name')->validated();
        $stmt = $this->pdo->prepare('INSERT INTO products (name,description,photo_url,is_active) VALUES (:name,:description,:photo_url,:is_active)');
        $stmt->execute(['name' => trim($d['name']), 'description' => isset($d['description']) ? trim((string) $d['description']) : null, 'photo_url' => null, 'is_active' => !empty($d['is_active']) ? 1 : 0]);
        Response::success(['id' => (int) $this->pdo->lastInsertId()], 'Produit créé.', 201);
    }

    public function updateProduct(array $params): never
    {
        $this->auth->requireRoles(['admin']); $d = Request::json(); if ($d === []) Response::error('Aucune modification fournie.', 422);
        $fields=[]; $values=['id'=>(int)$params['id']];
        foreach (['name','description'] as $field) if (array_key_exists($field,$d)) { $fields[]="$field=:$field"; $values[$field]=trim((string)$d[$field]); }
        if (array_key_exists('is_active',$d)) { $fields[]='is_active=:is_active'; $values['is_active']=$d['is_active'] ? 1 : 0; }
        if (!$fields) Response::error('Champs non modifiables.',422);
        $stmt=$this->pdo->prepare('UPDATE products SET '.implode(',',$fields).' WHERE id=:id'); $stmt->execute($values);
        if ($stmt->rowCount()===0) Response::error('Produit introuvable ou inchangé.',404); Response::success(null,'Produit mis à jour.');
    }

    public function uploadPhoto(array $params): never
    {
        $this->auth->requireRoles(['admin']);
        if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) Response::error('Photo invalide ou absente.', 422);
        $file=$_FILES['photo']; if ((int)$file['size'] > 5*1024*1024) Response::error('La photo dépasse 5 Mo.',422);
        $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']); $extensions=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
        if (!isset($extensions[$mime])) Response::error('Format autorisé : JPEG, PNG ou WEBP.',422);
        $check=$this->pdo->prepare('SELECT id FROM products WHERE id=:id'); $check->execute(['id'=>(int)$params['id']]); if (!$check->fetch()) Response::error('Produit introuvable.',404);
        $dir=dirname(__DIR__,2).'/uploads/products'; if (!is_dir($dir) && !mkdir($dir,0755,true) && !is_dir($dir)) Response::error('Impossible de préparer le stockage.',500);
        $name=bin2hex(random_bytes(16)).'.'.$extensions[$mime]; if (!move_uploaded_file($file['tmp_name'],$dir.'/'.$name)) Response::error('Échec de l’enregistrement de la photo.',500);
        $url='/uploads/products/'.$name; $stmt=$this->pdo->prepare('UPDATE products SET photo_url=:url WHERE id=:id'); $stmt->execute(['url'=>$url,'id'=>(int)$params['id']]); Response::success(['photo_url'=>$url],'Photo enregistrée.');
    }

    public function createVariant(): never
    {
        $this->auth->requireRoles(['admin']); $d=(new Validator(Request::json()))->required('product_id')->integer('product_id')->required('composition')->in('composition',['avec_sucre','sans_sucre'])->required('flavor')->in('flavor',['banane','vanille'])->required('format')->in('format',['petit','grand'])->required('price')->required('stock_qty')->integer('stock_qty',0)->validated();
        if (!is_numeric($d['price']) || (float)$d['price'] <= 0) Response::error('Prix invalide.',422);
        $stmt=$this->pdo->prepare('INSERT INTO product_variants (product_id,composition,flavor,format,price,stock_qty,low_stock_threshold,is_available) VALUES (:product_id,:composition,:flavor,:format,:price,:stock_qty,:threshold,:available)');
        try {$stmt->execute(['product_id'=>(int)$d['product_id'],'composition'=>$d['composition'],'flavor'=>$d['flavor'],'format'=>$d['format'],'price'=>(float)$d['price'],'stock_qty'=>(int)$d['stock_qty'],'threshold'=>isset($d['low_stock_threshold']) ? max(0,(int)$d['low_stock_threshold']) : 5,'available'=>!empty($d['is_available']) ? 1 : 0]);} catch (\PDOException) { Response::error('Produit introuvable ou variante déjà existante.',422); }
        Response::success(['id'=>(int)$this->pdo->lastInsertId()],'Variante créée.',201);
    }

    public function updateVariant(array $params): never
    {
        $this->auth->requireRoles(['admin']); $d=Request::json(); $fields=[];$values=['id'=>(int)$params['id']];
        foreach (['price','stock_qty','low_stock_threshold'] as $f) { if(array_key_exists($f,$d)){if(!is_numeric($d[$f]) || (float)$d[$f]<0) Response::error('Valeur numérique invalide.',422);$fields[]="$f=:$f";$values[$f]=$f==='price'?(float)$d[$f]:(int)$d[$f];} }
        if(array_key_exists('is_available',$d)){$fields[]='is_available=:is_available';$values['is_available']=$d['is_available']?1:0;} if(!$fields)Response::error('Aucune modification valide.',422);
        $stmt=$this->pdo->prepare('UPDATE product_variants SET '.implode(',',$fields).' WHERE id=:id');$stmt->execute($values);if($stmt->rowCount()===0)Response::error('Variante introuvable ou inchangée.',404);Response::success(null,'Variante mise à jour.');
    }
}
