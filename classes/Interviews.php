<?php

/**
 * Created by PhpStorm.
 * User: Kieran
 * Date: 06/12/2018
 * Time: 16:14
 */

include_once $_SERVER['DOCUMENT_ROOT'] . '/classes/User.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

class Interviews
{

    public static function list()
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM staff_interviews ORDER BY id DESC");
        $stmt->execute();
        $interviews = $stmt->fetchAll();
        if ($interviews) {
            return ['error' => false, "interviews" => $interviews];
        } else {
            return ['error' => true, 'message' => 'No Interviews Exist'];
        }
    }

    public static function fromID($id)
    {
        global $pdo;
        $user = new User;
        if ($id !== null && $user->isSLT()) {
            $stmt = $pdo->prepare('SELECT * FROM staff_interviews WHERE id = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_STR);
            $stmt->execute();
            $interview = $stmt->fetch();
            $intr = new User($interview->interviewer_id);
            $interview->interviewer = $intr->info;
            return $interview;
        }
        return false;
    }
}
