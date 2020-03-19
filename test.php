<?php

include_once $_SERVER['DOCUMENT_ROOT'] . '/classes/User.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';

//$gpdo = game_pdo();
//
//foreach ($gpdo->query('SELECT * FROM `players`') as $p) {
//    $bank = ($p->bankacc / 2 < 50000) ? 50000 : $p->bankacc / 2;
//
//    $stmt = $gpdo->prepare('UPDATE `players` SET cash = 0, bankacc = :b WHERE uid = :id');
//    $stmt->bindValue(':b', $bank, PDO::PARAM_INT);
//    $stmt->bindValue(':id', $p->uid, PDO::PARAM_INT);
//    $stmt->execute();
//}