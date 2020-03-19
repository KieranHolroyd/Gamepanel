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
        let selected;
        $(document).ready(function () {
            $('#chooseLogin').css('background-color', '#2e2c46');
            $('#chooseSignup').css('background-color', '#181830');
            $('.signup-div').slideUp(250);
            $('.login-div').slideDown(250);
            selected = 1;
        });
        $('#chooseLogin').click(function () {
            $('#chooseLogin').css('background-color', '#2e2c46');
            $('#chooseSignup').css('background-color', '#181830');
            $('.signup-div').slideUp(250);
            $('.login-div').slideDown(250);
            selected = 1;
        });
        $('#chooseSignup').click(function () {
            $('#chooseSignup').css('background-color', '#2e2c46');
            $('#chooseLogin').css('background-color', '#181830');
            $('.login-div').slideUp(250);
            $('.signup-div').slideDown(250);
            selected = 0;
        });
        $('#continue').click(function () {
            $(this).html('<img src="/img/loadw.svg" width="25px">');
            if (selected === 0) {
                $.post('api/signupUser', {
                    first_name: $('#s-firstname').val(),
                    last_name: $('#s-lastname').val(),
                    email: $('#s-email').val(),
                    password: $('#s-password').val(),
                    cpassword: $('#s-password-conf').val()
                }, function (data) {
                    new Noty({
                        type: 'success',
                        layout: 'topRight',
                        theme: 'metroui',
                        timeout: 3000,
                        text: data,
                    }).show();
                    $('#continue').text('Continue');
                });
            } else if (selected === 1) {
                $.post('api/loginUser', {
                    email: $('#l-email').val(),
                    password: $('#l-password').val(),
                }, function (data) {
                    data = JSON.parse(data);
                    if (data.token == "Failed") {
                        notify = "Login Failed. Try Again";
                        type = 'error';
                        $('#continue').text('Continue');
                    } else {
                        notify = "Login Success. Redirecting";
                        type = 'success';
                    }
                    new Noty({
                        text: notify,
                        progressBar: true,
                        type: type
                    }).show();
                    userArraySet();
                    $.post("api/checkLogin", {},
                        function (data) {
                            console.log(data)
                            if (!data) {
                                console.log('Login Failed');
                            } else {
                                location.replace("index.php");
                            }
                        });
                });
            }
        });

        function userArrayLoaded() {
            if (userArray.info.username !== "") {
                window.location.href = "./";
            }
        }
    </script>
<?php include "footer.php"; ?>