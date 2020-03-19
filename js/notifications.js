$(document).ready(() => {
    $("#notifications").draggable({containment: ".overlayContainer", scroll: false, handle: '.header'});
    $("#notifications .body").resizable({
        minWidth: 250,
        maxWidth: 400,
        minHeight: 550,
        containment: ".overlayContainer"
    });

    loadNotifications();
});

function loadNotifications() {
    $.get('/api/notifications', data => {
        data = JSON.parse(data);

        if (data.code === 200) {
            for(let n of data.response) {
                $('#notificationsDisplay').append(parseNotification(n));
            }

            if (!data.response.length) $('#notificationsDisplay').html('<p class="overlayErrorMessage">No Notifications Found</p>')
        } else {
            $('#notificationsDisplay').html(`<p class="overlayErrorMessage">${data.message}</p>`)
        }
    });
}

function parseNotification(n) {
    return `<div class="notification${(n.viewed === "1") ? ' viewed' : ''}" onclick="openCallback('${n.callback_url}')">
    <p class="title">${n.title}</p>
    <p class="content">${n.content}</p>
    <p class="time">${n.timestamp}</p>
</div>`;
}

function alertUserToNotification(n) {
    new Noty({
        type: 'info',
        text: `${n.title}<br><small>${n.content}</small>`,
        callbacks: {
            onClose: () => {
                openOverlay('#notifications')
            }
        }
    }).show();
}

function openCallback(u) {
    if (u.charAt(1) === "/" && confirm(`You're about to visit ${u} Are you sure?`)) {
        window.location.href = u;
    } else {
        window.location.href = u;
    }
}

let notChannel = pusher.subscribe(`notifications`);
notChannel.bind("receive", data => {
    if (data.for_user_id === userArray.info.id) {
        alertUserToNotification(data);
        $('#notificationsDisplay').prepend(parseNotification(data));
    }
});