<?php

namespace App\API\V2;

require __DIR__ . '/include.php';
$router = new \Bramus\Router\Router();

// Define routes
$router->setNamespace('\App\API\V2\Controller');
$router->get('/', 'WelcomeController@Root');

//-- Authentication Routes
$router->post('/auth/login', 'AuthenticationController@Login');
$router->post('/auth/logout', 'AuthenticationController@Logout');
$router->post('/auth/signup', 'AuthenticationController@Signup');
$router->post('/auth/check', 'AuthenticationController@Check');

//-- Guide Routes
$router->post('/guide/add', 'GuideController@addGuide');
$router->post('/guide/edit', 'GuideController@editGuide');
//$router->post('/guide/delete', 'GuideController@deleteGuide');
$router->post('/guide/full', 'GuideController@getFullGuide');
$router->get('/guide/get', 'GuideController@getGuides');

//-- Staff Routes
$router->get('/staff/cases/list', 'StaffController@List');
$router->get('/staff/list', 'StaffController@ListStaffTeam');
$router->post('/staff/rank/update', 'StaffController@UpdateStaffRank');
$router->post('/staff/team/update', 'StaffController@UpdateStaffTeam');

// Execute the router
$router->run();
