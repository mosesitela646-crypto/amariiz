<?php
$router->add('GET','/api/v1/cities',fn()=> $cityController->list());
$router->add('POST','/api/v1/admin/cities',fn()=> $cityController->create());
$router->add('PATCH','/api/v1/admin/cities/{id}',fn(array $p)=> $cityController->update($p));
$router->add('DELETE','/api/v1/admin/cities/{id}',fn(array $p)=> $cityController->deactivate($p));
