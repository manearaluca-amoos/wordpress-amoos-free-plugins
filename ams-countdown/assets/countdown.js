document.addEventListener('DOMContentLoaded', function() {
    const countdownElements = document.querySelectorAll('.ams-countdown');

    countdownElements.forEach(function(el) {
        const targetDateStr = el.getAttribute('data-target-date');
        if (!targetDateStr) return;

        const targetDate = new Date(targetDateStr);

        function updateCountdown() {
            const now = new Date();
            const diff = targetDate - now;

            if (diff <= 0) {
                el.innerHTML = 'Evenimentul a început!';
                return;
            }

            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff / (1000 * 60 * 60)) % 24);
            const minutes = Math.floor((diff / (1000 * 60)) % 60);
            const seconds = Math.floor((diff / 1000) % 60);

            el.innerHTML = `
                <div class="ams-countdown-item"><span class="ams-countdown-number">${days}</span> <span class="ams-countdown-label">zile</span></div>
                <div class="ams-countdown-item"><span class="ams-countdown-number">${hours}</span> <span class="ams-countdown-label">ore</span></div>
                <div class="ams-countdown-item"><span class="ams-countdown-number">${minutes}</span> <span class="ams-countdown-label">minute</span></div>
                <div class="ams-countdown-item"><span class="ams-countdown-number">${seconds}</span> <span class="ams-countdown-label">secunde</span></div>
            `;
        }

        updateCountdown();
        setInterval(updateCountdown, 1000);
    });
});
