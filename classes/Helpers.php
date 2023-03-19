<?php

include_once $_SERVER['DOCUMENT_ROOT'] . '/classes/User.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/classes/Webhook.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

// namespace App\Helper {
class Statistics {
    public static function _sort_to_bin(array &$stats, int $time_since_created, int $time_step, int $bin_size, string $key) {
        for ($x = 0; $x < $bin_size * $time_step; $x += $time_step) {
            $idx = $x / $time_step;
            if (!isset($stats[$key][$idx])) {
                $stats[$key][$idx] = 0;
            }
            if ($time_since_created >= ($x) && $time_since_created <= ($x + $time_step)) {
                $stats[$key][$idx]++;
            }
        }
        return;
    }
}
class Headers {
    /** the function takes an array of timings and returns a string compatible with the ServerTimingAPI header.
     * @param array $timings Required. the array should be in the following format:
     * [\
     * 'key' => [\
     * 		'description' => 'description of the timing',\
     * 		'value' => 'value of the timing',\
     * 	]\
     * ]
     * @return string The string compatible with the ServerTimingAPI header.
     */
    public static function transform_timings_to_server_timing_header(array $timings) {
        $server_timing_header = '';
        usort($timings, function ($a, $b) {
            return $a['value'] <=> $b['value'];
        });
        foreach ($timings as $key => $timing) {
            $server_timing_header .= $timing['name'] . ';desc="' . $timing['description'] . '";dur=' . $timing['value'] . ',';
        }
        return $server_timing_header;
    }

    /** the function takes a name, description and value and returns an array compatible with the ServerTimingAPI header.
     * @param string $name Required. the name of the timing.
     * @param string $description Required. the description of the timing.
     * @param float $value Required. the value of the timing.
     * @return array The array compatible with the transform function.
     */
    public static function create_timing_object(string $name, string $description, float $value) {
        if (!isset($_SESSION['timings']) || !is_array($_SESSION['timings']))
            $_SESSION['timings'] = [];

        $name = str_replace(':', '-', $name);

        $_SESSION['timings'][$name] = [
            'name' => $name,
            'description' => $description,
            'value' => $value / 1e6,
        ];

        return [
            'name' => $name,
            'description' => $description,
            'value' => $value,
        ];
    }

    /** the function sends the correctly formatted Server-Timing header if the timings array is set on the `$_SESSION` variable.
     * @return void
     */
    public static function send_server_timing_header() {
        if (isset($_SESSION['timings']) && is_array($_SESSION['timings'])) {
            header('Server-Timing: ' . self::transform_timings_to_server_timing_header($_SESSION['timings']));
            unset($_SESSION['timings']);
        }
    }
}
class AuditLog {
    public static function create($message, $metadata = [], $discord = true) {
        global $pdo;
        $user = new User;

        $ctx = ($user->isCommand() && !$user->isStaff()) ? 'PD_EMS_COMMAND' : 'admin';

        $stmt = $pdo->prepare('INSERT INTO audit_log (log_content, log_context, logged_in_user) VALUES (:content, :ctx, :liu)');
        $stmt->bindValue(':content', $message);
        $stmt->bindValue(':ctx', $ctx);
        $stmt->bindValue(':liu', ($user->isStaff()) ? @$user->info->id : 0); // Supressing error (using stdClass as array)
        $stmt->execute();
        $stmt->closeCursor();

        if ($discord) {
            //Dispatch to Discord
            try {
                $hook = new WebhookManager;
                $message = self::create_message(($user->isStaff()) ? $user : 0, $message);
                $response = @$hook->discord()->embed("Audit Log", $message, ($user->isStaff()) ? @$user->info->username : "No User")->send();
                if ($response["error"]) {
                    self::create("DISCORD_WEBHOOK_ERROR::" . $response["error"], false);
                }
            } catch (Exception $e) {
                self::create("DISCORD_WEBHOOK_ERROR::" . $e->getMessage(), false);
            }
        }
    }

    public static function create_message($user, $message) {
        return ($user->info->discord_id) ? "<@{$user->info->discord_id}> {$message}" : "{$user->info->username} $message";
    }
}
// }


// namespace {
class Helpers {

    public static function ParseOtherStaff($staff) {
        $staff = explode(' ', $staff);
        $list = [];
        foreach ($staff as $user) {
            if ($user != "") {
                array_push($list,  [
                    "id" => self::UsernameToID($user),
                    "username" => $user
                ]);
            }
        }
        return $list;
    }

    public static function UsernameToID($name = null) {
        global $pdo;
        if ($name) {
            $stmt = $pdo->prepare('SELECT id, username FROM users WHERE username = :username');
            $stmt->bindValue(':username', $name, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch();
            if ($user) {
                return $user->id;
            }
        }
        return false;
    }

    public static function IDToUsername($id = null) {
        global $pdo;
        if ($id) {
            $stmt = $pdo->prepare('SELECT id, username FROM users WHERE id = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch();
            if ($user) {
                return $user->username;
            }
        }
        return "Staff Not Found";
    }

    public static function APIResponse($message = null, $array = null, $code = null) {
        return json_encode(['response' => $array, 'code' => $code, 'message' => $message]);
    }

    public static function NewAPIResponse($array = null) {
        $_SERVER['Content-Type'] = 'application/json';
        return json_encode([...$array]);
    }

    public static function PusherSend($data, $channel, $event) {
        try {
            $pusher = new Pusher\Pusher(
                Config::$pusher['AUTH_KEY'],
                Config::$pusher['SECRET'],
                Config::$pusher['APP_ID'],
                Config::$pusher['DEFAULT_CONFIG']
            );
        } catch (\Pusher\PusherException $e) {
            return false;
        }

        try {
            $pusher->trigger($channel, $event, $data);
            return true;
        } catch (\Pusher\PusherException $e) {
            self::addAuditLog("PUSHER_ERROR::" . $e->getMessage());
            echo "PUSHER ERROR: " . $e->getMessage();
        }
        return true;
    }

    public static function addAuditLog($content, $discord = true) {
        global $pdo;
        $user = new User;

        $ctx = ($user->isCommand() && !$user->isStaff()) ? 'PD_EMS_COMMAND' : 'admin';

        $stmt = $pdo->prepare('INSERT INTO audit_log (log_content, log_context, logged_in_user) VALUES (:content, :ctx, :liu)');
        $stmt->bindValue(':content', $content);
        $stmt->bindValue(':ctx', $ctx);
        $stmt->bindValue(':liu', ($user->isStaff()) ? @$user->info->id : 0); // Supressing error (using stdClass as array)
        $stmt->execute();
        $stmt->closeCursor();

        if ($discord) {
            //Dispatch to Discord
            try {
                $hook = new WebhookManager;
                $content = self::parseAuditLogForWebhook($content);
                $response = @$hook->discord()->embed("Audit Log", $content, ($user->isStaff()) ? @$user->info->username : "No User")->send();
                if ($response["error"]) {
                    self::addAuditLog("DISCORD_WEBHOOK_ERROR::" . $response["error"], false);
                }
            } catch (Exception $e) {
                self::addAuditLog("DISCORD_WEBHOOK_ERROR::" . $e->getMessage(), false);
            }
        }
    }

    public static function parseAuditLogForWebhook($content) {
        // TODO: make audit log parser
        return $content;
    }

    public static function viewingPublicPage() {
        $publicurls = [
            '/errors/awaitingapproval',
            '/errors/nostaff',
            '/passport',
            '/holdingpage',
            '/purchases/activate',
            '/leaderboard',
            '/staff/apply'
        ];
        foreach ($publicurls as $url) {
            if (strpos($_SERVER['REQUEST_URI'], $url) !== false)
                return true;
        }
        return false;
    }

    public static function fixPlayersForCase($number, $errors) {
        global $pdo;

        $errors = json_encode($errors);

        $stmt = $pdo->prepare("INSERT INTO case_players (case_id, type, name, guid) VALUES (:id, :type, :nm, :guid)");
        $stmt->bindValue(":id", $number);
        $stmt->bindValue(":type", 'Failed');
        $stmt->bindValue(":nm", 'Please Give The Case ID to Kieran.');
        $stmt->bindValue(":guid", 'Undefined');
        if (!$stmt->execute()) {
            self::addAuditLog("CRITICAL_ERROR::Failed To Add Player To Report " . json_encode($stmt->errorinfo()));
        } else {
            self::addAuditLog("CRITICAL_ERROR_ERROR::{$errors}");
            self::addAuditLog("CRITICAL_ERROR_FiX::Corrected add player, (CASEID: {$number}) ");
        }
    }

    public static function sanitizeUserDataArray($users) {
        foreach ($users as $key => $user) {
            $users[$key] = self::sanitizeUserData($user);
        }
        return $users;
    }

    public static function sanitizeUserData($user) {
        unset($user->password);
        unset($user->email);
        unset($user->unique_id);
        return $user;
    }

    public static function getPlayersFromCase($caseid) {
        global $pdo;
        $stmt = $pdo->prepare('SELECT * FROM case_players WHERE case_id = :id');
        $stmt->bindValue(':id', $caseid, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function parsePlayers(array $players) {
        foreach ($players as $player) {
            $name = $player->name;
            $player->name = "<a href='" . Config::$base_url . "search?type=players&query={$name}'>{$name}</a>";
        }
        return $players;
    }

    public static function parsePunishment($p) {
        $comments = nl2br($p->comments);
        return "<div class='punishment_report'><span class='player'>{$p->player}'s Punishment Report</span> <span class='points'>{$p->points} Points</span><p class='rules'>Rules Broken: {$p->rules}</p><p class='comments'>Staff Comment: {$comments}</p></div>";
    }

    public static function parseBan($b) {
        $length = ($b->length) ? $b->length . ' Days' : 'Permanent';

        $ts = self::zeroOneToYesNo($b->teamspeak);
        $ig = self::zeroOneToYesNo($b->ingame);
        $wb = self::zeroOneToYesNo($b->website);
        $tsClass = ($b->teamspeak) ? 'punishmentincase' : 'typeofreport';
        $igClass = ($b->ingame) ? 'punishmentincase' : 'typeofreport';
        $wbClass = ($b->website) ? 'punishmentincase' : 'typeofreport';

        $custom_classes = "";
        $ban_expired_text = " ~ <a onclick='markBanExpired({$b->id}, {$b->case_id})'>Expire Ban</a>";

        if ($b->length != 0) {
            if (time() > strtotime(date("Y-m-d", strtotime($b->timestamp)) . ' + ' . $b->length . ' days')) {
                $custom_classes .= " ban_expired";
                $ban_expired_text = " ~ Ban Expired";
            }
        }

        //        $date = strtotime(date("Y-m-d", strtotime($b->timestamp)) . ' + ' . $b->length . ' days');

        return "<div class='punishment_report{$custom_classes}'><span class='player'>{$b->player}'s Ban Report {$ban_expired_text}</span> <span class='points'>{$length}</span>
                <p>Ban Message: `{$b->message}`</p>
                <div style='padding: 10px 0 0;'>
                    <span class='{$tsClass}'>Teamspeak Ban: {$ts}</span>
                    <span class='{$igClass}'>Ingame Ban: {$ig}</span>
                    <span class='{$wbClass}'>Website Ban: {$wb}</span>
                </div>
            </div>";
    }

    public static function zeroOneToYesNo($n) {
        return ($n) ? 'Yes' : 'No';
    }

    public static function checkCaseHasPunishment($id) {
        global $pdo;

        $stmt = $pdo->prepare('SELECT COUNT(*) AS reports FROM punishment_reports WHERE case_id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $returned = ($stmt->fetch()->reports > 0) ? true : false;

        return $returned;
    }

    public static function checkCaseHasBan($id) {
        global $pdo;

        $stmt = $pdo->prepare('SELECT COUNT(*) AS reports FROM ban_reports WHERE case_id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $returned = ($stmt->fetch()->reports > 0) ? true : false;

        return $returned;
    }

    public static function IDToStaff(int $id) {
        return new User($id);
    }

    public static function getBMMakeReservedRequest($steamid, $licenseKey, $orderID, $username) {
        try {
            $tok = Config::$battleMetrics['apiKey'];

            $headers = [
                'Authorization' => 'Bearer ' . $tok,
                'Accept' => 'application/json'
            ];

            $body = [
                "data" => [
                    [
                        "attributes" => [
                            "identifier" => $steamid,
                            "type" => "steamID"
                        ],
                        "type" => "identifier"
                    ]
                ]
            ];

            $body = Unirest\Request\Body::Form($body);

            $bmPlayerInfo = Unirest\Request::post('https://api.battlemetrics.com/players/match', $headers, $body);

            return '{
        "data": {
            "attributes": {
                "expires": "' . substr(date("c", time() + 60 * 60 * 24 * 30), 0, 19) . '.962Z",
                "identifiers": [
                    {
                        "type": "steamID",
                        "identifier": "' . $steamid . '",
                        "manual": true
                    }
                ]
            },
            "relationships": {
                "player": {
                    "data": {
                        "type": "player",
                        "id": "' . $bmPlayerInfo->body->data[0]->relationships->player->data->id . '"
                    }
                },
                "servers": {
                    "data": [
                        {
                            "type": "server",
                            "id": "2921049"
                        },{
                            "type": "server",
                            "id": "2829381"
                        }
                    ]
                },
                "organization": {
                    "data": {
                        "type": "organization",
                        "id": "8030"
                    }
                },
                "user": {
                    "data": {
                        "type": "user",
                        "id": "' . $bmPlayerInfo->body->data[0]->relationships->player->data->id . '"
                    }
                }
            },
            "type": "reservedSlot"
        }
    }';
        } catch (\Unirest\Exception $e) {
            self::addAuditLog('ERROR::Unirest Error [Get Player SteamID Match] ' . $e->getMessage());
            return false;
        }
    }

    public static function isValidAPIToken(string $key) {
        global $pdo;

        $stmt = $pdo->prepare('SELECT `id`, `owner`, `view`, `update`, `delete` FROM api_keys WHERE `key` = :k');
        $stmt->bindValue(':k', hash("sha512", $key), PDO::PARAM_STR);
        $stmt->execute();
        $api_key = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($api_key) {
            return $api_key;
        }
        return false;
    }

    private static $APITokenPerms = ['V' => 'view', 'U' => 'update', 'D' => 'delete'];

    public static function ValidateAPITokenPerms($keyInfo, $permission) {
        if (!$keyInfo)
            return false;

        if (!$keyInfo[self::$APITokenPerms[$permission]])
            return false;

        return true;
    }

    public static function BattlemetricsIssueBan($playerID, $banReason = "", $banningAdminName = "", $banLength = null) {
        $headers = [
            'Authorization' => 'Bearer ' . Config::$battleMetrics['apiKey'],
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        $body = [
            "data" => [
                [
                    "attributes" => [
                        "identifier" => $playerID,
                        "type" => "steamID"
                    ],
                    "type" => "identifier"
                ]
            ]
        ];

        $body = Unirest\Request\Body::Json($body);

        $bmPlayerInfo = Unirest\Request::post('https://api.battlemetrics.com/players/match', $headers, $body);

        $bl = substr(date("c", time() + 60 * 60 * 24 * $banLength), 0, 19) . '.962Z';

        $realBanLength = ($banLength == 0) ? 'null' : '"' . $bl . '"';

        $body = '{
      "data": {
        "type": "ban",
        "attributes": {
          "timestamp": "' . substr(date("c", time()), 0, 19) . '.962Z",
          "reason": "' . $banReason . ' - {{timeLeft}} - ' . $banningAdminName . '",
          "note": "",
          "expires": ' . $realBanLength . ',
          "identifiers": [
            {
              "type": "steamID",
              "identifier": "' . $playerID . '",
              "manual": true
            }
          ],
          "orgWide": true,
          "autoAddEnabled": false,
          "nativeEnabled": null
        },
        "relationships": {
          "organization": { "data": { "type": "organization", "id": "8030" } },
          "server": { "data": { "type": "server", "id": "2921049" } },
          "player": { "data": { "type": "player", "id": "' . $bmPlayerInfo->body->data[0]->relationships->player->data->id . '" } },
          "banList": {
            "data": {
              "type": "banList",
              "id": "84f68930-e938-11e8-b55f-a91a07062049"
            }
          }
        }
      }
    }';
        return Unirest\Request::post('https://api.battlemetrics.com/bans', $headers, $body)->body;
    }

    public static function getRankNameFromPosition($getHighestRank) {
        global $pdo;
        $stmt = $pdo->prepare('SELECT * FROM rank_groups WHERE position = :p');
        $stmt->bindValue(':p', $getHighestRank, PDO::PARAM_INT);
        $stmt->execute();
        return @$stmt->fetch()->name; // Supressing "using stdClass as array" error
    }

    public static function sendNotificationTo(int $user_id, $title = "No Title", $content = "No Content", $link = "/") {
        global $pdo;

        $stmt = $pdo->prepare('INSERT INTO notifications (`title`, `content`, `callback_url`, `for_user_id`) 
                                  VALUES (:t, :c, :url, :id)');
        $stmt->bindValue(':t', $title);
        $stmt->bindValue(':c', $content);
        $stmt->bindValue(':url', $link);
        $stmt->bindValue(':id', $user_id);

        $db_call = $stmt->execute();
        if (!$db_call) {
            self::addAuditLog("Failed to send notification to user $user_id. (DB Error: " . $stmt->errorInfo() . ")");
        }

        $data = [
            "callback_url" => $link,
            "content" => $content,
            "title" => $title,
            "viewed" => 0,
            "id" => $pdo->lastInsertId(),
            "timestamp" => date("F j, Y \a\\t g:ia", time()),
            "for_user_id" => $user_id
        ];

        Helpers::PusherSend($data, 'notifications', 'receive');
    }

    public static function getAuth() {
        if (isset($_COOKIE['LOGINTOKEN'])) {
            return $_COOKIE['LOGINTOKEN'];
        }

        if (isset($_SERVER['HTTP_X_TOKEN'])) {
            return $_SERVER['HTTP_X_TOKEN'];
        }

        return null;
    }
}
// }
