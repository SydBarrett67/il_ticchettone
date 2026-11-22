document.addEventListener("DOMContentLoaded", () => {
    const carouselEl = document.getElementById("carousel");
    if (!carouselEl) return;

    const items = carouselEl.querySelectorAll(".carousel-item");
    const indicators = carouselEl.querySelectorAll(".carousel-indicators button");
    if (items.length <= 1) return;

    const intervalMs = Number(carouselEl.dataset.bsInterval) || 4000;
    let currentIndex = Array.from(items).findIndex((item) => item.classList.contains("active"));
    if (currentIndex < 0) currentIndex = 0;

    function goToSlide(index) {
        currentIndex = (index + items.length) % items.length;

        items.forEach((item, i) => {
            item.classList.toggle("active", i === currentIndex);
        });

        indicators.forEach((btn, i) => {
            const isActive = i === currentIndex;
            btn.classList.toggle("active", isActive);
            if (isActive) {
                btn.setAttribute("aria-current", "true");
            } else {
                btn.removeAttribute("aria-current");
            }
        });
    }

    // Collega i click sugli indicatori
    indicators.forEach((btn, i) => {
        btn.addEventListener("click", () => {
            goToSlide(i);
            restartTimer();
        });
    });

    let timer = null;

    function startTimer() {
        stopTimer();
        timer = setInterval(() => {
            goToSlide(currentIndex + 1);
        }, intervalMs);
    }

    function stopTimer() {
        if (timer !== null) {
            clearInterval(timer);
            timer = null;
        }
    }

    function restartTimer() {
        startTimer();
    }

    carouselEl.addEventListener("mouseenter", stopTimer);
    carouselEl.addEventListener("mouseleave", startTimer);

    goToSlide(currentIndex);
    startTimer();
});
