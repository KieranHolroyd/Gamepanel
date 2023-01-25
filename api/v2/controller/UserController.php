<?php

namespace App\API\V2\Controller;

use  \Helpers, \PDO;

class UserController
{
	public function GetUserInformation()
	{
		global $pdo;
		if (!isset($_COOKIE['LOGINTOKEN'])) {
			echo Helpers::APIResponse('Invalid API Token', null, 401);
			exit;
		}

		$cookietoken = sha1($_COOKIE['LOGINTOKEN']);
		//Get User ID From Login Tokens
		$sql = "SELECT * FROM login_tokens WHERE token = :token";
		$query = $pdo->prepare($sql);
		$query->bindValue(':token', $cookietoken, PDO::PARAM_STR);
		$query->execute();
		$result = $query->fetch();
		//Get Logged In User's Information
		$sql2 = "SELECT * FROM users WHERE id = :id";
		$query2 = $pdo->prepare($sql2);
		$query2->bindValue(':id', $result->user_id, PDO::PARAM_STR);
		$query2->execute();
		$user = $query2->fetch();
		if ($user) {
			//Assign Values To An Array.
			$arr = [];
			$arr['info']['id'] = $user->id;
			$arr['info']['username'] = $user->username;
			$arr['info']['first_name'] = $user->first_name;
			$arr['info']['last_name'] = $user->last_name;
			$arr['info']['email'] = $user->email;
			$arr['info']['suspended'] = $user->suspended;
			$arr['info']['slt'] = $user->SLT;
			$arr['info']['dev'] = $user->Developer;
			$arr['info']['team'] = $user->staff_team;
			setcookie('userArrayPHP', serialize($arr), time() + 60 * 60 * 24 * 30, '/');
		} else {
			$arr = ['error' => true];
		}
		echo json_encode($arr);
	}
}
