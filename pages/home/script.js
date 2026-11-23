document.addEventListener("DOMContentLoaded", () => {
    const carouselEl = document.getElementById("carousel");
    if (!carouselEl) return;

    const items = Array.from(carouselEl.querySelectorAll(".carousel-item"));
    const indicators = Array.from(carouselEl.querySelectorAll(".carousel-indicators button"));
    if (items.length <= 1) return;

    let currentIndex = items.findIndex((el) => el.classList.contains("active"));
    if (currentIndex < 0) currentIndex = 0;

    function showSlide(index) {
        currentIndex = (index + items.length) % items.length;
        items.forEach((item, idx) => {
            item.classList.toggle("active", idx === currentIndex);
        });
        indicators.forEach((btn, idx) => {
            const active = idx === currentIndex;
            btn.classList.toggle("active", active);
            if (active) {
                btn.setAttribute("aria-current", "true");
            } else {
                btn.removeAttribute("aria-current");
            }
        });
    }

    indicators.forEach((btn, idx) => {
        btn.addEventListener("click", () => showSlide(idx));
    });

    const intervalMs = parseInt(carouselEl.dataset.bsInterval || "4000", 10);
    setInterval(() => {
        showSlide(currentIndex + 1);
    }, intervalMs);
})
