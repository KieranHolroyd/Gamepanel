<?php include "head.php";
Guard::init()->StaffRequired();
?>
<meta name="data-user-info" content='<?= json_encode($user->getInfoForFrontend()) ?>'>
<meta name="data-user-isMoreNeeded" content="<?= $user->needMoreInfo() ? "true" : "false" ?>">
<meta name="data-user-fieldsRequired" content='<?= json_encode($user->neededFields) ?>'>

<meta name="data-enabled-panel" content='<?= Config::$enableGamePanel ? "true" : "false" ?>'>
<meta name="data-org-name" content='<?= Config::$name ?>'>

<link rel="stylesheet" href="/app/dist/dashboard/index.css">
<script type="module" src="/app/dist/dashboard/index.js"></script>
<div id="app">
    <div style="padding: 20px 70px;"><img style="width: 24px;" src="/img/loadw.svg" alt="loading"> Loading...</div>
</div>
<?php if ($user->needMoreInfo()) : ?>
    <div class="modal" id="moreinfoneeded" style="display: block;">
        <button id="close">×</button>
        <div class="content open" style="max-width: 900px;border-radius: 5px;">
            <h2>Hold on a second,</h2>
            <p>We need some information about you</p><br>
            <?php
            if (in_array('region', $user->neededFields)) {
                echo "<div class='field'>
                        <div class='fieldTitle'>Your Region</div>
                        <select class='fieldSelector' id='userRegion'>
                            <option selected disabled>Choose A Global Region</option>
                            <option value='EU'>European Union</option>
                            <option value='NA'>North America</option>
                            <option value='SA'>South America</option>
                            <option value='AF'>Africa</option>
                            <option value='AS'>Asia</option>
                            <option value='AU'>Oceania</option>
                        </select></div>";
            }
            if (in_array('steamid', $user->neededFields)) {
                echo "<div class='field'>
                            <div class='fieldTitle'>Your Steam ID</div>
                            <input type='text' id='userSteamID' class='fieldInput' placeholder='Steam 64 ID'>
                        </div>";
            }
            if (in_array('discord_tag', $user->neededFields)) {
                echo "<div class='field'>
                            <div class='fieldTitle'>Your Discord Tag</div>
                            <input type='text' id='userDiscordTag' class='fieldInput' placeholder='Example: Kieran#1234'>
                    </div>";
            }
            ?>
            <button onclick="saveNeededInfo()" class="createPointBtn">Save information</button>
        </div>
    </div>
    <script>
        let needed = `<?= json_encode($user->neededFields); ?>`;

        function saveNeededInfo() {
            let needParse = JSON.parse(needed);
            if (needParse.indexOf('discord_tag') > -1) {
                apiclient.post(`/api/v2/staff/${userArray.info.id}/discord`, {
                    tag: $('#userDiscordTag').val()
                }).then(({
                    data
                }) => {
                    if (data.success) {
                        new Noty({
                            text: 'Discord saved.',
                            type: 'success'
                        }).show();
                    } else {
                        new Noty({
                            text: `Discord not saved. ${data.errors?.query?._errors?.map(e => e.message).join(', ') ?? "Unknown error"}`,
                            type: 'error',
                            timeout: 5000
                        }).show();
                    }
                });
            }
            if (needParse.indexOf('region') > -1) {
                apiclient.post(`/api/v2/staff/${userArray.info.id}/region`, {
                    region: $('#userRegion').val()
                }).then(({
                    data
                }) => {
                    if (data.success) {
                        new Noty({
                            text: 'Region saved.',
                            type: 'success'
                        }).show();
                    } else {
                        new Noty({
                            text: `Region not saved. ${data.errors.join(', ')}`,
                            type: 'error',
                            timeout: 5000
                        }).show();
                    }
                });
            }
            if (needParse.indexOf('steamid') > -1) {
                apiclient.post(`/api/v2/staff/${userArray.info.id}/steam`, {
                    uid: $('#userSteamID').val()
                }).then(({
                    data
                }) => {
                    if (data.success) {
                        new Noty({
                            text: 'Steam saved.',
                            type: 'success'
                        }).show();
                    } else {
                        new Noty({
                            text: `Steam not saved. ${data.errors.join(', ')}`,
                            type: 'error',
                            timeout: 5000
                        }).show();
                    }
                });
            }
        }
    </script>
<?php endif; ?>