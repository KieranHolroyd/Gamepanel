<?php

namespace App\API\V2\Controller;

use \User, \Permissions, \PDO, \Config, \Helpers;

class StatisticsController
{
	public function DailyCases()
	{
		global $pdo;
		$today = 0;
		$yesterday = 0;
		$twodays = 0;
		$threedays = 0;
		$fourdays = 0;
		foreach ($pdo->query('SELECT * FROM case_logs WHERE `timestamp` >= DATE_SUB(NOW(), INTERVAL 5 DAY)') as $r) {
			$timeinseconds = strtotime($r->timestamp) - time();
			if ($timeinseconds > -86400) {
				$today++;
			}
			if ($timeinseconds > -172800 && $timeinseconds < -86400) {
				$yesterday++;
			}
			if ($timeinseconds > -259200 && $timeinseconds < -172800) {
				$twodays++;
			}
			if ($timeinseconds > -345600 && $timeinseconds < -259200) {
				$threedays++;
			}
			if ($timeinseconds > -432000 && $timeinseconds < -345600) {
				$fourdays++;
			}
		}
		echo json_encode(['today' => $today, 'yesterday' => $yesterday, 'twodays' => $twodays, 'threedays' => $threedays, 'fourdays' => $fourdays]);
	}

	public function WeeklyCases()
	{
		global $pdo;

		$thisweek = 0;
		$lastweek = 0;
		$twoweeks = 0;
		$threeweeks = 0;
		$onemonth = 0;
		foreach ($pdo->query('SELECT * FROM case_logs WHERE `timestamp` >= DATE_SUB(NOW(), INTERVAL 5 WEEK)') as $r) {
			$timeinseconds = strtotime($r->timestamp) - time();
			if ($timeinseconds > -604800) {
				$thisweek++;
			}
			if ($timeinseconds > -1209600 && $timeinseconds < -604800) {
				$lastweek++;
			}
			if ($timeinseconds > -1814400 && $timeinseconds < -1209600) {
				$twoweeks++;
			}
			if ($timeinseconds > -2419200 && $timeinseconds < -1814400) {
				$threeweeks++;
			}
			if ($timeinseconds > -3024000 && $timeinseconds < -2419200) {
				$onemonth++;
			}
		}
		echo json_encode(['thisweek' => $thisweek, 'lastweek' => $lastweek, 'twoweeks' => $twoweeks, 'threeweeks' => $threeweeks, 'onemonth' => $onemonth]);
	}

	public function ServerStatistics()
	{
		$user = new User;

		if (Permissions::init()->hasPermission("VIEW_GENERAL") && Config::$enableGamePanel) {
			$gamepdo = game_pdo();

			$stmt = $gamepdo->prepare('SELECT COUNT(*) AS total from `players`');
			$stmt->execute();
			$players = $stmt->fetch(PDO::FETCH_OBJ);
			$stmt = $gamepdo->prepare('SELECT COUNT(*) AS total from `players` WHERE coplevel <> "0"');
			$stmt->execute();
			$cops = $stmt->fetch(PDO::FETCH_OBJ);
			$stmt = $gamepdo->prepare('SELECT COUNT(*) AS total from `players` WHERE mediclevel <> "0"');
			$stmt->execute();
			$medics = $stmt->fetch(PDO::FETCH_OBJ);
			$stmt = $gamepdo->prepare('SELECT SUM(bankacc) AS total from `players`');
			$stmt->execute();
			$serverBalance = $stmt->fetch(PDO::FETCH_OBJ);
			$stmt = $gamepdo->prepare('SELECT `bankacc`, `aliases`, `name`, `uid`, `pid`, `last_seen` from `players` ORDER BY bankacc DESC LIMIT 10');
			$stmt->execute();
			$richList = $stmt->fetchAll(PDO::FETCH_OBJ);
			foreach ($richList as $user) {
				$user->bankacc = "$" . number_format($user->bankacc, 0);
			}

			echo Helpers::APIResponse("Success", ['serverBalance' => ['read' => $serverBalance->total, 'formatted' => '$' . number_format($serverBalance->total)], 'players' => ['total' => $players->total, 'total_cops' => $cops->total, 'total_medics' => $medics->total, 'rich_list' => $richList]], 200);
		} else {
			echo Helpers::APIResponse("Unauthorised", null, 401);
		}
	}
}
