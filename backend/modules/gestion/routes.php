<?php
$router->add('GET','/api/v1/seller/orders',fn()=> $gestionController->orders());
$router->add('GET','/api/v1/seller/orders/search',fn()=> $gestionController->orders());
$router->add('GET','/api/v1/seller/orders/{id}',fn(array $p)=> $gestionController->order($p));
$router->add('PATCH','/api/v1/seller/orders/{id}/status',fn(array $p)=> $gestionController->updateStatus($p));
$router->add('GET','/api/v1/admin/clients',fn()=> $gestionController->clients());
$router->add('GET','/api/v1/admin/stock/low',fn()=> $gestionController->lowStock());
$router->add('GET','/api/v1/admin/dashboard',fn()=> $gestionController->dashboard());
$router->add('POST','/api/v1/orders/{id}/review',fn(array $p)=> $gestionController->createReview($p));
$router->add('GET','/api/v1/admin/reviews',fn()=> $gestionController->reviews());
