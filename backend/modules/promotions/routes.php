<?php
$router->add('GET','/api/v1/promotions',fn()=> $promotionController->publicList());
$router->add('GET','/api/v1/admin/promotions',fn()=> $promotionController->adminList());
$router->add('POST','/api/v1/admin/promotions',fn()=> $promotionController->create());
$router->add('PATCH','/api/v1/admin/promotions/{id}',fn(array $p)=> $promotionController->update($p));
