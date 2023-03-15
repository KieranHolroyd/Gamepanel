<?php

namespace App\API\V2\Controller;

use \Meetings, \User, \Helpers, \Permissions, \PDO;

class MeetingsController {
	public function ListMeetings() {
		global $pdo;
		$user = new User;

		$wheres = "";

		if ($user->isStaff()) {
			$wheres .= "staff = 1 ";
		}
		$or = ($wheres != "") ? 'OR' : '';
		if ($user->isPD()) {
			$wheres .= "{$or} pd = 1 ";
		}
		$or = ($wheres != "") ? 'OR' : '';
		if ($user->isEMS()) {
			$wheres .= "{$or} ems = 1 ";
		}
		$or = ($wheres != "") ? 'OR' : '';
		if ($user->isSLT()) {
			$wheres .= "{$or} slt = 1";
		}

		$stmt = $pdo->prepare("SELECT * FROM meetings WHERE {$wheres} ORDER BY date DESC");

		if (!$stmt->execute()) {
			echo Helpers::NewAPIResponse(["message" => "Database Call Failed", "success" => false, "error" => $stmt->errorInfo()]);
		}

		$meetings = $stmt->fetchAll();

		if (!$meetings) {
			echo Helpers::NewAPIResponse(["message" => "No meetings found.", "success" => false]);
		}

		foreach ($meetings as $meeting) {
			$stmt = $pdo->prepare('SELECT COUNT(*) AS count FROM meeting_points WHERE meetingID = :id');
			$stmt->bindValue(':id', $meeting->id, PDO::PARAM_STR);
			$stmt->execute();
			$point = $stmt->fetch();
			$meeting->points = (int)$point->count;
			if ($meeting->staff) $meeting->type = 'Staff ';
			if ($meeting->pd) $meeting->type = 'Police ';
			if ($meeting->ems) $meeting->type = 'EMS ';
			if ($meeting->slt) $meeting->type = 'SLT ';
		}

		if (!$meetings) {
			echo Helpers::NewAPIResponse(["message" => "No meetings found.", "success" => false]);
			exit;
		}

		if (!Permissions::init()->hasPermission("VIEW_MEETING")) {
			echo Helpers::NewAPIResponse(["message" => "You do not have permission to view meetings.", "success" => false]);
			exit;
		}

		echo Helpers::NewAPIResponse(["success" => true, "meetings" => $meetings]);
	}

	public function CreateMeeting() {
		global $pdo;
		$user = new User;

		$date = (isset($_POST['date'])) ? $_POST['date'] : false;
		$type = (isset($_POST['type'])) ? $_POST['type'] : false;

		if (Permissions::init()->hasPermission("ADD_MEETING")) {
			if ($date && $type) {
				$types = [
					"slt" => 0,
					"staff" => 0,
					"pd" => 0,
					"ems" => 0
				];

				$types[$type] = 1;

				$stmt = $pdo->prepare("INSERT INTO meetings (`date`, `slt`, `staff`, `pd`, `ems`) VALUES (:dte, :slt, :staff, :pd, :ems)");
				$stmt->bindValue(':dte', $date);
				$stmt->bindValue(':slt', $types['slt']);
				$stmt->bindValue(':ems', $types['ems']);
				$stmt->bindValue(':pd', $types['pd']);
				$stmt->bindValue(':staff', $types['staff']);
				if ($stmt->execute()) {
					Helpers::addAuditLog("MEETINGS::{$user->info->username} Scheduled A Meeting On {$date} [Type: {$type}]");
					echo Helpers::NewAPIResponse(["message" => "Meeting Added Successfully", "success" => true]);
				} else {
					echo Helpers::NewAPIResponse(["message" => "Failed To Add Meeting", "success" => false, "error" => $stmt->errorInfo()]);
				}
			} else {
				echo Helpers::NewAPIResponse(["message" => "Invalid Parameters", "success" => false]);
			}
		} else {
			Helpers::addAuditLog("AUTHENTICATION_FAILED::{$_SERVER['REMOTE_ADDR']} Triggered An Unauthenticated Response In `MeetingsController::CreateMeeting`");
			echo Helpers::NewAPIResponse(["message" => "You do not have permission to add meetings.", "success" => false]);
		}
	}

	public function GetMeeting($id) {
		global $pdo;
		$user = new User;
		if (!isset($id)) {
			echo Helpers::NewAPIResponse(["message" => "Invalid Parameters", "success" => false]);
			exit;
		}
		if (Permissions::init()->hasPermission("VIEW_MEETING")) {
			$arr = [];
			$stmt = $pdo->prepare("SELECT * FROM meeting_points WHERE meetingID=:id ORDER BY id DESC");
			$stmt->bindValue(":id", $id);
			$stmt->execute();
			$points = $stmt->fetchAll();
			foreach ($points as $point) {
				$temp = [];
				$temp['id'] = $point->id;
				$temp['name'] = $point->name;
				$pointAuthor = new User(Helpers::UsernameToID($point->author));
				$temp['author'] = $pointAuthor->getInfoForFrontend()['displayName'];
				$temp['canDelete'] = ($pointAuthor->info->id == $user->info->id || $user->isSLT());
				$arr[] = $temp;
			}
			echo Helpers::NewAPIResponse(["success" => true, "points" => $arr]);
		} else {
			echo Helpers::NewAPIResponse(["message" => "You do not have permission to view meetings.", "success" => false]);
		}
	}

	// TODO: rename point.
	public function AddPoint($id) {
		global $pdo;
		$user = new User;

		if (Permissions::init()->hasPermission("ADD_MEETING_POINT")) {
			$stmt = $pdo->prepare("INSERT INTO meeting_points (`name`, `description`, `author`, `meetingID`, `comments`) VALUES (:pointname, :pointdescription, :author, :meetingID, '[]')");
			$stmt->bindValue(":pointname", htmlspecialchars($_POST['title']));
			$stmt->bindValue(":pointdescription", htmlspecialchars($_POST['description']));
			$stmt->bindValue(":author", $user->info->username);
			$stmt->bindValue(":meetingID", htmlspecialchars($id));
			if ($stmt->execute()) {
				$lastID = $pdo->lastInsertId();
				if (!$lastID) {
					echo Helpers::APIResponse("Failed To Get Last Insert ID", $lastID, 500);
					exit;
				}
				$data = ['meetingID' => htmlspecialchars($id), 'id' => $lastID, 'name' => $_POST['title'], 'author' => $user->getInfoForFrontend()['displayName']];
				if (Helpers::PusherSend($data, "meetings", "addPoint")) {
					Helpers::addAuditLog("{$user->info->username} Added A New Point `{$_POST['title']}` To Meeting {$id}");
					echo Helpers::APIResponse("Added Point.", null, 200);
				} else {
					echo Helpers::APIResponse("Failed To Publish To Pusher", null, 500);
				}
			} else {
				echo Helpers::APIResponse("Failed To Add Point", $stmt->errorinfo(), 500);
			}
		} else {
			Helpers::addAuditLog("AUTHENTICATION_FAILED::{$_SERVER['REMOTE_ADDR']} Triggered An Unauthenticated Response In `MeetingsController::AddPoint`");
			echo Helpers::APIResponse("Authentication Failed", null, 401);
		}
	}

	public function AddPointComment($meetingid, $pointid) {
		global $pdo;
		$user = new User;

		if (Permissions::init()->hasPermission("ADD_MEETING_COMMENT")) {
			$stmt = $pdo->prepare("INSERT INTO meeting_comments (`content`, `author`, `pointID`) VALUES (:content, :author, :id)");
			$stmt->bindValue(":id", $pointid);
			$stmt->bindValue(":content", htmlspecialchars($_POST['content']));
			$stmt->bindValue(":author", $user->info->username);
			if ($stmt->execute()) {
				$lastID = $pdo->lastInsertId();
				if (!$lastID) {
					echo Helpers::APIResponse("Failed To Get Last Insert ID", $lastID, 500);
					exit;
				}
				$data = ['canDelete' => 1, 'content' => htmlspecialchars($_POST['content']), 'author' => $user->getInfoForFrontend(), 'id' => $lastID, 'pointID' => htmlspecialchars($pointid)];
				if (Helpers::PusherSend($data, 'meetings', 'addComment')) {
					Helpers::addAuditLog("{$user->info->username} Added Comment To Meeting Point {$pointid}");
					echo Helpers::APIResponse("Success", [$data, $stmt->errorInfo()], 200);
				} else {
					echo Helpers::APIResponse("Failed To Publish To Websocket", null, 500);
				}
			} else {
				echo Helpers::APIResponse("Database Error", $stmt->errorInfo(), 500);
			}
		} else {
			Helpers::addAuditLog("AUTHENTICATION_FAILED::{$_SERVER['REMOTE_ADDR']} Triggered An Unauthenticated Response In `MeetingController::AddPointComment`");
			echo Helpers::APIResponse("Authentication Failed", null, 401);
		}
	}

	public function DeletePoint($meetingid, $pointid) {
		global $pdo;
		$user = new User;

		$stmt = $pdo->prepare("SELECT author FROM meeting_points WHERE id = :id");
		$stmt->bindValue(":id", $pointid);
		$stmt->execute();
		$pointAuthor = $stmt->fetch();

		if (Permissions::init()->hasPermission("REMOVE_MEETING_POINT") || $pointAuthor->author == $user->info->username) {
			$stmt = $pdo->prepare("DELETE FROM meeting_points WHERE id = :id");
			$stmt->bindValue(':id', $pointid);
			if ($stmt->execute()) {
				$stmt = $pdo->prepare("DELETE FROM meeting_comments WHERE pointID = :id");
				$stmt->bindValue(':id', $pointid);
				if ($stmt->execute()) {
					$data = ['deleteID' => htmlspecialchars($pointid)];
					if (Helpers::PusherSend($data, 'meetings', 'deletePoint')) {
						echo Helpers::APIResponse("Success", null, 200);
					} else {
						echo Helpers::APIResponse("Failed To Publish To Pusher", null, 500);
					}
				} else {
					echo Helpers::APIResponse("Database Error [Failed To Delete Comments]", $stmt->errorInfo(), 500);
				}
			} else {
				echo Helpers::APIResponse("Database Error [Failed To Delete Point]", $stmt->errorInfo(), 500);
			}
		} else {
			Helpers::addAuditLog("AUTHENTICATION_FAILED::{$_SERVER['REMOTE_ADDR']} Triggered An Unauthenticated Response In `MeetingsController::RemovePoint`");
			echo Helpers::APIResponse("Authentication Failed", null, 401);
		}
	}

	public function GetPoint($meetingid, $pointid) {
		global $pdo;

		if (Permissions::init()->hasPermission("VIEW_MEETING")) {
			$stmt = $pdo->prepare("SELECT * FROM meeting_points WHERE id=:id");
			$stmt->bindValue(":id", $pointid);
			if (!$stmt->execute()) {
				echo Helpers::APIResponse("Database Error", null, 500);
				exit;
			}
			$point = $stmt->fetch(PDO::FETCH_ASSOC);
			$pointAuthor = new User(Helpers::UsernameToID($point['author']));
			$point['author'] = $pointAuthor->getInfoForFrontend()['displayName'];
			if ($point) {
				$stmt = $pdo->prepare("SELECT * FROM meeting_comments WHERE pointID = :id ORDER BY id DESC");
				$stmt->bindValue(":id", $pointid);
				if (!$stmt->execute()) {
					echo Helpers::APIResponse("Database Error", null, 500);
					exit;
				}
				$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
				foreach ($comments as $k => $c) {
					$commentAuthor = new User(Helpers::UsernameToID($c['author']));
					$comments[$k]['author'] = $commentAuthor->getInfoForFrontend();
				}
				$point['comments'] = $comments;
				echo Helpers::APIResponse("Fetched Point", $point, 200);
			} else {
				echo Helpers::APIResponse("No Point Found", null, 400);
			}
		} else {
			echo Helpers::APIResponse("Authentication Failed", null, 401);
		}
	}
}
