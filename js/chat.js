function staffSendMessage() {
  let compose = $("#messagingCompose"),
    saveMessageValue = compose.val();
  $.post(
    "/api/v1/sendMessage",
    {
      content: saveMessageValue,
    },
    (data) => {
      data = JSON.parse(data);
      if (data.code !== 200) {
        compose.val(saveMessageValue);
      }
    }
  );
  compose.val("");
}

let channel = pusher.subscribe(`staffchat-messages`);
channel.bind("receive", displayMessage);

$.post("/api/v1/getMessages", {}, (data) => {
  data = JSON.parse(data);
  if (data.code === 200) {
    if (data.list.length === 0)
      $("#messagesDisplay").html(
        `<p class="overlayErrorMessage">Error: No Messages Found</p>`
      );
    for (let i = 0; i < data.list.length; i++) {
      const message = data.list[i];
      displayMessage(message);
    }
  } else {
    $("#messagesDisplay").html(
      `<p class="overlayErrorMessage">Error: ${data.message}</p>`
    );
  }
});

function displayMessage(data) {
  let display = $("#messagesDisplay");

  display.append(
    `<p class="message"><span class="username">${data.username}</span> <span class="content">${data.message}</span></p>`
  );
  $("#messagesContainer").scrollTop(display[0].scrollHeight);
}
