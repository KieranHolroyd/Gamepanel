<?php

namespace App\API\V2;

use Headers;
use Helpers;

require __DIR__ . '/include.php';
$router = new \Bramus\Router\Router();

// Add CORS headers to all responses (for development)
$router->before('GET|POST|PUT|DELETE|OPTIONS', '/.*', function () {
	header("Access-Control-Allow-Origin: *");
	header("Access-Control-Allow-Headers: *");
	header("Access-Control-Allow-Credentials: true");
});

// Define routes
$router->setNamespace('\App\API\V2\Controller');
$router->set404(
	function () {
		echo Helpers::NewAPIResponse(["success" => false, "message" => "Route Not Found"]);
	}
);
$router->get('/', 'WelcomeController@Root');

//-- Authentication Routes
$router->post('/auth/login', 'AuthenticationController@Login');
$router->post('/auth/logout', 'AuthenticationController@Logout');
$router->post('/auth/signup', 'AuthenticationController@Signup');
$router->post('/auth/check', 'AuthenticationController@Check');

//-- User Routes
$router->get('/user/me', 'UserController@GetUserInformation');
$router->get('/user/me_new', 'UserController@GetUserInformationNew');

//-- Case Routes
$router->get('/cases/list', 'CasesController@GetCases');
$router->get('/cases/search', 'SearchController@Cases');
$router->get('/cases/{id}/info', 'CasesController@CaseInfo');
$router->post('/cases/submit', 'CasesController@SubmitCase');

//-- Guide Routes
$router->post('/guide/add', 'GuideController@addGuide');
$router->post('/guide/edit', 'GuideController@editGuide');
//$router->post('/guide/delete', 'GuideController@deleteGuide');
$router->post('/guide/full', 'GuideController@getFullGuide');
$router->get('/guide/get', 'GuideController@getGuides');

//-- Notification Routes
$router->get('/notifications/get', 'NotificationController@getNotifications');
$router->post('/notifications/set', 'NotificationController@setNotifications');
$router->post('/notifications/essential/mark', 'NotificationController@markEssentialRead');


//-- Player Routes
$router->get('/players/search', 'SearchController@players');
$router->get('/players/get', 'PlayerController@GetPlayerInformation');
$router->get('/players/vehicles', 'PlayerController@GetPlayerVehicles');
$router->get('/players/levels', 'PlayerController@GetLevelData');
$router->post('/players/update/admin', 'PlayerController@UpdatePlayerAdminLevel');
$router->post('/players/update/medic', 'PlayerController@UpdatePlayerMedicLevel');
$router->post('/players/update/police', 'PlayerController@UpdatePlayerPoliceLevel');
$router->post('/players/update/balance', 'PlayerController@UpdatePlayerBalance');

//-- Statistics Routes
$router->get('/statistics/cases', 'StatisticsController@CaseStatistics');
$router->get('/statistics/cases/daily', 'StatisticsController@DailyCases');
$router->get('/statistics/cases/weekly', 'StatisticsController@WeeklyCases');
$router->get('/statistics/game/server', 'StatisticsController@ServerStatistics');

//-- Staff Routes
$router->get('/staff/list', 'StaffController@ListStaffTeam');
$router->get('/staff/applications/list', 'StaffController@ListApplications');
$router->get('/staff/applications/get', 'StaffController@GetApplication');
$router->get('/staff/logger/list', 'StaffController@ListUsers');
$router->get('/staff/{id}/details', 'StaffController@StaffDetails');
$router->post('/staff/applications/submit', 'StaffController@SubmitApplication');
$router->post('/staff/rank/update', 'StaffController@UpdateStaffRank');
$router->post('/staff/team/update', 'StaffController@UpdateStaffTeam');

//-- Role Management Routes
$router->get('/roles/list', 'RoleController@List');
$router->get('/roles/{roleid}/get', 'RoleController@Get');
$router->post('/roles/add', 'RoleController@Add');
$router->post('/roles/{roleid}/update', 'RoleController@Update');
$router->post('/roles/{roleid}/shuffle', 'RoleController@Shuffle');
$router->post('/roles/{roleid}/delete', 'RoleController@Delete');

//-- Meeting Routes
$router->get('/meetings/list', 'MeetingsController@ListMeetings');
$router->get('/meetings/{meetingid}/point/{pointid}/get', 'MeetingsController@GetPoint');
$router->get('/meetings/{meetingid}/get', 'MeetingsController@GetMeeting');
$router->post('/meetings/{meetingid}/point/{pointid}/delete', 'MeetingsController@DeletePoint');
$router->post('/meetings/{meetingid}/point/{pointid}/comment', 'MeetingsController@AddPointComment');
$router->post('/meetings/{meetingid}/point/add', 'MeetingsController@AddPoint');
$router->post('/meetings/add', 'MeetingsController@CreateMeeting');

// Execute the router
$router->run();
