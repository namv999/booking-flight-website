document.addEventListener("DOMContentLoaded", function () {
    // 1. KHỞI TẠO BIẾN & THIẾT LẬP NGÀY TỐI THIỂU
    const departureDate = document.getElementById("departure-date");
    const returnDate = document.getElementById("return-date");
    const swapButton = document.getElementById("swap-airports");
    const tripTypeInputs = document.querySelectorAll('input[name="trip_type"]');
    
    const today = new Date().toISOString().split("T")[0];
    if (departureDate) departureDate.min = today;
    if (returnDate) returnDate.min = today;

    // Bật/tắt ô Ngày về theo Loại hành trình
    tripTypeInputs.forEach(function (input) {
        input.addEventListener("change", function () {
            if (returnDate) returnDate.disabled = (input.value === "one-way");
        });
    });

    // 2. CUSTOM AIRPORT SELECTOR
    function setupAirportPicker(type) {
        const inputDisplay = document.getElementById(`${type}-display`);
        const inputHidden = document.getElementById(`${type}-airport`);
        const dropdownMenu = document.getElementById(`${type}-dropdown-menu`);
        const searchInput = document.getElementById(`${type}-search-input`);

        if (!inputDisplay || !dropdownMenu) return;

        // Chuyển chữ có dấu sang không dấu: Hà Nội -> ha noi
        function removeAccents(str) {
            return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "").replace(/đ/g, "d").replace(/Đ/g, "D");
        }

        if (searchInput) {
            searchInput.addEventListener("input", function () {
                const keyword = removeAccents(this.value.toLowerCase().trim());
                
                // Tìm tất cả phần tử danh sách bên trong dropdown (trừ ô input search)
                const items = dropdownMenu.querySelectorAll(".airport-list > *, .dropdown-item, [data-id]");

                items.forEach(item => {
                    const text = removeAccents(item.textContent.toLowerCase());
                    if (text.includes(keyword)) {
                        item.classList.remove("d-none"); // Hiện
                    } else {
                        item.classList.add("d-none");    // Ẩn
                    }
                });
            });

            // Ngăn việc click vào ô search làm đóng dropdown
            searchInput.addEventListener("click", function (e) {
                e.stopPropagation();
            });
        }

        // Xử lý sự kiện click chọn sân bay
        dropdownMenu.addEventListener("click", function (e) {
            // Tìm phần tử chứa data-id gần nhất
            const option = e.target.closest("[data-id]");
            if (!option) return;

            const id = option.getAttribute("data-id");
            const code = option.getAttribute("data-code") || "";
            const city = option.getAttribute("data-city") || option.querySelector(".fw-bold")?.textContent.trim() || "";

            // Cập nhật giao diện và giá trị ẩn
            inputDisplay.value = code ? `${city} (${code})` : city;
            if (inputHidden) inputHidden.value = id;

            // Đóng Dropdown
            const dropdownInstance = bootstrap.Dropdown.getOrCreateInstance(inputDisplay);
            dropdownInstance.hide();
        });
    }

    // Khởi tạo bộ chọn cho cả Điểm đi (departure) và Điểm đến (arrival)
    setupAirportPicker("departure");
    setupAirportPicker("arrival");

    // Logic Nút Đổi điểm đi và điểm đến (Swap)
    if (swapButton) {
        swapButton.addEventListener("click", function () {
            const depDisplay = document.getElementById("departure-display");
            const arrDisplay = document.getElementById("arrival-display");
            const depHidden = document.getElementById("departure-airport");
            const arrHidden = document.getElementById("arrival-airport");

            if (depDisplay && arrDisplay && depHidden && arrHidden) {
                // Đổi hiển thị
                const tempDisplay = depDisplay.value;
                depDisplay.value = arrDisplay.value;
                arrDisplay.value = tempDisplay;

                // Đổi ID gửi form
                const tempHidden = depHidden.value;
                depHidden.value = arrHidden.value;
                arrHidden.value = tempHidden;
            }
        });
    }

    // 3. QUẢN LÝ SỐ LƯỢNG HÀNH KHÁCH & VÉ
    const adultsInput = document.getElementById("adults-input");
    const infantsInput = document.getElementById("infants-input");
    const passengerSummary = document.getElementById("passenger-summary");
    const passengerDoneButton = document.getElementById("passenger-done");

    const passengerConfig = [
        { key: "adults", min: 1, max: 9 },
        { key: "children", min: 0, max: 9 },
        { key: "infants", min: 0, max: 9 },
    ];

    function updatePassengerSummary() {
        if (!passengerSummary) return;
        const adults = document.getElementById("adults-input")?.value || 1;
        const children = document.getElementById("children-input")?.value || 0;
        const infants = document.getElementById("infants-input")?.value || 0;
        passengerSummary.textContent = `${adults} Người lớn, ${children} Trẻ em, ${infants} Em bé`;
    }

    // Kiểm tra giới hạn Em bé <= Người lớn
    function checkInfantLimit() {
        if (!adultsInput || !infantsInput) return;
        const adults = parseInt(adultsInput.value, 10) || 0;
        const infants = parseInt(infantsInput.value, 10) || 0;

        if (infants > adults) {
            infantsInput.value = adults;
            updatePassengerSummary();

            if (typeof Swal !== "undefined") {
                Swal.fire({
                    icon: "warning",
                    title: "Số lượng không hợp lệ",
                    text: "Số lượng em bé (dưới 2 tuổi) không được vượt quá số lượng người lớn đi cùng.",
                    confirmButtonColor: "#ffa726",
                    confirmButtonText: "Đã hiểu"
                });
            }
        }
    }

    // Đăng ký sự kiện Tăng/Giảm cho các nút Stepper
    passengerConfig.forEach(function (cfg) {
        const input = document.getElementById(`${cfg.key}-input`);
        if (!input) return;

        input.addEventListener("change", function () {
            let value = parseInt(input.value, 10);
            if (isNaN(value)) value = cfg.min;
            value = Math.min(cfg.max, Math.max(cfg.min, value));
            input.value = value;
            updatePassengerSummary();
            checkInfantLimit();
        });

        document.querySelectorAll(`.bf-stepper-btn[data-target="${cfg.key}"]`).forEach(function (btn) {
            btn.addEventListener("click", function () {
                const step = parseInt(btn.dataset.step, 10);
                let value = (parseInt(input.value, 10) || 0) + step;
                value = Math.min(cfg.max, Math.max(cfg.min, value));
                input.value = value;
                updatePassengerSummary();
                checkInfantLimit();
            });
        });
    });

    if (passengerDoneButton) {
        passengerDoneButton.addEventListener("click", function () {
            const toggle = document.getElementById("passenger-toggle");
            if (toggle) bootstrap.Dropdown.getOrCreateInstance(toggle).hide();
        });
    }

    // 4. NEWSLETTER FORM (chưa có mockup)
    const newsletterForm = document.getElementById("newsletter-form");
    if (newsletterForm) {
        newsletterForm.addEventListener("submit", function (event) {
            event.preventDefault();
            const newsletterEmail = document.getElementById("newsletter-email");
            const newsletterMessage = document.getElementById("newsletter-message");
            
            newsletterMessage.className = "bf-form-message";
            if (!newsletterEmail.value.trim() || !newsletterEmail.checkValidity()) {
                newsletterMessage.textContent = "Vui lòng nhập một địa chỉ email hợp lệ.";
                newsletterMessage.classList.add("bf-form-message--error");
                return;
            }
            newsletterMessage.textContent = "Cảm ơn bạn. Email đã được ghi nhận.";
            newsletterMessage.classList.add("bf-form-message--success");
            newsletterEmail.value = "";
        });
    }
});