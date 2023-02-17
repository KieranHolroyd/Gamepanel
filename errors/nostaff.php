<?php include "../head.php"; ?>

<style>
    body {
        overflow: hidden;
        padding: 0 0 !important;
    }

    .navSlider {
        top: 0;
    }
</style>
<div class="holdingpage">
    <h1 class="title">This is a little awkward...</h1>
    <p>You're not staff, So you can only access a few pages.</p>
    <button style="position: absolute; top: 0; right: 0;border-radius: .25em;" onclick="window.logout()">Logout</button>
    <a href="<?= Config::$forums_url; ?>">
        <button class="loginbtn"><?= Config::$name ?> Homepage</button>

    </a>
</div>