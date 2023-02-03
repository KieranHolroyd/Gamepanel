function closeAllModal() {
  if ($(".modal").is(":visible")) {
    $(".modal").fadeOut(200);
    $(".modal .content").removeClass("open");
  } else {
    console.log("Modal Not visible");
  }
}

function launchModal(launchid) {
  if ($("#" + launchid).is(":visible")) {
    console.log("ERROR: modal already open");
  } else {
    $("#" + launchid).fadeIn(200);
    $(`#${launchid} .content`).addClass("open");
  }
}

function closeModal(launchid) {
  if ($("#" + launchid).is(":visible")) {
    $("#" + launchid).fadeOut(200);
    $(`#${launchid} .content`).removeClass("open");
  }
}

$(document).on("click", "#modalLaunch", function () {
  let launchid = $(this).attr("launch");
  if ($("#" + launchid).is(":visible")) {
    console.log("ERROR: modal already open");
  } else {
    $("#" + launchid).fadeIn(200);
    $(`#${launchid} .content`).addClass("open");
  }
});
$(document).on("click", "#close", function () {
  closeAllModal();
});
$(document).on("click", ".modal", function (e) {
  let target = $(e.target);
  if (target.is(".modal")) {
    closeAllModal();
  }
});
function linkify(text) {
  let urlRegex =
    /(\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/gi;
  return text.replace(urlRegex, function (url) {
    return `<a target="_blank" href="${url}">${url}</a>`;
  });
}
