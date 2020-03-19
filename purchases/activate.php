<?php
$nonav = true;
include "../head.php";
$lk = (isset($_GET['lk'])) ? htmlspecialchars($_GET['lk']) : '';
?>
<?php if ($user->verified(false)) {
    include_once '../newnav.php';
} ?>
<div class="grid new">
    <div class="infoPanelContainer">
        <div class="infoPanel" id="staff_info">
            <h1>Activate Your Purchase</h1>
            <div class="field">
                <div class="fieldTitle">License Key</div>
                <input class="fieldInput" type="text" id="lk" placeholder="Enter License Key" value="<?=$lk;?>">
            </div>
            <div class="btnGroup">
                <button onclick="activate()">Activate</button>
            </div>
        </div>
    </div>
</div>
<script>
    function activate() {
        const lk = $('#lk').val();
        $.post('/api/activatePurchase', {
            license: lk
        }, data => {
            data = JSON.parse(data);
            if (data.code === 200) {
                new Noty({
                    type: 'success',
                    text: 'Activated Reserved Slot Successfully',
                    timeout: 10000
                }).show();
            } else {
                new Noty({
                    type: 'error',
                    text: data.message,
                    timeout: 10000
                }).show();
            }
        })
    }
</script>