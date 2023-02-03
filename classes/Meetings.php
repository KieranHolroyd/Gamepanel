<?php

/**
 * Created by PhpStorm.
 * User: kiera
 * Date: 28/11/2018
 * Time: 02:50
 */

include_once $_SERVER['DOCUMENT_ROOT'] . '/classes/User.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/classes/Helpers.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';

class Meetings
{
    public static function list(User $user)
    {
        global $pdo;

        $wheres = "";

        if ($user->isStaff()) {
            $wheres .= "staff = 1 ";
        }
        $or = ($wheres != "") ? 'OR' : '';
        if ($user->isPD()) {
            $wheres .= "{$or} pd = 1 ";
        }
        $or = ($wheres != "") ? 'OR' : '';
        if ($user->isEMS()) {
            $wheres .= "{$or} ems = 1 ";
        }
        $or = ($wheres != "") ? 'OR' : '';
        if ($user->isSLT()) {
            $wheres .= "{$or} slt = 1";
        }

        $stmt = $pdo->prepare("SELECT * FROM meetings WHERE {$wheres} ORDER BY date DESC");

        if (!$stmt->execute()) {
            return false;
        }

        $meetings = $stmt->fetchAll();

        if (!$meetings) {
            return false;
        }

        foreach ($meetings as $meeting) {
            $stmt = $pdo->prepare('SELECT COUNT(*) AS count FROM meeting_points WHERE meetingID = :id');
            $stmt->bindValue(':id', $meeting->id, PDO::PARAM_STR);
            $stmt->execute();
            $point = $stmt->fetch();
            $meeting->points = (int)$point->count;
            if ($meeting->staff) $meeting->type = 'Staff ';
            if ($meeting->pd) $meeting->type = 'Police ';
            if ($meeting->ems) $meeting->type = 'EMS ';
            if ($meeting->slt) $meeting->type = 'SLT ';
        }
        return $meetings;
    }

    public static function fromID($id)
    {
        global $pdo;
        $stmt = $pdo->prepare('SELECT * FROM meetings WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_STR);
        $stmt->execute();
        $meeting = $stmt->fetch();
        $stmt = $pdo->prepare('SELECT COUNT(*) AS count FROM meeting_points WHERE meetingID = :id');
        $stmt->bindValue(':id', $meeting->id, PDO::PARAM_STR);
        $stmt->execute();
        $point = $stmt->fetch();
        $meeting->points = (int)$point->count;
        return $meeting;
    }
}
