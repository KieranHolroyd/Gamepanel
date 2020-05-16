<?php include "head.php";
Guard::init()->SLTRequired();
?>
    <div class="grid">
        <div class="grid__col grid__col--2-of-6" style="padding-left: 20px !important;">
            <h1 class="info-title">Server Logs</h1>
            <div id="reports" style='height: calc(100vh - 69px) !important;' class="cscroll">
                <h2>Loading...</h2>
            </div>
        </div>
        <div class="grid__col grid__col--4-of-6">
            <div id="case_info" style='height: 100vh;' class="moreInfoPanel">
                <div class="layout">
                    <h2>Logs For <span id="logtype">surveilance</span></h2>
                    <div id="lines">Loading...</div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/socket.io/2.2.0/socket.io.js"></script>
    <script>
        let currentLogFile = 'surveilance';
        let clientid = "<?=$user->info->id;?>";
        let locallog = [];

        function escapeHtml(unsafe) {
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;")
                .replace(/\(/g, "&#040;")
                .replace(/\)/g, "&#041;");
        }

        function changeLogType(type) {
            if (type !== currentLogFile) {
                currentLogFile = type;
                locallog = [];
                $('#lines').html('Loading...');
                $('#logtype').text(type);
                socket.emit('read_file', type);
            }
        }

        let socket = new io("wss://ws.infishit.de", {
            query: `token=${clientid}`
        });
        socket.on('connect', () => {
            setInterval(() => {
                socket.emit('read_file', currentLogFile);
            }, 1000);

            socket.emit('available_files');

            socket.on('files', (data) => {
                let list = '';
                for (let type of data) {
                    if (type !== "rpt" && type !== "debug_expression" && type !== "taser") list += `<div class="case" onclick="changeLogType('${escapeHtml(type)}')"><span style="font-size: 25px;text-transform:capitalize;">${escapeHtml(type)}</span></div>`;
                }
                $('#reports').html(list);
            });

            socket.on('receive', (data) => {
                if (data.length > 0 && data !== null) {
                    if (JSON.stringify(data) !== locallog) {
                        locallog = JSON.stringify(data);
                        $('#lines').html(escapeHtml(data.map(_ => `{s}${_}{e}`).reverse().join('\r\n')).replace(/({s})/g, '<div class="log">').replace(/({e})/g, '</div>'));
                    }
                } else {
                    $('#lines').html(`<h2>No Logs Found</h2>`);
                }
            });
        });

    </script>