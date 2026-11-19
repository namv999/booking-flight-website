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
        const dobInput = byId('dob');
        const issueDateInput = byId('issue_date');
        const issuedCountry = byId('issued_country'); // chỉ có khi hành trình quốc tế
        const isInternational = issuedCountry !== null;

        // 1. Các trường chung luôn có cả nội địa và quốc tế: Họ tên, Ngày sinh, Giới tính
        setValue(nameInput, profile.full_name);
        setValue(dobInput, profile.date_of_birth);

        const male = byId('gender_male');
        const female = byId('gender_female');
        if (male && female && profile.gender) {
            male.checked = profile.gender === 'male';
            female.checked = profile.gender === 'female';
        }

        // 2. Điền giấy tờ tùy theo bay quốc tế hay nội địa
        if (isInternational) {
            // Bay quốc tế: Ưu tiên lấy thông tin Hộ chiếu
            const passNum = profile.passport_number || (profile.document_type === 'passport' ? profile.document_number : '');
            const passCountry = profile.passport_issued_country || (profile.document_type === 'passport' ? profile.document_issued_country : profile.nationality);
            const passIssue = profile.passport_issue_date || (profile.document_type === 'passport' ? profile.document_issue_date : '');
            const passExpiry = profile.passport_expiry_date || profile.document_expiry_date;

            setValue(docInput, passNum);
            setSelect(byId('nat'), profile.nationality || 'VNM');
            setSelect(issuedCountry, passCountry || 'VNM');
            setValue(issueDateInput, passIssue);
            setValue(byId('expiry_date'), passExpiry);
        } else {
            // Bay nội địa: Điền thông tin CCCD (Số CCCD & Ngày cấp CCCD)
            const cccdNum = profile.cccd_number || (profile.document_type === 'national_id' ? profile.document_number : (profile.passport_number ? '' : profile.document_number));
            const cccdIssue = profile.cccd_issue_date || (profile.document_type === 'national_id' ? profile.document_issue_date : '');

            setValue(docInput, cccdNum);
            setValue(issueDateInput, cccdIssue);
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
            const prefix = select.dataset.autofillGroup + '_' + select.dataset.autofillIndex;
            const isInternational = document.getElementById(prefix + '_issued_country') !== null;

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

                const cccd = p.cccd_number || (p.document_type === 'national_id' ? p.document_number : null);
                const passport = p.passport_number || (p.document_type === 'passport' ? p.document_number : null);

                let docLabel = '';
                if (isInternational) {
                    docLabel = passport ? ' (HC: ' + passport + ')' : (cccd ? ' (CCCD: ' + cccd + ')' : '');
                } else {
                    docLabel = cccd ? ' (CCCD: ' + cccd + ')' : (passport ? ' (HC: ' + passport + ')' : '');
                }

                option.textContent = p.full_name + docLabel;
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
