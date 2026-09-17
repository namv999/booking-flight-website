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
});
