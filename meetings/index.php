<?php
include "../head.php";
include "../classes/Meetings.php";
Guard::init()->LoginRequired();
if ($_GET['meeting'] == ""):
    ?>
    <script src="https://unpkg.com/tippy.js@2.2.3/dist/tippy.all.min.js"
            xmlns:v-on="http://www.w3.org/1999/xhtml"></script>
    <div id="titleText" style="z-index:2;display:table;width:100vw;text-align:center;table-layout: fixed;">
        <h1 style="display:table-cell;">Hello, <?= $user->info->username; ?></h1>
        <h1 style="display:table-cell;"
            title="<b><?php echo date("l m/d/Y"); ?>&nbsp;&nbsp;</b><img width='16px' src='https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/3.0.0/flags/4x3/us.svg'>"><?php echo date("l d/m/Y"); ?></h1>
        <h1 style="display:table-cell;">Welcome To Meetings</h1>
    </div>
    <div id="meetings" class="navCards meeting">
        <?php foreach (Meetings::list($user) as $meeting): ?>
            <a href="<?= $meeting->id; ?>">
                <div class="navCard-small">
                    <div class="navCard-items">
                        <p class="title"><?= $meeting->type . $meeting->date; ?></p>
                        <p class="shortcontent"><?= $meeting->points; ?> Points From Staff</p>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
    <?php if ($user->isSLT() || $user->isCommand()): ?>
    <button class="newMeeting" id="modalLaunch" launch="createMeeting">Schedule New Meeting</button>
    <div class="modal" id="createMeeting">
        <button id="close">×</button>
        <div class="content" style="max-width: 600px;border-radius: 5px;">
            <div class="field">
                <div class="fieldTitle">Date</div>
                <input type="date" id="date" class="fieldSelector"></div>
            <div class="field">
                <div class="fieldTitle">Meeting Type?</div>
                <select id="meetingType" class="fieldSelector">
                    <option selected disabled>Select A Meeting Type</option>
                    <?php if ($user->isSLT()): ?>
                        <option value="slt">SLT Meeting</option>
                        <option value="staff">Staff Meeting</option>
                    <?php endif; ?>
                    <?php if ($user->isPD()): ?>
                        <option value="pd">PD Meeting</option>
                    <?php endif; ?>
                    <?php if ($user->isEMS()): ?>
                        <option value="ems">EMS Meeting</option>
                    <?php endif; ?>
                </select></div>
            <button class="newsubmitBtn" onclick="addNewMeeting()">Schedule Meeting</button>
        </div>
    </div>
<?php endif; ?>
    <script>
        tippy('h1');

        setTimeout(function () {
            if (userArray.info.slt === 1) {
                $('.newMeeting').slideDown(200);
            }
        }, 3000);

        function addMeetingToList(json) {
            $('#meetings').prepend(`<a href="${json.id}"><div class="navCard-small"><div class="navCard-items"><p class="title" title="<b>${json.wrongDate} </b><img width='16px' src='https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/3.0.0/flags/4x3/us.svg'>" style="color:#${color};">${json.date}</p><p class="shortcontent" style="color:#16a085;">0 Points From Staff</p></div></div></a>`);
        }

        function addNewMeeting() {
            $.post('/api/addMeeting', {
                "date": $('#date').val(),
                "type": $('#meetingType').val()
            }, function (data) {
                data = JSON.parse(data);

                if (data.code === 200) {
                    window.location.reload();
                } else {
                    new Noty({
                        type: 'error',
                        text: data.message,
                        timeout: 4000
                    }).show();
                }
            });
        }
    </script>
    <style>
        .meeting .navCard-small {
            background-color: #4e4c79;
        }
    </style>
<?php else: ?>
    <script src="https://unpkg.com/react@16/umd/react.production.min.js" crossorigin></script>
    <script src="https://unpkg.com/react-dom@16/umd/react-dom.production.min.js" crossorigin></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
    <script type="text/babel" src="components/App.js?8"></script>
    <script type="text/babel" src="components/Meeting.js?70"></script>
    <?php $meeting = Meetings::fromID($_GET['meeting']); ?>
    <script type="text/babel">
        ReactDOM.render(<App meetingDate={"<?= $meeting->date; ?>"}
                             meetingID={<?= $meeting->id; ?>}/>, document.querySelector('#app'));
    </script>
    <div id="app">
        <div style="padding: 20px 70px;"><img style="width: 24px;" src="/img/loadw.svg" alt="loading"> Loading...</div>
    </div>
<?php endif; ?>