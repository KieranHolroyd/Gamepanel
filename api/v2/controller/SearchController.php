<?php

namespace App\API\V2\Controller;

use \Permissions, \Helpers, \PDO;
use User;

class SearchController
{
    public function players()
    {
        if (Permissions::init()->hasPermission("VIEW_GAME_PLAYER")) {
            $q = (isset($_GET['q'])) ? $_GET['q'] : '';
            $filters = (isset($_GET['filters'])) ? json_decode($_GET['filters']) : false;

            $sqlFilters = "AND (";
            $filterCount = 0;
            $filterConnections = [
                'onlyPolice' => '`natoRank` <> \'0\'',
                'onlyMedics' => '`mediclevel` <> \'0\'',
                'onlyAdmins' => '`adminlevel` <> \'0\''
            ];

            if ($filters) {
                foreach ($filters as $key => $filter) {
                    if ($filter) {
                        if ($filterCount > 0)
                            $sqlFilters .= "OR ";
                        $sqlFilters .= "{$filterConnections[$key]} ";
                        $filterCount++;
                    }
                }
            }

            if ($filterCount == 0) {
                $sqlFilters = "";
            } else {
                $sqlFilters .= ")";
            }

            $gamepdo = game_pdo();

            $stmt = $gamepdo->prepare("SELECT beguid as uid, playerid as pid, name FROM `players` WHERE (`beguid` LIKE :q OR `playerid` LIKE :q OR `name` LIKE :q) {$sqlFilters} ORDER BY uid ASC LIMIT 100");
            $stmt->bindValue(':q', '%' . $q . '%', PDO::PARAM_STR);
            $stmt->execute();
            $players = $stmt->fetchAll(PDO::FETCH_OBJ);
            $stmt = $gamepdo->prepare("SELECT COUNT(*) as count FROM `players` WHERE (`beguid` LIKE :q OR `playerid` LIKE :q OR `name` LIKE :q) {$sqlFilters}");
            $stmt->bindValue(':q', '%' . $q . '%', PDO::PARAM_STR);
            $stmt->execute();
            $playerTotalCount = $stmt->fetch(PDO::FETCH_OBJ);
            $playerTotalCount = intval($playerTotalCount->count);

            $playerCount = count($players);
            $refine = ($playerTotalCount > 100) ? ' Refine Your Search Terms.' : '';

            echo (Helpers::APIResponse("Displaying {$playerCount} Of {$playerTotalCount}{$refine}", $players, 200));
        } else {
            echo Helpers::APIResponse("Not High Enough Rank", null, 403);
        }
    }

    public function Cases()
    {
        global $pdo;

        $user = new User;
        if (Permissions::init()->hasPermission("VIEW_SEARCH")) {
            $searchquery = $_GET['query'];
            $searchType = $_GET['type'];
            switch ($searchType) {
                case 'cases':
                    $stmt = $pdo->prepare("SELECT * FROM `case_logs` WHERE `id` LIKE :query OR `lead_staff` LIKE :query OR `other_staff` LIKE :query OR `description_of_events` LIKE :query ORDER BY id DESC LIMIT 100");
                    $stmt->bindValue(':query', '%' . $searchquery . '%', PDO::PARAM_STR);
                    $stmt->execute();
                    $rf = $stmt->fetchAll();
                    $results = [];
                    foreach ($rf as $r) {
                        array_push($results, [
                            'id' => $r->id,
                            'case_id' => $r->id,
                            'type' => 'case',
                            'description' => htmlspecialchars($r->description_of_events),
                            'players' => [
                                'reporting' => Helpers::getPlayersFromCase($r->id),
                            ]
                        ]);
                    }
                    $searchcount = count($results);
                    $stmt = $pdo->prepare("SELECT count(*) as count FROM `case_logs` WHERE `id` LIKE :query OR `lead_staff` LIKE :query OR `other_staff` LIKE :query OR `description_of_events` LIKE :query");
                    $stmt->bindValue(':query', '%' . $searchquery . '%', PDO::PARAM_STR);
                    $stmt->execute();
                    $fetchCount = $stmt->fetch();
                    $totalcount = $fetchCount->count;
                    $refine = ($totalcount > 100) ? ' Refine Your Search Terms.' : '';
                    echo Helpers::APIResponse("Displaying {$searchcount} Of {$totalcount}{$refine}", $results, 200);
                    break;
                case 'punishments':
                    $stmt = $pdo->prepare("SELECT * FROM `punishment_reports` WHERE (player LIKE :query OR comments LIKE :query OR rules LIKE :query) AND case_id <> 0 ORDER BY id DESC LIMIT 100");
                    $stmt->bindValue(':query', '%' . $searchquery . '%', PDO::PARAM_STR);
                    $stmt->execute();
                    $rf = $stmt->fetchAll();
                    $results = [];
                    foreach ($rf as $r) {
                        if (is_integer($r->points))
                            $points = $r->points . " Points";

                        array_push($results, [
                            'id' => $r->id,
                            'type' => 'punishment',
                            'case_id' => $r->case_id,
                            'description' => htmlspecialchars($r->comments),
                            'metadata' => [
                                'points' => $points,
                            ],
                            'players' => [
                                'punished' => [$r->player],
                                'reporting' => Helpers::getPlayersFromCase($r->case_id),
                            ]
                        ]);
                    }
                    $searchcount = count($results);
                    $stmt = $pdo->prepare("SELECT count(*) as count FROM `punishment_reports` WHERE player LIKE :query OR comments LIKE :query OR rules LIKE :query");
                    $stmt->bindValue(':query', '%' . $searchquery . '%', PDO::PARAM_STR);
                    $stmt->execute();
                    $fetchCount = $stmt->fetch();
                    $totalcount = $fetchCount->count;
                    $refine = ($totalcount > 100) ? ' Refine Your Search Terms.' : '';
                    echo Helpers::APIResponse("Displaying {$searchcount} Of {$totalcount}{$refine}", $results, 200);
                    break;
                case 'bans':
                    $sql = "SELECT * FROM `ban_reports` WHERE (player LIKE :query OR message LIKE :query) AND case_id <> 0 ORDER BY id DESC LIMIT 100";
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindValue(':query', '%' . $searchquery . '%', PDO::PARAM_STR);
                    $stmt->execute();
                    $rf = $stmt->fetchAll();
                    $results = [];
                    foreach ($rf as $r) {
                        if ($r->length == 0) {
                            $ban_length = "Permanent Ban";
                        } else {
                            $ban_length = htmlspecialchars($r->length) . " Days";
                        }

                        array_push($results, [
                            'id' => $r->id,
                            'type' => "ban",
                            'case_id' => $r->case_id,
                            'players' => [
                                'punished' => [$r->player],
                                'reporting' => Helpers::getPlayersFromCase($r->case_id)
                            ],
                            'description' => htmlspecialchars($r->message),
                            'metadata' => ["ban_length" => $ban_length],
                        ]);
                    }
                    $searchcount = count($results);
                    $stmt = $pdo->prepare("SELECT count(*) as count FROM `ban_reports` WHERE (player LIKE :query OR message LIKE :query) AND case_id <> 0");
                    $stmt->bindValue(':query', '%' . $searchquery . '%', PDO::PARAM_STR);
                    $stmt->execute();
                    $fetchCount = $stmt->fetch();
                    $totalcount = $fetchCount->count;
                    $refine = ($totalcount > 100) ? ' Refine Your Search Terms.' : '';
                    echo Helpers::APIResponse("Displaying {$searchcount} Of {$totalcount}{$refine}", $results, 200);
                    break;
                case 'unbans':
                    $sql = "SELECT * FROM `case_logs` WHERE (`lead_staff` LIKE :query OR `other_staff` LIKE :query OR `description_of_events` LIKE :query) AND `type_of_report` = 'Unban Log' ORDER BY id DESC LIMIT 100";
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindValue(':query', '%' . $searchquery . '%', PDO::PARAM_STR);
                    $stmt->execute();
                    $rf = $stmt->fetchAll();
                    $results = [];
                    foreach ($rf as $r) {
                        array_push($results, [
                            'id' => $r->id,
                            'type' => 'unban',
                            'description' => htmlspecialchars($r->description_of_events),
                            'players' => [
                                'punished' => [$r->player],
                                'reporting' => Helpers::getPlayersFromCase($r->id)
                            ],
                        ]);
                    }
                    $searchcount = count($results);
                    $stmt = $pdo->prepare("SELECT count(*) as count FROM `case_logs` WHERE (`lead_staff` LIKE :query OR `other_staff` LIKE :query OR `description_of_events` LIKE :query) AND `type_of_report` = 'Unban Log'");
                    $stmt->bindValue(':query', '%' . $searchquery . '%', PDO::PARAM_STR);
                    $stmt->execute();
                    $fetchCount = $stmt->fetch();
                    $totalcount = $fetchCount->count;
                    $refine = ($totalcount > 100) ? ' Refine Your Search Terms.' : '';
                    echo Helpers::APIResponse("Displaying {$searchcount} Of {$totalcount}{$refine}", $results, 200);
                    break;
                case 'players':
                    $sql = "SELECT ANY_VALUE(`id`) as id, ANY_VALUE(`name`) as name, MAX(`guid`) as guid FROM `case_players` WHERE ANY_VALUE(`name`) LIKE :query OR ANY_VALUE(`guid`) LIKE :query OR ANY_VALUE(`case_id`) LIKE :query GROUP BY `name` LIMIT 100";
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindValue(':query', '%' . $searchquery . '%', PDO::PARAM_STR);
                    $stmt->execute();
                    $rf = $stmt->fetchAll();
                    $results = [];
                    foreach ($rf as $r) {
                        array_push($results, [
                            'id' => $r->id,
                            'type' => 'player',
                            'metadata' => [
                                'name' => htmlspecialchars($r->name),
                                'guid' => htmlspecialchars($r->guid),
                            ],
                            'players' => [
                                'reporting' => Helpers::getPlayersFromCase($r->id)
                            ]
                        ]);
                    }
                    $searchcount = count($results);
                    $stmt = $pdo->prepare("SELECT count(*) as count FROM `case_players` WHERE `name` LIKE :query OR `guid` LIKE :query OR `case_id` LIKE :query");
                    $stmt->bindValue(':query', '%' . $searchquery . '%', PDO::PARAM_STR);
                    $stmt->execute();
                    $fetchCount = $stmt->fetch();
                    $totalcount = $fetchCount->count;
                    $refine = ($totalcount > 100) ? ' Refine Your Search Terms.' : '';
                    echo Helpers::APIResponse("Displaying {$searchcount} Of {$totalcount}{$refine}", $results, 200);
                    break;
                default:
                    Helpers::addAuditLog("No Search Type Given By {$user->info->username} Type: {$searchType} ~ Query {$searchquery}");
                    echo Helpers::APIResponse("No Search Type Given", null, 400);
                    break;
            }
        } else {
            Helpers::addAuditLog("AUTHENTICATION_FAILED::{$_SERVER['REMOTE_ADDR']} Triggered An Unauthenticated Response In `GetSearchResults`");
            echo Helpers::APIResponse("Search Failed: Unauthorised", null, 401);
        }
    }
}
