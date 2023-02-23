<?php
include "./head.php";
Guard::init()->StaffRequired();
?>
<!-- <meta name="data-pusher-id" content="<?= Config::$pusher['AUTH_KEY'] ?>">
<meta name="data-pusher-cluster" content="<?= Config::$pusher['DEFAULT_CONFIG']['cluster']; ?>"> -->
<link rel="stylesheet" href="/app/dist/profile/index.css">
<script type="module" src="/app/dist/profile/index.js"></script>
<div id="app">
	<div style="padding: 20px 70px;"><img style="width: 24px;" src="/img/loadw.svg" alt="loading"> Loading...</div>
</div>