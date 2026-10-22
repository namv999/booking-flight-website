// resources/js/seat-map.js - Bước "Chọn ghế": vẽ sơ đồ ghế từng chặng, quản lý lựa chọn, sinh input hidden khi gửi form.
// JS thuần, không thư viện, không localStorage. Hợp đồng DOM/JSON: xem UI-GUIDE-SeatMap.md.
(function () {
    'use strict';

    const app = document.getElementById('seat-map-app');
    const form = document.getElementById('seat-form');

    if (!app || !form) {
        return;
    }

    const inputsBox = document.getElementById('seat-inputs');
    const confirmBtn = document.getElementById('seat-confirm');
    const autoBtn = document.getElementById('seat-auto');
    const reloadBtn = document.getElementById('seat-reload');
    const gridBox = document.getElementById('seat-grid');
    const chipsBox = document.getElementById('seat-chips');
    const noticeBox = document.getElementById('seat-notice');
    const warningsBox = document.getElementById('seat-warnings');
    const feeNote = document.getElementById('seat-fee-note');
    const feeTotal = document.getElementById('seat-fee-total');
    const summaryBox = document.getElementById('seat-summary-list');
    const tabs = Array.from(app.querySelectorAll('[data-leg-tab]'));
    const legInfos = Array.from(app.querySelectorAll('[data-leg-info]'));

    const MSG_FULL = 'Bạn đã chọn đủ ghế cho mọi hành khách ở chặng này. Bấm vào một ghế đã chọn để bỏ chọn trước khi chọn ghế khác.';
    const MSG_LOAD_ERROR = 'Phiên đặt vé đã hết hạn hoặc có lỗi tải sơ đồ ghế';
    const POSITION_LABELS = { window: 'cửa sổ', aisle: 'lối đi', middle: 'giữa' };

    const money = new Intl.NumberFormat('vi-VN');
    const fmtSpaced = (n) => money.format(n) + ' đ'; // dòng tổng: "40.000 đ"
    const fmtTight = (n) => money.format(n) + 'đ'; // aria-label: "40.000đ"

    function parseJson(raw, fallback) {
        try {
            const value = JSON.parse(raw);

            return value === null || value === undefined ? fallback : value;
        } catch (e) {
            return fallback;
        }
    }

    const passengers = parseJson(app.dataset.passengers, []);
    const rawUrls = parseJson(app.dataset.mapUrls, []);
    const oldSeats = parseJson(app.dataset.oldSeats, []);
    const homeUrl = app.dataset.homeUrl || '/';

    // mapUrls có thể là mảng hoặc object khoá theo chỉ số chặng
    const legKeys = Object.keys(rawUrls).map(Number).sort((a, b) => a - b);
    const paxKey = (p) => p.group + ':' + p.index;
    const paxByKey = {};
    passengers.forEach((p) => {
        paxByKey[paxKey(p)] = p;
    });

    const state = {
        leg: legKeys.length ? legKeys[0] : 0,
        maps: {}, // leg -> { status: idle|loading|ready|error, data }
        index: {}, // leg -> Map(flight_seat_id -> seat)
        selected: {}, // leg -> { paxKey: { id, number, fee } }
        active: {}, // leg -> paxKey | null (hành khách sẽ nhận ghế ở lần bấm tiếp theo)
        restored: {}, // leg -> đã khôi phục $oldSeats chưa
        warnings: [],
        submitting: false,
    };

    legKeys.forEach((leg) => {
        state.maps[leg] = { status: 'idle', data: null };
        state.index[leg] = new Map();
        state.selected[leg] = {};
        state.active[leg] = passengers.length ? paxKey(passengers[0]) : null;
        state.restored[leg] = false;
    });

    // ---------- Dữ liệu ----------

    function oldSeatId(leg, p) {
        const byLeg = oldSeats ? oldSeats[leg] : null;
        const byGroup = byLeg ? byLeg[p.group] : null;
        const value = byGroup ? byGroup[p.index] : null;
        const id = Number(value);

        return Number.isInteger(id) && id > 0 ? id : null;
    }

    function ownerOf(leg, seatId) {
        const entries = Object.entries(state.selected[leg] || {});
        const found = entries.find((entry) => entry[1].id === seatId);

        return found ? found[0] : null;
    }

    function firstWithoutSeat(leg) {
        const free = passengers.find((p) => !state.selected[leg][paxKey(p)]);

        return free ? paxKey(free) : null;
    }

    // Hành khách tiếp theo (vòng tròn) chưa có ghế ở chặng này, null nếu ai cũng đã có ghế
    function nextWithoutSeat(leg, currentKey) {
        const start = passengers.findIndex((p) => paxKey(p) === currentKey);

        for (let step = 1; step <= passengers.length; step++) {
            const p = passengers[(start + step) % passengers.length];

            if (!state.selected[leg][paxKey(p)]) {
                return paxKey(p);
            }
        }

        return null;
    }

    function assign(leg, p, seat) {
        state.selected[leg][paxKey(p)] = {
            id: seat.flight_seat_id,
            number: seat.seat_number,
            fee: Number(seat.fee) || 0,
        };
    }

    function legLabel(leg) {
        const tab = tabs.find((t) => Number(t.dataset.legTab) === leg);

        return tab ? tab.dataset.label : 'Chặng ' + (leg + 1);
    }

    function totalFee() {
        let total = 0;

        legKeys.forEach((leg) => {
            Object.values(state.selected[leg]).forEach((sel) => {
                total += sel.fee;
            });
        });

        return total;
    }

    // ---------- Tải sơ đồ ----------

    function warn(leg, seatNumber) {
        const text = seatNumber
            ? 'Ghế ' + seatNumber + ' ở chặng ' + (leg + 1) + ' vừa có người đặt, vui lòng chọn lại'
            : 'Một ghế bạn đã chọn ở chặng ' + (leg + 1) + ' không còn khả dụng, vui lòng chọn lại';

        if (!state.warnings.includes(text)) {
            state.warnings.push(text);
        }
    }

    // Khôi phục lựa chọn cũ (một lần) rồi bỏ mọi ghế đã chọn mà không còn selectable
    function reconcile(leg) {
        const idx = state.index[leg];

        if (!state.restored[leg]) {
            state.restored[leg] = true;

            passengers.forEach((p) => {
                const id = oldSeatId(leg, p);

                if (!id) {
                    return;
                }

                const seat = idx.get(id);

                if (seat && seat.selectable && !ownerOf(leg, id)) {
                    assign(leg, p, seat);
                } else {
                    warn(leg, seat ? seat.seat_number : null);
                }
            });
        }

        const lost = [];

        Object.entries(state.selected[leg]).forEach(([key, sel]) => {
            const seat = idx.get(sel.id);

            if (!seat || !seat.selectable) {
                delete state.selected[leg][key];
                lost.push(key);
                warn(leg, sel.number);
            }
        });

        // Người vừa mất ghế trở thành "đang chọn" để lần bấm tiếp theo chọn lại cho đúng người đó
        const current = state.active[leg];

        if (lost.length) {
            state.active[leg] = lost[0];
        } else {
            state.active[leg] = current && !state.selected[leg][current] ? current : firstWithoutSeat(leg);
        }
    }

    function loadLeg(leg, force) {
        const entry = state.maps[leg];

        if (!entry || entry.status === 'loading' || (entry.status === 'ready' && !force)) {
            return;
        }

        entry.status = 'loading';
        refresh(leg);

        fetch(rawUrls[leg], {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then((res) => {
                // 404, 419, hoặc 302 về trang chủ (fetch đi theo redirect rồi trả 200 HTML) đều là lỗi
                if (res.status !== 200 || res.redirected) {
                    throw new Error('http');
                }

                return res.json();
            })
            .then((data) => {
                if (!data || !Array.isArray(data.rows)) {
                    throw new Error('shape');
                }

                const idx = new Map();
                data.rows.forEach((row) => (row.seats || []).forEach((seat) => idx.set(seat.flight_seat_id, seat)));

                entry.data = data;
                entry.status = 'ready';
                state.index[leg] = idx;
                reconcile(leg);
            })
            .catch(() => {
                entry.status = 'error';
            })
            .finally(() => {
                refresh(leg);
            });
    }

    // ---------- Hành động ----------

    function setNotice(text) {
        noticeBox.textContent = text || '';
        noticeBox.classList.toggle('is-visible', Boolean(text));
    }

    function setLeg(leg) {
        state.leg = leg;
        setNotice('');
        loadLeg(leg, false);
        renderAll();
    }

    // Vẽ lại lưới chỉ khi sự kiện thuộc chặng đang xem; chặng khác (tải nền) chỉ cập nhật phần trạng thái
    function refresh(leg) {
        if (leg === state.leg) {
            renderAll();
        } else {
            renderState();
        }
    }

    function onSeatClick(seatId) {
        const leg = state.leg;
        const seat = state.index[leg].get(seatId);

        if (!seat || !seat.selectable) {
            return;
        }

        const owner = ownerOf(leg, seatId);

        if (owner) {
            // Bấm ghế đã chọn: bỏ chọn, hành khách đó thành "đang chọn"
            delete state.selected[leg][owner];
            state.active[leg] = owner;
            setNotice('');
        } else {
            const target = state.active[leg];

            if (!target) {
                setNotice(MSG_FULL);
                renderState();

                return;
            }

            assign(leg, paxByKey[target], seat);
            state.active[leg] = nextWithoutSeat(leg, target);
            setNotice('');
        }

        renderState();
    }

    function onChipClick(key) {
        state.active[state.leg] = key;
        setNotice('');
        renderState();
    }

    function clearAll() {
        legKeys.forEach((leg) => {
            state.selected[leg] = {};
            state.active[leg] = passengers.length ? paxKey(passengers[0]) : null;
        });
        setNotice('');
    }

    function buildInputs() {
        inputsBox.innerHTML = '';

        legKeys.forEach((leg) => {
            passengers.forEach((p) => {
                const sel = state.selected[leg][paxKey(p)];

                if (!sel) {
                    return; // không gửi field cho hành khách không chọn ghế
                }

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'seats[' + leg + '][' + p.group + '][' + p.index + ']';
                input.value = String(sel.id);
                inputsBox.appendChild(input);
            });
        });
    }

    function lockForm() {
        state.submitting = true;
        confirmBtn.disabled = true;
        autoBtn.disabled = true;
        confirmBtn.textContent = 'Đang xử lý...';
    }

    function unlockForm() {
        state.submitting = false;
        confirmBtn.disabled = false;
        autoBtn.disabled = false;
        confirmBtn.textContent = 'Xác nhận đặt vé';
    }

    // ---------- Vẽ giao diện ----------

    function el(tag, className, text) {
        const node = document.createElement(tag);

        if (className) {
            node.className = className;
        }
        if (text !== undefined) {
            node.textContent = text;
        }

        return node;
    }

    function seatLabel(seat, ownerName) {
        const parts = ['Ghế ' + seat.seat_number];

        if (POSITION_LABELS[seat.position]) {
            parts.push(POSITION_LABELS[seat.position]);
        }

        if (!seat.selectable) {
            parts.push(seat.status === 'held' ? 'đang được giữ' : 'đã đặt');
        } else {
            parts.push('phí ' + fmtTight(Number(seat.fee) || 0));
            if (ownerName) {
                parts.push('đã chọn cho ' + ownerName);
            }
        }

        return parts.join(', ');
    }

    function renderTabs() {
        tabs.forEach((tab) => {
            const leg = Number(tab.dataset.legTab);
            const isActive = leg === state.leg;
            const count = Object.keys(state.selected[leg] || {}).length;
            const badge = tab.querySelector('[data-leg-count]');

            tab.classList.toggle('is-active', isActive);
            tab.setAttribute('aria-selected', isActive ? 'true' : 'false');

            if (badge) {
                badge.textContent = count ? count + ' ghế' : 'Tự động';
                badge.classList.toggle('has-seats', count > 0);
            }
        });

        legInfos.forEach((info) => {
            info.hidden = Number(info.dataset.legInfo) !== state.leg;
        });
    }

    // Chip hành khách: tạo một lần (cùng danh sách cho mọi chặng), sau đó chỉ cập nhật trạng thái tại chỗ
    function buildChips() {
        chipsBox.innerHTML = '';

        passengers.forEach((p, i) => {
            const chip = el('button', 'bf-seat-chip');
            chip.type = 'button';
            chip.dataset.pax = paxKey(p);

            chip.appendChild(el('span', 'bf-seat-chip__no', String(i + 1)));

            const body = el('span', 'bf-seat-chip__body');
            body.appendChild(el('span', 'bf-seat-chip__name', p.name));
            body.appendChild(el('span', 'bf-seat-chip__type', p.type_label));
            chip.appendChild(body);

            chip.appendChild(el('span', 'bf-seat-chip__seat'));
            chipsBox.appendChild(chip);
        });
    }

    function updateChips() {
        const leg = state.leg;

        chipsBox.querySelectorAll('.bf-seat-chip').forEach((chip) => {
            const key = chip.dataset.pax;
            const sel = state.selected[leg][key];
            const isActive = state.active[leg] === key;
            const seatEl = chip.querySelector('.bf-seat-chip__seat');

            chip.classList.toggle('is-active', isActive);
            chip.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            seatEl.textContent = sel ? sel.number + (sel.fee ? ' · ' + fmtSpaced(sel.fee) : '') : 'Tự động';
            seatEl.classList.toggle('has-seat', Boolean(sel));
        });
    }

    function renderWarnings() {
        warningsBox.innerHTML = '';

        state.warnings.forEach((text, i) => {
            const alert = el('div', 'alert alert-warning d-flex align-items-center gap-2 py-2 small mb-2');
            alert.appendChild(el('i', 'bi bi-exclamation-triangle-fill flex-shrink-0'));
            alert.appendChild(el('div', 'flex-grow-1', text));

            const close = el('button', 'btn-close btn-sm');
            close.type = 'button';
            close.setAttribute('aria-label', 'Đóng cảnh báo');
            close.dataset.warning = String(i);
            alert.appendChild(close);

            warningsBox.appendChild(alert);
        });
    }

    function buildSeatButton(seat) {
        const btn = el('button', 'bf-seat');
        btn.type = 'button';
        btn.dataset.seatId = String(seat.flight_seat_id);
        btn.dataset.seatClass = seat.seat_class || '';
        btn.appendChild(el('span', 'bf-seat__no', seat.seat_number));
        btn.appendChild(el('span', 'bf-seat__tag'));

        if (!seat.selectable) {
            btn.disabled = true;
            btn.classList.add(seat.status === 'held' ? 'bf-seat--held' : 'bf-seat--booked');
            btn.title = seat.status === 'held' ? 'Đang được giữ' : 'Đã đặt';
        } else {
            btn.title = 'Phí chọn ghế ' + fmtSpaced(Number(seat.fee) || 0);
        }

        return btn;
    }

    function renderGrid() {
        const entry = state.maps[state.leg];
        gridBox.innerHTML = '';
        gridBox.removeAttribute('aria-busy');
        feeNote.textContent = '';

        if (!entry || entry.status === 'idle' || entry.status === 'loading') {
            gridBox.setAttribute('aria-busy', 'true');
            gridBox.appendChild(el('p', 'bf-seat-state', 'Đang tải sơ đồ ghế...'));

            return;
        }

        if (entry.status === 'error') {
            const box = el('div', 'bf-seat-state bf-seat-state--error');
            box.appendChild(el('p', 'mb-2', MSG_LOAD_ERROR));

            const retry = el('button', 'btn btn-sm btn-primary me-2', 'Thử lại');
            retry.type = 'button';
            retry.dataset.retry = String(state.leg);
            box.appendChild(retry);

            const home = el('a', 'btn btn-sm btn-outline-secondary', 'Về trang chủ');
            home.href = homeUrl;
            box.appendChild(home);

            gridBox.appendChild(box);

            return;
        }

        const rows = entry.data.rows;

        if (!rows.length) {
            gridBox.appendChild(el('p', 'bf-seat-state', 'Chặng này hiện không còn ghế để chọn. Hệ thống sẽ xếp ghế tự động.'));

            return;
        }

        // Tiêu đề cột lấy từ hàng nhiều ghế nhất; khoảng lối đi chỉ dựa vào aisle_after
        const widest = rows.reduce((a, b) => ((b.seats || []).length > (a.seats || []).length ? b : a), rows[0]);
        const head = el('div', 'bf-seat-row bf-seat-row--head');
        head.setAttribute('aria-hidden', 'true');
        head.appendChild(el('span', 'bf-seat-row__label'));
        (widest.seats || []).forEach((seat) => {
            head.appendChild(el('span', 'bf-seat-colhead', seat.col));
            if (seat.aisle_after) {
                head.appendChild(el('span', 'bf-seat-aisle'));
            }
        });
        gridBox.appendChild(head);

        rows.forEach((row) => {
            const rowEl = el('div', 'bf-seat-row');
            rowEl.setAttribute('role', 'group');
            rowEl.setAttribute('aria-label', 'Hàng ' + row.row);
            rowEl.appendChild(el('span', 'bf-seat-row__label', String(row.row)));

            (row.seats || []).forEach((seat) => {
                rowEl.appendChild(buildSeatButton(seat));
                if (seat.aisle_after) {
                    const aisle = el('span', 'bf-seat-aisle');
                    aisle.setAttribute('aria-hidden', 'true');
                    rowEl.appendChild(aisle);
                }
            });

            gridBox.appendChild(rowEl);
        });

        // Ghi chú phí: chỉ lấy từ trường fee của các ghế còn chọn được, không viết cứng số tiền
        const fees = [];
        rows.forEach((row) => (row.seats || []).forEach((seat) => seat.selectable && fees.push(Number(seat.fee) || 0)));

        if (fees.length) {
            const min = Math.min(...fees);
            const max = Math.max(...fees);
            feeNote.textContent = min === max
                ? 'Phí chọn mỗi ghế ở chặng này: ' + fmtSpaced(min)
                : 'Phí chọn ghế ở chặng này từ ' + fmtSpaced(min) + ' đến ' + fmtSpaced(max);
        }
    }

    // Cập nhật trạng thái các nút ghế tại chỗ (không dựng lại lưới, giữ focus bàn phím)
    function updateSeatStates() {
        const leg = state.leg;
        const idx = state.index[leg];

        gridBox.querySelectorAll('.bf-seat').forEach((btn) => {
            const seat = idx.get(Number(btn.dataset.seatId));

            if (!seat) {
                return;
            }

            const owner = ownerOf(leg, seat.flight_seat_id);
            const ownerPax = owner ? paxByKey[owner] : null;
            const tag = btn.querySelector('.bf-seat__tag');

            btn.classList.toggle('is-selected', Boolean(owner));
            btn.setAttribute('aria-pressed', owner ? 'true' : 'false');
            btn.setAttribute('aria-label', seatLabel(seat, ownerPax ? ownerPax.name : null));

            if (tag) {
                tag.textContent = owner ? String(passengers.indexOf(ownerPax) + 1) : '';
            }
        });
    }

    function renderSummary() {
        const total = totalFee();
        feeTotal.textContent = fmtSpaced(total);

        summaryBox.innerHTML = '';

        legKeys.forEach((leg) => {
            const block = el('div', 'bf-seat-summary__leg');
            block.appendChild(el('div', 'bf-seat-summary__leg-title', legLabel(leg)));

            const list = el('ul', 'bf-seat-summary__list');

            passengers.forEach((p) => {
                const sel = state.selected[leg][paxKey(p)];
                const item = el('li');
                item.appendChild(el('span', 'bf-seat-summary__name', p.name));
                item.appendChild(
                    el('span', 'bf-seat-summary__seat' + (sel ? ' has-seat' : ''), sel
                        ? sel.number + (sel.fee ? ' (' + fmtSpaced(sel.fee) + ')' : '')
                        : 'Tự động')
                );
                list.appendChild(item);
            });

            block.appendChild(list);
            summaryBox.appendChild(block);
        });

        const foot = el('div', 'bf-seat-summary__total');
        foot.appendChild(el('span', '', 'Phí chọn ghế tạm tính'));
        foot.appendChild(el('strong', '', fmtSpaced(total)));
        summaryBox.appendChild(foot);
        summaryBox.appendChild(el('div', 'small text-muted mt-1', 'Ghế xếp tự động: miễn phí. Phí chính thức do hệ thống tính lại khi xác nhận.'));
    }

    // Cập nhật phần phụ thuộc lựa chọn (không dựng lại lưới ghế, giữ focus bàn phím)
    function renderState() {
        renderTabs();
        updateChips();
        renderWarnings();
        updateSeatStates();
        renderSummary();
    }

    // Vẽ lại cả lưới của chặng đang xem rồi cập nhật trạng thái
    function renderAll() {
        renderGrid();
        renderState();
    }

    // ---------- Sự kiện ----------

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => setLeg(Number(tab.dataset.legTab)));
    });

    gridBox.addEventListener('click', (event) => {
        const retry = event.target.closest('[data-retry]');

        if (retry) {
            loadLeg(Number(retry.dataset.retry), true);

            return;
        }

        const seatBtn = event.target.closest('.bf-seat');

        if (seatBtn && !seatBtn.disabled) {
            onSeatClick(Number(seatBtn.dataset.seatId));
        }
    });

    chipsBox.addEventListener('click', (event) => {
        const chip = event.target.closest('.bf-seat-chip');

        if (chip) {
            onChipClick(chip.dataset.pax);
        }
    });

    warningsBox.addEventListener('click', (event) => {
        const close = event.target.closest('[data-warning]');

        if (close) {
            state.warnings.splice(Number(close.dataset.warning), 1);
            renderWarnings();
        }
    });

    if (reloadBtn) {
        reloadBtn.addEventListener('click', () => loadLeg(state.leg, true));
    }

    form.addEventListener('submit', (event) => {
        if (state.submitting) {
            event.preventDefault(); // chống bấm hai lần

            return;
        }

        buildInputs();
        lockForm();
    });

    autoBtn.addEventListener('click', () => {
        if (state.submitting) {
            return;
        }

        // Xếp ghế tự động: bỏ mọi lựa chọn rồi gửi form (không có field seats)
        clearAll();
        renderState();

        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else if (form.dispatchEvent(new Event('submit', { cancelable: true }))) {
            form.submit();
        }
    });

    // Quay lại bằng nút Back của trình duyệt (bfcache): mở khoá lại nút
    window.addEventListener('pageshow', (event) => {
        if (event.persisted && state.submitting) {
            unlockForm();
        }
    });

    // ---------- Khởi động ----------

    buildChips();
    renderAll();

    if (legKeys.length) {
        loadLeg(state.leg, false);

        // Chặng đã có lựa chọn cũ (form bị trả lại) tải sớm để khôi phục và kiểm tra ghế còn trống
        legKeys.forEach((leg) => {
            if (leg !== state.leg && passengers.some((p) => oldSeatId(leg, p))) {
                loadLeg(leg, false);
            }
        });
    }
})();
