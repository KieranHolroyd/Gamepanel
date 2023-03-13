<?php

namespace App\API\V2\Controller;

use \User, \Permissions, \PDO, \Helpers, \DateTime;

function invalid_staff_application($application) {
	return ($application == null || empty($application) || empty($application["age"]) || empty($application["email"]) || empty($application["discord"]) || empty($application["timezone"]) || empty($application["about_me"]) || empty($application["why_me"]) || empty($application["experience"]));
}

class StaffController {
	public function ListUsers() {
		global $pdo;

		$user = new User;

		if (Permissions::init()->hasPermission("VIEW_GENERAL")) {
			$staff = [];
			foreach ($pdo->query('SELECT `id`, `first_name`, `last_name`, `username` FROM `users` WHERE `staff_team` BETWEEN 0 AND 200 ORDER BY `username`') as $r) {
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

	public function ListStaffTeam() {
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

	public function UpdateStaffRank() {
		global $pdo;
		$user = new User;

		$id = ($_POST['id']) ? $_POST['id'] : null;
		$rank = ($_POST['rank']) ? $_POST['rank'] : null;
		$selected = ($_POST['selected']) ? $_POST['selected'] : null;

		if (Permissions::init()->hasPermission("EDIT_USER_PROMOTION")) {
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

	public function UpdateStaffTeam() {
		global $pdo;

		$user = new User;

		$id = (isset($_POST['id'])) ? $_POST['id'] : null;
		$team = (isset($_POST['team'])) ? $_POST['team'] : null;

		if (Permissions::init()->hasPermission("EDIT_USER_INFO")) {
			$exec = $pdo->prepare("UPDATE users SET staff_team = :team WHERE id = :id");
			$exec->execute(['team' => $team, 'id' => $id]);
			$updatedUsername = Helpers::IDToUsername($id);
			Helpers::addAuditLog("{$user->info->username} Updated {$updatedUsername}'s Team To {$team}");
		} else {
			Helpers::addAuditLog("AUTHENTICATION_FAILED::{$_SERVER['REMOTE_ADDR']} Triggered An Unauthenticated Response In `StaffController::UpdateStaffTeam`");
		}
	}

	public function ListApplications() {
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

	public function GetApplication() {
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

	public function SubmitApplication() {
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

		// Send notification to SLT
		// TODO: Make this more tidier, probably a function in Helpers
		$stmt = $pdo->prepare("SELECT id, rank_groups FROM users");
		$stmt->execute();
		$users = $stmt->fetchAll();
		$stmt = $pdo->prepare("SELECT id, permissions FROM rank_groups");
		$stmt->execute();
		$ranks = $stmt->fetchAll();

		$sent = false;
		foreach ($users as $user) {
			$rank_groups = json_decode($user->rank_groups);
			foreach ($rank_groups as $rank) {
				foreach ($ranks as $r) {
					if (!$sent && $r->id == $rank && in_array("VIEW_SLT", json_decode($r->permissions))) {
						$sent = true;
						Helpers::sendNotificationTo($user->id, "New Staff Application", "A new staff application has been submitted by {$name}", "/staff/applications");
					}
				}
			}
		}

		echo Helpers::NewAPIResponse(["message" => "success", "success" => true]);
	}

	//TODO: proof read, copilot wrote it
	public function UpdateApplication() {
		global $pdo;

		$id = (isset($_POST['id'])) ? $_POST['id'] : null;
		$status = (isset($_POST['status'])) ? $_POST['status'] : null;
		$comment = (isset($_POST['comment'])) ? $_POST['comment'] : null;

		if (!Permissions::init()->hasPermission("VIEW_SLT")) {
			Helpers::addAuditLog("AUTHENTICATION_FAILED::{$_SERVER['REMOTE_ADDR']} Triggered An Unauthenticated Response In `StaffController::UpdateApplication`");
			echo Helpers::NewAPIResponse(["message" => "Unauthorised", "success" => false]);
			exit;
		}

		$stmt = $pdo->prepare("UPDATE staff_applications SET status = :status, comment = :comment WHERE id = :id");
		$stmt->bindValue(':status', $status);
		$stmt->bindValue(':comment', $comment);
		$stmt->bindValue(':id', $id);
		$db_call = $stmt->execute();

		if (!$db_call) {
			echo Helpers::NewAPIResponse(["message" => "Failed to update application", "success" => false]);
			exit;
		}

		echo Helpers::NewAPIResponse(["message" => "success", "success" => true]);
	}

	public function StaffDetails($id) {
		global $pdo;
		$user = new User;

		if (Permissions::init()->hasPermission("VIEW_USER_INFO")) {
			$staffinfo = [];
			$sql = "SELECT * FROM users WHERE id = :id";
			$initial_query = $pdo->prepare($sql);
			$initial_query->bindValue(':id', $id, PDO::PARAM_STR);
			$initial_query->execute();
			$r = $initial_query->fetch();
			$staffname = $r->username;
			$stmt = $pdo->prepare("SELECT count(*) as Count FROM case_logs WHERE `lead_staff` LIKE :uname OR `other_staff` LIKE :uname");
			$stmt->bindValue(':uname', '%' . $staffname . '%', PDO::PARAM_STR);
			$stmt->execute();
			$AllTime = $stmt->fetch()->Count;
			$stmt = $pdo->prepare("SELECT count(*) as Count FROM case_logs WHERE `timestamp` > NOW() - INTERVAL 7 DAY AND (`lead_staff` LIKE :uname OR `other_staff` LIKE :uname)");
			$stmt->bindValue(':uname', '%' . $staffname . '%', PDO::PARAM_STR);
			$stmt->execute();
			$Recent = $stmt->fetch()->Count;
			$stmt = $pdo->prepare("SELECT count(*) as Count FROM case_logs WHERE `timestamp` > NOW() - INTERVAL 30 DAY AND (`lead_staff` LIKE :uname OR `other_staff` LIKE :uname)");
			$stmt->bindValue(':uname', '%' . $staffname . '%', PDO::PARAM_STR);
			$stmt->execute();
			$Month = $stmt->fetch()->Count;
			$stmt = $pdo->prepare("SELECT * FROM case_logs WHERE `timestamp` > NOW() - INTERVAL 7 DAY AND (`lead_staff` LIKE :uname OR `other_staff` LIKE :uname)");
			$stmt->bindValue(':uname', '%' . $staffname . '%', PDO::PARAM_STR);
			$stmt->execute();
			$Cases = $stmt->fetchAll();
			$recentCount = $Recent;
			$allTimeCount = $AllTime;
			//Check for activity warnings based on current weekly case count.
			$staffinfo['activity_warning'] = false;
			if ($r->rank_lvl >= 7) {
				if ($Recent < 5 && $Month < 10) {
					$staffinfo['activity_warning'] = true;
				}
			}

			$staffinfo['onLOA'] = false;

			if ($r->loa !== null) {
				/** @noinspection PhpUnhandledExceptionInspection */
				if (new DateTime() < new DateTime($r->loa)) {
					$staffinfo['onLOA'] = true;
					$staffinfo['loaEND'] = $r->loa;
				}
			}

			$activityGraph = [
				'Today' => 0,
				'Yesterday' => 0,
				'Two Days Ago' => 0,
				'Three Days Ago' => 0,
				'Four Days Ago' => 0,
				'Five Days Ago' => 0,
				'A Week Ago' => 0
			];

			foreach ($Cases as $case) {
				$i1 = time() - strtotime($case->timestamp);
				if ($i1 <= 86400) {
					$activityGraph['Today']++;
				} else if ($i1 <= 86400 * 2) {
					$activityGraph['Yesterday']++;
				} else if ($i1 <= 86400 * 3) {
					$activityGraph['Two Days Ago']++;
				} else if ($i1 <= 86400 * 4) {
					$activityGraph['Three Days Ago']++;
				} else if ($i1 <= 86400 * 5) {
					$activityGraph['Four Days Ago']++;
				} else if ($i1 <= 86400 * 6) {
					$activityGraph['Five Days Ago']++;
				} else if ($i1 <= 86400 * 7) {
					$activityGraph['A Week Ago']++;
				}
			}

			if ($r->notes == null)
				$r->notes = '';
			if ($r->steamid == null)
				$r->steamid = '';
			if ($r->region == null)
				$r->region = '';
			if ($r->discord_tag == null)
				$r->discord_tag = '';
			if ($r->rank_lvl == null)
				$r->rank_lvl = 100;
			if ($r->lastPromotion == null)
				$r->lastPromotion = 'CHANGE ME';

			$real_highest_rank_position = Permissions::getHighestRank(json_decode($r->rank_groups, true));

			$user_highest_rank_position = Permissions::getHighestRank(json_decode($user->info->rank_groups, true));

			$user_highest_rank_position = ($user_highest_rank_position == 10) ? 0 : $user_highest_rank_position;

			if (Permissions::init()->isOverlord())
				$user_highest_rank_position = 0;

			$stmt = $pdo->prepare("SELECT * FROM rank_groups WHERE position > :p ORDER BY position");
			$stmt->bindValue(':p', $user_highest_rank_position, PDO::PARAM_INT);
			$stmt->execute();
			$ranks_available = $stmt->fetchAll();

			$stmt = $pdo->prepare('SELECT * FROM rank_groups WHERE position = :i');
			$stmt->bindValue(':i', $real_highest_rank_position, PDO::PARAM_INT);
			$stmt->execute();
			$ri = $stmt->fetch();
			$rank = ($ri !== false) ? $ri : 'Unranked';

			$ranks = [];
			foreach (json_decode($r->rank_groups) as $rr) {
				$stmt = $pdo->prepare('SELECT * FROM rank_groups WHERE id = :i');
				$stmt->bindValue(':i', $rr, PDO::PARAM_INT);
				$stmt->execute();
				$ri = $stmt->fetch();
				$ranks[] = ($ri !== false) ? $ri : 'Unknown';
			}


			$staffinfo['id'] = $r->id;
			$staffinfo['name'] = $staffname;
			$staffinfo['display_name'] = $r->first_name . ' ' . $r->last_name;
			$staffinfo['primary_rank'] = $rank;
			$staffinfo['all_ranks'] = $ranks;
			if (Permissions::init()->hasPermission('VIEW_SLT')) {
				$staffinfo['notes'] = $r->notes;
			} else {
				$staffinfo['notes'] = "Permission `VIEW_SLT` required to view notes.";
			}
			$staffinfo['uid'] = $r->steamid;
			$staffinfo['lastPromotion'] = $r->lastPromotion;
			$staffinfo['rank_lvl'] = $r->rank_lvl;
			$staffinfo['team'] = $r->staff_team;
			$staffinfo['isSuspended'] = ($r->suspended) ? true : false;
			$staffinfo['region'] = $r->region;
			$staffinfo['discord_tag'] = $r->discord_tag;
			$staffinfo['activityGraph'] = (array) $activityGraph;
			$staffinfo['casecount'] = $allTimeCount;
			$staffinfo['casecount_week'] = $recentCount;
			$staffinfo['casecount_month'] = $Month;
			$staffinfo['ranks_available'] = $ranks_available;
			echo Helpers::APIResponse("Fetched User", $staffinfo, 200);
		} else {
			Helpers::addAuditLog("AUTHENTICATION_FAILED::{$_SERVER['REMOTE_ADDR']} Triggered An Unauthenticated Response In `StaffController::StaffDetails`");
			echo Helpers::APIResponse("Unauthorised", null, 401);
		}
	}
}
