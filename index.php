<?php include "head.php";
Guard::init()->StaffRequired();
?>
<div class="dashboardOverlay" id="app" v-cloak>
    <div id="titleText" style="z-index:2;padding: 10px;">
        <h1 style="display:inline-block;"><?= $user->displayName(); ?> <small style="font-weight: 300;font-size: 15px;"><?= $user->info->rank; ?></small></h1>
        <h1 style="float:right;display:inline-block;" id="dtime">00:00:00 AM</h1>
    </div>
    <h4 style="position: fixed;bottom: 10px;right: 10px;"><?= Config::$name; ?> Dashboard</h4>
    <?php if (Config::$enableGamePanel) : ?>
        <h1 class="info-title new">Statistics</h1>
        <div id="staff" class="selectionPanel" style="height: auto !important;">
            <div class="stat">
                <p>Total Players</p>
                <span id="totalplayers">0000</span>
            </div>
            <div class="stat">
                <p>Total Police</p>
                <span id="totalcops">000</span>
            </div>
            <div class="stat">
                <p>Total Medics</p>
                <span id="totalmedics">000</span>
            </div>
            <div class="stat">
                <p>Server Balance</p>
                <span id="serverbalance">$000,000,000</span>
            </div>
            <div class="stat">
                <p>Rich List</p>
                <div id="rich_list">Loading</div>
            </div>
        </div>
    <?php endif; ?>
    <div id="staff_info" class="case_stats infoPanel" style="opacity: 0;transition: 200ms;">
        <div class="cool-graph daily-cases"><b>Daily Cases</b></div>
        <div class="cool-graph weekly-cases"><b>Weekly Cases</b></div>
    </div>
</div>
<script>
    function getGraphs() {
        apiclient.get("api/v2/statistics/cases/daily").then(({
            data: cases
        }) => {
            new Chartist.Line('.daily-cases', {
                labels: ['Four Days Ago', 'Three Days Ago', 'Two Days Ago', 'Yesterday', 'Today'],
                series: [
                    [cases.fourdays, cases.threedays, cases.twodays, cases.yesterday, cases.today]
                ]
            }, {
                chartPadding: {
                    right: 10,
                    top: 20
                },
                color: 'red'
            });
        }).catch(noty_catch_error)
        apiclient.get("api/v2/statistics/cases/weekly").then(({
            data: cases
        }) => {
            new Chartist.Line('.weekly-cases', {
                labels: ['A Month Ago', 'Three Weeks Ago', 'Two Weeks Ago', 'Last Week', 'This Week'],
                series: [
                    [cases.onemonth, cases.threeweeks, cases.twoweeks, cases.lastweek, cases.thisweek]
                ]
            }, {
                chartPadding: {
                    right: 10,
                    top: 20
                },
                color: 'red'
            });
            $('#staff_info').css('opacity', 1);
        }).catch(noty_catch_error)

    }

    function getStats() {
        apiclient.get('api/v2/statistics/game/server').then(({
            data
        }) => {
            if (data.code === 200) {
                $('#totalplayers').text(data.response.players.total);
                $('#totalcops').text(data.response.players.total_cops);
                $('#totalmedics').text(data.response.players.total_medics);
                $('#serverbalance').text(data.response.serverBalance.formatted);
                $('#rich_list').html('');
                for (let key in Object.keys(data.response.players.rich_list)) {
                    const user = data.response.players.rich_list[key];
                    const real_key = parseInt(key) + 1;
                    $('#rich_list').append(`<div class="richListPlayer">Number ${real_key}: <a href="/game/players?query=${user.name}">${user.name}</a> ~ ${user.bankacc}</div>`);
                }
            }
        })
    }
    $('#dtime').text(currentTime());
    setInterval(() => {
        $('#dtime').text(currentTime());
    }, 1000);
    getStats();
    getGraphs();

    function selectBG(bg, custom) {
        if (!custom) {
            Cookies.set('cbg', '<?= Config::$base_url; ?>img/bg' + bg + '.png', {
                expires: 720
            });
            $('#selectBG' + bg).text('[SELECTED]');
            $('body').css('background-image', 'url("img/bg' + bg + '.png")');
        }
    }

    function setCustomBackground() {
        let cimg = $('#cimg').val();
        Cookies.set('cbg', cimg, {
            expires: 720
        });
        $('body').css('background-image', 'url("' + cimg + '")');
    }
    let vm = new Vue({
        el: '#app',
        data: {
            user: {
                info: {}
            },
            loaded: false
        }
    });
    apiclient.get(`api/v2/user/me_new`).then(({
        data
    }) => {
        if (data.success) {
            vm.user = data.user;
            vm.loaded = true;
        }
    }).catch(noty_catch_error);

    function userArrayLoaded() {
        return false;
    }
</script>
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
                console.log(userArray.info.id);
                $.post('/api/v1/saveStaffDiscordTag', {
                    tag: $('#userDiscordTag').val(),
                    id: userArray.info.id
                }, data => {
                    new Noty({
                        text: 'Saved Discord Tag, Once All Tasks Complete, Reload The Page.',
                        type: 'success'
                    }).show();
                });
            }
            if (needParse.indexOf('region') > -1) {
                console.log(userArray.info.id);
                $.post('/api/v1/saveStaffRegion', {
                    region: $('#userRegion').val(),
                    id: userArray.info.id
                }, data => {
                    new Noty({
                        text: 'Saved Region, Once All Tasks Complete, Reload The Page.',
                        type: 'success'
                    }).show();
                });
            }
            if (needParse.indexOf('steamid') > -1) {
                console.log(userArray.info.id);
                $.post('/api/v1/saveStaffUID', {
                    uid: $('#userSteamID').val(),
                    id: userArray.info.id
                }, data => {
                    new Noty({
                        text: 'Saved SteamID, Once All Tasks Complete, Reload The Page.',
                        type: 'success'
                    }).show();
                });
            }
        }
    </script>
<?php endif; ?>
<div class="modal" id="selectBG">
    <button id="close">×</button>
    <div class="content" style="max-width: 900px;border-radius: 5px;">
        <h2>Choose A Background Image</h2>
        <p>Background 1 (Default) <span id="selectBG1" style="cursor:pointer;" onclick="selectBG(1, false)"><?php if ($_COOKIE['bg'] === "1") {
                                                                                                                echo "[SELECTED]";
                                                                                                            } else {
                                                                                                                echo "[SELECT]";
                                                                                                            } ?></span></p>
        <img src="https://cdn.discordapp.com/attachments/528343271840153620/528474876739190793/wallpaper_1.jpg" onclick="selectBG(3, false)" style="border-radius: 5px;box-shadow: 0 0 5px 0 rgba(0,0,0,0.3);margin:5px;width: calc(100% - 10px);" alt="Background 1 (Default)" title="Background 1 (Default)">
        <p>Background 2 <span id="selectBG2" style="cursor:pointer;" onclick="selectBG(2, false)"><?php if ($_COOKIE['bg'] === "2") {
                                                                                                        echo "[SELECTED]";
                                                                                                    } else {
                                                                                                        echo "[SELECT]";
                                                                                                    } ?></span></p>
        <img src="/img/bg2.png" onclick="selectBG(2, false)" style="border-radius: 5px;box-shadow: 0 0 5px 0 rgba(0,0,0,0.3);margin:5px;width: calc(100% - 10px);" alt="Background 2" title="Background 2">
        <p>Have Your Own Background? [E.G. Imgur/gyazo links] <?php if (isset($_COOKIE['cbg'])) {
                                                                    echo "[SELECTED]";
                                                                } ?></p>
        <div class="field"><input class="fieldInput" style="background-color: #222;margin-top: 10px;" id="cimg" type="text" onkeyup="setCustomBackground();" placeholder="Your Link..." <?php if (isset($_COOKIE['cbg'])) {
                                                                                                                                                                                            echo "value='" . htmlspecialchars(strip_tags($_COOKIE['cbg'])) . "'";
                                                                                                                                                                                        } ?>></div>
        <button type="button" style="margin-top: 10px;" class="newsubmitBtn" onclick="setCustomBackground();">Set Custom
            Image
        </button>
    </div>
</div>

</html>