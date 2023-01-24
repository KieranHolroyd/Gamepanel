<?php $nonav = 0;
include "head.php";
Guard::init()->StaffRequired();
?>
<?php
$custom = (isset($_COOKIE['cbg']) && !empty($_COOKIE['cbg'])) ? $_COOKIE['cbg'] : false;
if (!$custom) {
    echo "<body style=\"background-size: stretch;background: url('https://cdn.discordapp.com/attachments/528343271840153620/528474876739190793/wallpaper_1.jpg') no-repeat fixed center center;\">";
} else {
    echo "<body style=\"background-size: stretch;background: url('" . htmlspecialchars(strip_tags($_COOKIE['cbg'])) . "'\">";
} ?>
<div class="dashboardOverlay" id="app" v-cloak>
    <div id="titleText" style="z-index:2;" v-if="loaded">
        <h1 style="padding: 10px;display:inline-block;">{{ user.firstName }} {{ user.lastName }} <small style="font-weight: 300;font-size: 15px;">{{ user.rank }}</small></h1>
        <h1 style="padding: 10px;float:right;display:inline-block;" id="dtime">00:00:00 AM</h1>
    </div>
    <h4 style="position: fixed;bottom: 10px;left: 10px;"><?= Config::$name; ?> Staff</h4>
    <div class="widgets">
        <div class="widget quickLinks">
            <h2>Quick Links</h2>
            <a v-tippy href="logger" title="Case Logger"><i class="fas fa-clipboard"></i></a>
            <a v-tippy href="policies" title="Staff Policies"><i class="fas fa-book"></i></a>
            <a v-tippy href="me" title="My Profile"><i class="fas fa-address-card"></i></a>
            <a v-tippy href="../../Before/Purple-Iron-Bulldog/meetings" title="Meetings"><i class="fas fa-calendar-alt"></i></a>
            <a v-tippy onclick="openOverlay('#messages');" title="Staff Chat"><i class="fas fa-comment-alt"></i></a>
            <a v-tippy href="viewer" v-if="this.user.isSLT == 1" title="View Cases"><i class="fas fa-eye"></i></a>
            <a v-tippy href="search?type=cases" v-if="this.user.isSLT == 1" title="Search Cases"><i class="fas fa-search"></i></a>
            <a v-tippy href="../../Before/Purple-Iron-Bulldog/staff" v-if="this.user.isSLT == 1" title="Manage Staff"><i class="fas fa-clipboard-list"></i></a>
            <a v-tippy href="staff/interviews" v-if="this.user.isSLT == 1" title="Staff Interviews"><i class="fas fa-microphone"></i></a>
            <a v-tippy href="staff/overview" v-if="this.user.isSLT == 1" title="Staff Overview"><i class="fas fa-info-circle"></i></a>
            <a v-tippy href="../../Before/Purple-Iron-Bulldog/game" v-if="this.user.rankLevel <= 8" title="Game Panel"><i class="fas fa-server"></i></a>
            <a v-tippy href="logs" v-if="this.user.isDeveloper == 1" title="Server Logs"><i class="fas fa-scroll"></i></a>
            <a v-tippy href="staff/audit" v-if="this.user.isSLT == 1" title="Audit Logs"><i class="fas fa-list-alt"></i></a>
            <a v-tippy href="staff/statistics" title="Statistics"><i class="fas fa-chart-line"></i></a>
            <a v-tippy id="modalLaunch" launch="selectBG" style="cursor: pointer;" title="Dashboard Settings"><i class="fas fa-cog"></i></a>
<!--            <a id="modalLaunch" launch="recentUpdates" style="cursor: pointer;" title="Case Logger"><i class="fas fa-home"></i></a>-->
        </div>
    </div>
    <?php if (Config::$enableGamePanel): ?>
        <h1 class="info-title new">Statistics</h1>
        <div id="staff" class="selectionPanel">
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
    <div id="staff_info" class="case_stats infoPanel">
        <div class="cool-graph daily-cases"><b>Daily Cases</b></div>
        <div class="cool-graph weekly-cases"><b>Weekly Cases</b></div>
    </div>
</div>
    <script>
        function getGraphs() {
            $.get('/api/v1/dailyCases', function (data) {
                let cases = JSON.parse(data);
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
            });
            $.get('/api/v1/weeklyCases', function (data) {
                let cases = JSON.parse(data);
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
            });
        }
        function getStats() {
            $.get('/api/v1/serverStats', data => {
                data = JSON.parse(data);
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
        getStats();getGraphs();
        function selectBG(bg, custom) {
            if(!custom){
                Cookies.set('cbg', 'https://staff.arma-life.com/img/bg'+bg+'.png', { expires: 720 });
                $('#selectBG'+bg).text('[SELECTED]');
                $('body').css('background-image', 'url("img/bg'+bg+'.png")');
            }
        }
        function setCustomBackground() {
            let cimg = $('#cimg').val();
            Cookies.set('cbg', cimg, { expires: 720 });
            $('body').css('background-image', 'url("'+cimg+'")');
        }
        let vm = new Vue({
            el: '#app',
            data: {
                user: {info: {}},
                loaded: false
            }
        });
        $.get("<?php echo $url; ?>api/v1/getUserInfoNew", function(data){
            vm.user=JSON.parse(data).response;
            vm.loaded = true;
        });
        function userArrayLoaded() {
            return false;
        }

    </script>
</body>
<?php if ($user->needMoreInfo()): ?>
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
                            <option value='AU'>Oceania</option>
                        </select></div>";
                }
                if (in_array('steamid', $user->neededFields)) {
                    echo "<div class='field'>
                            <div class='fieldTitle'>Your Steam ID</div>
                            <input type='text' id='userSteamID' class='fieldInput' placeholder='Steam 64 ID'>
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
        <p>Background 1 (Default) <span id="selectBG1" style="cursor:pointer;"
                                        onclick="selectBG(1, false)"><?php if ($_COOKIE['bg'] === "1") {
                                            echo "[SELECTED]";
                                        } else {
                                            echo "[SELECT]";
                                        } ?></span></p>
        <img src="https://cdn.discordapp.com/attachments/528343271840153620/528474876739190793/wallpaper_1.jpg" onclick="selectBG(3, false)"
             style="border-radius: 5px;box-shadow: 0 0 5px 0 rgba(0,0,0,0.3);margin:5px;width: calc(100% - 10px);"
             alt="Background 1 (Default)" title="Background 1 (Default)">
        <p>Background 2 <span id="selectBG2" style="cursor:pointer;"
                              onclick="selectBG(2, false)"><?php if ($_COOKIE['bg'] === "2") {
                                  echo "[SELECTED]";
                              } else {
                                  echo "[SELECT]";
                              } ?></span></p>
        <img src="../../Before/Purple-Iron-Bulldog/img/bg2.png" onclick="selectBG(2, false)"
             style="border-radius: 5px;box-shadow: 0 0 5px 0 rgba(0,0,0,0.3);margin:5px;width: calc(100% - 10px);"
             alt="Background 2" title="Background 2">
        <p>Have Your Own Background? [E.G. Imgur/gyazo links] <?php if (isset($_COOKIE['cbg'])) {
            echo "[SELECTED]";
        } ?></p>
        <div class="field"><input class="fieldInput" style="background-color: #222;margin-top: 10px;" id="cimg"
                                  type="text" onkeyup="setCustomBackground();"
                                  placeholder="Your Link..." <?php if (isset($_COOKIE['cbg'])) {
                                      echo "value='" . htmlspecialchars(strip_tags($_COOKIE['cbg'])) . "'";
                                  } ?>></div>
        <button type="button" style="margin-top: 10px;" class="newsubmitBtn" onclick="setCustomBackground();">Set Custom
            Image
        </button>
    </div>
</div>
<div class="modal" id="recentUpdates">
    <button id="close">×</button>
    <div class="content" style="max-width: 900px;border-radius: 5px;">
        <h2>Recent Platform Updates</h2>
        <label>11/12/2018</label>
        <ul>
            <li>Added Live Server Logs (Admin+ Only)</li>
        </ul>
        <label>02/12/2018</label>
        <ul>
            <li>Updated Search (Added Punishment Reports & Unban Reports)</li>
            <li>Update Logger (Add Points Fixed, Automatic Punishment & Ban Reporting)</li>
        </ul>
        <label>26/11/2018</label>
        <ul>
            <li>Changed the design of the menu (now in top right).</li>
            <li>Added more stuff to the staff manager.</li>
        </ul>
        <label>21/11/2018</label>
        <ul>
            <li>Permalinks are now always present in the Staff Manager page.</li>
            <li>You can now access the navigation menu with right click anywhere.</li>
        </ul>
        <label>20/11/2018</label>
        <ul>
            <li>Added Staff Group Chat.</li>
            <li>Case viewer updates with new cases in real-time.</li>
            <li>Updated the dashboard grid on the homepage.</li>
        </ul>
    </div>
</div>
<!--Created By Kieran Holroyd-->
</html>