/**
 * resources/js/payment.js
 * Quản lý tương tác trang thanh toán, đếm ngược thời gian thực (mm:ss)
 * và kích hoạt Modal thông báo khi hết hạn 20 phút.
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Quản lý tương tác chọn phương thức thanh toán
    const methodItems = document.querySelectorAll('.bf-payment-method-item');
    methodItems.forEach(function (item) {
        const radio = item.querySelector('input[type="radio"]');
        if (radio) {
            radio.addEventListener('change', function () {
                methodItems.forEach(i => i.classList.remove('active'));
                if (this.checked) {
                    item.classList.add('active');
                }
            });
        }
    });

    // 2. Quản lý đếm ngược real-time (mm:ss) và xử lý khi hết hạn
    const expiryBox = document.getElementById('payment-expiry-box');
    const countdownBadge = document.getElementById('payment-countdown-badge');

    if (expiryBox && countdownBadge) {
        const expiresAtSec = parseInt(expiryBox.dataset.expiresAt, 10);
        const serverNowSec = parseInt(expiryBox.dataset.serverNow, 10);
        const redirectUrl = expiryBox.dataset.redirectUrl || '/';

        // Tính khoảng thời gian còn lại (tính bằng giây) từ mốc server
        let remainingSeconds = Math.max(0, expiresAtSec - serverNowSec);

        function formatTime(totalSeconds) {
            const minutes = Math.floor(totalSeconds / 60);
            const seconds = totalSeconds % 60;
            return String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
        }

        // Cập nhật text ban đầu
        countdownBadge.textContent = formatTime(remainingSeconds);

        let countdownTimer = null;
        let isExpiredHandled = false;

        function handleExpiration() {
            if (isExpiredHandled) return;
            isExpiredHandled = true;

            if (countdownTimer) {
                clearInterval(countdownTimer);
            }

            countdownBadge.textContent = '00:00';

            // Vô hiệu hóa form thanh toán
            const form = document.getElementById('bf-payment-form');
            if (form) {
                form.classList.add('bf-payment-expired');
                const submitBtns = form.querySelectorAll('button[type="submit"]');
                submitBtns.forEach(btn => {
                    btn.disabled = true;
                });
            }

            // Hiển thị modal thông báo hết hạn
            const modalEl = document.getElementById('paymentExpiredModal');
            if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const modal = new bootstrap.Modal(modalEl, {
                    backdrop: 'static',
                    keyboard: false
                });
                modal.show();

                // Đếm ngược 5 giây tự động redirect
                let autoRedirectSeconds = 5;
                const redirectSecEl = document.getElementById('redirect-seconds');

                const redirectInterval = setInterval(function () {
                    autoRedirectSeconds--;
                    if (redirectSecEl) {
                        redirectSecEl.textContent = autoRedirectSeconds;
                    }
                    if (autoRedirectSeconds <= 0) {
                        clearInterval(redirectInterval);
                        window.location.href = redirectUrl;
                    }
                }, 1000);

                // Nút đóng modal hoặc nút chuyển trang
                const closeBtn = document.getElementById('btn-close-expired-modal');
                if (closeBtn) {
                    closeBtn.addEventListener('click', function () {
                        clearInterval(redirectInterval);
                        window.location.href = redirectUrl;
                    });
                }
            } else {
                // Fallback nếu modal không mở được
                alert('Phiên thanh toán của bạn đã kết thúc. Vui lòng quay lại trang chủ.');
                window.location.href = redirectUrl;
            }
        }

        if (remainingSeconds <= 0) {
            handleExpiration();
        } else {
            countdownTimer = setInterval(function () {
                remainingSeconds--;
                if (remainingSeconds <= 0) {
                    handleExpiration();
                } else {
                    countdownBadge.textContent = formatTime(remainingSeconds);
                }
            }, 1000);
        }
    }

    // 3. Quản lý Modal Popup cảnh báo hộ chiếu rủi ro (< 6 tháng hoặc hết hạn)
    const passportOverlay = document.getElementById('bfPassportRiskOverlay');
    const modalCheckbox = document.getElementById('modal_confirm_passport_risk');
    const btnAcknowledge = document.getElementById('btn-passport-acknowledge');
    const hiddenCheckbox = document.getElementById('confirm_passport_risk');
    const btnReopen = document.getElementById('btn-reopen-passport-modal');

    if (passportOverlay && modalCheckbox && btnAcknowledge) {
        // Khóa cuộn trang khi modal đang mở
        document.body.style.overflow = 'hidden';

        // Lắng nghe sự kiện tick checkbox
        modalCheckbox.addEventListener('change', function () {
            btnAcknowledge.disabled = !this.checked;
        });

        // Xử lý khi bấm nút "Tôi đã hiểu"
        btnAcknowledge.addEventListener('click', function () {
            if (this.disabled) return;

            // Đồng bộ trạng thái đã tick sang hidden input của form thanh toán
            if (hiddenCheckbox) {
                hiddenCheckbox.checked = true;
            }

            // Hiệu ứng đóng modal ease-in-out
            passportOverlay.classList.add('closing');
            setTimeout(function () {
                passportOverlay.style.display = 'none';
                document.body.style.overflow = '';
            }, 250);
        });

        // Nút xem lại cảnh báo (trên dòng hotline site)
        if (btnReopen) {
            btnReopen.addEventListener('click', function (e) {
                e.preventDefault();
                passportOverlay.classList.remove('closing');
                passportOverlay.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            });
        }

        // Chặn submit form nếu chưa xác nhận popup
        const paymentForm = document.getElementById('bf-payment-form');
        if (paymentForm && hiddenCheckbox) {
            paymentForm.addEventListener('submit', function (e) {
                if (!hiddenCheckbox.checked) {
                    e.preventDefault();
                    passportOverlay.classList.remove('closing');
                    passportOverlay.style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                }
            });
        }
    }
});

