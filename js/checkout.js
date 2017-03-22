/**
 * @file
 */

Drupal.behaviors.striper = {
    attach: function (context, settings) {

        function getParameterByName(name, url) {
            if (!url) {
                url = window.location.href;
            }
            name = name.replace(/[\[\]]/g, "\\$&");
            var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
                results = regex.exec(url);
            if (!results) {
                return null;
            }
            if (!results[2]) {
                return '';
            }
            return decodeURIComponent(results[2].replace(/\+/g, " "));
        }

        (function ($) {
            // If striper_click query parameter is set, then click the stripe button automatically.
            var striper_click = getParameterByName('striper_click');
            if (striper_click) {
                $('.stripe-button-el', context).once('clicked').click();
            }

        })(jQuery);
    }
};
