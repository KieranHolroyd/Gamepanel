<?php include "../head.php"; ?>
    <div class="grid new">
        <div class="grid__col grid__col--2-of-6" style="padding-left: 20px !important;">
            <h1 class="info-title new">Case Edits Awaiting Approval</h1>
            <div id="reports" style='height: calc(100vh - 68px) !important;' class="selectionPanel">

            </div>
        </div>
        <div class="grid__col grid__col--4-of-6">
            <div class="infoPanelContainer">
                <div id="diffViewer" class="infoPanel diffPanel">
                    <div class="pre_title">Select Edit For Details</div>
                    <div id="string"></div>
                </div>
            </div>
        </div>
    </div>
    <script>
        function test() {
            $.post('/api/getMoreInfo', {id: 100}, data => {
                data = JSON.parse(data);
                console.log(data);
                let editedString = `On 12/21/2018 @7:30 pm we had a user that was getting back into Arma 3 life he needed the mods and the Arma 3 sync-able to update the mods we gave him, he had general questions about gameplay that Fini and I answered and we told him the style of gameplay the server will be.`;
                $.get(`/api/stringDiffHTML?string1=${data.response.report.doe}&string2=${editedString}`, data => {
                    data = JSON.parse(data);
                    let diffHTML = ``;
                    for(let diff of data.response[0]) {
                        switch (diff[1]) {
                            case 0:
                                diffHTML += `<span>${diff[0]} </span>`;
                                break;
                            case 1:
                                diffHTML += `<span class='deleted'>${diff[0]}</span>`;
                                break;
                            case 2:
                                diffHTML += `<span class='inserted'>${diff[0]}</span>`;
                                break;
                        }
                    }
                    $('#string').html(diffHTML);
                });
            });
        }
        test();
    </script>