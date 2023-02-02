<?php include "../head.php";
Guard::init()->SLTRequired();
?>
<div class="grid new">
    <div class="grid__col grid__col--2-of-6" style="padding-left: 20px !important;">
        <h1 class="info-title new"><?= Config::$name; ?> Applicants</h1>
        <div id="applicants" class="selectionPanel">

        </div>
        <div id="cngcont" style="display: none;">
            <div style="height: 50px;"></div>
        </div>
    </div>
    <div class="grid__col grid__col--4-of-6">
        <div class="infoPanelContainer">
            <div class="infoPanel" id="guide_info">
                <div class="pre_title">Select Application For Details</div>
            </div>
        </div>
    </div>
</div>
<script>
    function GetApplicants() {
        apiclient.get('/api/v2/staff/applications/list').then(({
            data
        }) => {
            let html = '';
            if (data.applications !== undefined && data.applications.length > 0) {
                for (let i = 0; i < data.applications.length; i++) {
                    let applicant = data.applications[i];
                    html += `<div class="selectionTab" onclick="GetApplicant('${applicant.id}')">${applicant.name}</div>`;
                }
                $('#applicants').html(html);
            } else {
                $('#applicants').html('<div class="selectionTab">No Applicants</div>');
            }
        }).catch(noty_catch_error);
    }

    function GetApplicant(id) {
        apiclient.get(`/api/v2/staff/applications/get?id=${id}`).then(({
            data
        }) => {
            let html = '';
            if (data.application !== undefined) {
                const app_data = JSON.parse(data.application.data);
                console.log(app_data)
                html += `<h2>${data.application.name}</h2><p>Submitted at ${data.application.created_at}</p>`;
                html += `<div class="field"><div class="fieldTitle">Name</div><div>${data.application.name}</div></div>`;
                html += `<div class="field"><div class="fieldTitle">Status</div><div>${data.application.status}</div></div>`;
                html += Object.keys(app_data).map((key) => {

                    return `<div class="field"><div class="fieldTitle" style="text-transform: capitalize;">${key.replaceAll('_', ' ')}</div><div>${app_data[key]}</div></div>`;
                }).join('');
                $('#guide_info').html(html);
            } else {
                $('#guide_info').html('<div class="pre_title">Error: Application Not Found</div>');
            }
        })
    }
    //TODO: update application status

    window.addEventListener('load', () => {
        GetApplicants();
    })
</script>
<style>
    .field div {
        display: block;
        padding: 8px;
    }

    .field {
        margin: 12px;
        width: calc(100% - 24px);
    }
</style>