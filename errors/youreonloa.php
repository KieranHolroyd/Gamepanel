<?php $nonav = 0;
include "../head.php"; ?>
    <style>
        body {
            overflow: hidden;
            padding: 10px 10px;
        }
    </style>
    <div class="holdingpage">
        <h1 class="title">Arma-Life Roleplay Staff</h1>
        <p>Your account is activated but you are on <abbr title="Leave Of Absence">LOA</abbr> and can't access the staff
            panel until <?= $user->info->loa; ?>.</p>
        <a href="https://arma-life.com">
            <button class="loginbtn">Arma-Life Homepage</button>
        </a>
    </div>
<?php include "footer.php"; ?>