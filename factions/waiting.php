<?php include "../head.php";
Guard::init()->CommandRequired();
?>
<div class="grid new">
    <div class="grid__col grid__col--6-of-6" style="padding-left: 20px !important;">
        <h1 class="info-title new"><?= Config::$name; ?> Factions Waiting List</h1>
        <div id="staff" class="selectionPanel">
            <?php foreach ($pdo->query("SELECT * FROM users WHERE isPD = 0 AND isEMS = 0 AND isStaff = 0") as $u): ?>
                <div onclick="window.location.href = '/factions/manage#player:<?= $u->id;?>';" class="selectionTab">
                    <span style="font-size: 25px;"><?= $u->first_name . ' ' . $u->last_name; ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>