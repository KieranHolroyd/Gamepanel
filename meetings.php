<?php
include "head.php";
Guard::init()->StaffRequired();
?>
    <script src="https://unpkg.com/tippy.js@2.2.3/dist/tippy.all.min.js"></script>
    <div id="titleText" style="z-index:2;display:table;width:100vw;text-align:center;table-layout: fixed;">
        <h1 id="welcome" style="display:table-cell;">Hello, Human</h1>
        <h1 style="display:table-cell;"
            title="<b><?php echo date("l m/d/Y"); ?>&nbsp;&nbsp;</b><img width='16px' src='https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/3.0.0/flags/4x3/us.svg'>"><?php echo date("l d/m/Y"); ?></h1>
        <h1 style="display:table-cell;">Welcome To Meetings</h1>
    </div>
    <div id="meetings"></div>
    <?php if ($user->isSLT()): ?>
        <button style="border-radius:4px;position:fixed;bottom:10px;left:10px;box-shadow:0 0 5px 0 rgba(0,0,0,0.2);"
                class="newMeeting" id="modalLaunch" launch="createMeeting">Schedule New Meeting
        </button>
    <?php endif; ?>
    <div class="modal" id="createMeeting">
        <button id="close">×</button>
        <div class="content" style="max-width: 600px;border-radius: 5px;">
            <div class="field">
                <div class="fieldTitle">Date</div>
                <input type="date" id="date" class="fieldSelector"></div>
            <div class="field">
                <div class="fieldTitle">SLT Only?</div>
                <select id="sltonly" class="fieldSelector">
                    <option value="0">No</option>
                    <option value="1">Yes</option>
                </select></div>
            <button class="newsubmitBtn" onclick="addNewMeeting()">Schedule Meeting</button>
        </div>
    </div>
    <script>
        tippy('h1');

        function getMeetings() {
            $.get('https://www.nitrexdesign.co.uk/caselogger/api/v1/getMeetings', function (data) {
                var list = "";
                var json = JSON.parse(data);
                for (var i = 1; i < Object.keys(json).length + 1; i++) {
                    var color = "1abc9c";
                    var today = "";
                    var slt = "";
                    if (json[i].date === "<?php echo date("d/m/Y"); ?>") {
                        color = "ff9966";
                        today = " [Today]";
                    }
                    if (json[i].slt !== undefined) {
                        slt = " [SLT]";
                    }
                    list += '<a href=' + json[i].id + '"../../Before/Purple-Iron-Bulldog/meetings"><div class="navCard-small"><div class="navCard-items"><p class="title" title="<b>' + json[i].wrongDate + ' </b><img width=\'16px\' src=\'https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/3.0.0/flags/4x3/us.svg\'>" style="color:#' + color + ';">' + json[i].date + today + slt + '</p><p class="shortcontent" style="color:#16a085;">' + json[i].points + ' Points From Staff</p></div></div></a>';
                }
                $('#meetings').html(list);
                tippy('.title');
            })
        }

        getMeetings();

        function addNewMeeting() {
            $.post('https://www.nitrexdesign.co.uk/caselogger/api/v1/addMeeting', {
                "date": $('#date').val(),
                "slt": $('#sltonly').val()
            }, function (data) {
                getMeetings();
                new Noty({
                    type: 'success',
                    layout: 'topRight',
                    theme: 'metroui',
                    timeout: 3000,
                    text: data,
                }).show();
            });
        }
    </script>