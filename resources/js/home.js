document.addEventListener("DOMContentLoaded", function () {
    // 1. KHỞI TẠO BIẾN & THIẾT LẬP NGÀY TỐI THIỂU
    const departureDate = document.getElementById("departure-date");
    const returnDate = document.getElementById("return-date");
    const returnDateWrapper = document.getElementById("return-date-wrapper");
    const departureDateWrapper = document.getElementById("departure-date-wrapper");
    const extraLegsSection = document.getElementById("extra-legs-section");
    const extraLegsContainer = document.getElementById("extra-legs");
    const addLegBtn = document.getElementById("add-leg-btn");
    const leg1BadgeWrap = document.getElementById("leg-1-badge-wrap");
    const departureDisplay = document.getElementById("departure-display");
    const arrivalDisplay = document.getElementById("arrival-display");
    const departureAirport = document.getElementById("departure-airport");
    const arrivalAirport = document.getElementById("arrival-airport");
    const swapButton = document.getElementById("swap-airports");
    const tripTypeInputs = document.querySelectorAll('input[name="trip_type"]');
    // Ngày hôm nay theo giờ máy người dùng (toISOString trả về ngày UTC, có thể lệch 1 ngày)
    function localToday() {
        const d = new Date();
        d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
        return d.toISOString().split("T")[0];
    }

    const airportListHtml = document.querySelector("#departure-dropdown-menu .airport-list")?.innerHTML || "";

    const today = localToday();
    if (departureDate) departureDate.min = today;

    // Chuyển chữ có dấu sang không dấu: Hà Nội -> ha noi
    function removeAccents(str) {
        return (str || "")
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .replace(/đ/g, "d")
            .replace(/Đ/g, "D");
    }

    // Tra cứu thông tin sân bay theo ID từ danh sách mẫu
    function getAirportInfoById(id) {
        if (!id) return null;
        const option = document.querySelector(`#departure-dropdown-menu [data-id="${id}"]`);
        if (!option) return null;
        const code = option.getAttribute("data-code") || "";
        const city = option.getAttribute("data-city") || "";
        return {
            id: String(id),
            code: code,
            city: city,
            text: code ? `${city} (${code})` : city
        };
    }

    // 2. CUSTOM AIRPORT SELECTOR
    // setupAirportPicker(type, onSelectCallback)
    function setupAirportPicker(type, onSelectCallback) {
        const inputDisplay = document.getElementById(`${type}-display`);
        const inputHidden = document.getElementById(`${type}-airport`);
        const dropdownMenu = document.getElementById(`${type}-dropdown-menu`);
        const searchInput = document.getElementById(`${type}-search-input`);

        if (!inputDisplay || !dropdownMenu) return;

        if (searchInput) {
            searchInput.addEventListener("input", function () {
                const keyword = removeAccents(this.value.toLowerCase().trim());
                const items = dropdownMenu.querySelectorAll(".airport-list > *, .dropdown-item, [data-id]");

                items.forEach(item => {
                    const text = removeAccents(item.textContent.toLowerCase());
                    if (text.includes(keyword)) {
                        item.classList.remove("d-none");
                    } else {
                        item.classList.add("d-none");
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
            const option = e.target.closest("[data-id]");
            if (!option) return;

            const id = option.getAttribute("data-id");
            const code = option.getAttribute("data-code") || "";
            const city = option.getAttribute("data-city") || option.querySelector(".fw-bold")?.textContent.trim() || "";
            const text = code ? `${city} (${code})` : city;

            inputDisplay.value = text;
            if (inputHidden) inputHidden.value = id;

            // Đóng Dropdown
            const dropdownInstance = bootstrap.Dropdown.getOrCreateInstance(inputDisplay);
            dropdownInstance.hide();

            if (typeof onSelectCallback === "function") {
                onSelectCallback(id, text);
            }
        });

        // Hidden đã có id (old) nhưng ô hiển thị đang trống: lấy chữ từ option tương ứng
        if (inputHidden && inputHidden.value && !inputDisplay.value) {
            const saved = dropdownMenu.querySelector(`[data-id="${inputHidden.value}"]`);
            if (saved) {
                const code = saved.getAttribute("data-code") || "";
                const city = saved.getAttribute("data-city") || "";
                inputDisplay.value = code ? `${city} (${code})` : city;
            }
        }
    }

    // Gắn picker cho chặng 1
    setupAirportPicker("departure");
    setupAirportPicker("arrival", function onArrivalSelected(arrId, arrText) {
        onLeg1ArrivalChanged(arrId, arrText);
    });

    // Logic Nút Đổi điểm đi và điểm đến (Swap) cho chặng 1
    if (swapButton) {
        swapButton.addEventListener("click", function () {
            if (departureDisplay && arrivalDisplay && departureAirport && arrivalAirport) {
                const tempDisplay = departureDisplay.value;
                departureDisplay.value = arrivalDisplay.value;
                arrivalDisplay.value = tempDisplay;

                const tempHidden = departureAirport.value;
                departureAirport.value = arrivalAirport.value;
                arrivalAirport.value = tempHidden;

                // Sau khi swap chặng 1, gợi ý lại cho chặng 2 nếu chặng 2 chưa touched
                onLeg1ArrivalChanged(arrivalAirport.value, arrivalDisplay.value);
            }
        });
    }

    // 3. MULTI-CITY EXTRA LEGS MANAGEMENT
    let extraLegsData = [];

    // Gợi ý điểm đi chặng 2 khi điểm đến chặng 1 thay đổi (nếu chặng 2 chưa touched)
    function onLeg1ArrivalChanged(arrId, arrText) {
        if (!arrId) return;
        if (extraLegsData.length > 0) {
            const leg2 = extraLegsData[0];
            if (!leg2.touched) {
                leg2.depId = arrId;
                leg2.depText = arrText;
                const l2DepDisp = document.getElementById("extra-0-dep-display");
                const l2DepHidden = document.getElementById("extra-0-dep-airport");
                if (l2DepDisp) l2DepDisp.value = arrText;
                if (l2DepHidden) l2DepHidden.value = arrId;
            }
        }
    }

    const searchCard = document.getElementById("search-box");
    const leg1Card = document.getElementById("leg-1-card");
    const depCol = document.getElementById("dep-col");
    const swapCol = document.getElementById("swap-col");
    const arrCol = document.getElementById("arr-col");

    // Ô ngày về chỉ bật khi chọn Khứ hồi (input disabled không được gửi lên server)
    function syncTripType() {
        const checked = document.querySelector('input[name="trip_type"]:checked');
        const tripType = checked ? checked.value : "one-way";
        const isRound = tripType === "round-trip";
        const isMulti = tripType === "multi-city";

        // Mở rộng search card khi ở chế độ Đa chặng
        if (searchCard) {
            if (isMulti) {
                searchCard.classList.add("bf-search-card--multicity");
            } else {
                searchCard.classList.remove("bf-search-card--multicity");
            }
        }

        // 1. Ngày về
        if (returnDate) {
            returnDate.disabled = !isRound;
            returnDate.required = isRound;
            if (!isRound) returnDate.value = "";
        }
        if (returnDateWrapper) {
            if (isMulti) {
                returnDateWrapper.classList.add("d-none");
            } else if (isRound) {
                returnDateWrapper.classList.remove("d-none");
            } else {
                // one-way: có thể hiện disabled hoặc ẩn
                returnDateWrapper.classList.remove("d-none");
            }
        }

        // Căn chỉnh layout hàng Chặng 1 khi sang Đa chặng
        if (isMulti) {
            if (leg1Card) leg1Card.className = "extra-leg-row border rounded-4 p-3 mb-3 position-relative";
            if (leg1BadgeWrap) leg1BadgeWrap.className = "leg-card-header d-flex";
            if (depCol) depCol.className = "col-12 col-md-4 position-relative";
            if (swapCol) swapCol.className = "d-none"; // Ẩn swap ở chặng 1 khi đa chặng để layout 3 cột [Từ][Đến][Ngày] đều tăm tắp
            if (arrCol) arrCol.className = "col-12 col-md-4 position-relative";
            if (departureDateWrapper) departureDateWrapper.className = "col-12 col-md-4 mt-0";
        } else {
            if (leg1Card) leg1Card.className = "p-0 mb-3";
            if (leg1BadgeWrap) leg1BadgeWrap.className = "d-none";
            if (depCol) depCol.className = "col-12 col-md-5 position-relative";
            if (swapCol) swapCol.className = "col-12 col-md-2 d-flex justify-content-center align-items-end bf-swap-wrapper";
            if (arrCol) arrCol.className = "col-12 col-md-5 position-relative";
            if (departureDateWrapper) departureDateWrapper.className = "col-6 col-md-6 mt-2";
        }

        // 3. Khối chặng thêm
        if (extraLegsSection && extraLegsContainer) {
            if (isMulti) {
                extraLegsSection.classList.remove("d-none");
                // Nếu chưa có chặng thêm nào thì khởi tạo 1 chặng (chặng 2)
                if (extraLegsData.length === 0) {
                    addDefaultExtraLeg();
                } else {
                    renderExtraLegs();
                }
                enableExtraLegsInputs(true);
            } else {
                extraLegsSection.classList.add("d-none");
                enableExtraLegsInputs(false);
            }
        }
    }

    // Bật hoặc tắt disabled cho tất cả input bên trong khối chặng thêm
    function enableExtraLegsInputs(enable) {
        if (!extraLegsContainer) return;
        const inputs = extraLegsContainer.querySelectorAll("input, select, button");
        inputs.forEach(el => {
            if (el.classList.contains("remove-leg-btn")) {
                return;
            }
            el.disabled = !enable;
        });
    }

    // Ngày về không được trước ngày đi
    function syncReturnMin() {
        if (!returnDate) return;
        const min = departureDate && departureDate.value ? departureDate.value : today;
        returnDate.min = min;
        if (returnDate.value && returnDate.value < min) returnDate.value = min;
    }

    // Tính toán lại min date cho từng chặng thêm
    function syncExtraLegsMinDates() {
        if (!extraLegsData.length) return;
        let prevDate = (departureDate && departureDate.value) ? departureDate.value : today;

        extraLegsData.forEach((leg, idx) => {
            const minDate = prevDate || today;
            const row = extraLegsContainer ? extraLegsContainer.querySelectorAll(".extra-leg-row")[idx] : null;
            const dateInput = row ? row.querySelector(".extra-date-input") : null;

            if (dateInput) {
                dateInput.min = minDate;
                if (dateInput.value && dateInput.value < minDate) {
                    dateInput.value = minDate;
                    leg.date = minDate;
                }
            } else if (leg.date && leg.date < minDate) {
                leg.date = minDate;
            }

            if (leg.date) {
                prevDate = leg.date;
            }
        });
    }

    // Thêm 1 chặng mặc định (chặng 2 hoặc chặng tiếp theo)
    function addDefaultExtraLeg() {
        if (extraLegsData.length >= 3) return;

        let autoDepId = "";
        let autoDepText = "";

        // Gợi ý điểm đi từ điểm đến của chặng liền trước (nếu đã chọn)
        if (extraLegsData.length === 0) {
            if (arrivalAirport && arrivalAirport.value) {
                autoDepId = arrivalAirport.value;
                autoDepText = arrivalDisplay ? arrivalDisplay.value : "";
            }
        } else {
            const prevLeg = extraLegsData[extraLegsData.length - 1];
            if (prevLeg.arrId) {
                autoDepId = prevLeg.arrId;
                autoDepText = prevLeg.arrText;
            }
        }

        // Ngày mặc định: bằng ngày chặng trước nếu có
        let defaultDate = "";
        if (extraLegsData.length === 0) {
            defaultDate = (departureDate && departureDate.value) ? departureDate.value : today;
        } else {
            const prevLeg = extraLegsData[extraLegsData.length - 1];
            defaultDate = prevLeg.date || ((departureDate && departureDate.value) ? departureDate.value : today);
        }

        extraLegsData.push({
            depId: autoDepId,
            depText: autoDepText,
            arrId: "",
            arrText: "",
            date: defaultDate,
            touched: false
        });

        renderExtraLegs();
    }

    // Render toàn bộ các dòng chặng thêm dựa trên extraLegsData
    function renderExtraLegs() {
        if (!extraLegsContainer) return;
        extraLegsContainer.innerHTML = "";

        const template = document.getElementById("extra-leg-template");
        if (!template) return;

        extraLegsData.forEach((leg, i) => {
            const clone = template.content.cloneNode(true);
            const row = clone.querySelector(".extra-leg-row");
            const label = row.querySelector(".extra-leg-label");
            const removeBtn = row.querySelector(".remove-leg-btn");

            const depDisplay = row.querySelector(".extra-dep-display");
            const depHidden = row.querySelector(".extra-dep-hidden");
            const depMenu = row.querySelector(".extra-dep-menu");
            const depSearch = row.querySelector(".extra-dep-search");
            const depList = depMenu.querySelector(".airport-list");

            const arrDisplay = row.querySelector(".extra-arr-display");
            const arrHidden = row.querySelector(".extra-arr-hidden");
            const arrMenu = row.querySelector(".extra-arr-menu");
            const arrSearch = row.querySelector(".extra-arr-search");
            const arrList = arrMenu.querySelector(".airport-list");

            const dateInput = row.querySelector(".extra-date-input");

            // Đặt ID duy nhất cho bộ chọn theo đúng quy ước setupAirportPicker: extra-{i}-dep và extra-{i}-arr
            const depPrefix = `extra-${i}-dep`;
            depDisplay.id = `${depPrefix}-display`;
            depHidden.id = `${depPrefix}-airport`;
            depHidden.name = `extra_legs[${i}][departure_airport_id]`;
            depMenu.id = `${depPrefix}-dropdown-menu`;
            depSearch.id = `${depPrefix}-search-input`;
            if (leg.touched) depDisplay.setAttribute("data-touched", "1");

            const arrPrefix = `extra-${i}-arr`;
            arrDisplay.id = `${arrPrefix}-display`;
            arrHidden.id = `${arrPrefix}-airport`;
            arrHidden.name = `extra_legs[${i}][arrival_airport_id]`;
            arrMenu.id = `${arrPrefix}-dropdown-menu`;
            arrSearch.id = `${arrPrefix}-search-input`;

            dateInput.name = `extra_legs[${i}][date]`;
            dateInput.value = leg.date || "";

            // Nhãn: Chặng N (N = i + 2)
            label.textContent = `Chặng ${i + 2}`;

            // Điền danh sách sân bay vào menu
            if (depList) depList.innerHTML = airportListHtml;
            if (arrList) arrList.innerHTML = airportListHtml;

            // Thiết lập giá trị điểm đi
            if (leg.depId) {
                depHidden.value = leg.depId;
                if (!leg.depText) {
                    const info = getAirportInfoById(leg.depId);
                    leg.depText = info ? info.text : "";
                }
                depDisplay.value = leg.depText;
            }

            // Thiết lập giá trị điểm đến
            if (leg.arrId) {
                arrHidden.value = leg.arrId;
                if (!leg.arrText) {
                    const info = getAirportInfoById(leg.arrId);
                    leg.arrText = info ? info.text : "";
                }
                arrDisplay.value = leg.arrText;
            }

            // Ẩn nút xóa nếu chỉ có đúng 1 chặng thêm (chặng 2 duy nhất)
            if (extraLegsData.length <= 1) {
                removeBtn.classList.add("d-none");
            } else {
                removeBtn.classList.remove("d-none");
            }

            // Gắn sự kiện xóa
            removeBtn.addEventListener("click", function () {
                extraLegsData.splice(i, 1);
                renderExtraLegs();
                syncExtraLegsMinDates();
            });

            // Gắn sự kiện khi đổi ngày
            dateInput.addEventListener("change", function () {
                leg.date = this.value;
                syncExtraLegsMinDates();
            });

            extraLegsContainer.appendChild(clone);

            // Gắn setupAirportPicker cho điểm đi và điểm đến
            setupAirportPicker(depPrefix, function onDepSelected(id, text) {
                leg.depId = id;
                leg.depText = text;
                leg.touched = true;
                const disp = document.getElementById(`${depPrefix}-display`);
                if (disp) disp.setAttribute("data-touched", "1");
            });

            setupAirportPicker(arrPrefix, function onArrSelected(id, text) {
                leg.arrId = id;
                leg.arrText = text;

                // Gợi ý điểm đi cho chặng tiếp theo i + 1 nếu chặng đó chưa được người dùng tự chọn tay (touched)
                if (i + 1 < extraLegsData.length) {
                    const nextLeg = extraLegsData[i + 1];
                    if (!nextLeg.touched) {
                        nextLeg.depId = id;
                        nextLeg.depText = text;
                        const nextDepDisp = document.getElementById(`extra-${i + 1}-dep-display`);
                        const nextDepHidden = document.getElementById(`extra-${i + 1}-dep-airport`);
                        if (nextDepDisp) nextDepDisp.value = text;
                        if (nextDepHidden) nextDepHidden.value = id;
                    }
                }
            });
        });

        // Ẩn/hiện nút Thêm chặng (tối đa 3 chặng thêm)
        if (addLegBtn) {
            if (extraLegsData.length >= 3) {
                addLegBtn.classList.add("d-none");
            } else {
                addLegBtn.classList.remove("d-none");
            }
        }

        // Cập nhật min date
        syncExtraLegsMinDates();

        // Đảm bảo trạng thái disabled của inputs đúng với trip type hiện tại
        const checked = document.querySelector('input[name="trip_type"]:checked');
        enableExtraLegsInputs(checked && checked.value === "multi-city");
    }

    // Nút Thêm chặng
    if (addLegBtn) {
        addLegBtn.addEventListener("click", function () {
            addDefaultExtraLeg();
        });
    }

    // Khôi phục dữ liệu từ old('extra_legs') nếu có
    if (extraLegsContainer && extraLegsContainer.dataset.old) {
        try {
            const oldLegs = JSON.parse(extraLegsContainer.dataset.old);
            if (Array.isArray(oldLegs) && oldLegs.length > 0) {
                extraLegsData = oldLegs.map(item => {
                    const depId = item.departure_airport_id ? String(item.departure_airport_id) : "";
                    const arrId = item.arrival_airport_id ? String(item.arrival_airport_id) : "";
                    const depInfo = getAirportInfoById(depId);
                    const arrInfo = getAirportInfoById(arrId);
                    return {
                        depId: depId,
                        depText: depInfo ? depInfo.text : "",
                        arrId: arrId,
                        arrText: arrInfo ? arrInfo.text : "",
                        date: item.date || "",
                        touched: !!depId
                    };
                });
            }
        } catch (e) {
            console.error("Lỗi parse old extra_legs:", e);
        }
    }

    // Lắng nghe đổi trip type và ngày đi
    tripTypeInputs.forEach(function (input) {
        input.addEventListener("change", syncTripType);
    });
    if (departureDate) {
        departureDate.addEventListener("change", function () {
            syncReturnMin();
            syncExtraLegsMinDates();
        });
    }

    syncTripType();
    syncReturnMin();

    // 4. QUẢN LÝ SỐ LƯỢNG HÀNH KHÁCH & VÉ
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
    updatePassengerSummary();

    // 5. NEWSLETTER FORM
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