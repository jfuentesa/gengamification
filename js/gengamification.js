var gengamification = {
    embedCSS : function(){
        if (!$('#gengamif-css').length) {
            var genGamifCSS = '<style id="gengamif-css" type="text/css">' +
                '#gengamif-alerts-background {background: rgba(255, 255, 255, 0.8);position: absolute;top: 0;left: 0;z-index: 1000000;display: none;} ' +
                '#gengamif-alerts {margin-top: 10px;position: absolute;top: 0;left: 0;width: 600px;background: #FFF;height: 450px;border-radius: 10px;border: #26AF61 8px solid;overflow: hidden;display: none;z-index: 1000001;-webkit-box-shadow: rgba(190, 190, 190, 0.498039) 1px 1px 1px 1px;-moz-box-shadow:rgba(190, 190, 190, 0.498039) 1px 1px 1px 1px;box-shadow:rgba(190, 190, 190, 0.498039) 1px 1px 1px 1px;} ' +
                '#gengamif-alerts h3 {text-shadow: 0 0 1px rgba(51,51,51,0.3);text-align: center;font-size: 30px;margin: 10px;} ' +
                '#gengamif-alerts #gengamif-alerts-inner p {text-align: center;font-size: 25px;margin: 4px;} ' +
                '#gengamif-alerts #gengamif-alerts-inner #gengamif-level-number {font-weight: bold;font-size: 50px;color: #26AF61;height: 50px;} ' +
                '#gengamif-alerts #gengamif-alerts-inner #gengamif-level-label {font-size: 18px;color: #26AF61;} ' +
                '#gengamif-alerts #gengamif-alerts-inner #gengamif-alerts-footer p {text-align: center;font-size: 15px;margin: 10px;} ' +
                '#gengamif-alerts #gengamif-badgeslist {text-align: center;overflow: hidden} ' +
                '#gengamif-alerts #gengamif-badgeslist .gengamif-badgeimage {display: inline-block;margin:0 10px} ' +
                '.gengamif-badges-images {text-align: center;} ' +
                '.gengamif-badges-images .gengamif-badge-image {display: inline-block;margin:10px;} ' +
                '</style>';

            $(genGamifCSS).appendTo('head');
        }
    },
    showAlerts : function (html) {
        this.embedCSS();

        if (typeof html != 'undefined') {
            $('body').append(html);
        }

        if ($("#gengamif-alerts").length) {
            if (!$('#gengamif-alerts-background').length) {
                $('#gengamif-alerts-inner').hide();

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
                    $('#gengamif-alerts-inner').css("margin-top", Math.max(0, (($("#gengamif-alerts").height() / 2) - ($("#gengamif-alerts-inner").height() / 2))) + "px");
                    $('#gengamif-alerts-inner').fadeIn();
                });
                $('html, body').animate({scrollTop: 0}, 2000);
            }
        }
    }
};

$(document).ready(function(){
    gengamification.showAlerts();
});