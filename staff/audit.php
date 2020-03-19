<?php include "../head.php";
Guard::init()->SLTRequired();
$getoffset = (isset($_GET['page'])) ? htmlspecialchars($_GET['page']) : 1;
if (intval($getoffset) < 1) {echo '<h2>Error, Reloading.</h2><meta http-equiv="refresh" content="0;url=/staff/audit?page=1">';exit;}
$offset = ($getoffset * 500) - 500;
$stmt = $pdo->prepare("SELECT * FROM audit_log ORDER BY id DESC LIMIT 500 OFFSET :off");
$stmt->bindValue(':off', $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll();
?>
<div class="grid new">
    <style>
        .log .content {
            color: #b3b3e9;
        }
    </style>
    <div class="grid__col grid__col--2-of-6" style="padding-left: 20px !important;">
        <h1 class="info-title new"><?=Config::$name;?>'s Audit Log <small style="font-size: 14px;float: right;">
                [Displaying <?= number_format(count($logs)); ?> Logs]
                Page <?=$getoffset;?>
                <?php if($getoffset > 1): ?><a href="?page=<?= $getoffset-1; ?>">&lt;</a><?php endif; ?>
                <?php if(count($logs) == 500): ?><a href="?page=<?= $getoffset+1; ?>">&gt;</a><?php endif; ?>
            </small></h1>
        <div id="staff" class="selectionPanel">
            <?php
            foreach($logs as $log) {
                $liu = "";
                if ($log->logged_in_user !== null) {
                    $logstaff = Helpers::IDToUsername($log->logged_in_user);
                    $liu = " ~ <a class='user' href='/staff/#User:{$log->logged_in_user}'>$logstaff</a>";
                }
                $logcontent = htmlspecialchars($log->log_content);
                echo "<div class='selectionTab log'><span class='log_timestamp'>[{$log->timestamp}]</span>{$liu}<div class='content'>{$logcontent}</div></div>";
            }
            if (count($logs) == 0) {echo "<h1 style='padding: 10px;'>No Logs Found For Page {$getoffset}</h1>";}
            ?>
        </div>
    </div>
    <div class="grid__col grid__col--4-of-6">
        <div class="infoPanelContainer">
            <div class="infoPanel" id="staff_info">
                <div class="pre_title">In Development</div>
            </div>
        </div>
    </div>
</div>