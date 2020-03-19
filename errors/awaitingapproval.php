<?php $nonav = 0;
include "../head.php";
if ($user->error) {
    echo "<script>window.location.href = '/passport';</script>";
}
?>
<style>
    body {
        overflow: hidden;
        padding: 10px 10px;
    }
</style>
<div class="holdingpage">
    <h1 class="title">Arma-Life Roleplay Staff</h1>
    <p>Your account has not yet been approved for use by the SLT/SMT team, please wait for someone to issue a rank on
        this panel before you can use your account.</p>
    <a href="/">
        <button class="loginbtn">Retry</button>
    </a>
</div>