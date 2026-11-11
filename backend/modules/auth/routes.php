<?php
use Modules\Auth\AuthController;
$router->add('POST','/api/v1/auth/register',fn()=> $authController->register());
$router->add('POST','/api/v1/auth/login',fn()=> $authController->login());
$router->add('POST','/api/v1/auth/logout',fn()=> $authController->logout());
$router->add('GET','/api/v1/auth/me',fn()=> $authController->me());
