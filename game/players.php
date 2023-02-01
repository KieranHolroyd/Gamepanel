<?php include "../head.php";
Guard::init()->RequireGameAccess();
$initial_query = "";
if (!empty($_GET['query'])) {
    $initial_query = htmlspecialchars($_GET['query']);
}
?>
<div class="searchBox-container">
    <input type="text" class="searchBox" id="searchQuery" placeholder="Search Players" autofocus><button class="searchCases" id="searchCases">Search</button>
</div>
<div class="grid new" style="max-height: calc(100vh - 48px);overflow: hidden;">
    <div class="grid__col grid__col--2-of-6" style="padding-left: 20px !important;">
        <h1 class="info-title new" style='text-transform: capitalize;'>
            <?= Config::$name; ?> Game Manager ~ Players
            <div style="float: right;font-size: 14px;color: #999;" id="resultsfound">Loading Search Results Found</div>
        </h1>
        <br>
        <div id="reports" style='height: calc(100vh - 139px) !important;' class="selectionPanel">
            <img src="../img/loadw.svg">
        </div>
    </div>
    <div class="grid__col grid__col--4-of-6">
        <div class="infoPanelContainer">
            <div class="infoPanel" id="case_info">
                <h2>Choose Player For Information</h2>
            </div>
        </div>
        <div style="height: 48px;"></div>
    </div>
</div>
<div class="modal" id="compensate">
    <button id="close">×</button>
    <div class="content" style="max-width: 400px;">
        <h2>Compensating <span id="playerName">Loading</span></h2><br>
        <div class="field">
            <div class="fieldTitle">Compensation Amount</div>
            <input class="fieldInpit" id="compensationAmount" type="number" placeholder="Amount To Compensate">
        </div>
        <button onclick="issueCompensation()" class="createPointBtn">Issue Compensation</button>
    </div>
</div>
<div class="modal" id="player_audit_log">
    <button id="close">×</button>
    <div class="content" style="max-width: 800px;">
        <h2>Audit Logs</h2><br>
        <div id="audit_logs"></div>
    </div>
</div>
<button onclick="openFilters()" class="newEditBtn show"><i style="font-size: 14px;" class="fas fa-filter"></i></button>
<div id="filters" class="reveal">
    <p class="title">Filters</p>
    <div class="field inline">
        <input onchange="updateFilters(event);" type="checkbox" id="onlyPolice"><label for="onlyPolice"> Only Police</label>
    </div>
    <div class="field inline">
        <input onchange="updateFilters(event);" type="checkbox" id="onlyMedics"><label for="onlyMedics"> Only Medics</label>
    </div>
    <div class="field inline">
        <input onchange="updateFilters(event);" type="checkbox" id="onlyAdmins"><label for="onlyAdmins"> Only Staff</label>
    </div>
</div>
<script>
    let query = '<?= $initial_query; ?>';
    let playerBalance = 0;
    let playerID = 0;
    let levels = {};
    let filters = {
        "onlyPolice": false,
        "onlyMedics": false,
        "onlyAdmins": false,
    };

    apiclient.get('/api/v2/players/levels').then(({
        data
    }) => {
        levels = data.response;
    });

    function openFilters() {
        $('#filters').toggleClass('show');
    }

    function updateFilters(e) {
        filters[e.target.id] = e.target.checked;
        getPlayers();
    }

    function getPlayers() {
        $('#reports').html("<img src='../img/loadw.svg'>");
        list = "";
        let jsonFilters = JSON.stringify(filters);
        apiclient.get(`/api/v2/players/search?q=${query}&filters=${jsonFilters}`).then(function({
            data
        }) {
            let list = [];
            for (let player of data.response) {

                list.push(`<div class="selectionTab extraPadding" onclick="getPlayerInfo('${player.pid}')"><span style="position: absolute;bottom: 5px;font-size: 12px;">${player.pid}</span><span style="font-size: 25px;">${player.name}<br></span></div>`);
            }
            $('#resultsfound').text(data.message);
            $('#reports').html(list.join(''));
        });
    }
    getPlayers();

    function getPlayerInfo(id) {
        $('#case_info').html("<img src='../img/loadw.svg'>");
        list = "";
        apiclient.get(`/api/v2/players/get?id=${id}`).then(({
            data
        }) => {
            if (data.code === 200) {

                let player = data.response;

                playerBalance = player.bankacc;

                let admin = `<select class="fieldSelector" onchange="updateAdminLevel('${player.playerid}')" id="AdminLevelValue">
                            ${levels.admin !== undefined && levels.admin.map((_, level) => {
                                return `<option ${(player.adminlevel == level) ? 'selected' : ''} value="${level + 1}">${levels.admin[level] || level}</option>`;
                            })}
                            </select>`;

                let medic = `<select class="fieldSelector" onchange="updateMedicLevel('${player.playerid}')" id="MedicLevelValue">
                            ${levels.medic !== undefined && levels.medic.map((_, level) => {
                                return `<option ${(player.mediclevel == level) ? 'selected' : ''} value="${level + 1}">${levels.medic[level] || level}</option>`;
                            })}
                            </select>`;

                let police = `<select class="fieldSelector" onchange="updatePoliceLevel('${player.playerid}')" id="PoliceLevelValue">
                            ${levels.police !== undefined && levels.police.map((_, level) => {
                                return `<option ${(player.coplevel == level) ? 'selected' : ''} value="${level + 1}">${levels.police[level] || level}</option>`;
                            })}
                            </select>`;

                let bank = `<input class="fieldInput" onblur="updatePlayerBalance('${player.playerid}')" type="text" id="PlayerBankValue" value="${player.bankacc}" placeholder="Player Bank Balance">`;

                let list = `<h2>${player.name}</h2>
                    <p><b>SteamID: </b>${player.playerid}</p>
                    <p><b>Level: </b>${player.exp_level} (XP: ${player.exp_total})</p>
                    <p><b>Bank Balance: </b><span id="playerFormattedBankBalance">${player.formatbankacc}</span> (Cash: \$${player.cash})</p>
                    <p><b>Admin Level: </b>${admin}</p>
                    <p><b>Police Level: </b>${police}</p>
                    <p><b>Medic Level: </b>${medic}</p>
                    <p><b>Donated: </b>${boolToYesNo(player.donorlevel)}</p>
                    <p><b>Last Seen: </b>${player.last_seen}</p>
                    <p><b>Arrested: </b>${boolToYesNo(player.arrested)} (Previously Arrested For ${player.jail_time} Minutes)</p>
                    <div class="spacer"></div>
                    <p><b>Edit Player Balance: </b>${bank}</p>
                    <div class="spacer"></div>
                    <div class="btnGroup">
                        <button launch="compensate" id="modalLaunch">Compensation Assistant</button>
                        <button onclick="getPlayerVehicles('${player.playerid}')">View Player Vehicles</button>
                        <button onclick="openPlayerAudit()">View Audit Log</button>
                    </div>
            `;

                $('#audit_logs').html('');

                for (let log of player.edits) {
                    let staff = (log.logged_in_user !== "null") ? `~ <a href="/staff/#User:${log.logged_in_user}">${log.staff_member_name}</a>` : ``;
                    $('#audit_logs').append(`<div class="staffActivityCard" style="cursor: default;"><span style="text-transform: capitalize;">${log.log_context}</span> Log ~ ${log.timestamp} ${staff}<br>${log.log_content}</div>`);
                }

                if (player.edits.length === 0) $('#audit_logs').append('<h2>No Logs Found</h2>');

                playerID = player.uid;
                $('#playerName').text(player.name);

                $('#case_info').html(list);
            } else {
                $('#case_info').html(`<h2><b>Error </b>${data.message}</h2>`);
            }
        }).catch(noty_catch_error);
    }

    function openPlayerAudit() {
        launchModal('player_audit_log');
    }

    function getPlayerVehicles(id) {
        $('#case_info').html("<img src='../img/loadw.svg'>");
        let list = "";
        apiclient.get(`/api/v2/players/vehicles?id=${id}`).then(({
            data
        }) => {
            if (data.code === 200) {
                if (data.response.vehiclesFilled) {
                    for (let key in Object.values(data.response.vehicles)) {
                        const vehicle = data.response.vehicles[key];
                        list += `<div class="staffActivityCard" style="cursor: default;">${vehicle.side} ${vehicle.type} ${parseClassNameToVehicle(vehicle.classname)}<br>Plate: ${vehicle.plate} | Impounded: ${boolToYesNo(vehicle.impound)} | Insured: ${boolToYesNo(vehicle.insured)}</div>`
                    }
                    $('#case_info').html(list);
                } else {
                    $('#case_info').html(`<h2><b>No Vehicles Found</b></h2>`);
                }
            } else {
                $('#case_info').html(`<h2><b>Error </b>${data.message}</h2>`);
            }
        }).catch(noty_catch_error);
    }

    function parseClassNameToVehicle(classname) {
        return levels.vehicle_dictionary[classname] || classname;
    }

    function updateAdminLevel(id) {
        $.post(`/api/v2/players/update/admin`, {
            id: id,
            al: $('#AdminLevelValue').val()
        }, function(data) {
            data = JSON.parse(data);

            if (data.code === 200) {
                new Noty({
                    text: `Successfully Updated Admin Level To (${$('#AdminLevelValue').val()})`,
                    type: 'success',
                    timeout: 2000
                }).show();
            } else {
                getPlayerInfo(id);
                new Noty({
                    'text': data.message,
                    'type': 'error',
                    'timeout': 2000
                }).show();
            }
        });
    }

    function updateMedicLevel(id) {
        $.post(`/api/v1/playerChangeMedicLevel`, {
            id: id,
            ml: $('#MedicLevelValue').val()
        }, function(data) {
            data = JSON.parse(data);

            if (data.code === 200) {
                new Noty({
                    text: `Successfully Updated Medic Level To (${$('#MedicLevelValue').val()})`,
                    type: 'success',
                    timeout: 2000
                }).show();
            } else {
                getPlayerInfo(id);
                new Noty({
                    'text': data.message,
                    'type': 'error',
                    'timeout': 2000
                }).show();
            }
        });
    }

    function updateMedicDepartment(id) {
        $.post(`/api/v1/playerChangeMedicDepartment`, {
            id: id,
            md: $('#MedicDepartmentValue').val()
        }, function(data) {
            data = JSON.parse(data);

            if (data.code === 200) {
                new Noty({
                    text: `Successfully Updated Medic Department To (${$('#MedicDepartmentValue').val()})`,
                    type: 'success',
                    timeout: 2000
                }).show();
            } else {
                getPlayerInfo(id);
                new Noty({
                    'text': data.message,
                    'type': 'error',
                    'timeout': 2000
                }).show();
            }
        });
    }

    function updatePoliceLevel(id) {
        $.post(`/api/v1/playerChangePoliceLevel`, {
            id: id,
            pl: $('#PoliceLevelValue').val()
        }, function(data) {
            data = JSON.parse(data);

            if (data.code === 200) {
                new Noty({
                    text: `Successfully Updated Police Level To (${$('#PoliceLevelValue').val()})`,
                    type: 'success',
                    timeout: 2000
                }).show();
            } else {
                getPlayerInfo(id);
                new Noty({
                    'text': data.message,
                    'type': 'error',
                    'timeout': 2000
                }).show();
            }
        });
    }

    function updatePoliceDepartment(id) {
        $.post(`/api/v1/playerChangePoliceDepartment`, {
            id: id,
            pd: $('#PoliceDepartmentValue').val()
        }, function(data) {
            data = JSON.parse(data);

            if (data.code === 200) {
                new Noty({
                    text: `Successfully Updated Police Department To (${$('#PoliceDepartmentValue').val()})`,
                    type: 'success',
                    timeout: 2000
                }).show();
            } else {
                getPlayerInfo(id);
                new Noty({
                    'text': data.message,
                    'type': 'error',
                    'timeout': 2000
                }).show();
            }
        });
    }

    function updatePlayerBalance(id) {
        if ($('#PlayerBankValue').val() !== playerBalance) {
            $.post(`/api/v1/playerChangeBalance`, {
                id: id,
                pb: $('#PlayerBankValue').val()
            }, function(data) {
                data = JSON.parse(data);

                if (data.code === 200) {
                    playerBalance = $('#PlayerBankValue').val();
                    $('#playerFormattedBankBalance').text(parseInt($('#PlayerBankValue').val()).toLocaleString('en-US', {
                        style: 'currency',
                        currency: 'USD',
                        minimumFractionDigits: 0
                    }));
                    new Noty({
                        text: `Successfully Updated Player Balance To (${$('#PlayerBankValue').val()})`,
                        type: 'success',
                        timeout: 2000
                    }).show();
                } else {
                    getPlayerInfo(id);
                    new Noty({
                        'text': data.message,
                        'type': 'error',
                        'timeout': 2000
                    }).show();
                }
            });
        }
    }

    function issueCompensation() {
        $.post(`/api/v1/playerChangeBalance`, {
            id: playerID,
            pb: parseInt($('#PlayerBankValue').val()) + parseInt($('#compensationAmount').val()),
            comp: true
        }, function(data) {
            data = JSON.parse(data);

            if (data.code === 200) {
                playerBalance = parseInt($('#PlayerBankValue').val()) + parseInt($('#compensationAmount').val());
                $('#PlayerBankValue').val(playerBalance);
                $('#compensationAmount').val('');
                $('#playerFormattedBankBalance').text(parseInt($('#PlayerBankValue').val()).toLocaleString('en-US', {
                    style: 'currency',
                    currency: 'USD',
                    minimumFractionDigits: 0
                }));
                new Noty({
                    text: `Successfully Updated Player Balance To (${$('#PlayerBankValue').val()})`,
                    type: 'success',
                    timeout: 2000
                }).show();
            } else {
                getPlayerInfo(id);
                new Noty({
                    'text': data.message,
                    'type': 'error',
                    'timeout': 2000
                }).show();
            }
        });
    }

    function boolToYesNo(bool) {
        return (parseInt(bool)) ? 'Yes' : 'No';
    }

    function executeSearch() {
        window.history.pushState('search', null, `/game/players${query !== "" ? "?query=" + encodeURIComponent(query) : ""}`);
        if (query !== "") {
            getPlayers();
        }
    }

    $(document).ready(function() {
        $('#searchQuery').keyup(function(event) {
            query = event.target.value;
            if (event.keyCode === 13) {
                executeSearch();
            }
        });
        $('#searchCases').click(() => {
            executeSearch();
        })
        $('#searchQuery').val(query);
    });
</script>