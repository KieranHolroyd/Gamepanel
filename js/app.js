//Fixed Top Nav Gets A Shadow When User Scrolls
//Smooth Anchor Points
$('a[href*="#"]')
  .not('[href="#"]')
  .not('[href="#0"]')
  .click(function (event) {
    if (
      location.pathname.replace(/^\//, "") ===
        this.pathname.replace(/^\//, "") &&
      location.hostname === this.hostname
    ) {
      let target = $(this.hash);
      target = target.length ? target : $("[name=" + this.hash.slice(1) + "]");
      if (target.length) {
        event.preventDefault();
        $("html, body").animate(
          {
            scrollTop: target.offset().top - 110,
          },
          1000,
          function () {
            var $target = $(target);
            $target.focus();
            if ($target.is(":focus")) {
              return false;
            } else {
              $target.attr("tabindex", "-1");
              $target.focus();
            }
          }
        );
      }
    }
  });

// $(document).contextmenu((e) => {
//     if (e.target.tagName.toLowerCase() !== "textarea" && e.target.tagName.toLowerCase() !== "input" && !e.target.className.includes('ck-editor__editable')) {
//         e.preventDefault();
//         if ($('#moreMenu').data('opened') === "false" || $('#moreMenu').data('opened') === false) {
//             let x = e.pageX - $('body').offset().left;
//             let y = e.pageY - $('body').offset().top;
//             $('#moreMenu').css("left", x + "px");
//             $('#moreMenu').css("top", y + "px");
//             $('#moreMenu').addClass('open');
//             $('#moreMenu').data('opened', true);
//         } else {
//             $('#moreMenu').removeClass('open');
//             $('#moreMenu').data('opened', false);
//         }
//     }
// });
$(document).click(function (e) {
  if (
    e.target.id === "moreMenu" ||
    e.target.id === "openMore" ||
    e.target.parentElement.id === "moreMenu" ||
    e.target.id === "nav" ||
    e.target.parentElement.id === "nav"
  ) {
  } else {
    $("#moreMenu").removeClass("open");
    $("#moreMenu").data("opened", false);
  }
});

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

$(document).ready(() => {
  if (!window.localStorage.getItem("overlays")) {
    window.localStorage.setItem("overlays", "{}");
  }

  let overlays = JSON.parse(window.localStorage.getItem("overlays"));

  for (let key of Object.keys(overlays)) {
    let overlay = overlays[key];
    if (overlay === 1) {
      openOverlay(key);
    }
  }
});

function closeOverlay(id) {
  if (!id) throw new Error("No Overlay ID Provided");

  let overlay = $(id);

  if (!overlay) throw new Error(`No DOM Element With ID ${id}`);

  let getOverlays = JSON.parse(window.localStorage.getItem(`overlays`)) || {};

  if (!getOverlays[id] || getOverlays[id] === 1) {
    getOverlays[id] = 0;
    window.localStorage.setItem(`overlays`, JSON.stringify(getOverlays));
  }

  overlay.fadeOut(100);
}

function openOverlay(id) {
  if (!id) throw new Error("No Overlay ID Provided");

  let overlay = $(id);

  if (!overlay) throw new Error(`No DOM Element With ID ${id}`);

  overlay.fadeIn(100);

  let getOverlays = JSON.parse(window.localStorage.getItem(`overlays`)) || {};
  if (!getOverlays[id] || getOverlays[id] === 0) {
    getOverlays[id] = 1;
    window.localStorage.setItem(`overlays`, JSON.stringify(getOverlays));
  }
}

$(document).ready(() => {
  $("#messages").draggable({
    containment: ".overlayContainer",
    scroll: false,
    handle: ".header",
  });
  $("#messages .body").resizable({
    minWidth: 250,
    maxWidth: 400,
    minHeight: 100,
    containment: ".overlayContainer",
  });

  $("#messagingCompose").keydown((e) => {
    if (e.keyCode === 13) staffSendMessage();
  });
});

function markBanExpired(id, cid) {
  $.post("/api/v1/markBanExpired");
}
