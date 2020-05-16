<?php include "head.php";
Guard::init()->StaffRequired();
?>
    <div class="grid new">
        <div class="grid__col grid__col--2-of-6" style="padding-left: 20px !important;">
            <h1 class="info-title new">My Activity</h1>
            <style>
                .options {
                    padding: 0 25px;
                }

                .options div {
                    padding: 5px 12px;
                    display: inline-block;
                    cursor: pointer;
                    border-bottom: 3px solid #0000;
                    transition: 200ms;
                }

                .options div:hover {
                    border-bottom: #3c3b62 3px solid;
                }

                .options div.active {
                    box-shadow: 0 0 8px -3px #0004;
                    border-bottom: #4e4c79 3px solid;
                }
            </style>
            <div class="options">
                <div id="cv-activity" onclick="changeView('activity')" class="active">My Activity</div>
                <div id="cv-punishments" onclick="changeView('punishments')">My Punishments</div>
                <div id="cv-bans" onclick="changeView('bans')">My Bans</div>
                <div id="cv-audit" onclick="changeView('audit')">My Audit Log</div>
            </div>
            <div id="reports" style='height: calc(100vh - 100px) !important;' class="selectionPanel">
                <img src="/img/loadw.svg">
            </div>
        </div>
        <div class="grid__col grid__col--4-of-6">
            <div class="infoPanelContainer">
                <div id="case_info" class="infoPanel">
                    <h1><?= $user->info->username; ?>'s Profile</h1>
                    <img src="/img/loadw.svg">
                </div>
            </div>
        </div>
    </div>
    <script>
        let player_punished, player_banned, moreinfo, setMoreInfo;
        let players_involved, playersArray, player_title;
        let currentView = 'activity';

        $(document).ready(() => {
            checkHashString(window.location.hash);
        });
        window.onpopstate = () => {
            checkHashString(window.location.hash);
        };

        google.charts.load('current', {'packages': ['line']});

        function checkHashString(str) {
            let hash = ['default'];
            hash = str.substring(1).split(':');
            console.log(hash);
            switch (hash[0]) {
                case "case":
                    getCase(hash[1]);
                    break;
                default:
                    google.charts.setOnLoadCallback(getMoreInfo);
                    break;
            }
        }

        function changeView(view) {
            $(`#cv-${currentView}`).removeClass('active');
            currentView = view;
            switch (view) {
                case 'activity':
                    getStaffActivity();
                    break;
                case 'punishments':
                    getStaffPunishments();
                    break;
                case 'bans':
                    getStaffBans();
                    break;
                case 'audit':
                    getStaffAudit();
                    break;
            }
            $(`#cv-${view}`).addClass('active');
        }

        function getStaffBans() {
            $('#reports').html('<img src="/img/loadw.svg">');
            let other_staff;
            let other_staff_text;
            $.post('api/getMyActivity', {field: 'bans'}, function (data) {
                let activity = "";
                let res = JSON.parse(data);
                if (res.code === 200) {
                    moreinfo = res.response;
                    if (!$.isEmptyObject(moreinfo.punishment)) {
                        for (let i = 1; i < Object.keys(moreinfo.punishment).length + 1; i++) {
                            const p = moreinfo.punishment[i];
                            let banLength = (p.length < 1) ? 'Permanent Ban' : `${p.length} Days`;
                            activity += `<div class="selectionTab" onclick="getCase(${p.case_id})">${p.id} - ${p.player}<br>${banLength} ~ ${p.message}</div>`
                        }
                        setMoreInfo = `<h1>${name}</h1><div>${activity}</div>`;
                        $('#reports').html(setMoreInfo);
                    } else {
                        $('#reports').html("<h2 style='padding: 15px'>You Haven't Submitted Any Cases Yet.</h2>");
                    }
                } else {
                    $('#reports').html(`<h2 style='padding: 15px'>${res.message}</h2>`);
                }
            });
        }

        function getStaffAudit() {
            $('#reports').html('<img src="/img/loadw.svg">');
            $.get(`/api/staffAuditLogs?id=<?=$user->info->id;?>`, data => {
                data = JSON.parse(data);
                if (data.code === 200) {
                    let audit = '';
                    for (let i = 0; i < data.response.length; i++) {
                        const l = data.response[i];

                        audit += `<div class="selectionTab"><span style="text-transform: capitalize;">${l.log_context} Log</span> ~ ${l.timestamp}<br>${l.log_content}</div>`;
                    }
                    $('#reports').html(audit);
                } else {
                    $('#reports').html(`<p><b>Error </b>${res.message}</p>`);
                }
            });
        }

        function getStaffPunishments() {
            $('#reports').html('<img src="/img/loadw.svg">');
            let other_staff;
            let other_staff_text;
            $.post('api/getMyActivity', {field: 'punishments'}, function (data) {
                let activity = "";
                let res = JSON.parse(data);
                if (res.code === 200) {
                    moreinfo = res.response;
                    if (!$.isEmptyObject(moreinfo.punishment)) {
                        for (let i = 1; i < Object.keys(moreinfo.punishment).length + 1; i++) {
                            const p = moreinfo.punishment[i];
                            activity += `<div class="selectionTab" onclick="getCase(${p.case_id})">${p.id} - ${p.player}<br>${p.comments}</div>`
                        }
                        setMoreInfo = `<h1>${name}</h1><div>${activity}</div>`;
                        $('#reports').html(setMoreInfo);
                    } else {
                        $('#reports').html("<h2 style='padding: 15px'>You Haven't Submitted Any Cases Yet.</h2>");
                    }
                } else {
                    $('#reports').html(`<h2 style='padding: 15px'>${res.message}</h2>`);
                }
            });
        }

        function getStaffActivity() {
            $('#reports').html('<img src="/img/loadw.svg">');
            let other_staff;
            let other_staff_text;
            $.post('api/getMyActivity', {field: 'activity'}, function (data) {
                let activity = "";
                let res = JSON.parse(data);
                if (res.code === 200) {
                    moreinfo = res.response;
                    if (!$.isEmptyObject(moreinfo.log)) {
                        for (let i = 1; i < Object.keys(moreinfo.log).length + 1; i++) {
                            other_staff = "";
                            other_staff_text = "";
                            let reporting_player = "";
                            let reporting_player_name = "";
                            if (moreinfo.log[i].reporting_player !== "[]" && moreinfo.log[i].reporting_player !== "" && moreinfo.log[i].reporting_player !== null && moreinfo.log[i].reporting_player !== "null") {
                                reporting_player = moreinfo.log[i].reporting_player;
                                reporting_player_name = reporting_player[0].name;
                            } else {
                                reporting_player_name = "undefined";
                            }
                            if (moreinfo.log[i].other_staff === true) {
                                other_staff = "other_staff";
                                other_staff_text = " (Assisting)";
                            }
                            activity += `<div class="selectionTab ${other_staff}" onclick="getCase(${moreinfo.log[i].id})">${moreinfo.log[i].id} - ${reporting_player_name}${other_staff_text}<br>${moreinfo.log[i].doe}</div>`
                        }
                        setMoreInfo = `<h1>${name}</h1><div>${activity}</div>`;
                        $('#reports').html(setMoreInfo);
                    } else {
                        $('#reports').html("<h2 style='padding: 15px'>You Haven't Submitted Any Cases Yet.</h2>");
                    }
                } else {
                    $('#reports').html(`<h2 style='padding: 15px'>${res.message}</h2>`);
                }
            });
        }

        function getCase(id) {
            $('#case_info').html("<img src='/img/loadw.svg'>");
            players_involved = "";
            playersArray = "";
            player_title = "";
            $.post('api/getMoreInfo', {id: id}, function (data) {
                let res = JSON.parse(data);
                if (res.code === 200) {
                    moreinfo = res.response;
                    if (moreinfo.report.players !== "[]" && moreinfo.report.players !== "") {
                        for (let player of moreinfo.report.players) {
                            players_involved += `${player.type}: ${player.name} (${player.guid})<br>`;
                        }
                        player_title = moreinfo.report.players[0].name;
                    } else {
                        players_involved = "None";
                        player_title = moreinfo.report.lead_staff;
                    }

                    let punishments = ``;

                    for (let p of moreinfo.report.punishments) {
                        punishments += p.html;
                    }

                    let bans = ``;

                    for (let p of moreinfo.report.bans) {
                        bans += p.html;
                    }

                    setMoreInfo = `<p><a style="cursor: pointer;" onclick="getMoreInfo();"><i style="color: inherit;" class="fas fa-chevron-left"></i> Back</a></p><h2><span>Case ID:</span> ${moreinfo.report.id}-${player_title}</h2><p id="case"><span>Lead Staff:</span> ${moreinfo.report.lead_staff}</p><p id="case"><span>Other Staff:</span> ${moreinfo.report.other_staff}</p><p id="case"><span>Type Of Report:</span><br> ${moreinfo.report.typeofreport}</p><p id="case" style="text-transform: capitalize;"><span>Players Involved:</span><br> ${players_involved}</p><p id="case"><span>Description Of Events:</span><br> ${linkify(moreinfo.report.doe)}</p><p id="case"><span>Timestamp:</span> ${moreinfo.report.timestamp}</p>${linkify(punishments)}${linkify(bans)}`;
                    $('#case_info').html(setMoreInfo);
                } else {
                    $('#case_info').html("Error Fetching Case");
                }
            });
        }

        function getMoreInfo() {
            let actwarn_start = "";
            let actwarn_end = "";
            $.post('api/getMyInfo', {}, function (data) {
                let res = JSON.parse(data);
                if (res.code === 200) {
                    moreinfo = res.response;

                    if (moreinfo.activity_warning === true) {
                        actwarn_start = "<span style='color: orange;' title='Activity Warning'>";
                        actwarn_end = "</span>";
                    }
                    setMoreInfo = `<h1>${moreinfo.name}'s Profile</h1><p>You're A Team ${moreinfo.team} ${moreinfo.rank}</p><p>You've Completed ${moreinfo.casecount} Total Cases</p> <p>${actwarn_start}${moreinfo.casecount_week} Of Those Were Logged This week${actwarn_end}</p> <div id='activityGraph'></div>`;

                    let GraphData = Object.keys(moreinfo.activityGraph).map(i => {
                        return [i, moreinfo.activityGraph[i]]
                    });

                    let data = new google.visualization.DataTable();
                    data.addColumn('string', 'Timestamp');
                    data.addColumn('number', 'Cases');

                    data.addRows([
                        GraphData[6],
                        GraphData[5],
                        GraphData[4],
                        GraphData[3],
                        GraphData[2],
                        GraphData[1],
                        GraphData[0],
                    ]);

                    let options = {
                        chart: {
                            title: 'Staff Activity Across The Week',
                            subtitle: 'Daily',
                        },
                        curveType: 'function',
                        backgroundColor: '#3c3b62',
                        legend: {position: 'bottom'}
                    };

                    $('#case_info').html(setMoreInfo);

                    let chart = new google.charts.Line(document.getElementById('activityGraph'));

                    chart.draw(data, google.charts.Line.convertOptions(options));
                } else {
                    $('#case_info').html("Error Fetching User");
                }
            });
        }

        getStaffActivity();
    </script>