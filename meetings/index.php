<?php
include "../head.php";
include "../classes/Meetings.php";
Guard::init()->LoginRequired();
if (!isset($_GET['meeting']) || $_GET['meeting'] == "") :
?>
    <script src="https://unpkg.com/tippy.js@2.2.3/dist/tippy.all.min.js" xmlns:v-on="http://www.w3.org/1999/xhtml"></script>
    <div id="titleText" style="z-index:2;width:100vw;text-align:center;">
        <h1 title="<b><?php echo date("l m/d/Y"); ?>&nbsp;&nbsp;</b><img width='16px' src='https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/3.0.0/flags/4x3/us.svg'>"><?php echo date("l d/m/Y"); ?></h1>
    </div>
    <div id="meetings" class="navCards meeting">
    </div>
    <?php if ($user->isSLT() || $user->isCommand()) : ?>
        <button class="newMeeting" id="modalLaunch" launch="createMeeting">Schedule New Meeting</button>
        <div class="modal" id="createMeeting">
            <button id="close">×</button>
            <div class="content" style="max-width: 600px;border-radius: 5px;">
                <div class="field">
                    <div class="fieldTitle">Date</div>
                    <input type="date" id="date" class="fieldSelector">
                </div>
                <div class="field">
                    <div class="fieldTitle">Meeting Type?</div>
                    <select id="meetingType" class="fieldSelector">
                        <option selected disabled>Select A Meeting Type</option>
                        <?php if ($user->isSLT()) : ?>
                            <option value="slt">SLT Meeting</option>
                            <option value="staff">Staff Meeting</option>
                        <?php endif; ?>
                        <?php if ($user->isPD()) : ?>
                            <option value="pd">PD Meeting</option>
                        <?php endif; ?>
                        <?php if ($user->isEMS()) : ?>
                            <option value="ems">EMS Meeting</option>
                        <?php endif; ?>
                    </select>
                </div>
                <button class="newsubmitBtn" onclick="addNewMeeting()">Schedule Meeting</button>
            </div>
        </div>
    <?php endif; ?>
    <script>
        tippy('h1');

        setTimeout(function() {
            if (userArray.info.slt === 1) {
                $('.newMeeting').slideDown(200);
            }
        }, 3000);

        function listMeetings() {
            apiclient.get(`/api/v2/meetings/list`).then(({
                data
            }) => {
                if (data.success) {
                    let meeting_list = ``;
                    data.meetings.map(meeting => {
                        meeting_list += `<a href="/meetings/${meeting.id}">
                            <div class="navCard-small">
                                <div class="navCard-items">
                                    <p class="title">${meeting.type} ${meeting.date}</p>
                                    <p class="shortcontent">${meeting.points} Points From Staff</p>
                                </div>
                            </div>
                        </a>`;
                    });
                    $('#meetings').html(meeting_list);
                } else {
                    $('#meetings').html(`<div class="navCard-small"><div class="navCard-items"><p class="title">No Meetings Found</p><p class="shortcontent">Currently no meetings scheduled</p></div>`)
                }
            }).catch(noty_catch_error)
        }

        function addMeetingToList(json) {
            $('#meetings').prepend(`<a href="${json.id}"><div class="navCard-small"><div class="navCard-items"><p class="title" title="<b>${json.wrongDate} </b><img width='16px' src='https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/3.0.0/flags/4x3/us.svg'>" style="color:#${color};">${json.date}</p><p class="shortcontent" style="color:#16a085;">0 Points From Staff</p></div></div></a>`);
        }

        function addNewMeeting() {
            apiclient.post('/api/v2/meetings/add', {
                "date": $('#date').val(),
                "type": $('#meetingType').val()
            }).then(({
                data
            }) => {
                if (data.success) {
                    listMeetings();
                    closeModal('createMeeting')
                } else {
                    new Noty({
                        type: 'error',
                        text: data.message,
                        timeout: 4000
                    }).show();
                }
            });
        }

        window.addEventListener('load', function() {
            listMeetings();
        });
    </script>
    <style>
        .meeting .navCard-small {
            background-color: #4e4c79;
        }
    </style>
<?php else : ?>
    <?php $meeting = Meetings::fromID($_GET['meeting']); ?>
    <meta name="data-meeting-date" content="<?= $meeting->date ?>">
    <meta name="data-meeting-id" content="<?= $meeting->id ?>">
    <meta name="data-pusher-id" content="<?= Config::$pusher['AUTH_KEY'] ?>">
    <link rel="stylesheet" href="/app/dist/meetings/index.css">
    <script type="module" src="/app/dist/meetings/index.js"></script>
    <div id="app">
        <div style="padding: 20px 70px;"><img style="width: 24px;" src="/img/loadw.svg" alt="loading"> Loading...</div>
    </div>
<?php endif; ?>