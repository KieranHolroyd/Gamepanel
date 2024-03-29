<?php

namespace App\API\V2\Controller;

use Headers;
use \User, \Permissions, \PDO, \Config, \Helpers, \Cache, \Statistics;

class StatisticsController {
	public function CaseStatistics() {
		global $pdo, $cache;
		$in_cache = $cache->get('cases:stats:all');

		if ($in_cache) {
			echo Helpers::NewAPIResponse(["success" => true, "stats" => json_decode($in_cache)]);
			return;
		} else {
			$stats = ["daily" => [], "weekly" => [], "monthly" => []];
			foreach ($pdo->query('SELECT * FROM case_logs WHERE `timestamp` >= DATE_SUB(NOW(), INTERVAL 6 MONTH)') as $r) {
				$time_since_created = time() - strtotime($r->timestamp);

				Statistics::_sort_to_bin(/* reference */$stats, $time_since_created, /* day   */ 60 * 60 * 24,      8, 'daily');
				Statistics::_sort_to_bin(/* reference */$stats, $time_since_created, /* week  */ 60 * 60 * 24 * 7,  5, 'weekly');
				Statistics::_sort_to_bin(/* reference */$stats, $time_since_created, /* month */ 60 * 60 * 24 * 30, 7, 'monthly');
			}
			$cache->set('cases:stats:all', json_encode($stats), 60 * 60);
			echo Helpers::NewAPIResponse(["success" => true, "stats" => $stats]);
		}
	}

	public function DailyCases() {
		global $pdo, $cache;
		$in_cache = $cache->get('cases:stats:daily');

		if ($in_cache) {
			echo $in_cache;
			return;
		} else {
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
			$daily_stats = json_encode(['today' => $today, 'yesterday' => $yesterday, 'twodays' => $twodays, 'threedays' => $threedays, 'fourdays' => $fourdays]);
			$cache->set('cases:stats:daily', $daily_stats, 60 * 60);
			echo $daily_stats;
		}
	}

	public function WeeklyCases() {
		global $pdo, $cache;
		$in_cache = $cache->get('cases:stats:weekly');

		if ($in_cache) {
			echo $in_cache;
			return;
		} else {
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
			$weekly_cases = json_encode(['thisweek' => $thisweek, 'lastweek' => $lastweek, 'twoweeks' => $twoweeks, 'threeweeks' => $threeweeks, 'onemonth' => $onemonth]);
			$cache->set('cases:stats:weekly', $weekly_cases, 60 * 60 * 3);
			echo $weekly_cases;
		}
	}

	public function ServerStatistics() {
		global $cache;
		if (!Config::$enableGamePanel) {
			echo Helpers::APIResponse("Error", "Game Panel is disabled.", 403);
			return;
		}

		$user = new User;
		if (Permissions::init()->hasPermission("VIEW_GENERAL")) {
			$in_cache = $cache->get('server:stats:general');

			if ($in_cache) {
				echo Helpers::APIResponse("Success", json_decode($in_cache), 200);
				return;
			} else {
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
				$stmt = $gamepdo->prepare('SELECT `bankacc`, `name`, `uid`, `playerid`, `last_seen` from `players` ORDER BY bankacc DESC LIMIT 5');
				$stmt->execute();
				$richList = $stmt->fetchAll(PDO::FETCH_OBJ);

				foreach ($richList as $user) {
					$user->bankacc = number_format($user->bankacc, 0);
				}

				$server_data = ['serverBalance' => ['read' => $serverBalance->total, 'formatted' => '$' . number_format($serverBalance->total)], 'players' => ['total' => $players->total, 'total_cops' => $cops->total, 'total_medics' => $medics->total, 'rich_list' => $richList]];
				$cache->set('server:stats:general', json_encode($server_data), 60 * 60);
				echo Helpers::APIResponse("Success", $server_data, 200);
			}
		} else {
			echo Helpers::APIResponse("Unauthorised", null, 401);
		}
	}
}
