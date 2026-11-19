document.addEventListener("DOMContentLoaded", function () {
    const menuButton = document.getElementById("bf-mobile-menu-toggle");
    const navigation = document.getElementById("bf-main-navigation");
    const adminButton = document.getElementById("bf-admin-menu-toggle");
    const adminSidebar = document.getElementById("bf-admin-sidebar");

    if (menuButton && navigation) {
        menuButton.addEventListener("click", function () {
            const isOpen = navigation.classList.toggle("bf-navbar__nav--open");
            menuButton.setAttribute("aria-expanded", String(isOpen));
        });
    }

    if (adminButton && adminSidebar) {
        adminButton.addEventListener("click", function () {
            const isOpen = adminSidebar.classList.toggle("bf-admin-sidebar--open");
            adminButton.setAttribute("aria-expanded", String(isOpen));
        });
    }

    // Floating Action Button (FAB) Toggle & Close
    const fabContainer = document.getElementById("bf-fab-container");
    const fabToggle = document.getElementById("bf-fab-toggle");
    const fabClose = document.getElementById("bf-fab-close");

    if (fabContainer && fabToggle) {
        fabToggle.addEventListener("click", function (e) {
            e.stopPropagation();
            const isOpen = fabContainer.classList.toggle("is-open");
            fabToggle.setAttribute("aria-expanded", String(isOpen));
        });

        if (fabClose) {
            fabClose.addEventListener("click", function () {
                fabContainer.classList.remove("is-open");
                fabToggle.setAttribute("aria-expanded", "false");
            });
        }

        document.addEventListener("click", function (e) {
            if (!fabContainer.contains(e.target)) {
                fabContainer.classList.remove("is-open");
                fabToggle.setAttribute("aria-expanded", "false");
            }
        });

        document.addEventListener("keydown", function (e) {
            if (e.key === "Escape" && fabContainer.classList.contains("is-open")) {
                fabContainer.classList.remove("is-open");
                fabToggle.setAttribute("aria-expanded", "false");
            }
        });
    }
});
