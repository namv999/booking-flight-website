const bfNotificationTypes = {
    passport_submitted: { icon: "bi-person-vcard", color: "bf-notif-icon--warning" },
    passport_approved: { icon: "bi-check-circle-fill", color: "bf-notif-icon--success" },
    passport_rejected: { icon: "bi-x-circle-fill", color: "bf-notif-icon--danger" },
    booking_paid: { icon: "bi-ticket-perforated", color: "bf-notif-icon--primary" },
    order_paid: { icon: "bi-receipt", color: "bf-notif-icon--primary" },
    promotion: { icon: "bi-megaphone-fill", color: "bf-notif-icon--coral" },
    announcement: { icon: "bi-info-circle-fill", color: "bf-notif-icon--info" },
};
const bfNotificationFallback = { icon: "bi-bell", color: "bf-notif-icon--muted" };

document.addEventListener("DOMContentLoaded", function () {
    const root = document.getElementById("bf-notif");
    if (root) initializeNotificationBell(root);

    const broadcastPage = document.getElementById("bf-broadcast-form-page");
    if (broadcastPage) initializeBroadcastPreview(broadcastPage);
});

function initializeNotificationBell(root) {
    const feedUrl = root.dataset.feedUrl;
    const readAllUrl = root.dataset.readAllUrl;
    const pollMs = Number.parseInt(root.dataset.pollMs || "30000", 10);
    const toggle = root.querySelector("#bf-notif-toggle");
    const badge = root.querySelector(".bf-notif-badge");
    const list = root.querySelector("#bf-notif-list");
    const menu = root.querySelector(".bf-notif-menu");
    const readAllForm = root.querySelector("#bf-notif-read-all-form");
    const readAllButton = root.querySelector("#bf-notif-read-all");
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    function parseInitialItems() {
        if (!list) return [];
        const forms = list.querySelectorAll(".bf-notif-item-form");
        if (!forms.length) return [];
        const parsed = [];
        forms.forEach((form) => {
            const btn = form.querySelector(".bf-notif-item");
            const readUrl = form.dataset.readUrl || form.action;
            const titleEl = form.querySelector(".bf-notif-item__title");
            const titleText = titleEl ? titleEl.childNodes[0]?.textContent?.trim() : "";
            const msgEl = form.querySelector(".bf-notif-item__message");
            const timeEl = form.querySelector(".bf-notif-item__time");
            const iconEl = form.querySelector(".bf-notif-item__icon i");
            let itemType = "announcement";
            if (iconEl) {
                for (const [k, v] of Object.entries(bfNotificationTypes)) {
                    if (iconEl.classList.contains(v.icon)) {
                        itemType = k;
                        break;
                    }
                }
            }
            parsed.push({
                read_url: readUrl,
                type: itemType,
                title: titleText || "",
                message: msgEl ? msgEl.textContent : "",
                time: timeEl ? timeEl.textContent : "",
                read: btn ? btn.classList.contains("is-read") : false,
            });
        });
        return parsed;
    }

    let notifications = parseInitialItems();
    let unreadCount = Number.parseInt((badge?.textContent || "0").replace("+", ""), 10) || 0;
    let firstFeed = true;
    let renderQueued = false;
    let stopped = false;
    let intervalId = null;

    const headers = (json = false) => {
        const value = { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" };
        if (json) value["Content-Type"] = "application/json";
        if (csrfToken) value["X-CSRF-TOKEN"] = csrfToken;
        return value;
    };

    function setUnreadCount(nextCount, animate = true) {
        const next = Math.max(0, Number(nextCount) || 0);
        const previous = unreadCount;
        const changed = next !== previous;
        const increased = next > unreadCount;
        unreadCount = next;

        if (badge) {
            badge.textContent = next > 99 ? "99+" : String(next);
            badge.hidden = next === 0;
            badge.classList.remove("is-changing", "is-appearing");
            if (changed && animate && next > 0) {
                void badge.offsetWidth;
                badge.classList.add(previous === 0 ? "is-appearing" : "is-changing");
                if (increased && next > 0) {
                    toggle?.classList.remove("is-ringing");
                    void toggle?.offsetWidth;
                    toggle?.classList.add("is-ringing");
                }
            }
        }

        if (readAllForm) readAllForm.hidden = next === 0;
    }

    function iconFor(type) {
        return bfNotificationTypes[type] || bfNotificationFallback;
    }

    function makeIcon(type) {
        const meta = iconFor(type);
        const wrap = document.createElement("span");
        wrap.className = `bf-notif-item__icon ${meta.color}`;
        const icon = document.createElement("i");
        icon.className = `bi ${meta.icon}`;
        icon.setAttribute("aria-hidden", "true");
        wrap.append(icon);
        return wrap;
    }

    function renderItems(items, animate = false) {
        if (!list) return;
        const fragment = document.createDocumentFragment();

        if (!items.length) {
            const empty = document.createElement("div");
            empty.className = "bf-notif-empty";
            const icon = document.createElement("i");
            icon.className = "bi bi-bell-slash";
            icon.setAttribute("aria-hidden", "true");
            const text = document.createElement("span");
            text.textContent = "Chưa có thông báo nào";
            empty.append(icon, text);
            fragment.append(empty);
        } else {
            items.forEach((item, index) => {
                const form = document.createElement("form");
                form.method = "POST";
                form.action = item.read_url;
                form.className = "bf-notif-item-form";
                form.dataset.notifReadForm = "";
                form.dataset.readUrl = item.read_url;

                const token = document.createElement("input");
                token.type = "hidden";
                token.name = "_token";
                token.value = csrfToken || "";
                form.append(token);

                const button = document.createElement("button");
                button.type = "submit";
                button.className = `bf-notif-item ${item.read ? "is-read" : "is-unread"}`;
                button.append(makeIcon(item.type));

                const copy = document.createElement("span");
                copy.className = "bf-notif-item__copy";
                const title = document.createElement("span");
                title.className = "bf-notif-item__title";
                title.append(document.createTextNode(item.title || ""));
                const dot = document.createElement("span");
                dot.className = "bf-notif-unread-dot";
                if (!item.read) dot.setAttribute("aria-label", "Chưa đọc");
                title.append(dot);

                const message = document.createElement("span");
                message.className = "bf-notif-item__message";
                message.textContent = item.message || "";
                const time = document.createElement("span");
                time.className = "bf-notif-item__time";
                time.textContent = item.time || "";
                copy.append(title, message, time);
                button.append(copy);
                form.append(button);
                form.style.setProperty("--bf-notif-index", String(index));
                fragment.append(form);
            });
        }

        list.replaceChildren(fragment);
        list.classList.remove("is-animated");
        if (animate && items.length) {
            void list.offsetWidth;
            list.classList.add("is-animated");
        }
    }

    function isUserInteracting() {
        return Boolean(menu?.matches(":hover") || (menu && menu.contains(document.activeElement)));
    }

    function refreshVisibleList(force = false) {
        if (isUserInteracting() && !force) {
            renderQueued = true;
            return;
        }
        renderQueued = false;
        renderItems(notifications, force);
    }

    async function fetchFeed() {
        if (stopped || document.hidden || !feedUrl) return;
        try {
            const response = await fetch(feedUrl, {
                headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
                credentials: "same-origin",
            });
            if (response.status === 401 || response.status === 419) {
                stopped = true;
                if (intervalId) window.clearInterval(intervalId);
                return;
            }
            if (!response.ok) return;

            const data = await response.json();
            const count = Number(data.unread_count) || 0;
            const changed = count !== unreadCount;
            setUnreadCount(count, !firstFeed);
            notifications = Array.isArray(data.items) ? data.items : [];
            firstFeed = false;
            if (!menu?.classList.contains("show") || !isUserInteracting()) {
                refreshVisibleList(changed && !menu?.classList.contains("show"));
            }
        } catch {
            // A transient network failure leaves the current server-rendered list in place.
        }
    }

    async function submitJson(url, body) {
        const response = await fetch(url, {
            method: "POST",
            credentials: "same-origin",
            headers: headers(true),
            body: body ? JSON.stringify(body) : undefined,
        });
        if (!response.ok) throw new Error(`Notification request failed: ${response.status}`);
        return response.json();
    }

    root.addEventListener("submit", async (event) => {
        const form = event.target.closest("form");
        if (!form) return;

        if (form.matches("[data-notif-read-form]")) {
            event.preventDefault();
            try {
                const readUrl = form.dataset.readUrl;
                const item = notifications.find((notification) => notification.read_url === readUrl);
                const result = await submitJson(readUrl);
                if (typeof result.url !== "string") throw new Error("Notification redirect missing");
                window.location.href = result.url;
                if (item) item.read = true;
            } catch {
                HTMLFormElement.prototype.submit.call(form);
            }
            return;
        }

        if (form === readAllForm) {
            event.preventDefault();
            if (readAllButton) readAllButton.disabled = true;
            try {
                await submitJson(readAllUrl);
                notifications = notifications.map((item) => ({ ...item, read: true }));
                setUnreadCount(0);
                list?.querySelectorAll(".bf-notif-item.is-unread").forEach((el) => {
                    el.classList.remove("is-unread");
                    el.classList.add("is-read");
                });
            } catch {
                HTMLFormElement.prototype.submit.call(form);
            } finally {
                if (readAllButton) readAllButton.disabled = false;
            }
        }
    });

    toggle?.addEventListener("shown.bs.dropdown", () => {
        if (firstFeed && list) {
            list.classList.remove("is-animated");
            void list.offsetWidth;
            list.classList.add("is-animated");
        }
        refreshVisibleList(true);
    });
    menu?.addEventListener("mouseleave", () => {
        if (renderQueued) refreshVisibleList();
    });
    toggle?.querySelector(".bi-bell")?.addEventListener("animationend", () => toggle.classList.remove("is-ringing"));
    badge?.addEventListener("animationend", () => badge.classList.remove("is-changing", "is-appearing"));

    intervalId = window.setInterval(fetchFeed, Number.isFinite(pollMs) && pollMs > 0 ? pollMs : 30000);
    document.addEventListener("visibilitychange", () => {
        if (!document.hidden && !stopped) fetchFeed();
    });
    fetchFeed();
}

function initializeBroadcastPreview(page) {
    const type = page.querySelector("[data-bf-preview-type]");
    const title = page.querySelector("[data-bf-preview-title]");
    const message = page.querySelector("[data-bf-preview-message]");
    const titleText = page.querySelector("[data-bf-preview-title-text]");
    const messageText = page.querySelector("[data-bf-preview-message-text]");
    const iconWrap = page.querySelector("[data-bf-preview-icon-wrap]");
    const icon = page.querySelector("[data-bf-preview-icon]");
    const titleCount = page.querySelector("[data-bf-title-count]");
    const messageCount = page.querySelector("[data-bf-message-count]");
    const confirmation = page.querySelector("[data-bf-broadcast-confirm]");
    const submit = page.querySelector("[data-bf-broadcast-submit]");

    function updatePreview() {
        const kind = type?.value || "announcement";
        const typeStyle = bfNotificationTypes[kind] || bfNotificationFallback;
        if (titleText) titleText.textContent = title?.value.trim() || "Tiêu đề thông báo";
        if (messageText) messageText.textContent = message?.value.trim() || "Nội dung thông báo sẽ hiển thị tại đây.";
        if (titleCount) titleCount.textContent = String(title?.value.length || 0);
        if (messageCount) messageCount.textContent = String(message?.value.length || 0);
        if (icon) icon.className = `bi ${typeStyle.icon}`;
        if (iconWrap) {
            iconWrap.classList.remove(...Object.values(bfNotificationTypes).map((value) => value.color), bfNotificationFallback.color);
            iconWrap.classList.add(typeStyle.color);
        }
        if (submit) submit.disabled = !confirmation?.checked;
    }

    [type, title, message, confirmation].forEach((field) => field?.addEventListener("input", updatePreview));
    type?.addEventListener("change", updatePreview);
    confirmation?.addEventListener("change", updatePreview);
    updatePreview();
}
