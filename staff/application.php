<?php

include '../head.php';
//foreach($pdo->query('SELECT * FROM case_logs') as $case) {
//    foreach(json_decode($case->players) as $player) {
//        $stmt = $pdo->prepare("INSERT INTO case_players (case_id, type, name, guid) VALUES (:id, :type, :nm, :guid)");
//        $stmt->bindValue(":id", $case->id);
//        $stmt->bindValue(":type", $player->type);
//        $stmt->bindValue(":nm", $player->name);
//        $stmt->bindValue(":guid", $player->guid);
//        if(!$stmt->execute()) {
//            print_r($stmt->errorInfo());
//        }
//    }
//}