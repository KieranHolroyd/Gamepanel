<?php
include "head.php";
$initial_query = "";
if (!empty($_GET['type'])) {
    $searchType = htmlspecialchars($_GET['type']);
}
if (!empty($_GET['query'])) {
    $initial_query = htmlspecialchars($_GET['query']);
}
Guard::init()->SLTRequired();
?>
<style>
    .chooseSearch {
        font-size: 32px;
        font-weight: bold;
        border: 2px solid transparent;
        border-radius: 4px;
        transition: 200ms;
        background-color: #1c1b30;
    }

    .chooseSearch:hover {
        border: 2px solid #999;
    }
</style>
<div class="searchBox-container">
    <input type="text" class="searchBox" id="searchQuery" placeholder="Search All Cases" autofocus><button class="searchCases" id="searchCases">Search</button>
</div>
<div class="grid new">
    <div class="grid__col grid__col--2-of-6" style="padding-left: 20px !important;">
        <h1 class="info-title new">Search <select onchange="changeType()" id="searchTypeChooser" class="chooseSearch">
                <option <?php if ($searchType == "cases") {
                            echo 'selected';
                        } ?> value="cases">Cases
                </option>
                <option <?php if ($searchType == "punishments") {
                            echo 'selected';
                        } ?> value="punishments">Punishment Reports
                </option>
                <option <?php if ($searchType == "bans") {
                            echo 'selected';
                        } ?> value="bans">Ban Reports
                </option>
                <option <?php if ($searchType == "unbans") {
                            echo 'selected';
                        } ?> value="unbans">Unban Reports
                </option>
                <option <?php if ($searchType == "players") {
                            echo 'selected';
                        } ?> value="players">Players
                </option>
            </select>
            <div style="float: right;font-size: 14px;color: #999;" id="resultsfound">Loading Search Results Found</div>
        </h1>
        <br>
        <div id="reports" style='height: calc(100vh - 122px) !important;' class="selectionPanel">
            <img src="/img/loadw.svg">
        </div>
    </div>
    <div class="grid__col grid__col--4-of-6">
        <div class="infoPanelContainer" style='height: calc(100vh - 49px);'>
            <div id="case_info" class="infoPanel">
                <div class="pre_title">Select Result For Details</div>
            </div>
        </div>
    </div>
</div>
<script>
    let player_punished, player_banned, moreinfo, setMoreInfo, players_involved, playersArray, player_title;
    let query = "<?php echo $initial_query; ?>";
    let searchType = "<?= $searchType; ?>";

    function searchCases() {
        $('#reports').html('<img src="/img/loadw.svg">');
        $.get('api/v2/cases/search', {
            'query': query,
            'type': searchType
        }, function(data) {
            let activity = "";
            moreinfo = JSON.parse(data);
            $('#resultsfound').html(moreinfo.message);
            if (moreinfo.response.length === 0) {
                $('#reports').html("<h2 style='padding: 15px;'> No Results Found </h2>");
            } else {
                for (const log of moreinfo.response) {
                    console.log(log)
                    let other_staff = "",
                        other_staff_text = "",
                        reporting_player = "",
                        reporting_player_name = "";
                    if (log.searchType === undefined || log.searchType !== 'Player') {
                        if (log.reporting_player !== "[]" && log.reporting_player !== "" && log.reporting_player !== null && log.reporting_player !== "null") {
                            reporting_player = log.reporting_player;
                            reporting_player_name = reporting_player[0].name;
                        } else {
                            reporting_player_name = "undefined";
                        }
                        if (log.other_staff === true) {
                            other_staff = "other_staff";
                            other_staff_text = " (Support)";
                        }
                    }
                    let caseID = '';
                    let case_id = '';
                    switch (searchType) {
                        case 'cases':
                            caseID = `#${log.id} - ${reporting_player_name}`;
                            case_id = log.id;
                            break;
                        case 'punishments':
                            caseID = `${log.points} Points issued In Case #${log.case_id}`;
                            case_id = log.case_id;
                            break;
                        case 'bans':
                            caseID = `${log.ban_length} Ban Report Was Submitted In Case #${log.case_id} - ${log.player}`;
                            case_id = log.case_id;
                            break;
                        case 'unbans':
                            caseID = `Unban Report From #${log.id} For ${reporting_player_name}`;
                            case_id = log.id;
                            break;
                        case 'players':
                            caseID = `Player ${log.name}`;
                            case_id = log.id;
                            break;
                    }
                    if (log.searchType === 'Player') {
                        activity += `<div class="selectionTab" onclick="getPlayer('${log.name}')">${caseID}<br><span style='color: #999;'>GUID: ${log.guid}</span></div>`
                    } else {
                        activity += `<div class="selectionTab ${other_staff}" onclick="getCase(${case_id})">${caseID}${other_staff_text}<br><span style='color: #999;'>${log.doe}</span></div>`
                    }
                }
                $('#reports').html(activity);
            }
        });
    }

    function getPlayer(name) {
        $('#case_info').html('<img src="/img/loadw.svg">');
        players_involved = "";
        playersArray = "";
        player_title = "";
        $.get('api/v1/player', {
            'name': name
        }, function(data) {
            let res = JSON.parse(data);
            if (res.code === 200) {
                moreinfo = res.response;
                let result = '';
                let cases = '';
                for (let report of moreinfo) {
                    cases += `<div class="staffActivityCard" onclick="getCase(${report.case_id})">Case: ${report.case.id}-${report.case.players[0].name}</div>`;
                }
                setMoreInfo = `<h2>Cases \`${name}\` is in</h2>${cases}`;
                if (Object.keys(query).length >= 3) {
                    let regEx = new RegExp(query, "ig");
                    let replaceMask = `<span style='color: #FFFBCC;'>${query}</span>`;
                    result = setMoreInfo.replace(regEx, replaceMask);
                } else {
                    result = setMoreInfo;
                }
                $('#case_info').html(linkify(result));
            } else {
                $('#case_info').html(`<p><b>Error: </b>${res.message}</p>`);
            }
        });
    }

    function getCase(id) {
        $('#case_info').html('<img src="/img/loadw.svg">');
        players_involved = "";
        playersArray = "";
        player_title = "";
        $.post('api/v1/getMoreInfo', {
            'id': id
        }, function(data) {
            let res = JSON.parse(data);
            if (res.code === 200) {
                moreinfo = res.response;
                let result = '';
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

                setMoreInfo = `<h2><span>Case ID:</span> ${moreinfo.report.id}-${player_title}</h2><p id="case"><span>Lead Staff:</span> ${moreinfo.report.lead_staff}</p><p id="case"><span>Other Staff:</span> ${moreinfo.report.other_staff}</p><p id="case"><span>Type Of Report:</span><br> ${moreinfo.report.typeofreport}</p><p id="case" style="text-transform: capitalize;"><span>Players Involved:</span><br> ${players_involved}</p><p id="case"><span>Description Of Events:</span><br> ${linkify(moreinfo.report.doe)}</p><p id="case"><span>Timestamp:</span> ${moreinfo.report.timestamp}</p>${linkify(punishments)}${linkify(bans)}`;
                if (Object.keys(query).length >= 3) {
                    let regEx = new RegExp(`${query}(?!([^<]+)?>)`, "ig");
                    let replaceMask = `<span style='color: #FFFBCC;'>${query}</span>`;
                    result = setMoreInfo.replace(regEx, replaceMask);
                } else {
                    result = setMoreInfo;
                }
                $('#case_info').html(result);
            } else {
                $('#case_info').html(`<p><b>Error: </b>${res.message}</p>`);
            }
        });
    }

    function userArrayLoaded() {
        searchCases();
    }
    $('#searchCases').click(function() {
        if ($('#searchQuery').val() !== "") {
            window.history.pushState('search', undefined, `search?type=${searchType}&query=${$('#searchQuery').val()}`);
            query = $('#searchQuery').val();
            searchCases();
        }
    });
    $(document).ready(function() {
        $('#searchQuery').keyup(function(event) {
            setTimeout(function() {
                window.history.pushState('search', undefined, `search?type=${searchType}&query=${$('#searchQuery').val()}`);
                query = $('#searchQuery').val();
                searchCases();
            }, 10)
        });
        $('#searchQuery').val(query);
    });

    function changeType() {
        console.log($('#searchTypeChooser').val());
        window.location.href = `/search?type=${$('#searchTypeChooser').val()}&query=${query}`;
    }
</script>