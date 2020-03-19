<?php include "head.php";
$auth = new Auth;
$auth->SLTRequired();
?>
    <div class="grid">
        <div class="grid__col grid__col--4-of-6" style="padding-left: 20px !important;">
            <h1 class="info-title" id="welcome">Hello, <?=$auth->info->username;?></h1>
            <div id="reports" style="height: calc(100% - 68px);">
                <img src="../../Before/Purple-Iron-Bulldog/img/loadw.svg" alt="Failed To Load">
            </div>
        </div>
        <div class="grid__col grid__col--2-of-6">
            <div class="moreInfoPanel" id="case_info">
            </div>
        </div>
    </div>
    <script>
        let setMoreInfo = "";

        function getSuggestions() {
            $.get('api/getSuggestions', function (data) {
                moreinfo = JSON.parse(data);
                for (let i = 1; i < Object.keys(moreinfo.response).length + 1; i++) {
                    let suggestion = moreinfo.response[i];
                    setMoreInfo += `<div class="staffActivityCard" id="${suggestion.id}" onclick="more(${suggestion.id})"><span id="name">${suggestion.name}</span><br><span id="suggestion">${suggestion.suggestion}</span></div>`
                }
                $('#reports').html(setMoreInfo);
            });
        }

        function more(id) {
            let suggestion = $(`#${id} #suggestion`).text(),
                name = $(`#${id} #name`).text();
            setMoreInfo = `<h2>${name}'s Suggestion</h2><p><span>Suggestion: </span>${suggestion}</p>`;
            $('#case_info').html(linkify(setMoreInfo));
        }

        getSuggestions();
    </script>
<?php include "footer.php"; ?>