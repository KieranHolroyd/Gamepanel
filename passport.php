<?php $nonav = 0;
include "head.php";
?>
<div class="card card-sm">
    <div class="card-head">
        <h2><?= Config::$name; ?> Account</h2>
    </div>
    <div class="card-med">
        <div class="loginselector">
            <button id="chooseLogin" style="background-color: #2e2c46;">Login</button><button id="chooseSignup">Create Account</button>
        </div>
    </div>
    <div class="card-body">
        <div class="login-div">
            <input id="l-email" type="text" placeholder="Email">
            <input id="l-password" type="password" placeholder="Password">
        </div>
        <div class="signup-div" style="display: none;">
            <input id="s-firstname" type="text" placeholder="First Name">
            <input id="s-lastname" type="text" placeholder="Last Name">
            <input id="s-email" type="text" placeholder="Email">
            <input id="s-password" type="password" placeholder="Password">
            <input id="s-password-conf" type="password" placeholder="Confirm">
        </div>
    </div>
    <div class="card-footer">
        <button id="continue">Continue</button>
    </div>
</div>
<script>
    window.supress_errors = true;
    let selected;
    $(document).ready(function() {
        $('#chooseLogin').css('background-color', '#2e2c46');
        $('#chooseSignup').css('background-color', '#181830');
        $('.signup-div').slideUp(250);
        $('.login-div').slideDown(250);
        selected = 1;
    });
    $('#chooseLogin').click(function() {
        $('#chooseLogin').css('background-color', '#2e2c46');
        $('#chooseSignup').css('background-color', '#181830');
        $('.signup-div').slideUp(250);
        $('.login-div').slideDown(250);
        selected = 1;
    });
    $('#chooseSignup').click(function() {
        $('#chooseSignup').css('background-color', '#2e2c46');
        $('#chooseLogin').css('background-color', '#181830');
        $('.login-div').slideUp(250);
        $('.signup-div').slideDown(250);
        selected = 0;
    });

    function init_auth_request() {
        $('#continue').html('<img src="/img/loadw.svg" width="25px">');
        if (selected === 0) {
            // selected = 0 -> signup
            apiclient.post('api/v2/auth/signup', {
                first_name: $('#s-firstname').val(),
                last_name: $('#s-lastname').val(),
                email: $('#s-email').val(),
                password: $('#s-password').val(),
                cpassword: $('#s-password-conf').val()
            }).then(({
                data
            }) => {
                new Noty({
                    type: data.success ? 'success' : 'error',
                    timeout: 3000,
                    text: data.message,
                }).show();
                $('#continue').text('Continue');
            }).catch(noty_catch_error);
        } else if (selected === 1) {
            // selected = 1 -> login
            apiclient.post('api/v2/auth/login', {
                email: $('#l-email').val(),
                password: $('#l-password').val(),
            }).then(({
                data
            }) => {
                if (!data.success)
                    $('#continue').text('Continue');

                new Noty({
                    text: `${data.message}`,
                    progressBar: true,
                    type: data.success ? 'success' : 'error',
                }).show();
                apiclient.post("api/v2/auth/check").then(({
                    data
                }) => {
                    if (data.success) {
                        location.replace("/");
                    }
                }).catch(noty_catch_error);
            }).catch(noty_catch_error);
        }
    }
    $('#continue').click(init_auth_request);
    $('input').keydown(function(e) {
        if (e.keyCode === 13) { // enter
            init_auth_request();
        }
    });
</script>