<?php

include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';

class User
{
    public $info = [];
    public $neededFields = [];
    public $error = false;

    public function __construct($id = null)
    {
        global $pdo;
        if ($id) {
            $sql2 = "SELECT * FROM users WHERE id = :id";
            $query2 = $pdo->prepare($sql2);
            $query2->bindValue(':id', $id, PDO::PARAM_STR);
            if ($query2->execute()) {
                $usr = $query2->fetch();
                if ($usr) {
                    $this->info = $usr;
                } else {
                    $this->error = true;
                }
            } else {
                $this->error = true;
            }
        } else {
            $logintoken = 'a';
            if (isset($_COOKIE['LOGINTOKEN'])) {
                $logintoken = $_COOKIE['LOGINTOKEN'];
            }
            $sql = "SELECT * FROM login_tokens WHERE token = :token";
            $query = $pdo->prepare($sql);
            $query->bindValue(':token', sha1($logintoken), PDO::PARAM_STR);
            if ($query->execute()) {
                $result = $query->fetch();
                if ($result) {
                    $sql2 = "SELECT * FROM users WHERE id = :id";
                    $query2 = $pdo->prepare($sql2);
                    $query2->bindValue(':id', $result->user_id, PDO::PARAM_STR);
                    if ($query2->execute()) {
                        $usr = $query2->fetch();
                        if ($usr) {
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
            }
        }
    }

    public function isOnLOA()
    {
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

    public function isSLT()
    {
        if (!$this->infoExists())
            return false;
        if (($this->info->SLT || $this->info->Developer) && !$this->error) {
            return true;
        }
        return false;
    }

    public function isStaff()
    {
        if (!$this->infoExists())
            return false;
        if ($this->verified() && $this->info->isStaff) {
            return true;
        }
        return false;
    }

    public function isCommand()
    {
        if (!$this->infoExists())
            return false;
        if ($this->verified(false) && ($this->info->isCommand || $this->isSLT())) {
            return true;
        }
        return false;
    }

    public function verified($old = true)
    {
        if (!$this->infoExists())
            return false;
        if (!$this->error) {
            if ($old)
                return true;
            return true;
        }
        return false;
    }

    public function displayName()
    {
        if (!$this->infoExists())
            return false;
        return $this->info->first_name . ' ' . $this->info->last_name;
    }

    public function isSuspended()
    {
        if (!$this->infoExists())
            return false;
        if ($this->info->suspended) {
            return true;
        }
        return false;
    }

    public function hasGameReadAccess($level = 0)
    {
        if (!$this->infoExists())
            return false;
        if ($level == 0) {
            if ($this->info->rank_lvl <= 8 || $this->info->Developer || $this->isCommand()) {
                return true;
            }
        } else {
            if ($this->info->rank_lvl <= 7 || $this->info->Developer || $this->isCommand()) {
                return true;
            }
        }
        return false;
    }

    public function hasGameWriteAccess($comp = true)
    {
        if (!$this->infoExists())
            return false;
        if ($comp) {
            if ($this->info->rank_lvl <= 6 || $this->info->Developer) {
                return true;
            }
        } else {
            if ($this->info->rank_lvl <= 6 || $this->info->Developer || $this->isCommand()) {
                return true;
            }
        }

        return false;
    }

    public function needMoreInfo()
    {
        if (!$this->infoExists())
            return false;
        if ($this->info->region == null || $this->info->region == '')
            $this->neededFields[] = 'region';
        if ($this->info->steamid == null || $this->info->steamid == '')
            $this->neededFields[] = 'steamid';


        if ($this->neededFields)
            return true;
        return false;
    }

    public function isPD()
    {
        if (!$this->infoExists())
            return false;
        if ($this->info->isPD)
            return true;
        return false;
    }

    public function isEMS()
    {
        if (!$this->infoExists())
            return false;
        if ($this->info->isEMS)
            return true;
        return false;
    }

    public function getInfoForFrontend()
    {
        if (!$this->infoExists())
            return false;
        return [
            "isSLT" => $this->isSLT(),
            "isStaff" => $this->isStaff(),
            "isDeveloper" => ($this->info->Developer),
            "isSuspended" => $this->isSuspended(),
            "isPD" => $this->isPD(),
            "isEMS" => $this->isEMS(),
            "isOnLOA" => $this->isOnLOA(),
            "id" => $this->info->id,
            "rank" => Helpers::getRankNameFromPosition(Permissions::getHighestRank(json_decode($this->info->rank_groups, true))),
            "firstName" => $this->info->first_name,
            "lastName" => $this->info->last_name,
            "displayName" => $this->displayName(),
            "username" => $this->info->username,
            "team" => $this->info->staff_team,
            "faction_rank" => @$this->getFactionRank(), // NOTE: Error suppression 
            "faction_rank_real" => @$this->info->faction_rank // NOTE: Error suppression
        ];
    }

    private function getFactionRank()
    {
        if (!$this->infoExists() || $this->info->faction_rank == null || $this->info->faction_rank == '')
            return false;
        if ($this->isPD()) {
            return Config::$faction_ranks['police'][$this->info->faction_rank];
        } else {
            return Config::$faction_ranks['medic'][$this->info->faction_rank];
        }
    }

    public function fetchNotifications()
    {
        global $pdo;

        if (!$this->infoExists())
            return false;

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

        return $notifications;
    }

    public function pushNotification($title = "No Title", $content = "No Content", $link = "/")
    {
        global $pdo;

        if (!$this->infoExists())
            return false;

        $stmt = $pdo->prepare('INSERT INTO notifications (`title`, `content`, `callback_url`, `for_user_id`) 
                              VALUES (:t, :c, :url, :id)');
        $stmt->bindValue(':t', $title, PDO::PARAM_INT);
        $stmt->bindValue(':c', $content, PDO::PARAM_INT);
        $stmt->bindValue(':url', $link, PDO::PARAM_INT);
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

    private function infoExists()
    {
        if (gettype($this->info) !== gettype(json_decode("{\"example\": 0}")))
            return false;

        return true;
    }
}
