document.addEventListener("DOMContentLoaded", function () {
    const filterButtons = document.querySelectorAll("[data-trip-filter]");
    const tripCards = document.querySelectorAll("[data-trip-status]");

    filterButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            const selectedStatus = button.dataset.tripFilter;
            filterButtons.forEach(function (item) {
                item.classList.toggle("bf-dashboard-tabs__button--active", item === button);
                item.setAttribute("aria-selected", String(item === button));
            });
            tripCards.forEach(function (card) {
                card.hidden = card.dataset.tripStatus !== selectedStatus;
            });
        });
    });
});
