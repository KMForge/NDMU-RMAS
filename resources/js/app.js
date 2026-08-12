import './bootstrap';
import '@phosphor-icons/web/regular';
import '@phosphor-icons/web/bold';

// Livewire 4 ships Alpine.js. Do not import Alpine separately.

function initializePasswordToggles(root = document) {
    root.querySelectorAll('[data-password-toggle]').forEach((button) => {
        if (button.dataset.passwordToggleReady === 'true') {
            return;
        }

        const inputId = button.dataset.passwordInput;
        const input = inputId ? document.getElementById(inputId) : null;

        if (!(input instanceof HTMLInputElement)) {
            return;
        }

        const showIcon = button.querySelector('[data-password-show-icon]');
        const hideIcon = button.querySelector('[data-password-hide-icon]');
        const confirmation = inputId === 'password_confirmation';

        button.dataset.passwordToggleReady = 'true';
        input.type = 'password';

        button.addEventListener('click', () => {
            const shouldShow = input.type === 'password';

            input.type = shouldShow ? 'text' : 'password';
            button.setAttribute('aria-pressed', String(shouldShow));
            button.setAttribute(
                'aria-label',
                shouldShow
                    ? `Hide password${confirmation ? ' confirmation' : ''}`
                    : `Show password${confirmation ? ' confirmation' : ''}`,
            );
            showIcon?.classList.toggle('hidden', shouldShow);
            hideIcon?.classList.toggle('hidden', !shouldShow);
        });
    });
}

function initializeWelcomePage() {
    const page = document.querySelector('[data-welcome-page]');

    if (!page || page.dataset.scrollEffectsReady === 'true') {
        return;
    }

    page.dataset.scrollEffectsReady = 'true';

    const header = page.querySelector('[data-site-header]');
    const progress = page.querySelector('[data-scroll-progress]');
    const navigationLinks = [...page.querySelectorAll('[data-section-link]')];
    const sections = navigationLinks
        .map((link) => document.getElementById(link.dataset.sectionLink))
        .filter(Boolean);

    let frameRequested = false;
    let activeSection = null;

    const setActiveSection = (sectionId) => {
        if (activeSection === sectionId) {
            return;
        }

        activeSection = sectionId;

        navigationLinks.forEach((link) => {
            const isActive = link.dataset.sectionLink === sectionId;

            link.classList.toggle('is-active', isActive);

            if (isActive) {
                link.setAttribute('aria-current', 'page');
            } else {
                link.removeAttribute('aria-current');
            }
        });
    };

    const updateScrollState = () => {
        frameRequested = false;

        const headerHeight = header?.offsetHeight ?? 0;
        const marker = window.scrollY + headerHeight + window.innerHeight * 0.28;
        const documentHeight = document.documentElement.scrollHeight - window.innerHeight;
        const progressValue = documentHeight > 0 ? Math.min(window.scrollY / documentHeight, 1) : 0;

        header?.classList.toggle('is-scrolled', window.scrollY > 18);

        if (progress) {
            progress.style.transform = `scaleX(${progressValue})`;
        }

        let currentSection = sections[0]?.id ?? 'home';

        sections.forEach((section) => {
            if (section.offsetTop <= marker) {
                currentSection = section.id;
            }
        });

        if (window.scrollY + window.innerHeight >= document.documentElement.scrollHeight - 4) {
            currentSection = sections.at(-1)?.id ?? currentSection;
        }

        setActiveSection(currentSection);
    };

    const requestScrollUpdate = () => {
        if (frameRequested) {
            return;
        }

        frameRequested = true;
        window.requestAnimationFrame(updateScrollState);
    };

    navigationLinks.forEach((link) => {
        link.addEventListener('click', (event) => {
            const target = document.getElementById(link.dataset.sectionLink);

            if (!target) {
                return;
            }

            event.preventDefault();

            const headerHeight = header?.offsetHeight ?? 0;
            const top = Math.max(target.getBoundingClientRect().top + window.scrollY - headerHeight, 0);
            const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            window.scrollTo({
                top,
                behavior: reduceMotion ? 'auto' : 'smooth',
            });

            window.history.replaceState(null, '', `#${target.id}`);
            setActiveSection(target.id);
        });
    });

    const revealElements = [...page.querySelectorAll('section:not(#home), footer')];
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if ('IntersectionObserver' in window && !reduceMotion) {
        const revealObserver = new IntersectionObserver(
            (entries, observer) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) {
                        return;
                    }

                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                });
            },
            {
                rootMargin: '0px 0px -12% 0px',
                threshold: 0.08,
            },
        );

        revealElements.forEach((element) => {
            element.classList.add('scroll-reveal');
            revealObserver.observe(element);
        });
    } else {
        revealElements.forEach((element) => element.classList.add('is-visible'));
    }

    window.addEventListener('scroll', requestScrollUpdate, { passive: true });
    window.addEventListener('resize', requestScrollUpdate, { passive: true });
    updateScrollState();
}

function initializeSmartHeaderScroll(root = document) {
    const headers = root.querySelectorAll('[data-site-header], header.sticky, header');

    headers.forEach((header) => {
        if (header.dataset.smartHeaderReady === 'true') {
            return;
        }

        header.dataset.smartHeaderReady = 'true';
        header.classList.add('transition-all', 'duration-300', 'ease-in-out');

        let lastScrollY = window.scrollY;

        const handleScroll = () => {
            const currentScrollY = window.scrollY;

            if (currentScrollY <= 20) {
                header.classList.remove('-translate-y-full', 'opacity-0', 'pointer-events-none');
                header.classList.add('translate-y-0', 'opacity-100');
            } else if (currentScrollY > lastScrollY && currentScrollY > 70) {
                header.classList.remove('translate-y-0', 'opacity-100');
                header.classList.add('-translate-y-full', 'opacity-0', 'pointer-events-none');
            } else if (currentScrollY < lastScrollY) {
                header.classList.remove('-translate-y-full', 'opacity-0', 'pointer-events-none');
                header.classList.add('translate-y-0', 'opacity-100');
            }

            lastScrollY = currentScrollY;
        };

        window.addEventListener('scroll', handleScroll, { passive: true });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initializeWelcomePage();
        initializePasswordToggles();
        initializeSmartHeaderScroll();
    }, { once: true });
} else {
    initializeWelcomePage();
    initializePasswordToggles();
    initializeSmartHeaderScroll();
}

document.addEventListener('livewire:navigated', () => {
    initializePasswordToggles();
    initializeSmartHeaderScroll();
});
