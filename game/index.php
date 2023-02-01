<?php include "../head.php";
Guard::init()->RequireGameAccess();
?>
<div class="grid new" style="padding-left: 20px;">
    <h1 class="info-title new"><?= Config::$name; ?> Game Manager</h1>
    <div class="navCards game">
        <a href="players">
            <div class="navCard-small">
                <div class="navCard-items">
                    <p class="title">Players</p>
                    <p class="shortcontent">View Player Information</p>
                </div>
            </div>
        </a>
        <a href="manage">
            <div class="navCard-small">
                <div class="navCard-items">
                    <p class="title">Manage Server</p>
                    <p class="shortcontent">Manage The Game Server</p>
                </div>
            </div>
        </a>
    </div>
</div>