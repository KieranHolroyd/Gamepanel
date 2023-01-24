<?php

namespace App\API\V2\Controller;

use \User;
use \Permissions;
use \Helpers;
use PDO;

class GuideController
{
	public function addGuide()
	{
		global $pdo;
		$li = new User;

		if (Permissions::init()->hasPermission("GUIDE_ADD")) {
			$title = $_POST['title'];
			$body = $_POST['body'];
			$user = $li->info->first_name . " " . $li->info->last_name;
			$sql = "INSERT INTO guides (`title`, `body`, `author`) VALUES (:title, :body, :author)";
			$query = $pdo->prepare($sql);
			$query->bindValue(':title', $title, PDO::PARAM_STR);
			$query->bindValue(':body', $body, PDO::PARAM_STR);
			$query->bindValue(':author', $user, PDO::PARAM_STR);
			$query->execute();
			Helpers::addAuditLog("{$li->info->username} Added Guide {$title}");
			echo "Guide Added Successfully.";
		} else {
			Helpers::addAuditLog("AUTHENTICATION_FAILED::{$_SERVER['REMOTE_ADDR']} Triggered An Unauthenticated Response In `AddGuide`");
			echo "Insufficient Permissions.";
		}
	}

	public function editGuide()
	{
		global $pdo;
		$li = new User;

		if (Permissions::init()->hasPermission("GUIDE_EDIT")) {
			$id = $_POST['id'];
			$title = $_POST['title'];
			$body = $_POST['body'];
			$sql = "UPDATE guides SET title = :title, body = :body, timestamp = CURRENT_TIMESTAMP() WHERE id=:id";
			$query = $pdo->prepare($sql);
			$query->bindValue(':title', $title, PDO::PARAM_STR);
			$query->bindValue(':body', $body, PDO::PARAM_STR);
			$query->bindValue(':id', $id, PDO::PARAM_STR);
			$query->execute();
			Helpers::addAuditLog("{$li->info->username} Edited Guide `{$title}`");
			echo "Guide Edited Successfully.";
		} else {
			Helpers::addAuditLog("AUTHENTICATION_FAILED::{$_SERVER['REMOTE_ADDR']} Triggered An Unauthenticated Response In `EditGuide`");
			echo "Insufficient Permissions.";
		}
	}

	public function deleteGuide() //New Function (Seemed to be missing)
	{
		global $pdo;
		$li = new User;

		if (Permissions::init()->hasPermission("GUIDE_DELETE")) {
			$id = $_POST['id'];
			$sql = "DELETE FROM guides WHERE id=:id";
			$query = $pdo->prepare($sql);
			$query->bindValue(':id', $id, PDO::PARAM_STR);
			$query->execute();
			Helpers::addAuditLog("{$li->info->username} Deleted Guide `{$id}`");
			echo "Guide Deleted Successfully.";
		} else {
			Helpers::addAuditLog("AUTHENTICATION_FAILED::{$_SERVER['REMOTE_ADDR']} Triggered An Unauthenticated Response In `DeleteGuide`");
			echo "Insufficient Permissions.";
		}
	}

	public function getFullGuide()
	{
		global $pdo;

		if (Permissions::init()->hasPermission("VIEW_GENERAL")) {
			$id = $_POST['id'];
			$query = $pdo->prepare("SELECT * FROM guides WHERE id = :id");
			$query->bindValue(':id', $id, PDO::PARAM_STR);
			$query->execute();
			$r = $query->fetch();

			echo json_encode([
				'title' => htmlspecialchars($r->title),
				'body' => $r->body,
				'author' => $r->author,
				'time' => $r->timestamp,
				'effective' => $r->effective
			]);
		} else {
			Helpers::addAuditLog("AUTHENTICATION_FAILED::{$_SERVER['REMOTE_ADDR']} Triggered An Unauthenticated Response In `GetGuide`");
		}
	}

	public function getGuides()
	{
		global $pdo;
		$guides = [];
		foreach ($pdo->query('SELECT * FROM guides ORDER BY title') as $r) {
			array_push($guides, [
				'id' => $r->id,
				'title' => htmlspecialchars($r->title),
				'author' => $r->author,
				'body' => $r->body,
				'time' => $r->timestamp,
				'effective' => $r->effective
			]);
		}
		echo json_encode($guides);
	}
}
