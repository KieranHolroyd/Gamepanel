<?php

namespace App\API\V2\Controller;

use  \Helpers, \PDO, \User;

class UserController
{
	public function GetUserInformation()
	{
		global $pdo;
		if (!Helpers::getAuth()) {
			echo Helpers::APIResponse('Invalid API Token', null, 401);
			exit;
		}

		$cookietoken = sha1(Helpers::getAuth());
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
			$returned_user = [
				"id" => $user->id,
				"username" => $user->username,
				"first_name" => $user->first_name,
				"last_name" => $user->last_name,
				"email" => $user->email,
				"suspended" => $user->suspended,
				"slt" => $user->SLT,
				"dev" => $user->Developer,
				"team" => $user->staff_team
			];
			setcookie('userArrayPHP', serialize($returned_user), time() + 60 * 60 * 24 * 30, '/');
			echo Helpers::newAPIResponse(["user" => [
				"id" => $user->id,
				"username" => $user->username,
				"first_name" => $user->first_name,
				"last_name" => $user->last_name,
				"email" => $user->email,
				"suspended" => $user->suspended,
				"slt" => $user->SLT,
				"dev" => $user->Developer,
				"team" => $user->staff_team
			], "success" => true]);
		} else {
			echo Helpers::NewAPIResponse(["success" => false, "message" => "User not found"]);
		}
	}

	public function GetUserInformationNew()
	{
		$user = new User;

		if ($user->verified(false)) {
			echo Helpers::NewAPIResponse(["success" => true, "user" => $user->getInfoForFrontend()]);
		} else {
			echo Helpers::NewAPIResponse(["success" => false, "message" => "User not found"]);
		}
	}
}
