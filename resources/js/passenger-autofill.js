// Autofill form hành khách từ hồ sơ đã lưu (chỉ là tiện ích phía trình duyệt,
// server vẫn validate lại toàn bộ bằng PassengerBookingRequest).
(function () {
    if (window.__bfPassengerAutofillInit) {
        return;
    }
    window.__bfPassengerAutofillInit = true;

    // Khối nào chỉ liệt kê hồ sơ loại nào
    const GROUP_TYPE = {
        adults: 'adult',
        children: 'child',
        infants: 'infant',
    };

    function setValue(el, value) {
        if (el) {
            el.value = value ?? '';
        }
    }

    // Chỉ chọn nếu mã có trong danh sách option (hồ sơ cũ có thể là chữ tự do như "Vietnam")
    function setSelect(el, value) {
        if (!el || !value) {
            return;
        }
        const exists = Array.from(el.options).some(function (opt) {
            return opt.value === value;
        });
        if (exists) {
            el.value = value;
        }
    }

    function fill(select, profile) {
        const prefix = select.dataset.autofillGroup + '_' + select.dataset.autofillIndex;
        const byId = function (suffix) {
            return document.getElementById(prefix + '_' + suffix);
        };

        const nameInput = byId('name');
        const docInput = byId('doc');
        const issuedCountry = byId('issued_country'); // chỉ có khi hành trình quốc tế
        const isInternational = issuedCountry !== null;

        // 3 ô luôn có
        setValue(nameInput, profile.full_name);
        setValue(byId('dob'), profile.date_of_birth);

        // Form quốc tế yêu cầu SỐ HỘ CHIẾU: không nhét số CCCD vào đó
        const docValue = isInternational && profile.document_type === 'national_id'
            ? ''
            : profile.document_number;
        setValue(docInput, docValue);

        // Các ô hộ chiếu: chỉ điền nếu tồn tại trong DOM
        if (isInternational) {
            const male = byId('gender_male');
            const female = byId('gender_female');
            if (male && female) {
                male.checked = profile.gender === 'male';
                female.checked = profile.gender === 'female';
            }

            setSelect(byId('nat'), profile.nationality);
            setSelect(issuedCountry, profile.document_issued_country);
            setValue(byId('issue_date'), profile.document_issue_date);
            setValue(byId('expiry_date'), profile.document_expiry_date);
        }

        // Gán value bằng JS không tự bắn blur, nên bắn tay để script chuẩn hóa
        // (bỏ dấu, viết hoa) của form chạy như khi khách gõ tay
        [nameInput, docInput].forEach(function (el) {
            if (el) {
                el.dispatchEvent(new Event('blur'));
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const selects = document.querySelectorAll('select[data-autofill-group]');
        if (selects.length === 0) {
            return;
        }

        const profilesById = new Map();

        // Gắn sự kiện change: chỉ điền khi khách CHỌN, không điền lúc tải trang
        // (nếu không sẽ ghi đè old() khi form bị trả lại do lỗi validate)
        selects.forEach(function (select) {
            select.addEventListener('change', function () {
                if (select.value === '') {
                    return; // "Nhập tay": giữ nguyên những gì khách đã gõ
                }
                const profile = profilesById.get(select.value);
                if (profile) {
                    fill(select, profile);
                }
            });
        });

        function populate(select, profiles) {
            const wanted = GROUP_TYPE[select.dataset.autofillGroup];
            const matched = profiles.filter(function (p) {
                return p.passenger_type_default === wanted;
            });

            if (matched.length === 0) {
                select.options[0].textContent = '-- Chưa có hồ sơ phù hợp --';
                select.disabled = true;
                return;
            }

            matched.forEach(function (p) {
                const option = document.createElement('option');
                option.value = String(p.id);
                option.textContent = p.document_number
                    ? p.full_name + ' (' + p.document_number + ')'
                    : p.full_name;
                select.appendChild(option);
            });
        }

        function markUnavailable(select) {
            select.options[0].textContent = '-- Không tải được hồ sơ, hãy nhập tay --';
            select.disabled = true;
        }

        // fetch 1 lần, dùng chung cho mọi ô select
        fetch(selects[0].dataset.optionsUrl, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(function (profiles) {
                profiles.forEach(function (p) {
                    profilesById.set(String(p.id), p);
                });
                selects.forEach(function (select) {
                    populate(select, profiles);
                });
            })
            .catch(function () {
                selects.forEach(markUnavailable);
            });
    });
})();