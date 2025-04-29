jQuery(document).ready(function($) {
    function initializeCountdown($element) {
        var strTargetDate = $element.data('target-date');
        var objTargetDate = new Date(strTargetDate).getTime();

        if (!objTargetDate) {
            $element.find('.ams-countdown-timer').text('Data invalidă');
            return;
        }

        function updateCountdown() {
            var intNow = new Date().getTime();
            var intDistance = objTargetDate - intNow;

            if (intDistance < 0) {
                $element.find('.ams-countdown-timer').text('Timp Expirat!');
                clearInterval(intInterval);
                return;
            }

            var intDays = Math.floor(intDistance / (1000 * 60 * 60 * 24));
            var intHours = Math.floor((intDistance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            var intMinutes = Math.floor((intDistance % (1000 * 60 * 60)) / (1000 * 60));
            var intSeconds = Math.floor((intDistance % (1000 * 60)) / 1000);

            $element.find('.ams-countdown-timer').text(
                intDays + "d " + intHours + "h " + intMinutes + "m " + intSeconds + "s"
            );
        }

        var intInterval = setInterval(updateCountdown, 1000);
        updateCountdown();
    }

    $('.ams-countdown').each(function() {
        initializeCountdown($(this));
    });
});
