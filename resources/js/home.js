document.addEventListener("DOMContentLoaded", function () {
    const searchForm = document.getElementById("search-box");
    const departureAirport = document.getElementById("departure-airport");
    const arrivalAirport = document.getElementById("arrival-airport");
    const departureDate = document.getElementById("departure-date");
    const returnDate = document.getElementById("return-date");
    const returnDateField = document.getElementById("return-date-field");
    const searchMessage = document.getElementById("flight-search-message");
    const swapButton = document.getElementById("swap-airports");
    const tripTypeInputs = document.querySelectorAll('input[name="trip_type"]');
    const newsletterForm = document.getElementById("newsletter-form");
    const newsletterEmail = document.getElementById("newsletter-email");
    const newsletterMessage = document.getElementById("newsletter-message");

    const today = new Date().toISOString().split("T")[0];
    if (departureDate) departureDate.min = today;
    if (returnDate) returnDate.min = today;

    if (departureDate && returnDate) {
        departureDate.addEventListener("change", function () {
            returnDate.min = departureDate.value || today;
            if (returnDate.value && returnDate.value < departureDate.value) returnDate.value = "";
        });
    }

    if (swapButton && departureAirport && arrivalAirport) {
        swapButton.addEventListener("click", function () {
            const currentDeparture = departureAirport.value;
            departureAirport.value = arrivalAirport.value;
            arrivalAirport.value = currentDeparture;
        });
    }

    tripTypeInputs.forEach(function (input) {
        input.addEventListener("change", function () {
            const oneWay = input.value === "one-way" && input.checked;
            if (returnDateField) returnDateField.hidden = oneWay;
            if (returnDate) returnDate.required = !oneWay;
        });
    });

    if (searchForm) {
        searchForm.addEventListener("submit", function (event) {
            event.preventDefault();
            const from = departureAirport.value.trim();
            const to = arrivalAirport.value.trim();
            const date = departureDate.value;
            searchMessage.className = "bf-form-message";
            if (!from || !to || !date) {
                searchMessage.textContent = "Vui lòng chọn điểm đi, điểm đến và ngày khởi hành.";
                searchMessage.classList.add("bf-form-message--error");
                return;
            }
            if (from.toLowerCase() === to.toLowerCase()) {
                searchMessage.textContent = "Điểm đến cần khác điểm khởi hành.";
                searchMessage.classList.add("bf-form-message--error");
                return;
            }
            searchMessage.textContent = "Đã ghi nhận tìm kiếm mẫu. Kết quả sẽ được kết nối khi backend hoàn thiện.";
            searchMessage.classList.add("bf-form-message--success");
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
