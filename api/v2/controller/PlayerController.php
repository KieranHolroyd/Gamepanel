<?php

namespace App\API\V2\Controller;

use \Permissions, \Helpers, \PDO, \User;

class PlayerController
{
	public function GetPlayerInformation()
	{
		global $pdo;

		if (Permissions::init()->hasPermission("VIEW_GAME_PLAYER")) {
			$uid = (isset($_GET['id'])) ? $_GET['id'] : null;

			$gamepdo = game_pdo();

			if ($uid == null) {
				echo Helpers::APIResponse("No ID Passed", null, 400);
				exit;
			}


			$stmt = $gamepdo->prepare('SELECT * FROM `players` WHERE playerid = :uid');
			$stmt->bindValue(':uid', $uid, PDO::PARAM_STR);
			$stmt->execute();
			$player = $stmt->fetch(PDO::FETCH_OBJ);

			$stmt = $pdo->prepare("SELECT * FROM `audit_log` WHERE LOCATE(:id, log_content)>0 ORDER BY id DESC");
			$stmt->bindValue(':id', "Game_Player({$player->playerid})", PDO::PARAM_STR);
			$stmt->execute();
			$auditLogs = $stmt->fetchAll();

			foreach ($auditLogs as $log) {
				$log->staff_member_name = ($log->logged_in_user != null) ? Helpers::IDToUsername($log->logged_in_user) : '';
			}

			$player->formatbankacc = "$" . number_format($player->bankacc);
			$player->cash = number_format($player->cash);
			$player->exp_total = @number_format($player->exp_total); // supress error for setting an undefined variable
			$player->edits = $auditLogs;

			echo Helpers::APIResponse("Success", $player, 200);
		} else {
			echo Helpers::APIResponse("Not High Enough Rank", null, 403);
		}
	}

	public function GetPlayerVehicles()
	{

		if (Permissions::init()->hasPermission("VIEW_GAME_VEHICLES")) {
			$pid = (isset($_GET['id'])) ? $_GET['id'] : null;

			$gamepdo = game_pdo();

			if ($pid == null) {
				echo Helpers::APIResponse("No ID Passed", null, 400);
				exit;
			}

			$stmt = $gamepdo->prepare('SELECT * FROM `vehicles` WHERE pid = :pid');
			$stmt->bindValue(':pid', $pid, PDO::PARAM_STR);
			$stmt->execute();
			$playerVehicles = $stmt->fetchAll(PDO::FETCH_OBJ);

			echo Helpers::APIResponse("Success", ['vehicles' => $playerVehicles, 'vehiclesFilled' => count($playerVehicles)], 200);
		} else {
			echo Helpers::APIResponse("Not High Enough Rank", null, 403);
		}
	}

	public function GetLevelData()
	{
		if (Permissions::init()->hasPermission("VIEW_GAME_PLAYER")) {
			$levelSettings = [
				"police" => [
					0 => 'Not Whitelisted',
					1 => 'Cadet',
					2 => 'Officer',
					3 => 'Senior Officer',
					4 => 'Corporal',
					5 => 'Sergeant',
					6 => 'Lieutenant/Captain',
					7 => 'State Command'
				],
				"police_department" => [
					0 => 'No Department',
					1 => 'Department Of Corrections',
					2 => 'Patrol',
					3 => 'Highway Patrol',
					4 => 'Internal Affairs',
					5 => 'Corrections Response Team',
					6 => 'Special Weapons And Tactics (SWAT)',
					7 => 'Command'
				],
				"admin" => [
					0 => 'No Admin Rank',
					1 => 'Trial Administrator',
					2 => 'Moderator',
					3 => 'Senior Leadership Team',
					4 => 'Staff Manager',
					5 => 'Senior Management Team',
				],
				"medic" => [
					0 => 'Not Whitelisted',
					1 => 'EMT',
					2 => 'Advanced EMT',
					3 => 'Volunteer / Paramedic',
					4 => 'Advanced Paramedic',
					5 => 'Field Commander',
					6 => 'Captain',
					7 => 'Assistant Chief',
					8 => 'Deputy Chief',
					9 => 'Chief Of EMS',
				],
				"medic_department" => [
					0 => 'None',
					1 => 'EMS Department',
					2 => 'Fire Department'
				],
				"vehicle_dictionary" => json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/lib/carDictionary.json'))
			];
			echo Helpers::APIResponse("Success", $levelSettings, 200);
		}
	}

	public function UpdatePlayerAdminLevel()
	{
		$user = new User;

		if (Permissions::init()->hasPermission("EDIT_PLAYER_ADMIN")) {
			$uid = (isset($_POST['id'])) ? $_POST['id'] : null;
			$al = (isset($_POST['al'])) ? $_POST['al'] : null;

			$gamepdo = game_pdo();

			if ($uid == null || $al == null) {
				echo Helpers::APIResponse("No ID OR AdminLevel Passed", null, 400);
				exit;
			}

			$stmt = $gamepdo->prepare('UPDATE `players` SET adminlevel = :al WHERE playerid = :uid');
			$stmt->bindValue(':uid', $uid);
			$stmt->bindValue(':al', $al, PDO::PARAM_INT);
			if ($stmt->execute()) {
				Helpers::addAuditLog("GAME::{$user->info->username} Changed Game_Player({$uid}) Set AdminLevel = {$al}");
				echo Helpers::APIResponse("Success", null, 200);
			} else {
				Helpers::addAuditLog("DATABASE_ERROR::{$user->info->username} Failed To Change Game_Player({$uid})::" . json_encode($stmt->errorInfo()));
				echo Helpers::APIResponse("Database Error", $stmt->errorInfo(), 500);
			}
		} else {
			Helpers::addAuditLog("GAME_PLAYER_UNAUTHORISED::{$user->info->username} Failed To Change Game_Player Insufficient Rank");
			echo Helpers::APIResponse("Insufficient Rank", null, 401);
		}
	}
}

// else if ($url == "playerChangeAdminLevel") {
// 	$user = new User;

// 	if (Permissions::init()->hasPermission("EDIT_PLAYER_ADMIN")) {
// 			$uid = (isset($_POST['id'])) ? $_POST['id'] : null;
// 			$al = (isset($_POST['al'])) ? $_POST['al'] : null;

// 			$gamepdo = game_pdo();

// 			if ($uid == null || $al == null) {
// 					echo Helpers::APIResponse("No ID OR AdminLevel Passed", null, 400);
// 					exit;
// 			}

// 			$stmt = $gamepdo->prepare('UPDATE `players` SET adminlevel = :al WHERE uid = :uid');
// 			$stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
// 			$stmt->bindValue(':al', $al, PDO::PARAM_INT);
// 			if ($stmt->execute()) {
// 					Helpers::addAuditLog("GAME::{$user->info->username} Changed Game_Player({$uid}) Set AdminLevel = {$al}");
// 					echo Helpers::APIResponse("Success", null, 200);
// 			} else {
// 					Helpers::addAuditLog("DATABASE_ERROR::{$user->info->username} Failed To Change Game_Player({$uid})::" . json_encode($stmt->errorInfo()));
// 					echo Helpers::APIResponse("Database Error", $stmt->errorInfo(), 500);
// 			}
// 	} else {
// 			Helpers::addAuditLog("GAME_PLAYER_UNAUTHORISED::{$user->info->username} Failed To Change Game_Player Insufficient Rank");
// 			echo Helpers::APIResponse("Insufficient Rank", null, 401);
// 	}
// } else if ($url == "playerChangeMedicLevel") {
// 	$user = new User;

// 	if (Permissions::init()->hasPermission("EDIT_PLAYER_MEDIC")) {
// 			$uid = (isset($_POST['id'])) ? $_POST['id'] : null;
// 			$ml = (isset($_POST['ml'])) ? $_POST['ml'] : null;

// 			$gamepdo = game_pdo();

// 			if ($uid == null || $ml == null) {
// 					echo Helpers::APIResponse("No ID OR MedicLevel Passed", null, 400);
// 					exit;
// 			}

// 			$stmt = $gamepdo->prepare('UPDATE `players` SET mediclevel = :ml WHERE uid = :uid');
// 			$stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
// 			$stmt->bindValue(':ml', $ml, PDO::PARAM_INT);
// 			if ($stmt->execute()) {
// 					Helpers::addAuditLog("GAME::{$user->info->username} Changed Game_Player({$uid}) Set MedicLevel = {$ml}");
// 					echo Helpers::APIResponse("Success", null, 200);
// 			} else {
// 					Helpers::addAuditLog("DATABASE_ERROR::{$user->info->username} Failed To Change Game_Player({$uid})::" . json_encode($stmt->errorInfo()));
// 					echo Helpers::APIResponse("Database Error", $stmt->errorInfo(), 500);
// 			}
// 	} else {
// 			Helpers::addAuditLog("GAME_PLAYER_UNAUTHORISED::{$user->info->username} Failed To Change Game_Player Insufficient Rank");
// 			echo Helpers::APIResponse("Insufficient Rank", null, 401);
// 	}
// } else if ($url == "playerChangeMedicDepartment") {
// 	$user = new User;

// 	if (Permissions::init()->hasPermission("EDIT_PLAYER_MEDIC")) {
// 			$uid = (isset($_POST['id'])) ? $_POST['id'] : null;
// 			$ml = (isset($_POST['md'])) ? $_POST['md'] : null;

// 			$gamepdo = game_pdo();

// 			if ($uid == null || $ml == null) {
// 					echo Helpers::APIResponse("No ID OR MedicLevel Passed", null, 400);
// 					exit;
// 			}

// 			$stmt = $gamepdo->prepare('UPDATE `players` SET medicdept = :ml WHERE uid = :uid');
// 			$stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
// 			$stmt->bindValue(':ml', $ml, PDO::PARAM_INT);
// 			if ($stmt->execute()) {
// 					Helpers::addAuditLog("GAME::{$user->info->username} Changed Game_Player({$uid}) Set MedicDept = {$ml}");
// 					echo Helpers::APIResponse("Success", null, 200);
// 			} else {
// 					Helpers::addAuditLog("DATABASE_ERROR::{$user->info->username} Failed To Change Game_Player({$uid})::" . json_encode($stmt->errorInfo()));
// 					echo Helpers::APIResponse("Database Error", $stmt->errorInfo(), 500);
// 			}
// 	} else {
// 			Helpers::addAuditLog("GAME_PLAYER_UNAUTHORISED::{$user->info->username} Failed To Change Game_Player Insufficient Rank");
// 			echo Helpers::APIResponse("Insufficient Rank", null, 401);
// 	}
// } else if ($url == "playerChangePoliceLevel") {
// 	$user = new User;

// 	if (Permissions::init()->hasPermission("EDIT_PLAYER_POLICE")) {
// 			$uid = (isset($_POST['id'])) ? $_POST['id'] : null;
// 			$pl = (isset($_POST['pl'])) ? $_POST['pl'] : null;

// 			$gamepdo = game_pdo();

// 			if ($uid == null || $pl == null) {
// 					echo Helpers::APIResponse("No ID OR PoliceLevel Passed", null, 400);
// 					exit;
// 			}

// 			$stmt = $gamepdo->prepare('UPDATE `players` SET coplevel = :pl WHERE uid = :uid');
// 			$stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
// 			$stmt->bindValue(':pl', $pl, PDO::PARAM_INT);
// 			if ($stmt->execute()) {
// 					Helpers::addAuditLog("GAME::{$user->info->username} Changed Game_Player({$uid}) Set PoliceLevel = {$pl}");
// 					echo Helpers::APIResponse("Success", null, 200);
// 			} else {
// 					Helpers::addAuditLog("DATABASE_ERROR::{$user->info->username} Failed To Change Game_Player({$uid})::" . json_encode($stmt->errorInfo()));
// 					echo Helpers::APIResponse("Database Error", $stmt->errorInfo(), 500);
// 			}
// 	} else {
// 			Helpers::addAuditLog("GAME_PLAYER_UNAUTHORISED::{$user->info->username} Failed To Change Game_Player Insufficient Rank");
// 			echo Helpers::APIResponse("Insufficient Rank", null, 401);
// 	}
// } else if ($url == "playerChangePoliceDepartment") {
// 	$user = new User;

// 	if (Permissions::init()->hasPermission("EDIT_PLAYER_POLICE")) {
// 			$uid = (isset($_POST['id'])) ? $_POST['id'] : null;
// 			$ml = (isset($_POST['pd'])) ? $_POST['pd'] : null;

// 			$gamepdo = game_pdo();

// 			if ($uid == null || $ml == null) {
// 					echo Helpers::APIResponse("No ID OR MedicLevel Passed", null, 400);
// 					exit;
// 			}

// 			$stmt = $gamepdo->prepare('UPDATE `players` SET copdept = :ml WHERE uid = :uid');
// 			$stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
// 			$stmt->bindValue(':ml', $ml, PDO::PARAM_INT);
// 			if ($stmt->execute()) {
// 					Helpers::addAuditLog("GAME::{$user->info->username} Changed Game_Player({$uid}) Set PoliceDept = {$ml}");
// 					echo Helpers::APIResponse("Success", null, 200);
// 			} else {
// 					Helpers::addAuditLog("DATABASE_ERROR::{$user->info->username} Failed To Change Game_Player({$uid})::" . json_encode($stmt->errorInfo()));
// 					echo Helpers::APIResponse("Database Error", $stmt->errorInfo(), 500);
// 			}
// 	} else {
// 			Helpers::addAuditLog("GAME_PLAYER_UNAUTHORISED::{$user->info->username} Failed To Change Game_Player Insufficient Rank");
// 			echo Helpers::APIResponse("Insufficient Rank", null, 401);
// 	}
// } else if ($url == "playerChangeBalance") {
// 	$user = new User;

// 	if (Permissions::init()->hasPermission("EDIT_PLAYER_BALANCE")) {
// 			$uid = (isset($_POST['id'])) ? $_POST['id'] : null;
// 			$pb = (isset($_POST['pb'])) ? $_POST['pb'] : null;

// 			$gamepdo = game_pdo();

// 			if ($uid == null || $pb == null) {
// 					echo Helpers::APIResponse("No ID OR Balance Passed", null, 400);
// 					exit;
// 			}

// 			if ($pb == 'NaN')
// 					$pb = 0;

// 			$stmt = $gamepdo->prepare('SELECT * FROM `players` WHERE uid = :uid');
// 			$stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
// 			$stmt->execute();
// 			$current = $stmt->fetch(PDO::FETCH_OBJ);

// 			$stmt = $gamepdo->prepare('UPDATE `players` SET bankacc = :pb WHERE uid = :uid');
// 			$stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
// 			$stmt->bindValue(':pb', $pb, PDO::PARAM_INT);
// 			if ($stmt->execute()) {
// 					$comp = (isset($_POST['comp'])) ? ' [COMPENSATION]' : '';
// 					Helpers::addAuditLog("GAME::{$user->info->username} Changed Game_Player({$uid}) [Currently \${$current->bankacc}] Changed Balance To \${$pb}{$comp}");
// 					echo Helpers::APIResponse("Success", null, 200);
// 			} else {
// 					Helpers::addAuditLog("DATABASE_ERROR::{$user->info->username} Failed To Change Game_Player({$uid})::" . json_encode($stmt->errorInfo()));
// 					echo Helpers::APIResponse("Database Error", $stmt->errorInfo(), 500);
// 			}
// 	} else {
// 			Helpers::addAuditLog("GAME_PLAYER_UNAUTHORISED::{$user->info->username} Failed To Change Game_Player Insufficient Rank");
// 			echo Helpers::APIResponse("Insufficient Rank", null, 401);
// 	}
// }