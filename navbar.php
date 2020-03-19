<div id="moreMenu" data-opened="false">
    <div class="left">
        <?php if (Guard::init()->isStaff()): ?>
            <a href='<?php echo $url; ?>'><i class="fas fa-home"></i> Dashboard</a>
            <a href='<?php echo $url; ?>logger'><i class="fas fa-clipboard"></i> Log Case</a>
            <a onclick='openOverlay("#messages");'><i class="fas fa-comment-alt"></i> Staff Chat</a>
            <a href='<?php echo $url; ?>me'><i class="fas fa-address-card"></i> My Profile</a>
            <a href='<?php echo $url; ?>policies'><i class="fas fa-book"></i> Staff Policies</a>
            <a href='<?php echo $url; ?>meetings'><i class="far fa-calendar-alt"></i> Staff Meetings</a>
            <a href='<?php echo $url; ?>game'><i class="fas fa-server"></i> Game Panel</a>
            <?php if ($user->isSLT()): ?>
<!--                <a href='--><?php //echo $url; ?><!--logs'><i class="fas fa-scroll"></i> Server Logs</a>-->
                <a href='<?php echo $url; ?>viewer'><i class="fas fa-eye"></i> View Cases</a>
                <a href='<?php echo $url; ?>search?type=cases'><i class="fas fa-search"></i> Search Cases</a>
                <a href='<?php echo $url; ?>staff/'><i class="fas fa-clipboard-list"></i> Manage Staff</a>
                <a href='<?php echo $url; ?>staff/overview'><i class="fas fa-info-circle"></i> Staff Overview</a>
                <a href='<?php echo $url; ?>staff/interviews'><i class="fas fa-microphone"></i> Staff Interviews</a>
                <a href='<?php echo $url; ?>staff/audit'><i class="fas fa-list-alt"></i> Audit Log</a>
            <?php endif; ?>
            <a href='<?php echo $url; ?>staff/statistics'><i class="fas fa-chart-line"></i> Statistics</a>
        <?php else: ?>
        <?php endif; ?>
        <a onclick='logout();'><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
    <div class="right">
        <p><b>Mildly Relevant Information</b></p>
        <p>Server  1: <span id="server1players">00/90</span></p>
        <p>Server  2: <span id="server2players">000/110</span></p>
        <p>Teamspeak: <span id="teamspeakUsers">000/512</span></p>
    </div>
</div>
<script>
    function getAllMildlyRelevantInformation() {

        $.get('https://arma3-servers.net/api/?object=servers&element=detail&key=5p9s4QXs3QWE4MsL3h5WKBzTnYiohqlM9T', data => {
            data = JSON.parse(data);
            $('#server1players').text(`${data.players}/${data.maxplayers}`);
        });
        $.get('https://arma3-servers.net/api/?object=servers&element=detail&key=rI7QeSl6jHuvwnvrQjV7KnFPLYNROcDpBj', data => {
            data = JSON.parse(data);
            $('#server2players').text(`${data.players}/${data.maxplayers}`);
        });
        $.get('https://teamspeak-servers.org/api/?object=servers&element=detail&key=JjJjeLi6vAbRzMcbD02a0XMMGs4pZMRyK', data => {
            data = JSON.parse(data);
            $('#teamspeakUsers').text(`${data.players}/${data.maxplayers}`);
        });

    }

    getAllMildlyRelevantInformation();

    setInterval(getAllMildlyRelevantInformation, 10000);
</script>
<div id="topLevel">
    <div id="nav">
        <a class="left" onclick="toggleMenu()"><i class="fas fa-caret-left"></i></a>

        <a href="<?php echo $url; ?>">Dashboard</a>
        <a style="cursor:pointer;" id="openMore" data-opened="false">Navigation</a>
        <a id="time" style="color: #fff;cursor:default;"></a>
    </div>
</div>
<script>
    let isMenuOpen = false;

    $('#time').text(currentTime());
    setInterval(function () {
        $('#time').text(currentTime());
    }, 1000);

    function toggleMenu() {
        $('#nav').toggleClass('open');
        isMenuOpen = !isMenuOpen;
    }
</script>