<?php

namespace App\API\V2\Controller;

use \User, \Permissions, \PDO, \Helpers, \DateTime;

function invalid_staff_application($application)
{
	return ($application == null || empty($application) || empty($application["age"]) || empty($application["email"]) || empty($application["discord"]) || empty($application["timezone"]) || empty($application["about_me"]) || empty($application["why_me"]) || empty($application["experience"]));
}

class StaffController
{
	public function ListUsers()
	{
		global $pdo;

		$user = new User;

		if (Permissions::init()->hasPermission("VIEW_GENERAL")) {
			$staff = [];
			foreach ($pdo->query('SELECT `id`, `first_name`, `last_name`, `username` FROM `users` BETWEEN 0 AND 200 ORDER BY `username`') as $r) {
				if ($r->id !== $user->info->id) {
					array_push($staff, [
						'name' => $r->username,
						'display' => "{$r->first_name} {$r->last_name}"
					]);
				}
			}
			echo json_encode($staff);
		}
	}

	public function ListStaffTeam()
	{
		global $pdo;

		if (Permissions::init()->hasPermission("VIEW_SLT")) {
			$staff = [];
			$i = 1;
			$stmt = $pdo->prepare('SELECT * FROM users WHERE staff_team BETWEEN 0 AND 200 ORDER BY staff_team, username, id ASC;');
			$stmt->execute();
			$users = $stmt->fetchAll();
			foreach ($users as $r) {
				$staffname = $r->username;
				$stmt = $pdo->prepare("SELECT count(*) as count FROM case_logs WHERE `timestamp` > NOW() - INTERVAL 7 DAY AND (`lead_staff` LIKE :uname OR `other_staff` LIKE :uname)");
				$stmt->bindValue(':uname', '%' . $staffname . '%', PDO::PARAM_STR);
				$stmt->execute();
				$recent = $stmt->fetch()->count;
				$activity = 'Good';
				if ($r->rank_lvl < 4) {
					$activity = 'God';
				}
				if (($r->rank_lvl != 9 || (time() - strtotime($r->lastPromotion)) > 128000) && $r->rank_lvl > 6) {
					if ($recent < 20) {
						$activity = 'Initial Warning';
					}
					if ($recent < 10) {
						$activity = '<span style="color: #ff8a00;">Warning</span>';
					}
					if ($recent < 3) {
						$activity = '<span style="color: #ff0000;">Terrible</span>';
					}
				}

				$loa = '';
				if ($r->loa !== null) {
					/** @noinspection PhpUnhandledExceptionInspection */
					if (new DateTime() < new DateTime($r->loa)) {
						$loa = '<span title="Leave Of Absence" class="punishmentincase" style="font-size: 12px;vertical-align: middle;">LOA</span>';
					}
				}
				if ($r->suspended) {
					$loa = '<span title="Leave Of Absence" class="punishmentincase" style="font-size: 12px;vertical-align: middle;">SUSPENDED</span>';
				}

				$user_highest_rank = Permissions::getHighestRank(json_decode($r->rank_groups, true));

				$stmt = $pdo->prepare('SELECT * FROM rank_groups WHERE position = :i');
				$stmt->bindValue(':i', $user_highest_rank, PDO::PARAM_INT);
				$stmt->execute();
				$ri = $stmt->fetch();
				$rank = ($ri !== false) ? $ri : 'Unranked';
				array_push($staff, [
					'id' => $r->id,
					'name' => $loa . $staffname,
					'displayName' => $loa . $r->first_name . " " . $r->last_name,
					'team' => $r->staff_team,
					'highest_rank_position' => $user_highest_rank,
					'rank' => $rank,
					'region' => $r->region,
					'activity' => $activity
				]);
			}

			// TODO: make sure this shit works please
			usort($staff, fn ($a, $b) => $a['highest_rank_position'] - $b['highest_rank_position']);

			echo Helpers::APIResponse("Success", $staff, 200);
		} else {
			echo Helpers::APIResponse("Unauthorised", null, 403);
		}
	}

	public function UpdateStaffRank()
	{
		global $pdo;
		$user = new User;

		$id = ($_POST['id']) ? $_POST['id'] : null;
		$rank = ($_POST['rank']) ? $_POST['rank'] : null;
		$selected = ($_POST['selected']) ? $_POST['selected'] : null;

		if (Permissions::init()->hasPermission("EDIT_USER_RANK")) {
			if ($id === null || $rank === null || $selected === null) {
				echo Helpers::APIResponse("Invalid Request", [$id, $rank, $selected], 400);
				exit;
			}

			$user_highest_rank_position = Permissions::getHighestRank(json_decode($user->info->rank_groups, true));

			// $stmt = $pdo->prepare('SELECT * FROM rank_groups WHERE position = :i');
			// $stmt->bindValue(':i', $user_highest_rank_position, PDO::PARAM_INT);
			// $stmt->execute();
			// $highest_rank = $stmt->fetch();

			if ($user_highest_rank_position == 10)
				$user_highest_rank_position = 0;

			if (Permissions::init()->isOverlord())
				$user_highest_rank_position = 0;

			$stmt = $pdo->prepare("SELECT * FROM rank_groups WHERE position > :p");
			$stmt->bindValue(':p', $user_highest_rank_position, PDO::PARAM_INT);
			$stmt->execute();
			$ranks_available = $stmt->fetchAll();

			foreach ($ranks_available as $rr) {
				if ($rr->id >= $rank)
					$passed_auth = true;
			}

			if (!isset($passed_auth) || !$passed_auth) {
				echo Helpers::APIResponse("Unauthorised", [$rank, $ranks_available], 403);
				exit;
			}

			$stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
			$stmt->bindValue(':id', $id, PDO::PARAM_INT);
			$stmt->execute();
			$usr = $stmt->fetch();

			$promotion = date("Y-m-d", time());

			$usr_ranks = json_decode($usr->rank_groups, true);

			if ($selected == 'yes') {
				foreach ($usr_ranks as $k => $rr) {
					if ($rr == $rank)
						unset($usr_ranks[$k]);
				}
			} else {
				array_push($usr_ranks, (int) $rank);
			}

			$stmt = $pdo->prepare("UPDATE users SET rank_groups = :rg, lastPromotion = :lp WHERE id = :i");
			$stmt->bindValue(':rg', json_encode($usr_ranks), PDO::PARAM_STR);
			$stmt->bindValue(':lp', $promotion, PDO::PARAM_STR);
			$stmt->bindValue(':i', $usr->id, PDO::PARAM_STR);
			$stmt->execute();
			$updatedUsername = Helpers::IDToUsername($usr->id);
			echo Helpers::APIResponse("Success", null, 200);
			Helpers::addAuditLog("{$user->info->username} Updated {$updatedUsername}'s Ranks Added: {$rank}, Now: " . json_encode($usr_ranks));
		} else {
			echo Helpers::APIResponse("Unauthorised", null, 403);
			Helpers::addAuditLog("AUTHENTICATION_FAILED::{$_SERVER['REMOTE_ADDR']} Triggered An Unauthenticated Response In `SetStaffRank`");
		}
	}

	public function UpdateStaffTeam()
	{
		global $pdo;

		$user = new User;

		$id = (isset($_POST['id'])) ? $_POST['id'] : null;
		$team = (isset($_POST['team'])) ? $_POST['team'] : null;

		if (Permissions::init()->hasPermission("EDIT_USER_TEAM")) {
			$exec = $pdo->prepare("UPDATE users SET staff_team = :team WHERE id = :id");
			$exec->execute(['team' => $team, 'id' => $id]);
			$updatedUsername = Helpers::IDToUsername($id);
			Helpers::addAuditLog("{$user->info->username} Updated {$updatedUsername}'s Team To {$team}");
		} else {
			Helpers::addAuditLog("AUTHENTICATION_FAILED::{$_SERVER['REMOTE_ADDR']} Triggered An Unauthenticated Response In `StaffController::UpdateStaffTeam`");
		}
	}

	public function ListApplications()
	{
		global $pdo;

		if (!Permissions::init()->hasPermission("VIEW_SLT")) {
			Helpers::addAuditLog("AUTHENTICATION_FAILED::{$_SERVER['REMOTE_ADDR']} Triggered An Unauthenticated Response In `StaffController::ListApplicants`");
			echo Helpers::NewAPIResponse(["message" => "Unauthorised", "success" => false]);
			exit;
		}

		$stmt = $pdo->prepare("SELECT name,id FROM staff_applications WHERE status = 'Pending' ORDER BY created_at DESC");
		$stmt->execute();
		$applications = $stmt->fetchAll();

		if (!$applications) {
			echo Helpers::NewAPIResponse(["message" => "No applications found", "success" => false]);
			exit;
		}

		echo Helpers::NewAPIResponse(["message" => "success", "success" => true, "applications" => $applications]);
	}

	public function GetApplication()
	{
		global $pdo;

		if (!Permissions::init()->hasPermission("VIEW_SLT")) {
			Helpers::addAuditLog("AUTHENTICATION_FAILED::{$_SERVER['REMOTE_ADDR']} Triggered An Unauthenticated Response In `StaffController::GetApplication`");
			echo Helpers::NewAPIResponse(["message" => "Unauthorised", "success" => false]);
			exit;
		}

		$id = (isset($_GET['id'])) ? $_GET['id'] : null;

		if (!$id) {
			echo Helpers::NewAPIResponse(["message" => "Invalid Request", "success" => false]);
			exit;
		}

		$stmt = $pdo->prepare("SELECT * FROM staff_applications WHERE id = :id");
		$stmt->bindValue(':id', $id);
		$stmt->execute();
		$application = $stmt->fetch();

		if (!$application) {
			echo Helpers::NewAPIResponse(["message" => "No application found", "success" => false]);
			exit;
		}

		echo Helpers::NewAPIResponse(["message" => "success", "success" => true, "application" => $application]);
	}

	public function SubmitApplication()
	{
		global $pdo;

		$name = (isset($_POST['name'])) ? $_POST['name'] : null;
		$application = (isset($_POST['data'])) ? $_POST['data'] : null;

		// Check application has required fields
		if ($name == null || invalid_staff_application($application)) {
			echo Helpers::NewAPIResponse(["message" => "Invalid Request", "success" => false]);
			exit;
		}


		if (!$application) {
			echo Helpers::NewAPIResponse(["message" => "Invalid Request", "success" => false]);
			exit;
		}

		$stmt = $pdo->prepare("INSERT INTO staff_applications (name, data) VALUES (:name, :data)");
		$stmt->bindValue(':name', $name);
		$stmt->bindValue(':data', json_encode($application));
		$db_call = $stmt->execute();

		if (!$db_call) {
			echo Helpers::NewAPIResponse(["message" => "Failed to submit application", "success" => false]);
			exit;
		}

		echo Helpers::NewAPIResponse(["message" => "success", "success" => true]);
	}
}
