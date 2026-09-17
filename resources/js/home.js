document.addEventListener("DOMContentLoaded", function () {
    const departureAirport = document.getElementById("departure-airport");
    const arrivalAirport = document.getElementById("arrival-airport");
    const departureDate = document.getElementById("departure-date");
    const returnDate = document.getElementById("return-date");
    const swapButton = document.getElementById("swap-airports");
    const tripTypeInputs = document.querySelectorAll('input[name="trip_type"]');
    const newsletterForm = document.getElementById("newsletter-form");
    const newsletterEmail = document.getElementById("newsletter-email");
    const newsletterMessage = document.getElementById("newsletter-message");

    const today = new Date().toISOString().split("T")[0];
    if (departureDate) departureDate.min = today;
    if (returnDate) returnDate.min = today;

    if (swapButton && departureAirport && arrivalAirport) {
        swapButton.addEventListener("click", function () {
            const currentDeparture = departureAirport.value;
            departureAirport.value = arrivalAirport.value;
            arrivalAirport.value = currentDeparture;
        });
    }

    // Trip type: chỉ enable/disable field ngày về, KHÔNG có logic khứ hồi/đa chặng thật
    tripTypeInputs.forEach(function (input) {
        input.addEventListener("change", function () {
            if (returnDate) returnDate.disabled = input.value === "one-way";
        });
    });

    // Passenger stepper — tham khảo Traveloka: +/- button hoặc gõ tay đều được
    const passengerConfig = [
        { key: "adults", min: 1, max: 9 },
        { key: "children", min: 0, max: 9 },
        { key: "infants", min: 0, max: 9 },
    ];
    const passengerSummary = document.getElementById("passenger-summary");

    function updatePassengerSummary() {
        const adults = document.getElementById("adults-input").value;
        const children = document.getElementById("children-input").value;
        const infants = document.getElementById("infants-input").value;
        passengerSummary.textContent = adults + " Người lớn, " + children + " Trẻ em, " + infants + " Em bé";
    }

    passengerConfig.forEach(function (cfg) {
        const input = document.getElementById(cfg.key + "-input");
        if (!input) return;

        input.addEventListener("change", function () {
            let value = parseInt(input.value, 10);
            if (isNaN(value)) value = cfg.min;
            value = Math.min(cfg.max, Math.max(cfg.min, value));
            input.value = value;
            updatePassengerSummary();
        });

        document.querySelectorAll('.bf-stepper-btn[data-target="' + cfg.key + '"]').forEach(function (btn) {
            btn.addEventListener("click", function () {
                const step = parseInt(btn.dataset.step, 10);
                let value = parseInt(input.value, 10) + step;
                value = Math.min(cfg.max, Math.max(cfg.min, value));
                input.value = value;
                updatePassengerSummary();
            });
        });
    });

    const adultsInput = document.getElementById('adults-input');
    const infantsInput = document.getElementById('infants-input');

    function checkInfantLimit() {
        const adults = parseInt(adultsInput.value) || 0;
        const infants = parseInt(infantsInput.value) || 0;

        if (infants > adults) {
            // Tự động điều chỉnh số lượng em bé tối đa bằng số người lớn
            infantsInput.value = adults;

            // Hiển thị Popup thông báo
            Swal.fire({
                icon: 'warning',
                title: 'Số lượng không hợp lệ',
                text: 'Số lượng em bé (dưới 2 tuổi) không được vượt quá số lượng người lớn đi cùng.',
                confirmButtonColor: '#ffa726',
                confirmButtonText: 'Đã hiểu'
            });

            // Cập nhật lại chuỗi hiển thị tổng số hành khách (nếu có hàm update)
            if (typeof updatePassengerSummary === 'function') {
                updatePassengerSummary();
            }
        }
    }

    // Kiểm tra khi người dùng bấm nút tăng/giảm (+/-)
    document.querySelectorAll('.bf-stepper-btn').forEach(button => {
        button.addEventListener('click', function () {
            // Đợi stepper cập nhật value xong rồi kiểm tra
            setTimeout(checkInfantLimit, 50);
        });
    });

    // Kiểm tra khi người dùng thay đổi trực tiếp ô input hoặc giảm người lớn
    infantsInput.addEventListener('change', checkInfantLimit);
    adultsInput.addEventListener('change', checkInfantLimit);

    const passengerDoneButton = document.getElementById("passenger-done");
    if (passengerDoneButton) {
        passengerDoneButton.addEventListener("click", function () {
            const toggle = document.getElementById("passenger-toggle");
            bootstrap.Dropdown.getOrCreateInstance(toggle).hide();
        });
    }

    if (newsletterForm) {
        newsletterForm.addEventListener("submit", function (event) {
            event.preventDefault();
            newsletterMessage.className = "bf-form-message";
            if (!newsletterEmail.value.trim() || !newsletterEmail.checkValidity()) {
                newsletterMessage.textContent = "Vui lòng nhập một địa chỉ email hợp lệ.";
                newsletterMessage.classList.add("bf-form-message--error");
                return;
            }
            newsletterMessage.textContent = "Cảm ơn bạn. Email đã được ghi nhận trong bản giao diện mẫu.";
            newsletterMessage.classList.add("bf-form-message--success");
            newsletterEmail.value = "";
        });
    }
});