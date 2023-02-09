<?php
include "../head.php";
include "../classes/Meetings.php";
Guard::init()->LoginRequired();
$search_query = isset($_GET["query"]) ? $_GET["query"] : "";
$search_type = isset($_GET["type"]) ? $_GET["type"] : "";
?>
<meta name="data-search-query" content="<?= $search_query ?>">
<meta name="data-search-type" content="<?= $search_type ?>">
<script type="module" src="./dist/bundle.js"></script>
<div id="app">
    <div style="padding: 20px 70px;"><img style="width: 24px;" src="/img/loadw.svg" alt="loading"> Loading...</div>
</div>