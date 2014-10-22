var gengamification = {
    showAlerts : function (html) {
        if (typeof html != 'undefined') {
            $('body').append(html);
        }

        if ($("#gengamif-alerts").length) {
            if (!$('#gengamif-alerts-background').length) {
                $('body').append('<div id="gengamif-alerts-background"></div>');
                $('#gengamif-alerts-background').css("width", $(document).width());
                $('#gengamif-alerts-background').css("height", $(document).height());
                $('#gengamif-alerts-background').fadeIn();
                $('#gengamif-alerts-background').click(function(){
                    $('#gengamif-alerts').fadeOut(function(){
                        $(this).remove();
                    });
                    $(this).fadeOut(function(){
                        $(this).remove();
                    });
                });

                //    $(div).css("top", Math.max(0, (($(window).height() - $(this).outerHeight()) / 2) + $(window).scrollTop()) + "px");
                $('#gengamif-alerts').css("left", Math.max(0, (($(window).width() - $('#gengamif-alerts').outerWidth()) / 2) + $(window).scrollLeft()) + "px");
                $('#gengamif-alerts').css("top", "-" + $('#gengamif-alerts').outerHeight() + "px");
                $('#gengamif-alerts').show();
                $('#gengamif-alerts').animate({
                    top: 100
                }, 2000, 'swing', function(){

                });
                $('html, body').animate({scrollTop: 0}, 2000);
                $('#gengamif-alerts-inner').css("margin-top", Math.max(0, ($("#gengamif-alerts").height() - ($("#gengamif-alerts-inner").height() / 2)) + "px"));
            }
        }
    }
};

$(document).ready(function(){
    gengamification.showAlerts();
});