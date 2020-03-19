$(document).ready(function () {

    const ls = window.localStorage;
    $("#moreMenu").draggable({containment: ".overlayContainer", scroll: false});

    $('#openMore').click(function (e) {
        if ($('#moreMenu').data('opened') === "false" || $('#moreMenu').data('opened') === false) {
            let x = e.pageX - $('#topLevel').offset().left;
            let y = e.pageY - $('#topLevel').offset().top;
            $('#moreMenu').css("left", (x -= 100) + "px");
            $('#moreMenu').css("top", (y += 30) + "px");
            $('#moreMenu').addClass('open');
            $('#moreMenu').data('opened', true);
        } else {
            $('#moreMenu').removeClass('open');
            $('#moreMenu').data('opened', false);
        }
    });
});