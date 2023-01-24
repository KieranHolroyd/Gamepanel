<?php include "../head.php";
Guard::init()->SLTRequired();
?>
<div class="grid new">
    <div class="grid__col grid__col--2-of-6" style="padding-left: 20px !important;">
        <h1 class="info-title new">Team Overview</h1>
        <div id="staff" class="selectionPanel">
            <?php
            ini_set("display_errors", "0");
            $teams = [null => 0];
            foreach ($pdo->query("SELECT * FROM users WHERE (isStaff = 1 OR (isStaff = 0 AND isEMS = 0 AND isPD = 0))") as $r) {
                $teams[$r->staff_team]++;
            }
            foreach ($teams as $key => $count) {
                $team = Config::$teams[$key];
                if (gettype($team) == 'integer') {
                    $name = "Team {$key}";
                } else {
                    $name = $team;
                }
                $navKey = ($key == null) ? 0 : $key;
                echo "<a href='#team{$navKey}'><div class=\"selectionTab\"><span style=\"float: right;vertical-align: top;font-size: 12px;\">{$count} Members</span><span style=\"font-size: 25px;\">{$name}</span></div></a>";
            }
            ?>
        </div>
    </div>
    <div class="grid__col grid__col--4-of-6">
        <div class="infoPanelContainer">
            <div id="staff_info" class="infoPanel">
                <div class="pre_title">Select Team For Details</div>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(() => {
        checkHashString(window.location.hash);
    });
    window.onpopstate = () => {
        checkHashString(window.location.hash);
    };

    function checkHashString(str) {
        switch (str.substring(1, 5)) {
            case "team":
                getTeamStats(str.substring(5));
                break;
        }
    }

    function getTeamTitle(key) {
        switch (parseInt(key)) {
            case 0:
                return "<h1>Unassigned Members</h1>";
            case 100:
                return "<h1>Senior Management Team</h1>";
            case 500:
                return "<h1>Development Team</h1>";
            default:
                return `<h1>Staff Team ${key}</h1>`;
        }
    }

    function getTeamStats(key) {
        if (key === undefined) key = '0';
        $.get(`/api/v1/teamStats?team=${key}`, data => {
            data = JSON.parse(data);

            let setHTML = getTeamTitle(key);

            setHTML += `<div class="field"><input class="fieldInput" type="text" onkeyup="initSearch(event)" placeholder="Search Staff (Case Sensitive)"></div>`;

            if (data.response.staff.length === 0) setHTML += '<p>Staff Team Empty</p>';
            if (data.response.staff.length > 0) setHTML += parseStaffTeam(data.response.staff);

            $('#staff_info').html(setHTML);
        });
    }

    function safetyCheck(member) {
        if (member.rank === null) member.rank = '';
        return member;
    }

    function parseStaffTeam(team) {
        let teams = "";

        for (let member of team) {
            member = safetyCheck(member);
            teams += `<div class="clearfix staff-member"><div class="staff-list">${member.rank} ${member.username}</div> <span class="right">
                    Open in: <a href="/staff/#User:${member.id}">Staff Manager</a> ~ <a href="/factions/manage#player:${member.id}">Faction Manager</a>
            </span></div>`;
        }

        return teams;
    }

    function initSearch(e) {
        if (e.target.value.length === 0) clearSearch();
        if (e.target.value.length > 0) searchStaff(e.target.value);
    }

    function clearSearch() {
        $('.searchStaff').show();
    }

    function searchStaff(q) {
        $('.searchStaff').hide();
        $(`.searchStaff:contains("${q}")`).show();
    }

    function openStaffMember(id) {
        window.location.href = `/staff/#staf${id}`;
    }
</script>