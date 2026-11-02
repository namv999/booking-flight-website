// resources/js/flights.js - Xử lý lọc và sắp xếp trực tiếp trên trang kết quả tìm kiếm chuyến bay
document.addEventListener("DOMContentLoaded", function () {
    const flightContainer = document.getElementById("bf-flight-list");
    if (!flightContainer) {
        return;
    }

    const cards = Array.from(flightContainer.querySelectorAll(".bf-flight-card"));
    if (cards.length === 0) {
        return;
    }

    const airlineCheckboxes = document.querySelectorAll(".bf-airline-checkbox");
    const directCheckbox = document.getElementById("bf-filter-direct");
    const timeSlotButtons = document.querySelectorAll(".bf-time-slot-btn");
    const resetButtons = document.querySelectorAll("#bf-filter-reset, #bf-filter-reset-empty");
    const sortPills = document.querySelectorAll(".bf-sort-pill");
    const countDisplay = document.getElementById("bf-count-display");
    const emptyNotice = document.getElementById("bf-no-matching-flights");

    let currentSort = "price"; // "price" | "time"
    const activeTimeSlots = new Set();

    function hourMatchesSlot(hour, slot) {
        switch (slot) {
            case "early-morning":
                return hour >= 0 && hour < 6;
            case "morning":
                return hour >= 6 && hour < 12;
            case "afternoon":
                return hour >= 12 && hour < 18;
            case "evening":
                return hour >= 18 && hour < 24;
            default:
                return true;
        }
    }

    function applyFilters() {
        const checkedAirlines = new Set(
            Array.from(airlineCheckboxes)
                .filter((cb) => cb.checked)
                .map((cb) => cb.value.trim().toLowerCase())
        );

        const requireDirect = directCheckbox ? directCheckbox.checked : false;

        let visibleCount = 0;

        cards.forEach((card) => {
            const airline = (card.dataset.airline || "").trim().toLowerCase();
            const hour = parseInt(card.dataset.depHour, 10);
            const stops = card.dataset.stops || "direct";

            // 1. Lọc theo hãng
            const matchAirline = checkedAirlines.size === 0 || checkedAirlines.has(airline);

            // 2. Lọc theo điểm dừng
            const matchStops = !requireDirect || stops === "direct";

            // 3. Lọc theo khung giờ
            let matchTime = true;
            if (activeTimeSlots.size > 0) {
                matchTime = Array.from(activeTimeSlots).some((slot) => hourMatchesSlot(hour, slot));
            }

            const isVisible = matchAirline && matchStops && matchTime;
            card.style.display = isVisible ? "" : "none";

            if (isVisible) {
                visibleCount++;
            }
        });

        // Sắp xếp các card đang hiển thị
        applySorting();

        // Cập nhật số lượng đếm
        if (countDisplay) {
            countDisplay.textContent = visibleCount;
        }

        // Ẩn/hiện thông báo rỗng
        if (emptyNotice) {
            emptyNotice.style.display = visibleCount === 0 ? "block" : "none";
        }
    }

    function applySorting() {
        const visibleCards = cards.filter((c) => c.style.display !== "none");

        visibleCards.sort((a, b) => {
            if (currentSort === "price") {
                const priceA = parseFloat(a.dataset.price) || 0;
                const priceB = parseFloat(b.dataset.price) || 0;
                return priceA - priceB;
            } else if (currentSort === "time") {
                const timeA = parseInt(a.dataset.depTime, 10) || 0;
                const timeB = parseInt(b.dataset.depTime, 10) || 0;
                return timeA - timeB;
            }
            return 0;
        });

        visibleCards.forEach((card) => {
            flightContainer.appendChild(card);
        });
    }

    // Sự kiện checkbox hãng bay
    airlineCheckboxes.forEach((cb) => {
        cb.addEventListener("change", applyFilters);
    });

    // Sự kiện checkbox bay thẳng
    if (directCheckbox) {
        directCheckbox.addEventListener("change", applyFilters);
    }

    // Sự kiện nút khung giờ
    timeSlotButtons.forEach((btn) => {
        btn.addEventListener("click", function () {
            const slot = this.dataset.timeSlot;
            if (!slot) return;

            if (activeTimeSlots.has(slot)) {
                activeTimeSlots.delete(slot);
                this.classList.remove("active", "is-active");
            } else {
                activeTimeSlots.add(slot);
                this.classList.add("active", "is-active");
            }

            applyFilters();
        });

        // Hỗ trợ phím Enter/Space khi focus
        btn.addEventListener("keydown", function (e) {
            if (e.key === "Enter" || e.key === " ") {
                e.preventDefault();
                this.click();
            }
        });
    });

    // Sự kiện sort pills
    sortPills.forEach((pill) => {
        pill.addEventListener("click", function () {
            const sortMode = this.dataset.sort;
            if (!sortMode) return;

            currentSort = sortMode;
            sortPills.forEach((p) => p.classList.remove("active"));
            this.classList.add("active");

            applySorting();
        });

        pill.addEventListener("keydown", function (e) {
            if (e.key === "Enter" || e.key === " ") {
                e.preventDefault();
                this.click();
            }
        });
    });

    // Sự kiện Đặt lại
    resetButtons.forEach((btn) => {
        btn.addEventListener("click", function (e) {
            e.preventDefault();

            // Khôi phục checkbox hãng
            airlineCheckboxes.forEach((cb) => (cb.checked = true));

            // Khôi phục bay thẳng
            if (directCheckbox) {
                directCheckbox.checked = true;
            }

            // Xóa khung giờ
            activeTimeSlots.clear();
            timeSlotButtons.forEach((b) => b.classList.remove("active", "is-active"));

            // Đặt về giá thấp nhất
            currentSort = "price";
            sortPills.forEach((p) => {
                p.classList.toggle("active", p.dataset.sort === "price");
            });

            applyFilters();
        });
    });

    // Khởi chạy ban đầu
    applyFilters();
});
