<?php
header("Access-Control-Allow-Origin: *");
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/classes/User.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/classes/Auth.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/classes/Guard.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/classes/Helpers.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/classes/Config.php';
$user = new User();
if ($user->error && !Helpers::viewingPublicPage()) {
    header("Location: /passport");
}
if (!$user->verified(false) && !Helpers::viewingPublicPage()) {
    header("Location: /errors/awaitingapproval");
}
if ($user->isOnLOA() && $_SERVER['REQUEST_URI'] != '/errors/youreonloa') {
    header("Location: /errors/youreonloa");
}
if ($user->isSuspended() && $_SERVER['REQUEST_URI'] != '/errors/suspended') {
    header("Location: /errors/suspended");
}
$url = Config::$base_url; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>
        <?php Config::$name; ?> | Gamepanel
    </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>
    <link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
    <link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toast-css/1.1.0/grid.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/noty/3.1.4/noty.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/noty/3.1.4/noty.min.css">
    <link href="https://use.fontawesome.com/releases/v5.5.0/css/all.css" rel="stylesheet">
    <link rel="stylesheet" href="/styles.css">
    <link rel="stylesheet" href="//cdn.jsdelivr.net/chartist.js/latest/chartist.min.css">
    <script src="//cdn.jsdelivr.net/chartist.js/latest/chartist.min.js"></script>
    <script src="https://unpkg.com/popper.js@1/dist/umd/popper.min.js"></script>
    <script src="https://unpkg.com/tippy.js@4"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/js-cookie@2/src/js.cookie.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/1.2.5/axios.min.js" integrity="sha512-JEXkqJItqNp0+qvX53ETuwTLoz/r1Tn5yTRnZWWz0ghMKM2WFCEYLdQnsdcYnryMNANMAnxNcBa/dN7wQtESdw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>
        const apiclient = axios.create({
            baseURL: '<?= Config::$base_url ?>',
            timeout: 10000,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Accept': 'application/json'
            },
            withCredentials: true
        });

        const noty_catch_error = (e) => {
            console.warn(`Promise rejected:`, e)
            new Noty({
                text: `<b>Oops.</b> ${e.message}`,
                type: 'error',
            }).show();
        }
    </script>
    <script src="https://js.pusher.com/4.3/pusher.min.js"></script>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script src="<?php echo $url; ?>js/app.js"></script>
    <?php if (!isset($nonav)) : ?>
        <script src="<?php echo $url; ?>js/dragUI.js"></script>
    <?php endif; ?>
    <script src="<?php echo $url; ?>js/modal.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue-tippy/dist/vue-tippy.min.js"></script>
    <script>
        let pusher_key = "<?= Config::$pusher['AUTH_KEY']; ?>";
        let pusher = new Pusher(pusher_key, {
            cluster: "eu",
            forceTLS: true
        });
    </script>
</head>

<body>
    <?php if (!isset($nonav))
        include "includes/navbar.php"; ?>
    <div class="overlayContainer"></div>
    <?php if ($user->verified(false))
        include 'includes/notifications.php'; ?>
    <script>
        let loginToken = "<?php echo isset($_COOKIE['LOGINTOKEN']) ? $_COOKIE['LOGINTOKEN'] : false; ?>";
        let userArray = {};

        function userArraySet() {
            apiclient.get('/api/v2/user/me')
                .then(({
                    data
                }) => {
                    if (data.success) {
                        userArray.info = data.user;
                        if (typeof vm !== 'undefined') {
                            vm.user = data.user;
                        }
                        $('#welcome').html('Hello, ' + userArray.info.username);
                        if (typeof userArrayLoaded === 'function') userArrayLoaded();
                    } else {
                        new Noty({
                            text: `Failed to fetch user data`,
                            type: 'error',
                        }).show();
                    }
                })
                .catch(noty_catch_error);
        }

        $(window).on('load', userArraySet());

        function logout() {
            apiclient.post("api/v2/auth/logout").then(({
                data
            }) => {
                if (data.success) {

                    window.location.replace("/passport");
                    console.log(data)
                }
            }).catch(noty_catch_error);
            userArray = {};
        }
        window.logout = logout;

        function currentTime() {
            let currentTime = new Date();
            let hours = currentTime.getHours();
            let minutes = currentTime.getMinutes();
            let seconds = currentTime.getSeconds();
            if (minutes < 10) {
                minutes = "0" + minutes;
            }
            if (seconds < 10) {
                seconds = "0" + seconds;
            }
            let time = hours + ":" + minutes + ":" + seconds + " ";
            if (hours > 11) {
                time += "PM";
            } else {
                time += "AM";
            }
            return time;
        }
    </script>
    <?php if ((!$user->error) && $user->info->essentialNotification != '' && !$user->info->readEssentialNotification) : ?>
        <div class="modal" id="essentialNotification" style="display: block;">
            <button id="close">×</button>
            <div class="content open" style="max-width: 500px;border-radius: 5px;">
                <h2>Attention!</h2>
                <?= $user->info->essentialNotification; ?>
                <div class="btnGroup">
                    <button onclick="markNotificationRead()">Mark As Read</button>
                </div>
            </div>
        </div>
        <script>
            function markNotificationRead() {
                closeAllModal();
                $.post('/api/v2/notifications/essential/mark', {}, data => {
                    data = JSON.parse(data);
                    if (data.code === 200) {
                        new Noty({
                            type: 'success',
                            text: 'Marked As Read',
                            timeout: 4000
                        }).show();
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
    <?php endif; ?>