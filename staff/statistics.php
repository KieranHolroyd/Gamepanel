<?php include "../head.php";
?>
    <div class="grid new">
        <div class="grid__col grid__col--2-of-6" style="padding-left: 20px !important;">
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
        </div>
        <div class="grid__col grid__col--4-of-6">
            <div class="infoPanelContainer">
                <div id="staff_info" class="infoPanel">
                    <div class="cool-graph daily-cases"><b>Daily Cases</b></div>
                    <div class="cool-graph weekly-cases"><b>Weekly Cases</b></div>
                </div>
            </div>
        </div>
    </div>
    <script>
        $(document).ready(function () {
            getGraphs();
            getStats();
        });

        function getStats() {
            $.get('/api/serverStats', data => {
                data = JSON.parse(data);
                if (data.code === 200) {
                    $('#totalplayers').text(data.response.players.total);
                    $('#totalcops').text(data.response.players.total_cops);
                    $('#totalmedics').text(data.response.players.total_medics);
                    $('#serverbalance').text(data.response.serverBalance.formatted);
                    $('#rich_list').html('');
                    for(let key in Object.keys(data.response.players.rich_list)) {
                        const user = data.response.players.rich_list[key];
                        const real_key = parseInt(key)+1;
                        $('#rich_list').append(`<div class="richListPlayer">Number ${real_key}: <a href="/game/players?query=${user.name}">${user.name}</a> ~ ${user.bankacc}</div>`);
                    }
                }
            })
        }

        function getGraphs() {
            $.get('/api/dailyCases', function (data) {
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
            $.get('/api/weeklyCases', function (data) {
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
    </script>
    <style>
        .ct-label {
            color: #fff;
            font-weight: 300 !important;
            font-size: 10px !important;
        }

        .ct-line, .ct-point {
            stroke: #fff !important;
        }
    </style>