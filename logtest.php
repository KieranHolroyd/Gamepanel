<?php session_start();
include "head.php"; ?>
<div class="grid new" style="padding-left:15px;z-index: 25;">
    <div class="grid__col grid__col--1-of-6" style="box-shadow: 0 0 5px 0 rgba(0,0,0,0.2);">
        <div class="selectionPanel" style="margin-left: 5px;max-width: 100%;background-color: #1c1b30;height: 100vh;overflow: auto;">
            <p class="label" style="text-align: center;"><?= Config::$name; ?> Staff</p>
            <p class="label">Assisting Staff</p>
            <button class="pickbtn" id="addOtherStaff" disabled>Add
                Assisting Staff
            </button>
            <button class="pickbtn" id="removeOtherStaff" disabled>Remove
                Assisting Staff
            </button>
            <p class="label">Involved Players</p>
            <button class="pickbtn" id="addPlayerReporter">Add Reporting Player</button>
            <button class="pickbtn" id="addPlayerReported">Add Reported Player</button>
            <button class="pickbtn" id="removePlayer">Remove Last Player</button>
            <p class="label">Type Of Report: <span id="typeofreportdisplay">Other</span></p>
            <button class="pickbtn" id="TypeOfReportButton">Select <i id="torangle" class="ts2 fas fa-angle-down"></i>
            </button>
            <div class="reportTypes" id="TypeOfReportList">
                <button class="pickbtn tor submenuBtn" value="Player Report">Player Report</button>
                <button class="pickbtn tor submenuBtn" value="Tech Support">Tech Support</button>
                <button class="pickbtn tor submenuBtn" value="General Question">General Question</button>
                <button class="pickbtn tor submenuBtn" value="Website Tags">Website Tags</button>
                <button class="pickbtn tor submenuBtn" value="Teamspeak Tags">Teamspeak Tags</button>
                <button class="pickbtn tor submenuBtn" value="Forum Work">Forum Work</button>
                <button class="pickbtn tor submenuBtn" value="Whitelisting">Whitelist</button>
                <button class="pickbtn tor submenuBtn" value="Donation Support">Donation Support</button>
                <button class="pickbtn tor submenuBtn" value="Compensation">Compensation</button>
                <button class="pickbtn tor submenuBtn" value="Ban Log">Ban Log</button>
                <button class="pickbtn tor submenuBtn" value="Unban Log">Unban Log</button>
                <button class="pickbtn tor submenuBtn" value="Other">Other</button>
            </div>
            <p class="label">Punishment</p>
            <button class="pickbtn" id="PunishmentReportButton">Add Punishment Report</button>
<!--            <button class="pickbtn" id="addPointsButton">Add Points <i id="pointangle"-->
<!--                                                                       class="ts2 fas fa-angle-down"></i></button>-->
<!--            <div class="reportTypes" id="addPointsList">-->
<!--                <button class="pickbtn submenuBtn" onclick="addPoints('Fail Roleplay/OOC', 10)">Fail RP/OOC</button>-->
<!--                <button class="pickbtn submenuBtn" onclick="addPoints('RDM/VDM', 10)">RDM/VDM</button>-->
<!--                <button class="pickbtn submenuBtn" onclick="addPoints('Major Disrespect', 25)">Major Disrespect</button>-->
<!--                <button class="pickbtn submenuBtn" onclick="addPoints('Racism', 50)">Racism</button>-->
<!--                <button class="pickbtn submenuBtn" onclick="addPoints('Bigotry', 50)">Bigotry</button>-->
<!--                <button class="pickbtn submenuBtn" onclick="addPoints('Combat Logging', 15)">Combat Logging</button>-->
<!--                <button class="pickbtn submenuBtn" onclick="addPoints('Meta Gaming', 20)">Meta Gaming</button>-->
<!--                <button class="pickbtn submenuBtn" onclick="addPoints('Safezone Violation', 10)">Safezone Violation-->
<!--                </button>-->
<!--                <button class="pickbtn submenuBtn" onclick="addPoints('Exploiting', 25)">Exploiting</button>-->
<!--                <button class="pickbtn submenuBtn" onclick="addPoints('Hacking / Dupping', 50)">Hacking / Dupping-->
<!--                </button>-->
<!--                <button class="pickbtn submenuBtn" onclick="addPoints('NLR Violation', 10)">NLR Violation</button>-->
<!--                <button class="pickbtn submenuBtn" onclick="addPoints('Police Violation', 10)">Police Violation</button>-->
<!--                <button class="pickbtn submenuBtn" onclick="addPoints('EMS Violation', 10)">EMS Violation</button>-->
<!--                <button class="pickbtn submenuBtn" onclick="addPoints('Mass RDM/VDM', 25)">Mass RDM (3+ Killed)</button>-->
<!--                <button class="pickbtn submenuBtn" onclick="addPoints('Website Violation', 10)">Website Violation-->
<!--                </button>-->
<!--                <button class="pickbtn submenuBtn" onclick="addPoints('Lying to Staff', 15)">Lying to Staff</button>-->
<!--                <button class="pickbtn submenuBtn" onclick="addPoints('Disconnecting in Support', 15)">Disconnecting in-->
<!--                    Support-->
<!--                </button>-->
<!--                <button class="pickbtn submenuBtn" onclick="addPoints('Sexual Roleplay', 50)">Sexual Roleplay</button>-->
<!--            </div>-->
            <p class="label">Ban</p>
            <button class="pickbtn" id="BanReportButton">Add Ban Report</button>
            <div style="height: 40px;"></div>
            <div class="bottomBar">
                <span>Logged in as </span>
                <?= $user->displayName(); ?>
            </div>
            <style>
                .bottomBar {
                    position: fixed;
                    left: 0;
                    bottom: 0;
                    width: 16.44445%;
                    background-color: #2c2f50;
                    padding: 12px;
                    border-top-left-radius: 8px;
                    border-top-right-radius: 8px;
                    box-shadow: 0 0 8px 0 rgba(0, 0, 0, 0.3);
                }

                .bottomBar span {
                    color: #888;
                }
            </style>
        </div>
    </div>
    <div class="grid__col grid__col--5-of-6" style="height: 100vh !important;overflow: auto;z-index: 0;">
        <div class="infoPanelContainer">
            <div class="infoPanel">
                <div class="field">
                    <div class="fieldTitle" id="doeTitle">Description Of Events <span id="doeTitleWords"
                                                                                      style="color: #555;">(0 Words)</span>
                    </div>
                    <textarea class="fieldTextarea" id="doi" placeholder="Description Of The Events?*"
                              onkeyup="$('#doeTitleWords').text('('+ wordCount($(this).val()) +' Words)')"></textarea>
                </div>
                <input id="lsm" type="hidden" value="<?= $user->info->username; ?>">
                <input id="typeOfReportField" type="hidden" value="Other">
                <div id="otherStaffList"></div>
                <div id="playerList">
                    <div class='field'>
                        <div class='fieldTitle'>Player Involved #1 (Reporter)</div>
                        <input class='fieldInput' id='player1' placeholder='Add Reporter'><input class='fieldInput'
                                                                                                 id='playerGUID1'
                                                                                                 placeholder='Player GUID'>
                    </div>
                </div>
                <button onclick="confirmSubmit(event)" class="newsubmitBtn">Submit</button>
            </div>
        </div>
    </div>
</div>
<button class="newEditBtn "><i style="font-size: 14px;" class="fas fa-question"></i></button>
<div class="modal" id="confirmCase">
    <button id="close">×</button>
    <div class="content" style="max-width: 900px;padding:0;min-height: 600px;transition: 300ms;">
        <div id="confirmBody" style='margin: 10px;padding-top: 10px;'></div>
        <button style='width:100%;margin: 0;transition: 0;' id="submitRealButton" onclick="submit()">Send</button>
    </div>
</div>
<div class="modal" id="addPunishment">
    <button id="close">×</button>
    <div class="content" style="max-width: 400px;padding:0;">
        <div class="field">
            <div class="fieldTitle">Select Player To Punish</div>
            <select class="fieldSelector" id="selectPlayerToPunish">
                <option disabled selected>Choose A Player</option>
            </select>
        </div>
        <div class="field">
            <div class="fieldTitle">Amount Of Points Issued</div>
            <input type="number" id="amountOfPoints" class="fieldInput" placeholder="10">
        </div>
        <div class="field">
            <div class="fieldTitle">Rules Broken</div>
            <input type="text" id="rulesBroken" class="fieldInput" placeholder="rdm, failrp, etc">
        </div>
        <div class="field">
            <div class="fieldTitle">Comments/Evidence</div>
            <textarea class="fieldTextarea" id="punishmentComments"
                      placeholder="Link to player report, video of offence"></textarea>
        </div>
        <button style='width:100%;margin: 0;transition: 0;border-bottom-right-radius: 3px;border-bottom-left-radius: 3px;'
                id="submitRealButton" onclick="addPunishmentReport()">Add Punishment Report
        </button>
    </div>
</div>
<div class="modal" id="addBan">
    <button id="close">×</button>
    <div class="content" style="max-width: 400px;padding:0;">
        <div class="field">
            <div class="fieldTitle">Select Player To Ban</div>
            <select class="fieldSelector" id="selectPlayerToBan">
                <option disabled selected>Choose A Player</option>
            </select>
        </div>
        <div class="field">
            <div class="fieldTitle">Ban Length</div>
            <input class="fieldInput" id="bl" type="text" placeholder="Ban Length (Days) (0 for perm)*"></div>
        <div class="field">
            <div class="fieldTitle">Ban Message</div>
            <input class="fieldInput" id="bm" type="text" placeholder="Ban Message*"></div>
        <div class="field">
            <div class="fieldTitle">Teamspeak Ban?</div>
            <select class="fieldSelector" id="ts">
                <option value="0">No</option>
                <option value="1">Yes</option>
            </select></div>
        <div class="field">
            <div class="fieldTitle">Ingame Ban?</div>
            <select class="fieldSelector" id="ig">
                <option value="0">No</option>
                <option value="1">Yes</option>
            </select></div>
        <div class="field">
            <div class="fieldTitle">Website Ban?</div>
            <select class="fieldSelector" id="wb">
                <option value="0">No</option>
                <option value="1">Yes</option>
            </select></div>
        <div class="field">
            <div class="fieldTitle">Permanent Ban?</div>
            <select class="fieldSelector" id="pb">
                <option value="0">No</option>
                <option value="1">Yes</option>
            </select></div>
        <button style='width:100%;margin: 0;transition: 0;border-bottom-right-radius: 3px;border-bottom-left-radius: 3px;'
                id="submitRealButton" onclick="addBanReport()">Add Ban Report
        </button>
    </div>
</div>
<script>
    let banReport, punishmentReport, otherStaffParsed, tor = false, aps = false;
    let punishment_reports = [], ban_reports = [];
    //Functionality Script For The New Design
    $('#TypeOfReportButton').click(function () {
        $('#TypeOfReportList').slideToggle(200);
        $('#TypeOfReportButton').toggleClass('open');
        if (!tor) {
            $('#torangle').css('transform', 'rotate(180deg)');
        } else {
            $('#torangle').css('transform', 'rotate(0deg)');
        }
        tor = !tor;
    });
    $('#addPointsButton').click(function () {
        $('#addPointsList').slideToggle(300);
        if (!aps) {
            $('#pointangle').css('transform', 'rotate(180deg)');
        } else {
            $('#pointangle').css('transform', 'rotate(0deg)');
        }
        aps = !aps;
    });
    $(document).on('click', '.tor', function () {
        let typeofreport = $(this).attr('value');
        $('#typeOfReportField').val(typeofreport);
        $('#typeofreportdisplay').text(typeofreport);
        $('#TypeOfReportList').slideToggle(200);
        if (!tor) {
            $('#torangle').css('transform', 'rotate(180deg)');
        } else {
            $('#torangle').css('transform', 'rotate(0deg)');
        }
        tor = !tor;
    });

    function addPunishmentReport() {
        $.post('/api/punishment', {
            points: $('#amountOfPoints').val(),
            rules: $('#rulesBroken').val(),
            comments: $('#punishmentComments').val(),
            player: $('#selectPlayerToPunish').val()
        }, data => {
            data = JSON.parse(data);

            if (data.code === 200) {
                punishment_reports = [...punishment_reports, data.response[0]];
                new Noty({
                    type: 'success',
                    text: `Added Punishment Report For ${$('#selectPlayerToPunish').val()}`,
                    timeout: 3000
                }).show();
            } else {
                new Noty({
                    type: 'error',
                    text: `Failed To Add Punishment Report For ${$('#selectPlayerToPunish').val()} <b>[Error: ${data.message}]</b>`,
                    timeout: 3000
                }).show();
            }
        })
    }

    function addBanReport() {
        $.post('/api/ban', {
            length: $('#bl').val(),
            message: $('#bm').val(),
            teamspeak: $('#ts').val(),
            ingame: $('#ig').val(),
            website: $('#wb').val(),
            permanent: $('#pb').val(),
            player: $('#selectPlayerToBan').val()
        }, data => {
            data = JSON.parse(data);

            if (data.code === 200) {
                ban_reports = [...ban_reports, data.response[0]];
                new Noty({
                    type: 'success',
                    text: `Added Ban Report For ${$('#selectPlayerToBan').val()}`,
                    timeout: 3000
                }).show();
            } else {
                new Noty({
                    type: 'error',
                    text: `Failed To Add Ban Report For ${$('#selectPlayerToBan').val()} <b>[Error: ${data.message}]</b>`,
                    timeout: 3000
                }).show();
            }
        })
    }

    function preparePlayers() {
        let string = "<option selected>Choose A Player</option>";
        let reported = 0;
        playerArray.map((value, index) => {
            index++;
            let name = $(`#player${index}`).val();
            if (value.reported !== undefined && name !== '') {
                reported++;
                string = `${string}<option value="${name}">${name}</option>`;
            }
        });
        if (reported === 0) string = "<option selected>No Reported Players Found</option>";
        $('#selectPlayerToPunish').html(string);
        $('#selectPlayerToBan').html(string);
    }

    $('#PunishmentReportButton').click(function () {
        preparePlayers();
        launchModal('addPunishment');
    });
    $('#BanReportButton').click(function () {
        preparePlayers();
        launchModal('addBan');
    });

    function addPoints(reason, amount) {
        let current = $('#aop').val();
        if ($('#oc').val()) {
            $('#oc').val(`${$('#oc').val()}, ${reason}`)
        } else {
            $('#oc').val(reason)
        }
        if (current !== "") {
            current = parseInt(current);
        }

        if (current + amount >= 20) {
            if (!$('#BanReportButton').attr('open')) {
                $('#banReport').slideDown();
                $('#BanReportButton').text('Remove Ban Report');
                $('#BanReportButton').attr('open', true);
                banReport = 1;
            }
            if (current + amount < 30) {
                $('#bl').val(3);
                $('#bm').val(`3 Day Ban For ${$('#oc').val()} | #${userArray.info.username}`);
            }
        }
        if (current + amount >= 30) {
            if (!$('#BanReportButton').attr('open')) {
                $('#banReport').slideDown();
                $('#BanReportButton').text('Remove Ban Report');
                $('#BanReportButton').attr('open', true);
                banReport = 1;
            }
            if (current + amount < 40) {
                $('#bl').val(7);
                $('#bm').val(`7 Day Ban For ${$('#oc').val()} | #${userArray.info.username}`);
            }
        }
        if (current + amount >= 40) {
            if (!$('#BanReportButton').attr('open')) {
                $('#banReport').slideDown();
                $('#BanReportButton').text('Remove Ban Report');
                $('#BanReportButton').attr('open', true);
                banReport = 1;
            }
            if (current + amount < 50) {
                $('#bl').val(14);
                $('#bm').val(`14 Day Ban For ${$('#oc').val()} | #${userArray.info.username}`);
            }
        }
        if (current + amount >= 50) {
            if (!$('#BanReportButton').attr('open')) {
                $('#banReport').slideDown();
                $('#BanReportButton').text('Remove Ban Report');
                $('#BanReportButton').attr('open', true);
                banReport = 1;
            }
            $('#pb').val(2);
            $('#bl').val('Permenant');
            $('#bm').val(`Permenant Ban For ${$('#oc').val()} | #${userArray.info.username}`);
        }
        if (!$('#PunishmentReportButton').attr('open')) {
            $('#punishmentReport').slideDown();
            $('#PunishmentReportButton').text('Remove Punishment Report');
            $('#PunishmentReportButton').attr('open', true);
            punishmentReport = 1;
        }
        if (typeof current == "number") {
            current += amount;
        } else {
            current = amount;
        }
        new Noty({
            type: 'info',
            text: `Added ${amount} Points For ${reason}`,
            timeout: 5000
        }).show();
        $('#aop').val(current);
    }

    function checks(get) {
        let rList = [];
        let error = false;
        if (wordCount($('#doi').val()) < 5) {
            error = true;
            rList.push(" Word Count Must Be Greater Than 5");
        }
        for (let i = 1; i < otherStaff + 1; i++) {
            if (parseInt($('#os' + i).val()) === 0) {
                error = true;
                rList.push(" All Other Staff Must Be Selected");
            }
        }
        playerArray.forEach(function (value, index) {
            if ($('#player' + index).val() === "") {
                error = true;
                rList.push(" All Players Must Be Filled In");
            }
        });
        if (!error) {
            rList.push(" None");
        }
        if (get === "error") {
            return error;
        } else if (get === "rList") {
            return rList;
        }
    }

    function confirmSubmit(ev) {
        $('#submitRealButton').removeAttr('disabled');
        $('#submitRealButton').css('background-color', '#222');
        $('#submitRealButton').css('border', '');
        $('#submitRealButton').css('color', '#fff');
        $('#submitRealButton').fadeIn(200);
        $('#confirmCase .content').css('max-width', '900px');
        $('#confirmCase .content').css('min-height', '600px');
        $('#confirmCase .content').css('border-radius', '');
        var gotPoints, gotBanned;
        if (punishmentReport === 1) {
            gotPoints = "Yes"
        } else {
            gotPoints = "No"
        }
        if (banReport === 1) {
            gotBanned = "Yes"
        } else {
            gotBanned = "No"
        }
        otherStaffParsed = "";
        for (let i = 1; i < otherStaff + 1; i++) {
            otherStaffParsed += $('#os' + i).val() + " ";
            console.log(otherStaffParsed);
        }
        let list = '<div style="height: 100%;" id="case_info"><p id="case"><span>Case Title: ' + $('#player1').val() + '</span></p><p id="case"><span>Lead Staff:</span> ' + $('#lsm').val() + '</p><p id="case"><span>Other Staff:</span> ' + otherStaffParsed + '</p><p id="case"><span>Type Of Report:</span> ' + $('#typeOfReportField').val() + '</p><p id="case"><span>Description Of Events:</span> ' + $('#doi').val() + '</p><p id="case"><span>Link To Player Report:</span>' + $('#ltpr').val() + '</p><p id="case"><span>Points?:</span> ' + gotPoints + '</p><p id="case"><span>Amount Of Points:</span> ' + $('#aop').val() + '</p><p id="case"><span>Offence Committed:</span><br> ' + $('#oc').val() + '</p><p id="case"><span>Evidence Given:</span><br> ' + $('#es').val() + '</p><p id="case"><span>Banned?:</span> ' + gotBanned + '</p><p id="case"><span>Ban Length:</span> ' + $('#bl').val() + ' Days</p><p id="case"><span>Ban Message:</span><br> ' + $('#bm').val() + '</p><p id="case"><span>TS Ban:</span> ' + onetwotoyesno($('#ts').val()) + '</p><p id="case"><span>Ingame Ban:</span> ' + onetwotoyesno($('#ig').val()) + '</p><p id="case"><span>Website Ban:</span> ' + onetwotoyesno($('#wb').val()) + '</p><p id="case"><span>Permenant Ban:</span> ' + onetwotoyesno($('#pb').val()) + '</p><p id="case"><span>Timestamp:</span> ' + currentTime() + '</p><p id="case"><span>Errors:</span>' + checks("rList") + '</p></div>';
        if (checks("error")) {
            $('#submitRealButton').attr('disabled', 'true');
            $('#submitRealButton').css('background-color', '#3f3f3f');
            $('#submitRealButton').css('border', 'none');
            $('#submitRealButton').css('color', '#ccc');
        }
        $('#confirmBody').html(linkify(list));
        if (ev.ctrlKey) {
            submit();
        } else {
            launchModal('confirmCase');
        }
    }

    function onetwotoyesno(val) {
        if (val === 1) return 'Yes';
        return 'No';
    }

    let staffList = "";

    function submit() {
        if (!checks("error")) {
            $('#confirmCase .content').css('max-width', '100px');
            $('#confirmCase .content').css('min-height', '100px');
            setTimeout(function () {
                $('#confirmCase .content').css('border-radius', '50%');
            }, 100);
            $('#confirmBody').html("<center><h1><img src='../../Before/Purple-Iron-Bulldog/img/loadw.svg'></h1></center>");
            $('#submitRealButton').fadeOut(200);
            let type;
            otherStaffParsed = "";
            for (let i = 1; i < otherStaff + 1; i++) {
                otherStaffParsed += $('#os' + i).val() + " ";
                console.log(otherStaffParsed);
            }
            playerArray.forEach(function (value, index) {
                console.log(value + index)
                type = "";
                if (playerArray[index].reported == undefined) {
                    type = "reporter";
                } else {
                    type = "reported";
                }
                playerArray[index] = {
                    type: type,
                    name: $('#player' + index).val(),
                    guid: $('#playerGUID' + index).val()
                };
            });
            playerArray.splice(0, 1);
            $.post('api/submitCase', {
                'lead_staff': $('#lsm').val(),
                'other_staff': otherStaffParsed,
                'description_of_events': $('#doi').val(),
                'player_guid': $('#guid').val(),
                'link_to_player_report': $('#ltpr').val(),
                'offence_committed': $('#oc').val(),
                'points_awarded': punishmentReport,
                'ammount_of_points': $('#aop').val(),
                'evidence_supplied': $('#es').val(),
                'ban_awarded': banReport,
                'ban_length': $('#bl').val(),
                'ban_message': $('#bm').val(),
                'ts_ban': $('#ts').val(),
                'ingame_ban': $('#ig').val(),
                'website_ban': $('#wb').val(),
                'ban_perm': $('#pb').val(),
                'players': playerArray,
                'type_of_report': $('#typeOfReportField').val(),
                'csrf': $('#csrf').val(),
                'punishment_reports': punishment_reports,
                'ban_reports': ban_reports
            }, function (data) {
                $('#osi').val('');
                $('#doi').val('');
                $('#guid').val('');
                $('#ltpr').val('');
                $('#oc').val('');
                $('#apg').val('');
                $('#aop').val('');
                $('#es').val('');
                $('#bl').val('');
                $('#bm').val('');
                $('#pt').val('');
                $('#ts').val('1');
                $('#ig').val('1');
                $('#wb').val('1');
                $('#pb').val('1');
                $('#name').val('');
                $('#punishmentReport').slideUp();
                $('#PunishmentReportButton').text('Add Punishment Report');
                $('#PunishmentReportButton').removeAttr('open');
                punishmentReport = 0;
                $('#banReport').slideUp();
                $('#BanReportButton').text('Add Ban Report');
                $('#BanReportButton').removeAttr('open');
                banReport = 0;
                $('#otherStaffList').html('');
                otherStaff = 0;
                $('#playerList').html("<div class='field'><div class='fieldTitle'>Player Involved #1 (Reporter)</div><input class='fieldInput' id='player1' placeholder='Add Reporter'><input class='fieldInput' id='playerGUID1' placeholder='Player GUID'></div>");
                playerCount = 1;
                playerArray = [{}];
                playerArray.push({
                    reporter: ''
                });
                $('#doeTitleWords').text('(' + wordCount($('#doi').val()) + ' Words)');
                new Noty({
                    type: 'success',
                    layout: 'topRight',
                    theme: 'metroui',
                    timeout: 3000,
                    text: 'Case Logged Successfully!',
                }).show();
                $('#confirmBody').fadeOut(200);
                setTimeout(() => {
                    $('#confirmBody').html('<center><img src="../../Before/Purple-Iron-Bulldog/img/success.svg"></center>');
                    $('#confirmBody').fadeIn(200);
                }, 200);
                setTimeout(() => {
                    closeAllModal()
                }, 1000)
            });
        } else {
            new Noty({
                type: 'warning',
                text: checks("rList"),
                timeout: 10000
            }).show();
        }
    }

    let otherStaff = 0;
    let playerCount = 1;
    let playerArray = [];
    $(document).ready(function () {
        playerArray.push({
            reporter: ''
        });
        $('#addOtherStaff').click(function () {
            if (otherStaff < 10) {
                otherStaff++;
                $('#otherStaffList').append("<div class='field' style='display: none;'><div class='fieldTitle'>Assistant Staff Member #" + otherStaff + "</div><select class='fieldSelector' id='os" + otherStaff + "'><option value='0'>Select A Staff Member</option>" + staffList + "</select></div>");
                $('#otherStaffList .field').last().slideDown(150);
            } else {
                new Noty({
                    type: 'error',
                    layout: 'topRight',
                    theme: 'metroui',
                    timeout: 3000,
                    text: 'Max Other Staff Reached (10)',
                }).show();
            }
        });
        $('#removeOtherStaff').click(function () {
            if (otherStaff > 0) {
                $('#otherStaffList .field').last().slideUp(150);
                setTimeout(function () {
                    $('#otherStaffList .field').last().remove();
                }, 150);
                otherStaff--;
            }
        });
        $('#addPlayerReporter').click(function () {
            if (playerCount < 25) {
                playerCount++;
                playerArray.push({
                    reporter: ''
                });
                $('#playerList').append("<div class='field' style='display: none;'><div class='fieldTitle'>Player Involved #" + playerCount + " (Reporter)</div><input class='fieldInput' id='player" + playerCount + "' placeholder='Add Reporter'><input class='fieldInput' id='playerGUID" + playerCount + "' placeholder='Player GUID'></div>");
                $('#playerList .field').last().slideDown(150);
            } else {
                new Noty({
                    text: 'Maximum Of 25 Players',
                    type: 'error'
                }).show();
            }
        });
        $('#addPlayerReported').click(function () {
            if (playerCount < 25) {
                playerCount++;
                playerArray.push({
                    reported: ''
                });
                $('#playerList').append("<div class='field' style='display: none;'><div class='fieldTitle'>Player Involved #" + playerCount + " (Reported)</div><input class='fieldInput' id='player" + playerCount + "' placeholder='Add Reported Player'><input class='fieldInput' id='playerGUID" + playerCount + "' placeholder='Player GUID'></div>");
                $('#playerList .field').last().slideDown(150);
            } else {
                new Noty({
                    text: 'Maximum Of 25 Players',
                    type: 'error'
                }).show();
            }
        })
        $('#removePlayer').click(function () {
            if (playerCount > 1) {
                playerArray.splice(-1, 1);
                $('#playerList .field').last().slideUp(150);
                setTimeout(function () {
                    $('#playerList .field').last().remove();
                }, 150);
                playerCount--;
            }
        });
        gsl();
    });

    function wordCount(str) {
        return str.trim().split(/\s+/).length;
    }

    function gsl() {
        $.get('api/getStaffList', function (data) {
            let staff = JSON.parse(data);
            for (let i = 1; i < Object.keys(staff).length + 1; i++) {
                staffList += `<option value='${staff[i].name}'>${staff[i].display}</option>`;
            }
            $('#addOtherStaff').removeAttr('disabled');
            $('#removeOtherStaff').removeAttr('disabled');
        });
    }
</script>
</body>
<!--Created By Kieran Holroyd-->
</html>