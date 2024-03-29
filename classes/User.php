<?php

include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/classes/Cache.php';

const USER_CACHE_EXPIRY = 120;

class User {
    public $info = [];
    public $neededFields = [];
    public $error = false;

    public function __construct($id = null) {
        global $pdo, $cache;
        if ($id) {
            $exists_in_cache = $cache->get("user:{$id}");
            if ($exists_in_cache) {
                return $this->info = json_decode($exists_in_cache);
            } else {
                $query2 = $pdo->prepare("SELECT * FROM users WHERE id = :id");
                $query2->bindValue(':id', $id);
                if ($query2->execute()) {
                    $usr = $query2->fetch();
                    if ($usr) {
                        $cache->set("user:{$usr->id}", json_encode($usr), ["ex" => USER_CACHE_EXPIRY]);
                        $this->info = $usr;
                    } else {
                        $this->error = true;
                    }
                } else {
                    $this->error = true;
                }
            }
        } else {
            if (Helpers::getAuth()) {
                $token = sha1(Helpers::getAuth());
                $stmt = $pdo->prepare("SELECT * FROM login_tokens WHERE token = :token");
                $stmt->bindValue(':token', $token);
                if ($stmt->execute()) {
                    $result = $stmt->fetch();
                    $stmt->closeCursor();
                    if ($result) {
                        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
                        $stmt->bindValue(':id', $result->user_id);
                        if ($stmt->execute()) {
                            $usr = $stmt->fetch();
                            if ($usr) {
                                $cache->set("user:{$usr->id}", json_encode($usr), ["ex" => USER_CACHE_EXPIRY]);
                                $this->info = $usr;
                            } else {
                                $this->error = true;
                            }
                        } else {
                            $this->error = true;
                        }
                    } else {
                        $this->error = true;
                    }
                } else {
                    $this->error = true;
                    die("Error: " . $stmt->errorInfo());
                }
            } else {
                $this->error = true;
            }
        }
    }

    public function isOnLOA() {
        if (!$this->infoExists())
            return false;
        if ($this->info->loa !== null) {
            /** @noinspection PhpUnhandledExceptionInspection */
            if (new DateTime() < new DateTime($this->info->loa)) {
                return true;
            }
        }
        return false;
    }

    public function isSLT() {
        if (!$this->infoExists())
            return false;
        if ($this->info->SLT || $this->info->Developer || $this->info->isServerOwner) {
            return true;
        }
        return false;
    }

    public function isStaff() {
        if (!$this->infoExists())
            return false;
        if ($this->verified() && Permissions::forUserID($this->info->id)->hasPermission("VIEW_GENERAL")) {
            return true;
        }
        return false;
    }

    public function isDeveloper() {
        if (!$this->infoExists())
            return false;
        if ($this->verified() && $this->info->Developer) {
            return true;
        }
        return false;
    }

    public function isCommand() {
        if (!$this->infoExists())
            return false;
        if ($this->verified(false) && ($this->info->isCommand || $this->isSLT())) {
            return true;
        }
        return false;
    }

    public function verified($old = true) {
        if (!$this->infoExists())
            return false;
        if (!$this->error) {
            if ($old)
                return true;
            return true;
        }
        return false;
    }

    public function displayName() {
        if (!$this->infoExists())
            return false;
        return $this->info->first_name . ' ' . $this->info->last_name;
    }

    public function isSuspended() {
        if (!$this->infoExists())
            return false;
        if ($this->info->suspended) {
            return true;
        }
        return false;
    }

    public function needMoreInfo() {
        if (!$this->infoExists())
            return false;
        if ($this->info->region == null || $this->info->region == '')
            $this->neededFields[] = 'region';
        if ($this->info->steamid == null || $this->info->steamid == '')
            $this->neededFields[] = 'steamid';
        if ($this->info->discord_tag == null || $this->info->discord_tag == '')
            $this->neededFields[] = 'discord_tag';


        if ($this->neededFields)
            return true;
        return false;
    }

    public function isPD() {
        if (!$this->infoExists())
            return false;
        if ($this->info->isPD)
            return true;
        return false;
    }

    public function isEMS() {
        if (!$this->infoExists())
            return false;
        if ($this->info->isEMS)
            return true;
        return false;
    }

    public function getInfoForFrontend() {
        if (!$this->infoExists())
            return false;
        return [
            "isSLT" => $this->isSLT(),
            "isStaff" => $this->isStaff(),
            "isDeveloper" => $this->isDeveloper(),
            "isSuspended" => $this->isSuspended(),
            "isPD" => $this->isPD(),
            "isEMS" => $this->isEMS(),
            "isOnLOA" => $this->isOnLOA(),
            "id" => $this->info->id,
            "rank" => $this->highestRankString(),
            "firstName" => $this->info->first_name,
            "lastName" => $this->info->last_name,
            "displayName" => $this->displayName(),
            "username" => $this->info->username,
            "team" => $this->info->staff_team,
            "faction_rank" => @$this->getFactionRank(), // NOTE: Error suppression 
            "faction_rank_real" => @$this->info->faction_rank // NOTE: Error suppression
        ];
    }

    private function getFactionRank() {
        if (!$this->infoExists() || $this->info->faction_rank == null || $this->info->faction_rank == '')
            return null;
        if ($this->isPD()) {
            return Config::$faction_ranks['police'][$this->info->faction_rank];
        } else {
            return Config::$faction_ranks['medic'][$this->info->faction_rank];
        }
    }

    public function fetchNotifications() {
        global $pdo, $cache;

        if (!$this->infoExists())
            return false;

        $notifications = $cache->get("notifications:{$this->info->id}");
        if ($notifications) {
            return json_decode($notifications);
        } else {
            $stmt = $pdo->prepare('SELECT * FROM notifications WHERE for_user_id = :id ORDER BY id DESC LIMIT 25');
            $stmt->bindValue(':id', $this->info->id, PDO::PARAM_INT);

            $stmt->execute();
            $notifications = $stmt->fetchAll();

            $stmt = $pdo->prepare('UPDATE notifications SET viewed = 1 WHERE for_user_id = :id');
            $stmt->bindValue(':id', $this->info->id, PDO::PARAM_INT);

            $stmt->execute();

            foreach ($notifications as $n) {
                $n->timestamp = date("F j, Y \a\\t g:ia", strtotime($n->timestamp));
            }

            $cache->set("notifications:{$this->info->id}", json_encode($notifications), USER_CACHE_EXPIRY);
        }

        return $notifications;
    }

    public function pushNotification($title = "No Title", $content = "No Content", $link = "/") {
        global $pdo;

        if (!$this->infoExists())
            return false;

        $stmt = $pdo->prepare('INSERT INTO notifications (`title`, `content`, `callback_url`, `for_user_id`) 
                              VALUES (:t, :c, :url, :id)');
        $stmt->bindValue(':t', $title, PDO::PARAM_STR);
        $stmt->bindValue(':c', $content, PDO::PARAM_STR);
        $stmt->bindValue(':url', $link, PDO::PARAM_STR);
        $stmt->bindValue(':id', $this->info->id, PDO::PARAM_INT);

        $stmt->execute();

        $data = [
            "callback_url" => $link,
            "content" => $content,
            "title" => $title,
            "viewed" => 0,
            "id" => $pdo->lastInsertId(),
            "timestamp" => date("F j, Y \a\\t g:ia", time()),
            "for_user_id" => $this->info->id
        ];

        Helpers::PusherSend($data, 'notifications', 'receive');
    }

    public function highestRank() {
        if (!$this->infoExists())
            return false;
        return Permissions::getHighestRank(json_decode($this->info->rank_groups, true));
    }

    public function highestRankString() {
        if (!$this->infoExists())
            return false;
        return Helpers::getRankNameFromPosition(Permissions::getHighestRank(json_decode($this->info->rank_groups, true)));
    }

    public function discord_tag() {
        if (!$this->infoExists())
            return false;
        if ($this->info->discord_tag == null || $this->info->discord_tag == '')
            return false;
        return "@" . $this->info->discord_tag;
    }

    public function discord_id() {
        if (!$this->infoExists())
            return false;
        if ($this->info->discord_id == null || $this->info->discord_id == '')
            return false;
        return $this->info->discord_id;
    }

    private function infoExists() {
        if (gettype($this->info) !== gettype(json_decode("{\"example\": 0}")))
            return false;

        return true;
    }
}
