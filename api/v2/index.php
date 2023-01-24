<?php
namespace App\API\V2;

require __DIR__ . '/include.php';
$router = new \Bramus\Router\Router();

// Define routes
$router->setNamespace('\App\API\V2\Controller');
$router->get('/', 'WelcomeController@Root');
// Authentication Routes
$router->post('/auth/login', 'AuthenticationController@Login');
$router->post('/auth/logout', 'AuthenticationController@Logout');
$router->post('/auth/signup', 'AuthenticationController@Signup');
$router->post('/auth/check', 'AuthenticationController@Check');

// Execute the router
$router->run();