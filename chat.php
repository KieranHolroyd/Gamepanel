<script src="/js/chat.js"></script>
<div id="messages" class="directmessages">
    <div class="header">
        <p class="title">Messages</p>
        <button onclick="closeOverlay('#messages');" class="closeDraggableMenu">&times;</button>
    </div>
    <div class="body">
        <div class="messagesContainer" id="messagesContainer">
            <div id="messagesDisplay"></div>
            <input type="text" id="messagingCompose" placeholder="Send Message...">
        </div>
    </div>
</div>