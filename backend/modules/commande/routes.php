<?php
$router->add('GET','/api/v1/cart',fn()=> $cartController->show());
$router->add('POST','/api/v1/cart/items',fn()=> $cartController->add());
$router->add('PATCH','/api/v1/cart/items/{variantId}',fn(array $p)=> $cartController->update($p));
$router->add('DELETE','/api/v1/cart/items/{variantId}',fn(array $p)=> $cartController->delete($p));
$router->add('POST','/api/v1/orders',fn()=> $orderController->create());
$router->add('GET','/api/v1/orders/mine',fn()=> $orderController->mine());
$router->add('GET','/api/v1/orders/{id}/whatsapp',fn(array $p)=> $orderController->whatsapp($p));
