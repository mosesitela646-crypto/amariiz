<?php
$router->add('GET','/api/v1/catalogue/products',fn()=> $catalogueController->list());
$router->add('GET','/api/v1/catalogue/products/{id}',fn(array $p)=> $catalogueController->show($p));
$router->add('GET','/api/v1/catalogue/products/{id}/flavors',fn(array $p)=> $catalogueController->flavors($p));
$router->add('POST','/api/v1/admin/products',fn()=> $catalogueController->createProduct());
$router->add('PATCH','/api/v1/admin/products/{id}',fn(array $p)=> $catalogueController->updateProduct($p));
$router->add('POST','/api/v1/admin/products/{id}/photo',fn(array $p)=> $catalogueController->uploadPhoto($p));
$router->add('POST','/api/v1/admin/variants',fn()=> $catalogueController->createVariant());
$router->add('PATCH','/api/v1/admin/variants/{id}',fn(array $p)=> $catalogueController->updateVariant($p));
