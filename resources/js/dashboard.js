document.addEventListener("DOMContentLoaded", function () {
    const filterButtons = document.querySelectorAll("[data-trip-filter]");
    const tripCards = document.querySelectorAll("[data-trip-status]");
    const emptyUpcoming = document.getElementById("trip-empty-upcoming");
    const emptyCompleted = document.getElementById("trip-empty-completed");

    filterButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            const selectedStatus = button.dataset.tripFilter;
            filterButtons.forEach(function (item) {
                const isActive = item === button;
                item.classList.toggle("bf-dashboard-tabs__button--active", isActive);
                item.setAttribute("aria-selected", String(isActive));
            });

            let visibleCount = 0;
            tripCards.forEach(function (card) {
                const match = card.dataset.tripStatus === selectedStatus;
                card.hidden = !match;
                if (match) {
                    visibleCount++;
                }
            });

            if (emptyUpcoming) {
                emptyUpcoming.hidden = selectedStatus !== "upcoming" || visibleCount > 0;
            }
            if (emptyCompleted) {
                emptyCompleted.hidden = selectedStatus !== "completed" || visibleCount > 0;
            }
        });
    });
});
