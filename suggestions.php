<?php session_start();
include "head.php";
?>
<div class="grid" style="padding:15px;">
    <div class="grid__col grid__col--2-of-6 grid__col--push-2-of-6">
        <div id="basic_report">
            <p><b>Submit a suggestion for the staff case logger</b></p><br>
            <p>Submitting As <span style="font-weight: bold;" id="sas">Human</span></p>
            <input id="name" type="hidden">
            <textarea id="suggestion" class="fieldTextarea" placeholder="Your Suggestion?"></textarea>
        </div>
    </div>
</div>
<div style="margin-top: 120px;"></div>
<button onclick="submit();" class="submitBtn">Submit</button>
<script>
    function submit() {
        $.post('api/addSuggestion', {
            'name': $('#sas').text(),
            'suggestion': $('#suggestion').val()
        }, function (data) {
            data = JSON.parse(data);
            if (data.code === 200) {
                $('#suggestion').val('');
                new Noty({
                    type: 'success',
                    layout: 'topRight',
                    theme: 'metroui',
                    timeout: 3000,
                    text: data.message,
                }).show();
            } else {
                new Noty({
                    type: 'warning',
                    layout: 'topRight',
                    theme: 'metroui',
                    timeout: 3000,
                    text: `[${data.code}] ${data.message}`,
                }).show();
            }
        });
    }
</script>
</body>
<!--Created By Kieran Holroyd-->
</html>