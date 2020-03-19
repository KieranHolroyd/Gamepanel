<?php include "../head.php";
$auth = new Auth;
$auth->RequireGameAccess();
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/socket.io/2.2.0/socket.io.js"></script>
<div class="grid new">
    <h1 class="info-title new"><?= Config::$name; ?> Game Server Manager</h1>
    <div class="navCards game">
        <a>
            <div class="navCard-small">
                <div class="navCard-items">
                    <p class="title">Restart Main Server</p>
                </div>
            </div>
        </a>
        <a>
            <div class="navCard-small">
                <div class="navCard-items">
                    <p class="title">Restart Development Server</p>
                </div>
            </div>
        </a>
    </div>
    <script>
        let socket = new WebSocket('wss://ws.alyt.ro');
        // let socket = new WebSocket('wss://142.44.143.176:8080');
        socket.addEventListener('open', _ => socket.send('Hello Server!'));
        socket.addEventListener('message', event => {
            console.log('Message from server ', event.data);
        });
    </script>
</div>
