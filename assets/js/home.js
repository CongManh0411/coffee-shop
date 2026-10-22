/**
 * Góc Nhỏ Coffee & Tea — Home Interactive Experience
 * Enhanced booking form, menu filtering, mobile navigation, and micro-interactions
 */
(() => {
    'use strict';

    // ----------------------------------------------------
    // 1. Sticky Header & Back to Top Button
    // ----------------------------------------------------
    const header = document.querySelector('.site-header');
    const backToTopBtn = document.getElementById('backToTopBtn');

    window.addEventListener('scroll', () => {
        const scrollY = window.pageYOffset || document.documentElement.scrollTop;
        if (header) {
            if (scrollY > 30) {
                header.classList.add('is-scrolled');
            } else {
                header.classList.remove('is-scrolled');
            }
        }
        if (backToTopBtn) {
            if (scrollY > 400) {
                backToTopBtn.classList.add('is-visible');
            } else {
                backToTopBtn.classList.remove('is-visible');
            }
        }
    }, { passive: true });

    if (backToTopBtn) {
        backToTopBtn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // ----------------------------------------------------
    // 2. Mobile Drawer Navigation
    // ----------------------------------------------------
    const mobileToggle = document.getElementById('mobileNavToggle');
    const mobileDrawer = document.getElementById('mobileDrawer');
    const mobileOverlay = document.getElementById('mobileDrawerOverlay');
    const mobileClose = document.getElementById('mobileDrawerClose');

    function openMobileMenu() {
        if (!mobileDrawer) return;
        mobileDrawer.classList.add('is-open');
        if (mobileOverlay) mobileOverlay.classList.add('is-open');
        if (mobileToggle) mobileToggle.classList.add('is-active');
        document.body.style.overflow = 'hidden';
    }

    function closeMobileMenu() {
        if (!mobileDrawer) return;
        mobileDrawer.classList.remove('is-open');
        if (mobileOverlay) mobileOverlay.classList.remove('is-open');
        if (mobileToggle) mobileToggle.classList.remove('is-active');
        document.body.style.overflow = '';
    }

    if (mobileToggle) {
        mobileToggle.addEventListener('click', () => {
            if (mobileDrawer && mobileDrawer.classList.contains('is-open')) {
                closeMobileMenu();
            } else {
                openMobileMenu();
            }
        });
    }

    if (mobileOverlay) mobileOverlay.addEventListener('click', closeMobileMenu);
    if (mobileClose) mobileClose.addEventListener('click', closeMobileMenu);

    // Close mobile menu when clicking any link inside
    if (mobileDrawer) {
        mobileDrawer.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', closeMobileMenu);
        });
    }

    // ----------------------------------------------------
    // 3. Smooth Anchor Scrolling
    // ----------------------------------------------------
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const targetId = this.getAttribute('href');
            if (!targetId || targetId === '#') return;
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                e.preventDefault();
                const headerOffset = 84;
                const elementPosition = targetElement.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });

                // Update active link
                document.querySelectorAll('.site-nav a').forEach(a => a.classList.remove('active'));
                this.classList.add('active');
            }
        });
    });

    // ----------------------------------------------------
    // 4. Menu Category Filter Tabs
    // ----------------------------------------------------
    const filterTabs = document.querySelectorAll('.filter-tab');
    const menuCards = document.querySelectorAll('.menu-card');
    let activeMenuCategory = 'all';

    function updateMenuCards() {
        const matchingCards = Array.from(menuCards).filter(card =>
            activeMenuCategory === 'all' || card.dataset.category === activeMenuCategory
        );
        const visibleCount = matchingCards.length;
        matchingCards.forEach((card, index) => { card.hidden = index >= visibleCount; });
        menuCards.forEach(card => {
            if (!matchingCards.includes(card)) card.hidden = true;
        });
    }

    filterTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            filterTabs.forEach(t => t.classList.remove('is-active'));
            tab.classList.add('is-active');

            activeMenuCategory = tab.getAttribute('data-filter') || 'all';
            menuVisibleLimit = menuInitialCount;
            updateMenuCards();
        });
    });

    updateMenuCards();

    // ----------------------------------------------------
    // 5. Reservation Form & Party Size Pills
    // ----------------------------------------------------
    const form = document.getElementById('reservationForm');
    if (!form) return;

    const dateInput = document.getElementById('reservedAt');
    const message = document.getElementById('reservationMessage');
    const submit = form.querySelector('[type="submit"]');
    const partySelect = form.querySelector('select[name="partySize"]');
    const partyPills = document.querySelectorAll('.party-pill');

    // Calculate minimum allowed reservation time (current time + 30m, rounded to 15m)
    const minDate = new Date(Date.now() + 30 * 60 * 1000);
    minDate.setMinutes(Math.ceil(minDate.getMinutes() / 15) * 15, 0, 0);
    const localDate = new Date(minDate.getTime() - minDate.getTimezoneOffset() * 60000);
    const minDateISO = localDate.toISOString().slice(0, 16);
    dateInput.min = minDateISO;

    // Set default reservation time to 1 hour from now for great UX
    const defaultDate = new Date(Date.now() + 60 * 60 * 1000);
    defaultDate.setMinutes(Math.ceil(defaultDate.getMinutes() / 15) * 15, 0, 0);
    const localDefault = new Date(defaultDate.getTime() - defaultDate.getTimezoneOffset() * 60000);
    if (!dateInput.value) {
        dateInput.value = localDefault.toISOString().slice(0, 16);
    }

    // Party size pills sync
    partyPills.forEach(pill => {
        pill.addEventListener('click', () => {
            const size = pill.getAttribute('data-size');
            if (partySelect && size) {
                partySelect.value = size;
                partyPills.forEach(p => p.classList.remove('is-active'));
                pill.classList.add('is-active');
            }
        });
    });

    if (partySelect) {
        partySelect.addEventListener('change', () => {
            const val = partySelect.value;
            partyPills.forEach(p => {
                if (p.getAttribute('data-size') === val) {
                    p.classList.add('is-active');
                } else {
                    p.classList.remove('is-active');
                }
            });
        });
    }

    // Handle form submit
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        message.hidden = true;
        submit.disabled = true;
        const originalText = submit.innerHTML;
        submit.innerHTML = '<span>⏳ Đang gửi yêu cầu đặt bàn…</span>';

        const data = Object.fromEntries(new FormData(form).entries());
        data.partySize = Number(data.partySize);

        try {
            await Api.post('reservations/public', data);
            message.textContent = '🎉 Quán đã nhận yêu cầu đặt bàn thành công! Nhân viên Góc Nhỏ sẽ sớm liên hệ qua số điện thoại để xác nhận giữ bàn cho bạn.';
            message.className = 'reservation-message success';
            form.reset();
            dateInput.min = minDateISO;
            dateInput.value = localDefault.toISOString().slice(0, 16);
            if (partySelect) partySelect.value = '2';
            partyPills.forEach(p => {
                p.classList.toggle('is-active', p.getAttribute('data-size') === '2');
            });
        } catch (error) {
            message.textContent = '⚠️ ' + (error.message || 'Chưa gửi được yêu cầu. Vui lòng kiểm tra lại thông tin hoặc gọi hotline quán.');
            message.className = 'reservation-message error';
        } finally {
            message.hidden = false;
            submit.disabled = false;
            submit.innerHTML = originalText;
            message.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    });
})();
