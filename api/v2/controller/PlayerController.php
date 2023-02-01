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
			$stmt = $gamepdo->prepare('SELECT name FROM `players` WHERE playerid = :pid');
			$stmt->bindValue(':pid', $pid, PDO::PARAM_STR);
			$stmt->execute();
			$player = $stmt->fetch(PDO::FETCH_OBJ);

			echo Helpers::APIResponse("Success", ['name' => $player->name, 'vehicles' => $playerVehicles, 'vehiclesFilled' => count($playerVehicles)], 200);
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
			$player_id = (isset($_POST['id'])) ? $_POST['id'] : null;
			$al = (isset($_POST['al'])) ? $_POST['al'] : null;

			$gamepdo = game_pdo();

			if ($player_id == null || $al == null) {
				echo Helpers::APIResponse("No ID OR AdminLevel Passed", null, 400);
				exit;
			}

			$stmt = $gamepdo->prepare('UPDATE `players` SET adminlevel = :al WHERE playerid = :playerid');
			$stmt->bindValue(':playerid', $player_id);
			$stmt->bindValue(':al', $al, PDO::PARAM_INT);
			if ($stmt->execute()) {
				Helpers::addAuditLog("GAME::{$user->info->username} Changed Game_Player({$player_id}) Set AdminLevel = {$al}");
				echo Helpers::APIResponse("Success", null, 200);
			} else {
				Helpers::addAuditLog("DATABASE_ERROR::{$user->info->username} Failed To Change Game_Player({$player_id})::" . json_encode($stmt->errorInfo()));
				echo Helpers::APIResponse("Database Error", $stmt->errorInfo(), 500);
			}
		} else {
			Helpers::addAuditLog("GAME_PLAYER_UNAUTHORISED::{$user->info->username} Failed To Change Game_Player Insufficient Rank");
			echo Helpers::APIResponse("Insufficient Rank", null, 401);
		}
	}

	public function UpdatePlayerMedicLevel()
	{
		$user = new User;

		if (Permissions::init()->hasPermission("EDIT_PLAYER_MEDIC")) {
			$player_id = (isset($_POST['id'])) ? $_POST['id'] : null;
			$ml = (isset($_POST['ml'])) ? $_POST['ml'] : null;

			$gamepdo = game_pdo();

			if ($player_id == null || $ml == null) {
				echo Helpers::APIResponse("No ID OR MedicLevel Passed", null, 400);
				exit;
			}

			$stmt = $gamepdo->prepare('UPDATE `players` SET mediclevel = :ml WHERE playerid = :playerid');
			$stmt->bindValue(':playerid', $player_id);
			$stmt->bindValue(':ml', $ml, PDO::PARAM_INT);
			if ($stmt->execute()) {
				Helpers::addAuditLog("GAME::{$user->info->username} Changed Game_Player({$player_id}) Set MedicLevel = {$ml}");
				echo Helpers::APIResponse("Success", null, 200);
			} else {
				Helpers::addAuditLog("DATABASE_ERROR::{$user->info->username} Failed To Change Game_Player({$player_id})::" . json_encode($stmt->errorInfo()));
				echo Helpers::APIResponse("Database Error", $stmt->errorInfo(), 500);
			}
		} else {
			Helpers::addAuditLog("GAME_PLAYER_UNAUTHORISED::{$user->info->username} Failed To Change Game_Player Insufficient Rank");
			echo Helpers::APIResponse("Insufficient Rank", null, 401);
		}
	}
	public function UpdatePlayerPoliceLevel()
	{
		$user = new User;

		if (Permissions::init()->hasPermission("EDIT_PLAYER_POLICE")) {
			$player_id = (isset($_POST['id'])) ? $_POST['id'] : null;
			$pl = (isset($_POST['pl'])) ? $_POST['pl'] : null;

			$gamepdo = game_pdo();

			if ($player_id == null || $pl == null) {
				echo Helpers::APIResponse("No ID OR PoliceLevel Passed", null, 400);
				exit;
			}

			$stmt = $gamepdo->prepare('UPDATE `players` SET natoRank = :pl WHERE playerid = :playerid');
			$stmt->bindValue(':playerid', $player_id);
			$stmt->bindValue(':pl', $pl, PDO::PARAM_INT);
			if ($stmt->execute()) {
				Helpers::addAuditLog("GAME::{$user->info->username} Changed Game_Player({$player_id}) Set PoliceLevel = {$pl}");
				echo Helpers::APIResponse("Success", null, 200);
			} else {
				Helpers::addAuditLog("DATABASE_ERROR::{$user->info->username} Failed To Change Game_Player({$player_id})::" . json_encode($stmt->errorInfo()));
				echo Helpers::APIResponse("Database Error", $stmt->errorInfo(), 500);
			}
		} else {
			Helpers::addAuditLog("GAME_PLAYER_UNAUTHORISED::{$user->info->username} Failed To Change Game_Player Insufficient Rank");
			echo Helpers::APIResponse("Insufficient Rank", null, 401);
		}
	}
	// public function UpdatePlayerMedicDepartment()
	// {
	// 	$user = new User;

	// 	if (Permissions::init()->hasPermission("EDIT_PLAYER_MEDIC")) {
	// 		$player_id = (isset($_POST['id'])) ? $_POST['id'] : null;
	// 		$md = (isset($_POST['md'])) ? $_POST['md'] : null;

	// 		$gamepdo = game_pdo();

	// 		if ($player_id == null || $md == null) {
	// 			echo Helpers::APIResponse("No ID OR MedicLevel Passed", null, 400);
	// 			exit;
	// 		}

	// 		$stmt = $gamepdo->prepare('UPDATE `players` SET medicdept = :md WHERE playerid = :playerid');
	// 		$stmt->bindValue(':playerid', $player_id, PDO::PARAM_INT);
	// 		$stmt->bindValue(':md', $md, PDO::PARAM_INT);
	// 		if ($stmt->execute()) {
	// 			Helpers::addAuditLog("GAME::{$user->info->username} Changed Game_Player({$player_id}) Set MedicDept = {$md}");
	// 			echo Helpers::APIResponse("Success", null, 200);
	// 		} else {
	// 			Helpers::addAuditLog("DATABASE_ERROR::{$user->info->username} Failed To Change Game_Player({$player_id})::" . json_encode($stmt->errorInfo()));
	// 			echo Helpers::APIResponse("Database Error", $stmt->errorInfo(), 500);
	// 		}
	// 	} else {
	// 		Helpers::addAuditLog("GAME_PLAYER_UNAUTHORISED::{$user->info->username} Failed To Change Game_Player Insufficient Rank");
	// 		echo Helpers::APIResponse("Insufficient Rank", null, 401);
	// 	}
	// }
	// public function UpdatePlayerPoliceDepartment()
	// {
	// 	$user = new User;

	// 	if (Permissions::init()->hasPermission("EDIT_PLAYER_POLICE")) {
	// 		$player_id = (isset($_POST['id'])) ? $_POST['id'] : null;
	// 		$pd = (isset($_POST['pd'])) ? $_POST['pd'] : null;

	// 		$gamepdo = game_pdo();

	// 		if ($player_id == null || $pd == null) {
	// 			echo Helpers::APIResponse("No ID OR MedicLevel Passed", null, 400);
	// 			exit;
	// 		}

	// 		$stmt = $gamepdo->prepare('UPDATE `players` SET copdept = :pd WHERE playerid = :playerid');
	// 		$stmt->bindValue(':playerid', $player_id, PDO::PARAM_INT);
	// 		$stmt->bindValue(':pd', $pd, PDO::PARAM_INT);
	// 		if ($stmt->execute()) {
	// 			Helpers::addAuditLog("GAME::{$user->info->username} Changed Game_Player({$player_id}) Set PoliceDept = {$pd}");
	// 			echo Helpers::APIResponse("Success", null, 200);
	// 		} else {
	// 			Helpers::addAuditLog("DATABASE_ERROR::{$user->info->username} Failed To Change Game_Player({$player_id})::" . json_encode($stmt->errorInfo()));
	// 			echo Helpers::APIResponse("Database Error", $stmt->errorInfo(), 500);
	// 		}
	// 	} else {
	// 		Helpers::addAuditLog("GAME_PLAYER_UNAUTHORISED::{$user->info->username} Failed To Change Game_Player Insufficient Rank");
	// 		echo Helpers::APIResponse("Insufficient Rank", null, 401);
	// 	}
	// }

	public function UpdatePlayerBalance()
	{
		$user = new User;

		if (Permissions::init()->hasPermission("EDIT_PLAYER_BALANCE")) {
			$player_id = (isset($_POST['id'])) ? $_POST['id'] : null;
			$pb = (isset($_POST['pb'])) ? $_POST['pb'] : null;
			$comp = (isset($_POST['comp'])) ? ' [COMPENSATION]' : '';

			$gamepdo = game_pdo();

			if ($player_id == null || $pb == null) {
				echo Helpers::APIResponse("No ID OR Balance Passed", null, 400);
				exit;
			}

			if ($pb == 'NaN')
				$pb = 0;

			$stmt = $gamepdo->prepare('SELECT * FROM `players` WHERE playerid = :playerid');
			$stmt->bindValue(':playerid', $player_id);
			$stmt->execute();
			$current = $stmt->fetch(PDO::FETCH_OBJ);

			$stmt = $gamepdo->prepare('UPDATE `players` SET bankacc = :pb WHERE playerid = :playerid');
			$stmt->bindValue(':playerid', $player_id);
			$stmt->bindValue(':pb', $pb, PDO::PARAM_INT);
			if ($stmt->execute()) {
				Helpers::addAuditLog("GAME::{$user->info->username} Changed Game_Player({$player_id}) [Currently \${$current->bankacc}] Changed Balance To \${$pb}{$comp}");
				echo Helpers::APIResponse("Success", null, 200);
			} else {
				Helpers::addAuditLog("DATABASE_ERROR::{$user->info->username} Failed To Change Game_Player({$player_id})::" . json_encode($stmt->errorInfo()));
				echo Helpers::APIResponse("Database Error", $stmt->errorInfo(), 500);
			}
		} else {
			Helpers::addAuditLog("GAME_PLAYER_UNAUTHORISED::{$user->info->username} Failed To Change Game_Player Insufficient Rank");
			echo Helpers::APIResponse("Insufficient Rank", null, 401);
		}
	}
}
