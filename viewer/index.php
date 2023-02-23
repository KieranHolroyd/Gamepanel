<?php
include "../head.php";
Guard::init()->SLTRequired();
?>
<meta name="data-pusher-id" content="<?= Config::$pusher['AUTH_KEY'] ?>">
<meta name="data-pusher-cluster" content="<?= Config::$pusher['DEFAULT_CONFIG']['cluster']; ?>">
<link rel="stylesheet" href="/app/dist/viewer/index.css">
<script type="module" src="/app/dist/viewer/index.js"></script>
<div id="app">
	<div style="padding: 20px 70px;"><img style="width: 24px;" src="/img/loadw.svg" alt="loading"> Loading...</div>
</div>