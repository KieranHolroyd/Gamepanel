<?php include "../head.php";
Guard::init()->SLTRequired();
?>
<div class="grid new">
    <div class="grid__col grid__col--2-of-6" style="padding-left: 20px !important;">
        <h1 class="info-title new"><?= Config::$name; ?> Team <i onclick="openSearchBox();" style="float: right;" class="fas fa-search"></i></h1>
        <div class="field" id="searchStaffMembers" style="display: none;">
            <input class="fieldInput" type="text" onkeyup="initSearch(event)" placeholder="Search Staff (Case Sensitive)">
        </div>
        <div id="staff" class="selectionPanel">
            <img src="/img/loadw.svg" alt="Loading...">
        </div>
    </div>
    <div class="grid__col grid__col--4-of-6">
        <div class="infoPanelContainer">
            <div id="staff_info" class="infoPanel">
                <div class="pre_title">Select User For Details</div>
            </div>
        </div>
    </div>
</div>
<style>
    .inline-button {
        display: inline-block;
        margin: 0 0 0 10px;
    }
</style>
<script>
    let offset = 0;
    let staff;
    let list = "";
    let activity = "";
    let player_punished, player_banned, moreinfo, setMoreInfo;
    let players_involved, playersArray, player_title;
    let staffbgc = "";
    let stafftextc = "";
    let changeNameID = null;
    let currentlyOpenTab = "";

    function openChangeName(id, name) {
        changeNameID = id;
        $('#cn' + id).html(`<input type="text" style="width: auto;" id="newname" value="${name}"><button class="inline-button" onclick="changeName()">Change Name</button>`)
    }

    function changeName() {
        $.post('/api/v1/changeStaffName', {
            newName: $('#newname').val(),
            id: changeNameID
        }, data => {
            data = JSON.parse(data);
            console.log(data);
            if (data.code === 200) {
                getMoreInfo(changeNameID);
            } else {
                new Noty({
                    text: data.message,
                    timeout: 4000,
                    type: 'error'
                }).show();
            }
        });
    }

    function getStaffTeam() {
        $('#staff').html("<img src='../img/loadw.svg'>");
        list = "";
        $.get('/api/v2/staff/list', function(data) {
            data = JSON.parse(data);

            if (data.code === 200) {
                staff = data.response;
                for (let i = 0; i < Object.keys(staff).length; i++) {
                    staffbgc = getStaffColor(staff[i].team);

                    list += `<div class="selectionTab extraPadding" style="color: ${staffbgc};" onclick="getMoreInfo(${staff[i].id})">${staffShortInfo(staff[i], staffbgc)}<span style="position: absolute;bottom: 5px;font-size: 12px;color: ${staffbgc};">Activity: ${staff[i].activity}</span><span style="font-size: 25px;color: ${staffbgc};">${staff[i].displayName}<br></span></div>`;
                }
                $('#staff').html(list);
                offset = offset + 20;
            }
        });
    }

    function staffShortInfo(member, color) {
        let region = '';
        if (member.region !== null) region = `${member.region} `;

        return `<span style="float: right;vertical-align: top;font-size: 12px;color: ${color};">${region}${parseStaffTeamToName(member.team)} ${member.rank.name}</span>`;
    }

    function parseStaffTeamToName(team) {
        if (parseInt(team) === 100) {
            return `<span style="font-weight: normal;color:inherit;" title="Senior Management Team">SMT</span>`;
        } else if (parseInt(team) === 6) {
            return `Support Team`;
        } else if (team === null) {
            return `Unassigned Member`;
        } else {
            return `Staff Team ${team}`;
        }
    }

    function initSearch(e) {
        console.log(e.target.value.length);
        if (e.target.value.length === 0) clearSearch();
        if (e.target.value.length > 0) searchStaff(e.target.value);
    }

    function searchStaff(q) {
        $('.selectionTab').hide();
        $(`.selectionTab:contains("${q}")`).show();
    }

    function clearSearch() {
        $('.selectionTab').show();
    }

    function openSearchBox() {
        $('#searchStaffMembers').slideToggle(300);
    }

    let actwarn_start;
    let actwarn_end;
    $(document).ready(() => {
        checkHashString(window.location.hash);
    });
    window.onpopstate = () => {
        checkHashString(window.location.hash);
    };

    function checkHashString(str) {
        let parts = str.substring(1).split(':');
        console.log(parts);
        switch (parts[0]) {
            case "User":
                getMoreInfo(parts[1]);
                break;
            case "Activity":
                getStaffActivity(parts[1], parts[2]);
                break;
            case "Case":
                getCase(parts[1]);
                break;
            case "Audit":
                getStaffAudit(parts[1]);
                break;
        }
    }

    function getStaffColor(team) {
        switch (parseInt(team)) {
            case 1:
                return "#BFFF00";
            case 2:
                return "#429ef4";
            case 3:
                return "#41f49d";
            case 4:
                return "#f44141";
            case 5:
                return "#62fdff";
            case 6:
                return "#f48d21";
            case 100:
                return "#df80ff";
            default:
                return "#FFFFFF";
        }
    }

    function getMoreInfo(id) {
        currentlyOpenTab = "";
        $('#staff_info').html("<p><img src='../img/loadw.svg'></p>");
        actwarn_start = "";
        actwarn_end = "";
        $.post('/api/v1/getStaffMoreInfo', {
            'id': id
        }, function(data) {
            let res = JSON.parse(data);
            if (res.code === 200) {
                moreinfo = res.response;
                if (moreinfo.id !== null) {
                    window.history.pushState({}, moreinfo.name, `#User:${moreinfo.id}`);
                    staffbgc = getStaffColor(moreinfo.team);

                    let region = '';
                    if (moreinfo.region !== null) region = `${moreinfo.region} `;

                    if (moreinfo.activity_warning === true) {
                        actwarn_start = "<span style='color: orange;' title='Activity Warning'>";
                        actwarn_end = "</span>";
                    }
                    let changeName = "";
                    if (moreinfo.id !== userArray.info.id)
                        changeName = `ondblclick='openChangeName(${moreinfo.id}, "${moreinfo.display_name}")'`;
                    setMoreInfo = `<h1 style='color: ${staffbgc};' id='cn${moreinfo.id}' ${changeName}>${moreinfo.display_name}</h1><p style='color: ${staffbgc};'>${region}${parseStaffTeamToName(moreinfo.team)} ${moreinfo.primary_rank.name}</p><p style='color: ${staffbgc};'>${moreinfo.casecount} Cases Complete (${actwarn_start}${moreinfo.casecount_week} this week | ${moreinfo.casecount_month} this month${actwarn_end})</p>`;
                    setMoreInfo += `<p style='color: ${staffbgc};'>${moreinfo.primary_rank.name} Since ${moreinfo.lastPromotion}</p>`;

                    if (moreinfo.id !== userArray.info.id) {
                        if (moreinfo.onLOA) {
                            setMoreInfo += `<p style='color: ${staffbgc};'>${moreinfo.name} is on LOA until ${moreinfo.loaEND}</p>`;
                            setMoreInfo += `<div style='color: ${staffbgc};' class='staffActivityCard' onclick='bringOffLOA(${moreinfo.id});'>Remove LOA</div>`;
                        }
                        if (moreinfo.isSuspended) {
                            setMoreInfo += `<div style='color: ${staffbgc};' class='staffActivityCard' onclick='bringOffSuspension(${moreinfo.id});'>Remove Suspension</div>`;
                        }
                        setMoreInfo += `<div class='field'><div class='fieldTitle'>Notes</div><textarea class='fieldTextarea' id='staffNotesTextarea'>${moreinfo.notes}</textarea></div>`;
                        setMoreInfo += `<div class='field'><div class='fieldTitle'>Region</div>${regionInputSelector(moreInfo.region)}</div>`;
                        setMoreInfo += `<div class='field'><div class='fieldTitle'>Discord Tag</div><input class='fieldInput' id='staffDiscordInput' value="${moreinfo.discord_tag}"></div>`;
                        setMoreInfo += `<div class='field'><div class='fieldTitle'>UID</div><input class='fieldInput' id='staffUIDInput' value="${moreinfo.uid}"></div>`;

                        setMoreInfo += `<div class="spacer"></div><div class="btnGroup">`;

                        setMoreInfo += `<button style='color: ${staffbgc};' onclick='saveAll("${moreinfo.id}")'>Save Info</button>`;

                        if (moreinfo.casecount > 0) {
                            setMoreInfo += `<button style='color: ${staffbgc};' onclick='getStaffActivity("${moreinfo.id}", "cases")'>View Cases</button>`;
                            setMoreInfo += `<button style='color: ${staffbgc};' onclick='getStaffActivity("${moreinfo.id}", "punishments")'>View Punishments</button>`;
                            setMoreInfo += `<button style='color: ${staffbgc};' onclick='getStaffActivity("${moreinfo.id}", "bans")'>View Bans</button>`;
                        }
                        setMoreInfo += `<button style='color: ${staffbgc};' onclick='getStaffAudit("${moreinfo.id}")'>Staff Audit</button>`;

                        setMoreInfo += `<button style='color: ${staffbgc};' onclick='assign_team_menu();'>Assign Team</button>`;
                        if (!moreinfo.onLOA) {
                            setMoreInfo += `<button style='color: ${staffbgc};' onclick='send_on_loa_menu();'>Send on LOA</button>`;
                        }
                        if (!moreinfo.isSuspended) {
                            setMoreInfo += `<button style='color: ${staffbgc};' title='Double Click' ondblclick='suspend(${moreinfo.id});'>Suspend</button>`;
                        }
                        setMoreInfo += `<button style='color: ${staffbgc};' onclick='update_lastpromotion_menu();'>Update Last Promotion</button>`;
                        if (moreinfo.rank_lvl >= 6 || moreinfo.rank_lvl === "" || parseInt(userArray.info.rank_lvl) === 1 || userArray.info.dev) {
                            setMoreInfo += `<button style='color: ${staffbgc};' onclick='assign_rank_menu();'>Assign Rank</button>`;
                            setMoreInfo += `<button style='color: ${staffbgc};' id='rfl${id}' onclick='removeFromLogger(${id})'>Remove From Logger</button>`;
                        }
                        setMoreInfo += `</div>`;


                        setMoreInfo += `<div class="dropdownGroup">`;

                        if (moreinfo.rank_lvl >= 6 || moreinfo.rank_lvl === "" || parseInt(userArray.info.rank_lvl) === 1 || userArray.info.dev) {
                            setMoreInfo += `<div id='assignRankMenu' style='display: none;color: ${staffbgc};'>`;
                            for (let r of moreinfo.ranks_available) {
                                let selected = false;
                                for (let rr of moreinfo.all_ranks)
                                    if (rr.id === r.id) selected = true;

                                let icon = (selected) ? '<i id="selected-icon-' + r.id + '" class="fas fa-check"></i>' : '<i id="selected-icon-' + r.id + '" class="fas fa-times"></i>';

                                setMoreInfo += `<div class='staffActivityCard' onclick='assign_rank(\`${id}\`, \`${r.id}\`, \`${(selected) ? 'yes' : 'no'}\`);'>${icon} ${r.name} <i style="float: right;" data-tippy-content="Permission: ${JSON.parse(r.permissions).join(', ')}" class="fas fa-question-circle"></i></div>`;
                            }
                            setMoreInfo += `</div>`;
                        }
                        if (!moreinfo.onLOA) {
                            setMoreInfo += `<div id='sendOnLoaMenu' style='display: none;color: ${staffbgc};'><div class="field"><div class="fieldTitle">Time Of Return</div><input type="date" class="fieldInput" id="timeOfReturn"></div><div class='staffActivityCard' onclick='sendOnLOA(${moreinfo.id});'>Confirm</div></div>`;
                        }
                        setMoreInfo += `<div id='assignTeamMenu' style='display: none;color: ${staffbgc};'><div class='staffActivityCard' onclick='assign_team(\`${id}\`, \`1\`);'>Staff Team 1</div><div class='staffActivityCard' onclick='assign_team(\`${id}\`, \`2\`);'>Staff Team 2</div><div class='staffActivityCard' onclick='assign_team(\`${id}\`, \`3\`);'>Staff Team 3</div><div class='staffActivityCard' onclick='assign_team(\`${id}\`, \`4\`);'>Staff Team 4</div><div class='staffActivityCard' onclick='assign_team(\`${id}\`, \`5\`);'>Staff Team 5</div><div class='staffActivityCard' onclick='assign_team(\`${id}\`, \`6\`);'>Support Team</div><div class='staffActivityCard' onclick='assign_team(\`${id}\`, \`100\`);'>Senior Management Team</div><div class='staffActivityCard' onclick='assign_team(\`${id}\`, \`500\`);'>Development Team</div></div>`;
                        setMoreInfo += `<div id='updateLastPromotionMenu' style='display: none;color: ${staffbgc};'><div class="field"><div class="fieldTitle">Promotion Date</div><input type="date" class="fieldInput" value="${moreinfo.lastPromotion}" id="promotionDate"></div><div class='staffActivityCard' onclick='updateLastPromotion(${moreinfo.id});'>Confirm</div></div>`;
                        setMoreInfo += `</div>`;

                        setMoreInfo += `<div id='activityGraph'></div>`;

                        google.charts.load('current', {
                            'packages': ['line']
                        });

                        google.charts.setOnLoadCallback(finishDisplay);

                        function finishDisplay() {
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
                                legend: {
                                    position: 'bottom'
                                }
                            };

                            $('#staff_info').html(setMoreInfo);

                            let chart = new google.charts.Line(document.getElementById('activityGraph'));

                            chart.draw(data, google.charts.Line.convertOptions(options));

                            tippy('[data-tippy-content]', {
                                placement: 'left'
                            });
                        }
                    }
                    $('#staff_info').html(setMoreInfo);
                } else {
                    $('#staff_info').html(`<p><b>Oops </b>This staff member could not be found, they may have been removed.</p>`);
                }
            } else {
                $('#staff_info').html(`<p><b>Error </b>${res.message}</p>`);
            }
        });
    }

    function regionInputSelector(val) {
        return `<select class='fieldSelector' id='staffRegionInput'>
            <option ${val == "" ? 'selected' : ''} disabled>Choose A Global Region</option>
            <option ${val == "EU" ? 'selected' : ''} value='EU'>European Union</option>
            <option ${val == "NA" ? 'selected' : ''} value='NA'>North America</option>
            <option ${val == "SA" ? 'selected' : ''} value='SA'>South America</option>
            <option ${val == "AF" ? 'selected' : ''} value='AF'>Africa</option>
            <option ${val == "AS" ? 'selected' : ''} value='AS'>Asia</option>
            <option ${val == "AU" ? 'selected' : ''} value='AU'>Oceania</option>
        </select>`
    }

    function closeAllMenus() {
        $('#assignRankMenu').slideUp(250);
        $('#sendOnLoaMenu').slideUp(250);
        $('#assignTeamMenu').slideUp(250);
        $('#updateLastPromotionMenu').slideUp(250);
    }

    function assign_rank_menu() {
        if (currentlyOpenTab !== "assignRank") {
            currentlyOpenTab = "assignRank";
            closeAllMenus();
            $('#assignRankMenu').slideToggle(250);
        } else {
            currentlyOpenTab = "";
            closeAllMenus();
        }
    }

    function send_on_loa_menu() {
        if (currentlyOpenTab !== "sendOnLoa") {
            currentlyOpenTab = "sendOnLoa";
            closeAllMenus();
            $('#sendOnLoaMenu').slideToggle(250);
        } else {
            currentlyOpenTab = "";
            closeAllMenus();
        }
    }

    function assign_team_menu() {
        if (currentlyOpenTab !== "assignTeam") {
            currentlyOpenTab = "assignTeam";
            closeAllMenus();
            $('#assignTeamMenu').slideToggle(250);
        } else {
            currentlyOpenTab = "";
            closeAllMenus();
        }
    }

    function update_lastpromotion_menu() {
        if (currentlyOpenTab !== "updatePromotion") {
            currentlyOpenTab = "updatePromotion";
            closeAllMenus();
            $('#updateLastPromotionMenu').slideToggle(250);
        } else {
            currentlyOpenTab = "";
            closeAllMenus();
        }
    }

    function assign_rank(id, rank, s = 'no') {
        $.post('/api/v2/staff/rank/update', {
            id: id,
            rank: rank,
            selected: s
        }, data => {
            data = JSON.parse(data);

            if (data.code === 200) {
                getStaffTeam();
                getMoreInfo(id);
                new Noty({
                    text: data.message,
                    timeout: 4000,
                    type: 'success'
                }).show();
            } else {
                new Noty({
                    text: data.message,
                    timeout: 4000,
                    type: 'error'
                }).show();
            }
        })
    }

    function assign_team(id, team) {
        $.post('/api/v2/staff/team/update', {
            'id': id,
            'team': team
        }, function(data) {
            getStaffTeam();
            getMoreInfo(id);
        });
    }

    function removeFromLogger(id) {
        $('#rfl' + id).attr('onclick', 'removeFromLoggerConfirm(' + id + ')');
        $('#rfl' + id).text('Confirm');
        setTimeout(function() {
            $('#rfl' + id).attr('onclick', 'removeFromLogger(' + id + ')');
            $('#rfl' + id).text('Remove From Logger');
        }, 3000)
    }

    function removeFromLoggerConfirm(id) {
        $.post('/api/v1/removeStaff', {
            'id': id
        }, function(data) {
            getStaffTeam();
            $('#staff_info').html("<h1>Select A Staff Member To Get Statistics</h1>");
        });
    }

    function sendOnLOA(id) {
        $.post('/api/v1/sendOnLOA', {
            id: id,
            time: $('#timeOfReturn').val()
        }, data => {
            console.log(data);
            getMoreInfo(id);
            getStaffTeam();
        });
    }

    function bringOffLOA(id) {
        $.post('/api/v1/sendOnLOA', {
            id: id,
            time: '1999-00-00'
        }, data => {
            console.log(data);
            getMoreInfo(id);
            getStaffTeam();
        });
    }

    function suspend(id) {
        $.post('/api/v1/suspend', {
            id: id,
            remove: 0
        }, data => {
            console.log(data);
            getMoreInfo(id);
            getStaffTeam();
        });
    }

    function bringOffSuspension(id) {
        $.post('/api/v1/suspend', {
            id: id,
            remove: 1
        }, data => {
            console.log(data);
            getMoreInfo(id);
            getStaffTeam();
        });
    }

    function getStaffAudit(id) {
        $.get(`/api/v1/staffAuditLogs?id=${id}`, data => {
            data = JSON.parse(data);
            window.history.pushState({}, `Audit Log`, `#Audit:${id}`);
            if (data.code === 200) {
                let audit = '';
                for (let i = 0; i < data.response.length; i++) {
                    const l = data.response[i];

                    audit += `<div class="staffActivityCard"><span style="text-transform: capitalize;">${l.log_context} Log</span> ~ ${l.timestamp}<br>${l.log_content}</div>`;
                }
                audit += `<div class='panel-controls'><button onclick='getMoreInfo(${id})'>Back To Overview</button><button onclick='getStaffAudit(${id})'>Reload</button></div>`;
                $('#staff_info').html(`<h1>Staff Audit</h1> ${audit}`);
            } else {
                $('#staff_info').html(`<p><b>Error </b>${res.message}</p>`);
            }
        });
    }

    function getStaffActivity(id, type) {
        $('#staff_info').html("<img src='../img/loadw.svg'>");
        let other_staff;
        let other_staff_text;
        $.post('/api/v1/getStaffActivity', {
            'id': id,
            'field': type
        }, function(data) {
            activity = "";
            let res = JSON.parse(data);
            if (res.code === 200) {
                moreinfo = res.response;
                window.history.pushState({}, `${moreinfo.user.username} Activity`, `#Activity:${moreinfo.user.id}:${type}`);
                switch (type) {
                    case 'cases':
                        if (typeof moreinfo.log !== undefined) {
                            for (let i = 1; i < Object.keys(moreinfo.log).length + 1; i++) {
                                activity += parseCaseLog(moreinfo.log[i]);
                            }
                        }
                        break;
                    case 'punishments':
                        if (typeof moreinfo.punishment !== "undefined") {
                            for (let i = 1; i < Object.keys(moreinfo.punishment).length + 1; i++) {
                                activity += parsePunishmentLog(moreinfo.punishment[i]);
                            }
                        }
                        break;
                    case 'bans':
                        if (typeof moreinfo.punishment !== "undefined") {
                            for (let i = 1; i < Object.keys(moreinfo.punishment).length + 1; i++) {
                                activity += parseBanLog(moreinfo.punishment[i]);
                            }
                        }
                        break;
                }
                activity += `<div class='panel-controls'><button onclick='getMoreInfo(${id})'>Back To Overview</button><button onclick='getStaffActivity(${id})'>Reload</button></div>`;
                $('#staff_info').html(`<h1>${moreinfo.user.displayname}'s Activity</h1> ${activity}`);
            } else {
                $('#staff_info').html(`<p><b>Error </b>${res.message}</p>`);
            }
        });
    }

    function parseCaseLog(log) {
        let other_staff = "";
        let other_staff_text = "";
        let reporting_player_name;
        let reporting_player = "";
        if (log.reporting_player !== "[]" && log.reporting_player !== "" && log.reporting_player !== null && log.reporting_player !== "null") {
            reporting_player = log.reporting_player;
            reporting_player_name = reporting_player[0].name;
        } else {
            reporting_player_name = "undefined";
        }
        if (log.other_staff === true) {
            other_staff = "other_staff";
            other_staff_text = " (Assisting)";
        }
        return `<div class="staffActivityCard ${other_staff}" onclick="getCase(${log.id})">${log.id} - ${reporting_player_name}${other_staff_text}<br>${log.doe}</div>`;
    }

    function parsePunishmentLog(log) {
        return `<div class="staffActivityCard" onclick="getCase(${log.case_id})">${log.case_id} - ${log.player}<br>${log.comments}</div>`;
    }

    function parseBanLog(log) {
        let length = (log.length === "0") ? `Permenant` : `${log.length} Days`;
        return `<div class="staffActivityCard" onclick="getCase(${log.case_id})">${log.case_id} - ${log.player} - ${length}<br>${log.message}</div>`;
    }

    function getCase(id, userid) {
        $('#staff_info').html("<img src='../img/loadw.svg'>");
        players_involved = "";
        playersArray = "";
        player_title = "";
        $.post('/api/v1/getMoreInfo', {
            'id': id
        }, function(data) {
            let res = JSON.parse(data);
            if (res.code === 200) {
                moreinfo = res.response;
                window.history.pushState({}, `Case #${moreinfo.report.id}-${moreinfo.report.players[0].name} Report`, `#Case:${moreinfo.report.id}`);
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

                setMoreInfo = `<style>#staff_info{color: ${stafftextc} !important;}</style><h2><span>Case ID:</span> ${moreinfo.report.id}-${player_title}</h2><p id="case"><span>Lead Staff:</span> ${moreinfo.report.lead_staff}</p><p id="case"><span>Other Staff:</span> ${moreinfo.report.other_staff}</p><p id="case"><span>Type Of Report:</span><br> ${moreinfo.report.typeofreport}</p><p id="case" style="text-transform: capitalize;"><span>Players Involved:</span><br> ${players_involved}</p><p id="case"><span>Description Of Events:</span><br> ${linkify(moreinfo.report.doe)}</p><p id="case"><span>Timestamp:</span> ${moreinfo.report.timestamp}</p>${linkify(punishments)}${linkify(bans)}`;
                setMoreInfo += `<div class='panel-controls'><button onclick='getMoreInfo(${moreinfo.report.lead_staff_id})'>Back To Overview</button><button onclick='getStaffActivity(${moreinfo.report.lead_staff_id})'>Back To Cases</button><button onclick='getCase(${id})'>Reload</button></div>`;
                $('#staff_info').html(setMoreInfo);
            } else {
                $('#staff_info').html(`<p>${res.message}</p>`);
            }
        });
    }

    function userArrayLoaded() {
        if (userArray.info.id === "") {
            location.replace('holdingpage');
        }
        if ((userArray.info.slt === "0" && userArray.info.dev === "0") || userArray.info.slt === "") {
            location.replace('holdingpage');
        }
        $('#welcome').html("Hello, " + userArray.info.username);
    }

    function saveAll(id) {
        setStaffNotes(id);
        setStaffUID(id);
        setStaffRegion(id);
        setStaffDiscordTag(id);
    }

    function setStaffNotes(id) {
        $.post('/api/v1/saveStaffNotes', {
            notes: $('#staffNotesTextarea').val(),
            id: id
        }, data => {
            console.log(data);
        });
    }

    function setStaffRegion(id) {
        $.post('/api/v1/saveStaffRegion', {
            region: $('#staffRegionInput').val(),
            id: id
        }, data => {
            console.log(data);
        });
    }

    function setStaffDiscordTag(id) {
        $.post('/api/v1/saveStaffDiscordTag', {
            tag: $('#staffDiscordInput').val(),
            id: id
        }, data => {
            console.log(data);
        });
    }

    function setStaffUID(id) {
        $.post('/api/v1/saveStaffUID', {
            uid: $('#staffUIDInput').val(),
            id: id
        }, data => {
            console.log(data);
        });
    }

    function updateLastPromotion(id) {
        $.post('/api/v1/saveStaffPromotion', {
            promotionTime: $('#promotionDate').val(),
            id: id
        }, data => {
            getMoreInfo(id);
            console.log(data);
        });
    }

    window.addEventListener("load", getStaffTeam);
</script>