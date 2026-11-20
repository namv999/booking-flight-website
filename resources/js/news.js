document.addEventListener("DOMContentLoaded", function () {
    const section = document.querySelector("[data-news-section]");
    const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");

    if (section) {
        const carousel = section.querySelector(".bf-news-carousel");
        const track = section.querySelector(".bf-news-track");
        const tabs = Array.from(section.querySelectorAll("[data-news-tab]"));
        const previous = section.querySelector(".bf-news-prev");
        const next = section.querySelector(".bf-news-next");
        const dots = section.querySelector(".bf-news-dots");
        let slides = Array.from(track.querySelectorAll(".bf-news-slide"));
        let visibleSlides = slides;
        let activeIndex = 0;
        let activeCategory = "all";
        let transitionId = 0;
        const pauseReasons = new Set();

        function setPause(reason, paused) {
            if (paused) pauseReasons.add(reason);
            else pauseReasons.delete(reason);
            carousel.classList.toggle("is-paused", pauseReasons.size > 0 || reducedMotion.matches);
        }

        function getSlideStep() {
            if (!visibleSlides.length) return 0;
            const first = visibleSlides[0];
            const second = visibleSlides[1];
            return second ? second.offsetLeft - first.offsetLeft : first.getBoundingClientRect().width;
        }

        function maxIndex() {
            const step = getSlideStep();
            if (!step) return 0;
            return Math.max(0, Math.round((track.scrollWidth - track.clientWidth) / step));
        }

        function nearestIndex() {
            const step = getSlideStep();
            if (!step) return 0;
            return Math.max(0, Math.min(maxIndex(), Math.round(track.scrollLeft / step)));
        }

        function updateControls() {
            activeIndex = nearestIndex();
            previous.disabled = activeIndex <= 0;
            next.disabled = activeIndex >= maxIndex();
            Array.from(dots.children).forEach((dot, index) => {
                const active = index === activeIndex;
                dot.classList.toggle("is-active", active);
                dot.setAttribute("aria-current", active ? "true" : "false");
            });
        }

        function buildDots() {
            dots.replaceChildren();
            const count = maxIndex() + 1;
            for (let index = 0; index < count; index += 1) {
                const button = document.createElement("button");
                button.type = "button";
                button.className = "bf-news-dot";
                button.setAttribute("aria-label", `Đi tới nhóm bài viết ${index + 1}`);
                button.innerHTML = '<span class="bf-news-dot__progress" aria-hidden="true"></span>';
                button.addEventListener("click", function () {
                    const step = getSlideStep();
                    track.scrollTo({ left: step * index, behavior: reducedMotion.matches ? "auto" : "smooth" });
                });
                dots.appendChild(button);
            }
            updateControls();
        }

        function scrollBySlide(direction) {
            const targetIndex = activeIndex + direction;
            const step = getSlideStep();
            if (!step || targetIndex < 0 || targetIndex > maxIndex()) return;
            track.scrollBy({ left: direction * step, behavior: reducedMotion.matches ? "auto" : "smooth" });
        }

        function applyCategory(category) {
            if (category === activeCategory) return;
            activeCategory = category;
            transitionId += 1;
            const currentTransition = transitionId;
            tabs.forEach(tab => {
                const selected = tab.dataset.newsTab === category;
                tab.classList.toggle("is-active", selected);
                tab.setAttribute("aria-selected", selected ? "true" : "false");
            });

            slides.forEach(slide => {
                slide.classList.add("is-leaving");
                slide.classList.remove("is-entering", "is-visible");
            });

            const finishFiltering = function () {
                if (currentTransition !== transitionId) return;
                slides.forEach(slide => {
                    const matches = category === "all" || slide.dataset.category === category;
                    slide.hidden = !matches;
                    slide.classList.remove("is-leaving");
                    if (matches) {
                        slide.classList.add("is-entering");
                    }
                });
                visibleSlides = slides.filter(slide => !slide.hidden);
                track.scrollTo({ left: 0, behavior: reducedMotion.matches ? "auto" : "smooth" });
                buildDots();
                requestAnimationFrame(() => {
                    visibleSlides.forEach((slide, index) => {
                        slide.style.setProperty("--news-delay", reducedMotion.matches ? "0ms" : `${index * 60}ms`);
                        slide.classList.add("is-visible");
                    });
                    window.setTimeout(() => {
                        visibleSlides.forEach(slide => slide.classList.remove("is-entering", "is-visible"));
                    }, reducedMotion.matches ? 0 : 500);
                });
            };

            if (reducedMotion.matches) finishFiltering();
            else window.setTimeout(finishFiltering, 200);
        }

        tabs.forEach(tab => tab.addEventListener("click", () => applyCategory(tab.dataset.newsTab)));
        previous.addEventListener("click", () => scrollBySlide(-1));
        next.addEventListener("click", () => scrollBySlide(1));
        track.addEventListener("scroll", () => window.requestAnimationFrame(updateControls), { passive: true });
        carousel.addEventListener("keydown", event => {
            if (event.key === "ArrowLeft") {
                event.preventDefault();
                scrollBySlide(-1);
            } else if (event.key === "ArrowRight") {
                event.preventDefault();
                scrollBySlide(1);
            }
        });
        carousel.addEventListener("pointerenter", () => setPause("pointer", true));
        carousel.addEventListener("pointerleave", () => setPause("pointer", false));
        carousel.addEventListener("focusin", () => setPause("focus", true));
        carousel.addEventListener("focusout", () => window.setTimeout(() => {
            setPause("focus", carousel.contains(document.activeElement));
        }, 0));
        carousel.addEventListener("touchstart", () => setPause("touch", true), { passive: true });
        carousel.addEventListener("touchend", () => window.setTimeout(() => setPause("touch", false), 1200), { passive: true });
        carousel.addEventListener("touchcancel", () => setPause("touch", false), { passive: true });
        document.addEventListener("visibilitychange", () => setPause("visibility", document.hidden));

        const autoplay = window.setInterval(() => {
            if (pauseReasons.size > 0 || reducedMotion.matches || !visibleSlides.length) return;
            if (activeIndex >= maxIndex()) {
                track.scrollTo({ left: 0, behavior: "smooth" });
            } else {
                scrollBySlide(1);
            }
        }, 6000);
        reducedMotion.addEventListener?.("change", () => {
            carousel.classList.toggle("is-paused", pauseReasons.size > 0 || reducedMotion.matches);
            buildDots();
        });

        buildDots();
        setPause("visibility", document.hidden);
        if ("IntersectionObserver" in window) {
            const observer = new IntersectionObserver(entries => {
                if (entries.some(entry => entry.isIntersecting)) {
                    section.classList.add("is-revealed");
                    observer.disconnect();
                }
            }, { threshold: 0.16 });
            observer.observe(section);
        } else {
            section.classList.add("is-revealed");
        }

        window.addEventListener("resize", () => {
            buildDots();
        });
        window.addEventListener("pagehide", () => window.clearInterval(autoplay), { once: true });
    }

    const excerpt = document.getElementById("excerpt");
    const excerptCount = document.querySelector("[data-excerpt-count]");
    if (excerpt && excerptCount) {
        excerpt.addEventListener("input", () => {
            excerptCount.textContent = String(excerpt.value.length);
        });
    }

    const thumbnailInput = document.querySelector("[data-thumbnail-input]");
    const thumbnailPreview = document.querySelector("[data-thumbnail-preview]");
    const removeThumbnail = document.getElementById("remove_thumbnail");
    let previewUrl = null;
    if (thumbnailInput && thumbnailPreview) {
        thumbnailInput.addEventListener("change", function () {
            if (previewUrl) URL.revokeObjectURL(previewUrl);
            const file = this.files?.[0];
            if (!file) return;
            previewUrl = URL.createObjectURL(file);
            thumbnailPreview.src = previewUrl;
            thumbnailPreview.classList.remove("d-none");
            if (removeThumbnail) {
                removeThumbnail.checked = false;
                thumbnailPreview.style.opacity = "1";
            }
        });
        window.addEventListener("pagehide", () => {
            if (previewUrl) URL.revokeObjectURL(previewUrl);
        }, { once: true });
    }

    if (removeThumbnail && thumbnailPreview) {
        removeThumbnail.addEventListener("change", function () {
            thumbnailPreview.style.opacity = this.checked ? "0.35" : "1";
        });
    }
});
