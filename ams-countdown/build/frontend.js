document.addEventListener('DOMContentLoaded', function () {
    const countdownEl = document.querySelector('.ams-countdown');

    if (!countdownEl) return;

    const targetDateStr = countdownEl.getAttribute('data-date');
    if (!targetDateStr) return;

    const targetDate = new Date(targetDateStr);

    function updateCountdown() {
        const now = new Date();
        const diff = targetDate - now;

        if (diff <= 0) {
            countdownEl.textContent = 'Evenimentul a început!';
            return;
        }

        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diff / (1000 * 60 * 60)) % 24);
        const minutes = Math.floor((diff / (1000 * 60)) % 60);
        const seconds = Math.floor((diff / 1000) % 60);

        countdownEl.textContent = `${days}z ${hours}h ${minutes}m ${seconds}s`;
    }

    updateCountdown();
    setInterval(updateCountdown, 1000);
});
