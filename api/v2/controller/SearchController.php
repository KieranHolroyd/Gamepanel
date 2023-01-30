<?php

namespace App\API\V2\Controller;

use \Permissions, \Helpers, \PDO;

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
                'onlyPolice' => '`coplevel` <> \'0\'',
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
}
