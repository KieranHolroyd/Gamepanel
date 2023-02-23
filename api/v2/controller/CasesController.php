<?php

namespace App\API\V2\Controller;

use \User, \Permissions, \Helpers, \PDO;

class CasesController
{
    public function GetCases()
    {
        global $pdo;

        $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
        if ($offset <= 0)
            $offset = 0;

        if (Permissions::init()->hasPermission("VIEW_CASE")) {
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
            Helpers::addAuditLog("AUTHENTICATION_FAILED::{$_SERVER['REMOTE_ADDR']} Triggered An Unauthenticated Response In `CasesController::GetCases`");
        }
    }

    public function SubmitCase()
    {
        global $pdo;
        $user = new User;

        if (Permissions::init()->hasPermission("SUBMIT_CASE")) {
            $lead_staff = (isset($_POST['lead_staff'])) ? htmlspecialchars($_POST['lead_staff']) : null;
            $other_staff = (isset($_POST['other_staff'])) ? htmlspecialchars($_POST['other_staff']) : null;
            $description_of_events = (isset($_POST['description_of_events'])) ? htmlspecialchars($_POST['description_of_events']) : null;
            $players = (isset($_POST['players'])) ? $_POST['players'] : null;
            $punishment_reports = (isset($_POST['punishment_reports'])) ? $_POST['punishment_reports'] : null;
            $ban_reports = (isset($_POST['ban_reports'])) ? $_POST['ban_reports'] : null;
            $type_of_report = (isset($_POST['type_of_report'])) ? htmlspecialchars($_POST['type_of_report']) : null;
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
            $query->bindValue(':ls', $lead_staff, PDO::PARAM_STR);
            $query->bindValue(':os', $other_staff, PDO::PARAM_STR);
            $query->bindValue(':doe', $description_of_events, PDO::PARAM_STR);
            $query->bindValue(':torep', $type_of_report, PDO::PARAM_STR);
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

            $punishment_issued = false;
            $ban_issued = false;

            foreach ($punishment_reports as $punishment) {
                $stmt = $pdo->prepare('UPDATE punishment_reports SET case_id = :cid WHERE id = :id');
                $stmt->bindValue(':cid', $caseid, PDO::PARAM_INT);
                $stmt->bindValue(':id', $punishment, PDO::PARAM_INT);
                if (!$stmt->execute()) {
                    $punishment_issued = true;
                    Helpers::addAuditLog("CRITICAL_ERROR::Failed To Update Punishment Report (ID {$punishment}) " . json_encode($stmt->errorinfo()));
                }
                Helpers::addAuditLog("LOG::PUNISHMENT_REPORT::{$caseid}--{$punishment}");
            }

            foreach ($ban_reports as $ban) {
                $stmt = $pdo->prepare('UPDATE ban_reports SET case_id = :cid WHERE id = :id');
                $stmt->bindValue(':cid', $caseid, PDO::PARAM_INT);
                $stmt->bindValue(':id', $ban, PDO::PARAM_INT);
                if (!$stmt->execute()) {
                    $ban_issued = true;
                    Helpers::addAuditLog("CRITICAL_ERROR::Failed To Update Ban Report (ID {$ban}) " . json_encode($stmt->errorinfo()));
                }
                Helpers::addAuditLog("LOG::BAN_REPORT::{$caseid}--{$ban}");
            }


            if (count($playersArray) == 0)
                Helpers::fixPlayersForCase($caseid, null);


            $stmt = $pdo->prepare('SELECT * FROM case_logs WHERE id = :id');
            $stmt->bindValue(':id', $caseid, PDO::PARAM_STR);
            $stmt->execute();
            $row = $stmt->fetch();

            $playersInvolved = Helpers::getPlayersFromCase($caseid);

            if (trim($other_staff) != '') {
                foreach (explode(' ', $other_staff) as $staff_name) {
                    $staff_member = new User(Helpers::UsernameToID($staff_name));
                    $staff_member->pushNotification('You Supported A Case', "Click to view Case #{$caseid}-{$playersInvolved[0]->name}", "/me#case:{$caseid}");
                }
            }

            $data = [
                "id" => $row->id,
                "lead_staff" => $row->lead_staff,
                "typeofreport" => $row->type_of_report,
                "ltpr" => @$row->link_to_player_report,
                "pa" => $punishment_issued,
                "ba" => $ban_issued,
                "timestamp" => $row->timestamp,
                "reporting_player" => $playersInvolved
            ];
            Helpers::addAuditLog("{$user->info->username} Submitted A Case");
            Helpers::PusherSend($data, 'caseInformation', 'receive');
            $user->pushNotification('You Submitted A Case', "Click to view Case #{$caseid}-{$playersInvolved[0]->name}", "/me#case:{$caseid}");
        } else {
            Helpers::addAuditLog("{$user->info->name} Tried to use `CasesController::SubmitCase` without permission.");
            echo "Insufficient Permissions";
        }
    }

    public function CaseInfo($id)
    {
        global $pdo;

        if (Permissions::init()->hasPermission("VIEW_GENERAL")) {
            $initial_query = $pdo->prepare("SELECT * FROM case_logs WHERE id = :id");
            $initial_query->bindValue(':id', $id, PDO::PARAM_STR);
            $initial_query->execute();
            $r = $initial_query->fetch();
            $report = [];
            $stmt = $pdo->prepare("SELECT * FROM case_players WHERE case_id = :id");
            $stmt->bindValue(':id', $r->id, PDO::PARAM_INT);
            $stmt->execute();
            $players = $stmt->fetchAll();
            $stmt = $pdo->prepare("SELECT * FROM punishment_reports WHERE case_id = :id");
            $stmt->bindValue(':id', $r->id, PDO::PARAM_INT);
            $stmt->execute();
            $punishments = $stmt->fetchAll();
            $stmt = $pdo->prepare("SELECT * FROM ban_reports WHERE case_id = :id");
            $stmt->bindValue(':id', $r->id, PDO::PARAM_INT);
            $stmt->execute();
            $bans = $stmt->fetchAll();

            foreach ($punishments as $p) {
                $p->html = Helpers::parsePunishment($p);
            }

            foreach ($bans as $b) {
                $b->html = Helpers::parseBan($b);
            }

            $report['report']['id'] = $r->id;
            $report['report']['lead_staff'] = Helpers::ParseOtherStaff($r->lead_staff);
            $report['report']['other_staff'] = Helpers::ParseOtherStaff($r->other_staff);
            $report['report']['typeofreport'] = htmlspecialchars($r->type_of_report);
            $report['report']['players'] = $players;
            $report['report']['punishments'] = $punishments;
            $report['report']['bans'] = $bans;
            $report['report']['doe'] = htmlspecialchars($r->description_of_events);
            $report['report']['timestamp'] = htmlspecialchars($r->timestamp);
            echo Helpers::APIResponse("Fetched More Info", $report, 200);
        } else {
            Helpers::addAuditLog("AUTHENTICATION_FAILED::{$_SERVER['REMOTE_ADDR']} Triggered An Unauthenticated Response In `CasesController::CaseInfo`");
            echo Helpers::APIResponse("Failed: Unauthorised", null, 401);
        }
    }
}
