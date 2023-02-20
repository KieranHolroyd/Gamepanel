<?php

include_once $_SERVER['DOCUMENT_ROOT'] . '/classes/User.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/classes/Config.php';

class Permissions extends User
{
    private static $instance;

    public static function init()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function getHighestRank($user_ranks)
    {
        global $pdo;

        $arr = [];

        foreach ($pdo->query('SELECT * FROM rank_groups') as $g) {
            if (in_array($g->id, $user_ranks)) {
                $arr[] = (int) $g->position;
            }
        }

        if (count($arr) > 0) {
            return min((array) $arr);
        } else {
            return false;
        }
    }

    public function hasPermission($name)
    {
        global $pdo;

        if (!$this->verified(false)) return false;

        if ($this->isOverlord()) return true;

        $perms = [];

        foreach (json_decode($this->info->rank_groups) as $g) {
            $stmt = $pdo->prepare("SELECT * FROM rank_groups WHERE id = :id");
            $stmt->execute(['id' => $g]);

            $fetch = $stmt->fetch();

            foreach (json_decode($fetch->permissions) as $f) {
                $perms[] = $f;
            }
        }

        if (in_array($name, $perms) || in_array('*', $perms)) {
            return true;
        }
        return false;
    }

    public function hasSudo()
    {
        global $pdo;

        if (!$this->verified(false)) return false;

        if ($this->isOverlord()) return true;

        $perms = [];

        foreach (json_decode($this->info->rank_groups) as $g) {
            $stmt = $pdo->prepare("SELECT * FROM rank_groups WHERE id = :id");
            $stmt->execute(['id' => $g]);

            $fetch = $stmt->fetch();

            foreach (json_decode($fetch->permissions) as $f) {
                $perms[] = $f;
            }
        }

        // Check if user has sudo/any permission
        if (in_array('*', $perms)) {
            return true;
        }
        return false;
    }

    public function isOverlord()
    {
        if ($this->info->isServerOwner)
            return true;
        return false;
    }
}
