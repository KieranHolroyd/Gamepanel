<script src="/js/notifications.js?<?=rand(0,1000000);?>"></script>
<div id="notifications" class="edirectmessags">
    <div class="header">
        <p class="title">Notifications</p>
        <button onclick="closeOverlay('#notifications');" class="closeDraggableMenu">&times;</button>
    </div>
    <div class="body">
        <div class="notificationsContainer">
            <div id="notificationsDisplay"></div>
        </div>
    </div>
</div>