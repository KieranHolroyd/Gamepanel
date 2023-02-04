<?php

namespace App\API\V2\Controller;

use \Meetings, \User, \Helpers, \Permissions;

class MeetingsController
{
	public function ListMeetings()
	{
		$user = new User;

		$meetings = Meetings::list($user);

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

	public function CreateMeeting()
	{
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

	public function GetMeeting($id)
	{
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
	public function AddPoint($id)
	{
		global $pdo;
		$user = new User;

		if (Permissions::init()->hasPermission("ADD_MEETING_POINT")) {
			$stmt = $pdo->prepare("INSERT INTO meeting_points (`name`, `description`, `author`, `meetingID`) VALUES (:pointname, :pointdescription, :author, :meetingID)");
			$stmt->bindValue(":pointname", htmlspecialchars($_POST['title']));
			$stmt->bindValue(":pointdescription", htmlspecialchars($_POST['description']));
			$stmt->bindValue(":author", $user->info->username);
			$stmt->bindValue(":meetingID", htmlspecialchars($id));
			if ($stmt->execute()) {
				$data = ['meetingID' => htmlspecialchars($id), 'id' => $pdo->lastInsertId(), 'name' => $_POST['title'], 'author' => $user->getInfoForFrontend()['displayName']];
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
			Helpers::addAuditLog("AUTHENTICATION_FAILED::{$_SERVER['REMOTE_ADDR']} Triggered An Unauthenticated Response In `AddPointNew`");
			echo Helpers::APIResponse("Authentication Failed", null, 401);
		}
	}
}
