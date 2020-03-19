<?php

require 'db.php';
require 'classes/Helpers.php';

//$stmt = $pdo->prepare('ALTER TABLE users ADD notes text');
//
//echo $stmt->execute();
//
//$stmt = $pdo->prepare('SELECT * FROM case_players ORDER BY case_id');
//$stmt->execute();
//
//$i = 0;
//foreach ($stmt->fetchAll() as $player) {
//    if (($player->case_id - 1) > $i) $emptyplayer[($player->case_id - 1)] = true;
//    $i = $player->case_id;
//}
//
//foreach ($emptyplayer as $key => $player) {
//    echo $key . PHP_EOL;
//    Helpers::fixPlayersForCase($key);
//}

//Helpers::fixPlayersForCase(6970);

//$stmt = $pdo->prepare('SELECT * FROM case_logs');
//$stmt->execute();
//$cases = $stmt->fetchAll(PDO::FETCH_OBJ);
//
//foreach($cases as $case) {
//    if ($case->points_awarded !== null) {
//        $stmt = $pdo->prepare("INSERT INTO punishment_reports (case_id, points, rules, comments, player) VALUES (:id, :p, :r, :c, :pl)");
//        $stmt->bindValue(':id', $case->id, PDO::PARAM_STR);
//        $stmt->bindValue(':p', $case->amount_of_points, PDO::PARAM_STR);
//        $stmt->bindValue(':r', $case->offence_committed, PDO::PARAM_STR);
//        $stmt->bindValue(':c', $case->evidence_supplied, PDO::PARAM_STR);
//        $stmt->bindValue(':pl', 'First Reported Player', PDO::PARAM_STR);
//        $stmt->execute();
//    }
//    if ($case->ban_awarded !== null) {
//        $ban_length = (intval($case->ban_length) > 0) ? $case->ban_length : 0;
//        $stmt = $pdo->prepare("INSERT INTO ban_reports (case_id, length, message, teamspeak, ingame, website, permenant, player) VALUES (:id, :l, :m, :t, :i, :w, :p, :pl)");
//        $stmt->bindValue(':id', $case->id, PDO::PARAM_STR);
//        $stmt->bindValue(':l', $case->ban_length, PDO::PARAM_STR);
//        $stmt->bindValue(':m', $case->ban_message, PDO::PARAM_STR);
//        $stmt->bindValue(':t', $case->ts_ban, PDO::PARAM_STR);
//        $stmt->bindValue(':i', $case->ingame_ban, PDO::PARAM_STR);
//        $stmt->bindValue(':w', $case->website_ban, PDO::PARAM_STR);
//        $stmt->bindValue(':p', $case->ban_perm, PDO::PARAM_STR);
//        $stmt->bindValue(':pl', 'First Reported Player', PDO::PARAM_STR);
//        $stmt->execute();
//    }
//}