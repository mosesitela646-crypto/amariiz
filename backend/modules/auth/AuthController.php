<?php
declare(strict_types=1);
namespace Modules\Auth;
use Core\Auth; use Core\Response; use Core\Request; use Core\Validator; use PDO;
final class AuthController {
    public function __construct(private PDO $pdo, private Auth $auth) {}
    public function register(): never { $d=(new Validator(Request::json()))->required('name')->required('phone')->phone('phone')->required('email')->email('email')->required('password')->validated(); if (mb_strlen((string)$d['password'])<8) Response::error('Données invalides.',422,['password'=>['Le mot de passe doit contenir au moins 8 caractères.']]); $s=$this->pdo->prepare('SELECT id FROM users WHERE email=:email OR phone=:phone LIMIT 1'); $s->execute(['email'=>strtolower(trim($d['email'])),'phone'=>trim($d['phone'])]); if($s->fetch()) Response::error('Un compte utilise déjà cet e-mail ou ce téléphone.',422); $s=$this->pdo->prepare('INSERT INTO users (name,phone,email,password_hash,role) VALUES (:name,:phone,:email,:password_hash,\'client\')'); $s->execute(['name'=>trim($d['name']),'phone'=>trim($d['phone']),'email'=>strtolower(trim($d['email'])),'password_hash'=>password_hash($d['password'],PASSWORD_DEFAULT)]); $id=(int)$this->pdo->lastInsertId(); Response::success(['user'=>['id'=>$id,'name'=>trim($d['name']),'role'=>'client'],'token'=>$this->auth->issueToken($id)],'Inscription réussie.',201); }
    public function login(): never { $d=(new Validator(Request::json()))->required('email')->email('email')->required('password')->validated(); $s=$this->pdo->prepare('SELECT id,name,phone,email,password_hash,role FROM users WHERE email=:email LIMIT 1'); $s->execute(['email'=>strtolower(trim($d['email']))]); $u=$s->fetch(); if(!$u || !password_verify($d['password'],$u['password_hash'])) Response::error('E-mail ou mot de passe incorrect.',401); unset($u['password_hash']); Response::success(['user'=>$u,'token'=>$this->auth->issueToken((int)$u['id'])],'Connexion réussie.'); }
    public function logout(): never { $this->auth->revokeCurrentToken(); Response::success(null,'Déconnexion réussie.'); }
    public function me(): never { Response::success($this->auth->user(),'Utilisateur authentifié.'); }
}
