// resources/js/flights.js - Xử lý bộ lọc, dropdown hãng bay tìm kiếm & sắp xếp chuyến bay
document.addEventListener("DOMContentLoaded", function () {
    const filterForm = document.getElementById("bf-filter-form");
    if (!filterForm) {
        return;
    }

    /* ============================================================
       1. AUTO-SUBMIT CHO KHUNG GIỜ VÀ SẮP XẾP
       ============================================================ */
    const autoSubmitElements = document.querySelectorAll(
        '#bf-filter-form input[name="dep_time[]"], ' +
        '#bf-filter-form input[name="arr_time[]"], ' +
        'input[name="sort"][form="bf-filter-form"]'
    );

    autoSubmitElements.forEach((el) => {
        el.addEventListener("change", function () {
            filterForm.requestSubmit();
        });
    });

    /* ============================================================
       2. DROPDOWN HÃNG HÀNG KHÔNG TÌM KIẾM & ĐA CHỌN (MULTI-SELECT)
       ============================================================ */
    const dropdownWrap = document.getElementById("bf-airline-dropdown");
    const triggerBtn = document.getElementById("bf-airline-trigger");
    const menuPopover = document.getElementById("bf-airline-menu");
    const searchInput = document.getElementById("bf-airline-search-input");
    const searchClear = document.getElementById("bf-airline-search-clear");
    const selectAllBtn = document.getElementById("bf-airline-select-all");
    const clearAllBtn = document.getElementById("bf-airline-clear-all");
    const visibleCountEl = document.getElementById("bf-airline-visible-count");
    const triggerTextEl = document.getElementById("bf-airline-trigger-text");
    const noResultEl = document.getElementById("bf-airline-no-result");
    const airlineOptions = document.querySelectorAll(".bf-airline-option");

    // Khôi phục trạng thái mở của dropdown sau khi submit tự động
    if (sessionStorage.getItem("bf_airline_dropdown_open") === "1") {
        sessionStorage.removeItem("bf_airline_dropdown_open");
        if (dropdownWrap && triggerBtn) {
            dropdownWrap.classList.add("is-open");
            triggerBtn.setAttribute("aria-expanded", "true");
        }
    }

    // Hàm xóa dấu tiếng Việt phục vụ tìm kiếm mềm
    function removeVietnameseTones(str) {
        if (!str) return "";
        return str
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .replace(/đ/g, "d")
            .replace(/Đ/g, "D")
            .toLowerCase()
            .trim();
    }

    if (dropdownWrap && triggerBtn && menuPopover) {
        // Mở/đóng dropdown
        triggerBtn.addEventListener("click", function (e) {
            e.preventDefault();
            e.stopPropagation();
            const isOpen = dropdownWrap.classList.toggle("is-open");
            triggerBtn.setAttribute("aria-expanded", String(isOpen));

            if (isOpen && searchInput) {
                setTimeout(() => {
                    searchInput.focus();
                }, 100);
            }
        });

        // Bấm ra ngoài thì đóng dropdown
        document.addEventListener("click", function (e) {
            if (!dropdownWrap.contains(e.target)) {
                dropdownWrap.classList.remove("is-open");
                triggerBtn.setAttribute("aria-expanded", "false");
            }
        });

        // Phím Escape đóng dropdown
        document.addEventListener("keydown", function (e) {
            if (e.key === "Escape" && dropdownWrap.classList.contains("is-open")) {
                dropdownWrap.classList.remove("is-open");
                triggerBtn.setAttribute("aria-expanded", "false");
                triggerBtn.focus();
            }
        });

        // Tìm kiếm thời gian thực qua bàn phím
        if (searchInput) {
            searchInput.addEventListener("input", function () {
                const keyword = removeVietnameseTones(this.value);
                let visibleCount = 0;

                if (searchClear) {
                    searchClear.classList.toggle("d-none", keyword.length === 0);
                }

                airlineOptions.forEach((opt) => {
                    const code = removeVietnameseTones(opt.getAttribute("data-airline-code") || "");
                    const name = removeVietnameseTones(opt.getAttribute("data-airline-name") || "");
                    const matches = code.includes(keyword) || name.includes(keyword);

                    if (matches) {
                        opt.classList.remove("d-none");
                        visibleCount++;
                    } else {
                        opt.classList.add("d-none");
                    }
                });

                if (visibleCountEl) {
                    visibleCountEl.textContent = `${visibleCount} hãng`;
                }

                if (noResultEl) {
                    noResultEl.classList.toggle("d-none", visibleCount > 0);
                }
            });

            // Khi nhấn Enter trong ô tìm kiếm -> Áp dụng bộ lọc và giữ dropdown mở
            searchInput.addEventListener("keydown", function (e) {
                if (e.key === "Enter") {
                    e.preventDefault();
                    sessionStorage.setItem("bf_airline_dropdown_open", "1");
                    filterForm.requestSubmit();
                }
            });
        }

        // Xóa từ khóa tìm kiếm
        if (searchClear && searchInput) {
            searchClear.addEventListener("click", function () {
                searchInput.value = "";
                searchInput.dispatchEvent(new Event("input"));
                searchInput.focus();
            });
        }

        // Tự động submit khi click chọn lần 1 và bỏ chọn lần 2
        airlineOptions.forEach((opt) => {
            const cb = opt.querySelector(".bf-airline-checkbox");
            if (cb) {
                cb.addEventListener("change", function () {
                    sessionStorage.setItem("bf_airline_dropdown_open", "1");
                    filterForm.requestSubmit();
                });
            }
        });

        // Chọn tất cả -> Tự động submit
        if (selectAllBtn) {
            selectAllBtn.addEventListener("click", function (e) {
                e.preventDefault();
                let changed = false;
                airlineOptions.forEach((opt) => {
                    if (!opt.classList.contains("d-none")) {
                        const cb = opt.querySelector(".bf-airline-checkbox");
                        if (cb && !cb.checked) {
                            cb.checked = true;
                            changed = true;
                        }
                    }
                });
                if (changed) {
                    sessionStorage.setItem("bf_airline_dropdown_open", "1");
                    filterForm.requestSubmit();
                }
            });
        }

        // Bỏ chọn tất cả -> Tự động submit
        if (clearAllBtn) {
            clearAllBtn.addEventListener("click", function (e) {
                e.preventDefault();
                let changed = false;
                airlineOptions.forEach((opt) => {
                    const cb = opt.querySelector(".bf-airline-checkbox");
                    if (cb && cb.checked) {
                        cb.checked = false;
                        changed = true;
                    }
                });
                if (changed) {
                    sessionStorage.setItem("bf_airline_dropdown_open", "1");
                    filterForm.requestSubmit();
                }
            });
        }
    }

    /* ============================================================
       3. LÀM SẠCH INPUT GIÁ TRỐNG TRƯỚC KHI SUBMIT
       ============================================================ */
    filterForm.addEventListener("submit", function () {
        const priceInputs = filterForm.querySelectorAll('input[name="price_min"], input[name="price_max"]');
        priceInputs.forEach((input) => {
            if (!input.value || !input.value.trim()) {
                input.removeAttribute("name");
            }
        });
    });
});

