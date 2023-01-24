<?php
namespace App\API\V2\Controller;

use \User,\Permissions,\Helpers,\PDO;

class CasesController
{ 
	public function getCases()
	{
		global $pdo;

        if (Permissions::init()->hasPermission("VIEW_CASE")) {
            $offset = intval($_POST['offset']);
            if ($offset <= 0) {
                $offset = 0;
            }
            $sql = "SELECT * FROM case_logs ORDER BY id DESC LIMIT 100 OFFSET :offset";
            $query = $pdo->prepare($sql);
            $query->bindValue(':offset', $offset, PDO::PARAM_INT);
            $query->execute();
            $rows = $query->fetchAll();
            $row_count = count($rows);
            $reports = [];
            $reports['info']['count'] = $row_count;
            $reports['info']['offset'] = $offset;
            $i = 0;
            foreach ($rows as $row) {
                $reports['caseno'][$i]['id'] = $row->id;
                $reports['caseno'][$i]['lead_staff'] = $row->lead_staff;
                $reports['caseno'][$i]['typeofreport'] = $row->type_of_report;
                $reports['caseno'][$i]['pa'] = Helpers::checkCaseHasPunishment($row->id);
                $reports['caseno'][$i]['ba'] = Helpers::checkCaseHasBan($row->id);
                $reports['caseno'][$i]['timestamp'] = $row->timestamp;
                $reports['caseno'][$i]['reporting_player'] = Helpers::getPlayersFromCase($row->id);
                $i++;
            }
            echo json_encode($reports);
        } else {
            Helpers::addAuditLog("AUTHENTICATION_FAILED::{$_SERVER['REMOTE_ADDR']} Triggered An Unauthenticated Response In `GetCases`");
        }
	}

	public function submitCase()
	{
		global $pdo;
		$user = new User;

        if (Permissions::init()->hasPermission("SUBMIT_CASE")) {
            $ls = (isset($_POST['lead_staff'])) ? htmlspecialchars($_POST['lead_staff']) : null;
            $os = (isset($_POST['other_staff'])) ? htmlspecialchars($_POST['other_staff']) : null;
            $doe = (isset($_POST['description_of_events'])) ? htmlspecialchars($_POST['description_of_events']) : null;
            $players = (isset($_POST['players'])) ? $_POST['players'] : null;
            $punishment_reports = (isset($_POST['punishment_reports'])) ? $_POST['punishment_reports'] : null;
            $ban_reports = (isset($_POST['ban_reports'])) ? $_POST['ban_reports'] : null;
            $torep = (isset($_POST['type_of_report'])) ? htmlspecialchars($_POST['type_of_report']) : null;
            $playersArray = [];
            $i = 0;
            if ($players) {
                $decoded_players = json_decode($players);
                foreach ($decoded_players as $player) {
                    $i++;
                    $playersArray[$i]['type'] = $player->type;
                    $playersArray[$i]['name'] = $player->name;
                    $playersArray[$i]['guid'] = $player->guid;
                }
            } else {
                Helpers::addAuditLog('No Players Found... Exiting');
                exit;
            }

            if ($punishment_reports) {
                $punishment_reports = json_decode($punishment_reports);
            } else {
                $punishment_reports = [];
            }
            if ($ban_reports) {
                $ban_reports = json_decode($ban_reports);
            } else {
                $ban_reports = [];
            }
            $sql = "INSERT INTO case_logs (`lead_staff`, `other_staff`, `description_of_events`, `type_of_report`) VALUES (:ls, :os, :doe, :torep)";
            $query = $pdo->prepare($sql);
            $query->bindValue(':ls', $ls, PDO::PARAM_STR);
            $query->bindValue(':os', $os, PDO::PARAM_STR);
            $query->bindValue(':doe', $doe, PDO::PARAM_STR);
            $query->bindValue(':torep', $torep, PDO::PARAM_STR);
            $query->execute();
            print_r($query->errorinfo());

            $caseid = $pdo->lastInsertId();

            foreach ($playersArray as $player) {
                $stmt = $pdo->prepare("INSERT INTO case_players (case_id, type, name, guid) VALUES (:id, :type, :nm, :guid)");
                $stmt->bindValue(":id", $caseid);
                $stmt->bindValue(":type", $player['type']);
                $stmt->bindValue(":nm", $player['name']);
                $stmt->bindValue(":guid", $player['guid']);
                if (!$stmt->execute()) {
                    Helpers::addAuditLog("CRITICAL_ERROR::Failed To Add Player To Report " . json_encode($stmt->errorinfo()));
                    Helpers::fixPlayersForCase($caseid, $stmt->errorInfo());
                }
            }

            $pa = false;
            $ba = false;

            foreach ($punishment_reports as $p) {
                $stmt = $pdo->prepare('UPDATE punishment_reports SET case_id = :cid WHERE id = :id');
                $stmt->bindValue(':cid', $caseid, PDO::PARAM_INT);
                $stmt->bindValue(':id', $p, PDO::PARAM_INT);
                if (!$stmt->execute()) {
                    $pa = true;
                    Helpers::addAuditLog("CRITICAL_ERROR::Failed To Update Punishment Report (ID {$p}) " . json_encode($stmt->errorinfo()));
                }
                Helpers::addAuditLog("LOG::PUNISHMENT_REPORT::{$caseid}--{$p}");
            }

            foreach ($ban_reports as $p) {
                $stmt = $pdo->prepare('UPDATE ban_reports SET case_id = :cid WHERE id = :id');
                $stmt->bindValue(':cid', $caseid, PDO::PARAM_INT);
                $stmt->bindValue(':id', $p, PDO::PARAM_INT);
                if (!$stmt->execute()) {
                    $ba = true;
                    Helpers::addAuditLog("CRITICAL_ERROR::Failed To Update Ban Report (ID {$p}) " . json_encode($stmt->errorinfo()));
                }
                Helpers::addAuditLog("LOG::BAN_REPORT::{$caseid}--{$p}");
            }


            if (count($playersArray) == 0)
                Helpers::fixPlayersForCase($caseid, null);


            $stmt = $pdo->prepare('SELECT * FROM case_logs WHERE id = :id');
            $stmt->bindValue(':id', $caseid, PDO::PARAM_STR);
            $stmt->execute();
            $row = $stmt->fetch();

            $playersInvolved = Helpers::getPlayersFromCase($caseid);

            if (trim($os) != '') {
                foreach (explode(' ', $os) as $s) {
                    $sendNotificationToUser = new User(Helpers::UsernameToID($s));
                    $sendNotificationToUser->pushNotification('You Supported A Case', "Click to view Case #{$caseid}-{$playersInvolved[0]->name}", "/me#case:{$caseid}");
                }
            }

            $data = [];
            $data['id'] .= $row->id;
            $data['lead_staff'] .= $row->lead_staff;
            $data['typeofreport'] .= $row->type_of_report;
            $data['ltpr'] .= $row->link_to_player_report;
            $data['pa'] .= $pa;
            $data['ba'] .= $ba;
            $data['timestamp'] .= $row->timestamp;
            $data['reporting_player'] = $playersInvolved;
            Helpers::addAuditLog("{$user->info->username} Submitted A Case");
            Helpers::PusherSend($data, 'caseInformation', 'receive');
            $user->pushNotification('You Submitted A Case', "Click to view Case #{$caseid}-{$playersInvolved[0]->name}", "/me#case:{$caseid}");
        } else {
            Helpers::addAuditLog("AUTHENTICATION_FAILED::{$_SERVER['REMOTE_ADDR']} Triggered An Unauthenticated Response In `SubmitCase`");
            echo "Insufficient Permissions";
        }
	}
}