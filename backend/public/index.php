<?php
declare(strict_types=1);

use Core\{Auth, Env, Response, Router};
use Modules\Auth\AuthController;
use Modules\Commande\{CartController, OrderController};
use Modules\Paiement\PaymentController;
use Modules\Catalogue\CatalogueController;
use Modules\Villes\CityController;
use Modules\Gestion\GestionController;
use Modules\Promotions\PromotionController;

spl_autoload_register(function (string $class): void { $base=dirname(__DIR__); if(str_starts_with($class,'Core\\')){$file=$base.'/core/'.substr($class,5).'.php';}else{$parts=explode('\\',$class);$file=$base.'/modules/'.strtolower($parts[1]??'').'/'.end($parts).'.php';} if(is_file($file)) require $file; });
Env::load(dirname(__DIR__).'/.env');
$origin=$_SERVER['HTTP_ORIGIN']??''; $allowed=Env::get('CORS_ALLOWED_ORIGIN',''); if($origin!=='' && hash_equals($allowed,$origin)) { header('Access-Control-Allow-Origin: '.$origin); header('Vary: Origin'); header('Access-Control-Allow-Credentials: true'); header('Access-Control-Allow-Headers: Authorization, Content-Type'); header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS'); }
if(($_SERVER['REQUEST_METHOD']??'GET')==='OPTIONS'){http_response_code(204);exit;}
set_exception_handler(function(Throwable $e): never { error_log($e->getMessage()); Response::error(Env::get('APP_DEBUG','false')==='true' ? $e->getMessage() : 'Erreur interne du serveur.',500); });
$pdo=require dirname(__DIR__).'/config/database.php'; $auth=new Auth($pdo); $router=new Router(); $authController=new AuthController($pdo,$auth); $cartController=new CartController($pdo); $orderController=new OrderController($pdo,$cartController,$auth); $paymentController=new PaymentController($pdo,$auth);
$catalogueController=new CatalogueController($pdo,$auth); $cityController=new CityController($pdo,$auth); $gestionController=new GestionController($pdo,$auth); $promotionController=new PromotionController($pdo,$auth);
require dirname(__DIR__).'/modules/auth/routes.php'; require dirname(__DIR__).'/modules/commande/routes.php'; require dirname(__DIR__).'/modules/paiement/routes.php'; require dirname(__DIR__).'/modules/catalogue/routes.php'; require dirname(__DIR__).'/modules/villes/routes.php'; require dirname(__DIR__).'/modules/gestion/routes.php'; require dirname(__DIR__).'/modules/promotions/routes.php';
$path=parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH) ?: '/'; $base=rtrim(dirname($_SERVER['SCRIPT_NAME']??''),'/'); if($base!=='' && str_starts_with($path,$base)) $path=substr($path,strlen($base)) ?: '/'; $router->dispatch($_SERVER['REQUEST_METHOD']??'GET',$path);
