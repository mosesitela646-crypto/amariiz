<?php
$router->add('GET','/api/v1/payment-methods',fn()=> $paymentController->methods());
$router->add('PATCH','/api/v1/orders/{orderId}/payment',fn(array $p)=> $paymentController->update($p));
