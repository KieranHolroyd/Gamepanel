<?php include "../head.php";
include $_SERVER['DOCUMENT_ROOT'] . '/classes/Interviews.php';
Guard::init()->SLTRequired();
?>
<div class="grid new">
    <div class="grid__col grid__col--2-of-6" style="padding-left: 20px !important;">
        <h1 class="info-title new">Staff Interviews</h1>
        <div id="staff" class="selectionPanel">

        </div>
    </div>
    <button onclick="toggleCreateNewInterview()" class="newPointBtn">+</button>
    <div class="grid__col grid__col--4-of-6">
        <div class="infoPanelContainer">
            <div class="infoPanel" id="staff_info">
                <div class="pre_title">Select Interview For Details</div>
            </div>
        </div>
    </div>
</div>
<div class="drawer" id="interview">
    <h1>New Interview</h1>
    <form onsubmit="handleNewInterviewSubmit(event)">
        <div class="field">
            <p class="fieldTitle">Applicant Name</p>
            <input class="fieldInput" type="text"
                   placeholder="Applicant Name" id="applicantName"/>
        </div>
        <div class="field">
            <p class="fieldTitle">Applicant Region</p>
            <select class="fieldSelector" id="applicantRegion">
                <option value="null" selected disabled>Select Region</option>
                <option value="EU">European Union</option>
                <option value="NA">USA/Canada</option>
                <option value="AF">Africa</option>
                <option value="AU">Oceania</option>
            </select>
        </div>
        <div class="field">
            <p class="fieldTitle">Previous Experience</p>
            <textarea id="previousExperience" class="fieldTextarea pdesc" placeholder="Previous Experience"></textarea>
        </div>
        <div class="field">
            <p class="fieldTitle">Any Previous Bans?</p>
            <textarea id="previousBans" class="fieldTextarea pdesc" placeholder="Previous Bans"></textarea>
        </div>
        <div class="field">
            <p class="fieldTitle">How Much Time Can They Dedicate?</p>
            <textarea id="dedicateTime" class="fieldTextarea pdesc" placeholder="Dedicate Time"></textarea>
        </div>
        <div class="field">
            <p class="fieldTitle">How Much Time For Support?</p>
            <textarea id="timeAwayFromServer" class="fieldTextarea pdesc" placeholder="Time Away From Server"></textarea>
        </div>
        <div class="field">
            <p class="fieldTitle">Can They Work Flexibly?</p>
            <select class="fieldSelector" id="workFlexibly">
                <option value="Yes" selected disabled>Select Option</option>
                <option value="Yes">Yes</option>
                <option value="No">No</option>
            </select>
        </div>
        <button type="submit" id="ignoreSubmitButton" class="createPointBtn">Create</button>
    </form>
</div>

<div class="drawer" style="z-index: 99;" id="edit">
    <h1>Update Interview
        <button style="margin: 0;padding: 4px 8px;float: right;border-radius: 4px;" onclick="toggleUpdateInterview()">Close</button>
    </h1>
    <form onsubmit="handleEditInterviewSubmit(event)">
        <div class="field">
            <p class="fieldTitle">Applicant Name</p>
            <input class="fieldInput" type="text"
                   placeholder="Applicant Name" id="edit_applicantName"/>
        </div>
        <div class="field">
            <p class="fieldTitle">Applicant Region</p>
            <select class="fieldSelector" id="edit_applicantRegion">
                <option value="null" selected disabled>Select Region</option>
                <option value="EU">European Union</option>
                <option value="NA">USA/Canada</option>
                <option value="AF">Africa</option>
                <option value="AU">Oceania</option>
            </select>
        </div>
        <div class="field">
            <p class="fieldTitle">Previous Experience</p>
            <textarea id="edit_previousExperience" class="fieldTextarea pdesc" placeholder="Previous Experience"></textarea>
        </div>
        <div class="field">
            <p class="fieldTitle">Any Previous Bans?</p>
            <textarea id="edit_previousBans" class="fieldTextarea pdesc" placeholder="Previous Bans"></textarea>
        </div>
        <div class="field">
            <p class="fieldTitle">How Much Time Can They Dedicate?</p>
            <textarea id="edit_dedicateTime" class="fieldTextarea pdesc" placeholder="Dedicate Time"></textarea>
        </div>
        <div class="field">
            <p class="fieldTitle">How Much Time For Support?</p>
            <textarea id="edit_timeAwayFromServer" class="fieldTextarea pdesc"
                      placeholder="Time Away From Server"></textarea>
        </div>
        <div class="field">
            <p class="fieldTitle">Can They Work Flexibly?</p>
            <select class="fieldSelector" id="edit_workFlexibly">
                <option value="Yes" selected disabled>Select Option</option>
                <option value="Yes">Yes</option>
                <option value="No">No</option>
            </select>
        </div>
        <div class="field">
            <p class="fieldTitle">Did They Pass?</p>
            <select class="fieldSelector" id="edit_passed">
                <option value="-1" selected disabled>Select Option</option>
                <option value="1">Yes</option>
                <option value="0">No</option>
            </select>
        </div>
        <div class="field">
            <p class="fieldTitle">Has this been processed?</p>
            <select class="fieldSelector" id="edit_processed">
                <option value="-1" selected disabled>Select Option</option>
                <option value="1">Yes</option>
                <option value="0">No</option>
            </select>
        </div>
        <button type="submit" id="ignoreSubmitButton" class="createPointBtn">Update</button>
    </form>
</div>

<script>
    let open = false;
    let updateOpen = false;
    let updateID = null;

    function getInterviews() {
        $.get('helper/getInterviews.php', data => {
            $('#staff').html(data);
        });
    }

    getInterviews();

    function handleNewInterviewSubmit(e) {
        e.preventDefault();
        let params = {};
        console.log(e);
        for (let i = 0; i < e.target.elements.length; i++) {
            const target = e.target.elements[i];
            if (target.id !== 'ignoreSubmitButton') {
                params = {
                    ...params,
                    [target.id]: target.value
                }
            }
        }
        console.log(params);
        $.post('/api/v1/interview', {
            ...params
        }, data => {
            data = JSON.parse(data);
            if (data.code === 200) {
                getInterviews();
            } else {
                new Noty({
                    text: data.message,
                    type: 'error',
                    timeout: 4000
                }).show();
            }
        });
    }

    function handleEditInterviewSubmit(e) {
        e.preventDefault();
        if (updateID !== null) {
            let params = {};
            console.log(e);
            for (let i = 0; i < e.target.elements.length; i++) {
                const target = e.target.elements[i];
                if (target.id !== 'ignoreSubmitButton') {
                    params = {
                        ...params,
                        [target.id]: target.value
                    }
                }
            }
            console.log(params);
            $.post('/api/v1/editInterview', {
                ...params,
                updateID
            }, data => {
                data = JSON.parse(data);
                if (data.code === 200) {
                    getInterviews();
                    getInterviewDetails(updateID);
                } else {
                    new Noty({
                        text: data.message,
                        type: 'error',
                        timeout: 4000
                    }).show();
                }
            });
        }
    }

    function updateEditInterviewValues(interview) {
        $('#edit_applicantName').val(interview.applicant_name);
        $('#edit_applicantRegion').val(interview.applicant_region);
        $('#edit_previousExperience').val(interview.previous_experience);
        $('#edit_previousBans').val(interview.ever_banned_reason);
        $('#edit_dedicateTime').val(interview.how_much_time);
        $('#edit_timeAwayFromServer').val(interview.time_away_from_server);
        $('#edit_workFlexibly').val(interview.work_flexibly);
        $('#edit_passed').val(interview.passed);
        $('#edit_processed').val(interview.processed);
    }

    function getInterviewDetails(id) {
        if (id !== null) {
            $.get(`/api/v1/interviewDetails?id=${id}`, data => {
                data = JSON.parse(data);
                let interview = data.response;
                updateID = interview.id;
                let passed_string = (parseInt(interview.passed)) ? 'Yes' : 'No';
                let processed_string = (parseInt(interview.processed)) ? 'Yes' : 'No';
                let html = `<h1>Interview Details</h1><p><b>Applicant: </b>${interview.applicant_name}</p><p><b>Previous Experience: </b>${interview.previous_experience}</p><p><b>Previously Banned: </b>${interview.ever_banned_reason}</p><p><b>How Much Time Can You Give To Staff: </b>${interview.how_much_time}</p><p><b>Can You Give Time Away From The Server: </b>${interview.time_away_from_server}</p><p><b>Can You Work Flexibly: </b>${interview.work_flexibly}</p><p><b>Passed? </b>${passed_string}</p><p><b>Processed? </b>${processed_string}</p><p><b>Applicant Region: </b>${interview.applicant_region}</p><p><b>Interviewer: </b>${interview.interviewer.username}</p><div class="btnGroup"><button onclick="toggleUpdateInterview()">Update Interview</button></div>`;

                updateEditInterviewValues(interview);

                $('#staff_info').html(html);
                console.log(data);
            });
        }
    }

    function toggleUpdateInterview() {
        if (!updateOpen) {
            $('#edit').addClass('open');
        } else {
            $('#edit').removeClass('open');
        }
        updateOpen = !updateOpen;
    }

    function toggleCreateNewInterview() {
        if (!open) {
            $('#interview').addClass('open');
            $('.newPointBtn').addClass('open');
        } else {
            $('#interview').removeClass('open');
            $('.newPointBtn').removeClass('open');
        }
        open = !open;
    }
</script>