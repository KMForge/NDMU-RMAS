import './bootstrap';
import * as docx from 'docx-preview';
import * as pdfjsLib from 'pdfjs-dist';
import pdfjsWorker from 'pdfjs-dist/build/pdf.worker.mjs?url';

pdfjsLib.GlobalWorkerOptions.workerSrc = pdfjsWorker;
window.docx = docx;
window.pdfjsLib = pdfjsLib;
window.initializeDocxViewers = initializeDocxViewers;
window.initializePdfViewers = initializePdfViewers;

function initializeDocxViewers(root = document) {
    root.querySelectorAll('[data-docx-viewer]').forEach((container) => {
        if (container.dataset.docxReady === 'true') {
            return;
        }
        container.dataset.docxReady = 'true';
        const url = container.dataset.docxUrl;
        const statusEl = container.querySelector('[data-docx-status]');
        const contentEl = container.querySelector('[data-docx-content]');

        if (!url || !contentEl) {
            return;
        }

        fetch(url)
            .then((res) => {
                if (!res.ok) {
                    throw new Error(`HTTP error ${res.status}`);
                }
                return res.arrayBuffer();
            })
            .then((buffer) => {
                return docx.renderAsync(buffer, contentEl, null, {
                    className: 'docx-rendered-document',
                    inWrapper: true,
                    ignoreWidth: false,
                    ignoreHeight: false,
                    ignoreFonts: false,
                    breakPages: true,
                    ignoreLastRenderedPageBreak: false,
                    experimental: false,
                    trimXmlDeclaration: true,
                    useBase64URL: true,
                });
            })
            .then(() => {
                if (statusEl) {
                    statusEl.classList.add('hidden');
                }
                contentEl.classList.remove('hidden');

                // 1. Tag each page element
                const pages = contentEl.querySelectorAll('section.docx, .docx-wrapper > section, .docx-rendered-document > section');
                const pageElements = pages.length > 0 ? Array.from(pages) : [contentEl];

                pageElements.forEach((pageEl, idx) => {
                    const pageNum = idx + 1;
                    pageEl.dataset.pageNumber = String(pageNum);
                    pageEl.setAttribute('id', `doc-page-${pageNum}`);
                });

                // 2. Inject inline Reviewer Note Callouts directly into the document preview
                let commentsData = [];
                try {
                    if (container.dataset.docxComments) {
                        commentsData = JSON.parse(container.dataset.docxComments);
                    }
                } catch (e) {
                    console.warn('Could not parse docx comments:', e);
                }

                if (Array.isArray(commentsData) && commentsData.length > 0) {
                    commentsData.forEach((c) => {
                        const targetPageNum = c.page_number ? parseInt(c.page_number, 10) : (c.page ? parseInt(String(c.page).replace(/\D/g, ''), 10) || 1 : 1);
                        const targetEl = pageElements[targetPageNum - 1] || pageElements[0];
                        if (targetEl) {
                            const callout = document.createElement('div');
                            callout.className = 'reviewer-note-callout';
                            const authorLabel = c.name ? `${c.name}${c.role ? ' (' + c.role + ')' : ''}` : 'Reviewer Note';
                            const pageLabel = targetPageNum ? `Page ${targetPageNum}` : 'General';
                            const commentBody = (c.text || c.comment || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                            
                            callout.innerHTML = `
                                <div class="reviewer-note-header">
                                    <svg class="reviewer-note-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="12" y1="16" x2="12" y2="12"></line>
                                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                    </svg>
                                    <span class="reviewer-note-title">REVIEWER NOTE · ${authorLabel}</span>
                                    <span class="reviewer-note-page-tag">${pageLabel}</span>
                                </div>
                                <div class="reviewer-note-body">${commentBody}</div>
                            `;
                            targetEl.appendChild(callout);
                        }
                    });
                }

                // 3. Auto-detect current page on scroll
                const scrollContainer = container.closest('.overflow-auto') || container.closest('.overflow-y-auto') || window;
                const updateCurrentPage = () => {
                    if (pageElements.length <= 1) {
                        window.dispatchEvent(new CustomEvent('document-page-change', { detail: { page: 1 } }));
                        return;
                    }
                    const containerRect = scrollContainer === window
                        ? { top: 0, height: window.innerHeight }
                        : scrollContainer.getBoundingClientRect();
                    const containerMidY = containerRect.top + (containerRect.height / 3);

                    let closestPage = 1;
                    let minDistance = Infinity;

                    pageElements.forEach((p, idx) => {
                        const r = p.getBoundingClientRect();
                        const dist = Math.abs(r.top - containerMidY);
                        if (dist < minDistance) {
                            minDistance = dist;
                            closestPage = idx + 1;
                        }
                    });

                    window.dispatchEvent(new CustomEvent('document-page-change', { detail: { page: closestPage } }));
                };

                if (scrollContainer !== window) {
                    scrollContainer.addEventListener('scroll', updateCurrentPage, { passive: true });
                } else {
                    window.addEventListener('scroll', updateCurrentPage, { passive: true });
                }
                updateCurrentPage();
            })
            .catch((err) => {
                console.error('Error rendering DOCX preview:', err);
                if (statusEl) {
                    statusEl.innerHTML = `
                        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 text-center text-amber-800">
                            <i class="ph ph-warning-circle text-3xl mb-2 block text-amber-600"></i>
                            <p class="font-bold text-sm">Unable to render in-browser preview for this file.</p>
                            <p class="text-xs text-amber-700 mt-1">Please use the Download button above to review the original document.</p>
                        </div>
                    `;
                }
            });
    });
}

function initializePdfViewers(root = document) {
    root.querySelectorAll('[data-pdf-viewer]').forEach((container) => {
        if (container.dataset.pdfReady === 'true') {
            return;
        }
        container.dataset.pdfReady = 'true';
        const url = container.dataset.pdfUrl;
        const statusEl = container.querySelector('[data-pdf-status]');
        const contentEl = container.querySelector('[data-pdf-content]');

        if (!url || !contentEl) {
            return;
        }

        const renderIframeFallback = () => {
            if (statusEl) statusEl.classList.add('hidden');
            contentEl.classList.remove('hidden');
            contentEl.innerHTML = `
                <div class="w-full h-full min-h-[750px] rounded-xl overflow-hidden shadow-xs border border-slate-300 bg-white">
                    <iframe src="${url}" class="w-full h-full min-h-[750px] border-0" title="PDF Preview"></iframe>
                </div>
            `;
        };

        fetch(url, { credentials: 'same-origin' })
            .then((res) => {
                if (!res.ok) throw new Error(`HTTP error ${res.status}`);
                return res.arrayBuffer();
            })
            .then(async (buffer) => {
                const loadingTask = pdfjsLib.getDocument({
                    data: new Uint8Array(buffer),
                    cMapUrl: 'https://cdn.jsdelivr.net/npm/pdfjs-dist@4.10.38/cmaps/',
                    cMapPacked: true,
                });

                const pdf = await loadingTask.promise;

                if (statusEl) {
                    statusEl.classList.add('hidden');
                }
                contentEl.classList.remove('hidden');
                contentEl.innerHTML = '';

                let commentsData = [];
                try {
                    if (container.dataset.pdfComments) {
                        commentsData = JSON.parse(container.dataset.pdfComments);
                    }
                } catch (e) {
                    console.warn('Could not parse pdf comments:', e);
                }

                const pageElements = [];

                for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
                    const page = await pdf.getPage(pageNum);
                    const scale = 1.6;
                    const viewport = page.getViewport({ scale });

                    const pageWrapper = document.createElement('div');
                    pageWrapper.className = 'pdf-page-container';
                    pageWrapper.dataset.pageNumber = String(pageNum);
                    pageWrapper.setAttribute('id', `pdf-page-${pageNum}`);

                    const canvas = document.createElement('canvas');
                    const context = canvas.getContext('2d');
                    canvas.height = viewport.height;
                    canvas.width = viewport.width;
                    canvas.style.width = '100%';
                    canvas.style.height = 'auto';

                    const renderContext = {
                        canvasContext: context,
                        viewport: viewport,
                    };
                    await page.render(renderContext).promise;

                    pageWrapper.appendChild(canvas);

                    // Inject comments matching this page
                    if (Array.isArray(commentsData) && commentsData.length > 0) {
                        commentsData.forEach((c) => {
                            const targetPageNum = c.page_number ? parseInt(c.page_number, 10) : (c.page ? parseInt(String(c.page).replace(/\D/g, ''), 10) || 1 : 1);
                            if (targetPageNum === pageNum) {
                                const callout = document.createElement('div');
                                callout.className = 'reviewer-note-callout';
                                const authorLabel = c.name ? `${c.name}${c.role ? ' (' + c.role + ')' : ''}` : 'Reviewer Note';
                                const pageLabel = `Page ${pageNum}`;
                                const commentBody = (c.text || c.comment || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');

                                callout.innerHTML = `
                                    <div class="reviewer-note-header">
                                        <svg class="reviewer-note-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <line x1="12" y1="16" x2="12" y2="12"></line>
                                            <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                        </svg>
                                        <span class="reviewer-note-title">REVIEWER NOTE · ${authorLabel}</span>
                                        <span class="reviewer-note-page-tag">${pageLabel}</span>
                                    </div>
                                    <div class="reviewer-note-body">${commentBody}</div>
                                `;
                                pageWrapper.appendChild(callout);
                            }
                        });
                    }

                    contentEl.appendChild(pageWrapper);
                    pageElements.push(pageWrapper);
                }

                // Scroll listener for PDF page detection
                const scrollContainer = container.closest('.overflow-auto') || container.closest('.overflow-y-auto') || window;
                const updateCurrentPage = () => {
                    if (pageElements.length <= 1) {
                        window.dispatchEvent(new CustomEvent('document-page-change', { detail: { page: 1 } }));
                        return;
                    }
                    const containerRect = scrollContainer === window
                        ? { top: 0, height: window.innerHeight }
                        : scrollContainer.getBoundingClientRect();
                    const containerMidY = containerRect.top + (containerRect.height / 3);

                    let closestPage = 1;
                    let minDistance = Infinity;

                    pageElements.forEach((p, idx) => {
                        const r = p.getBoundingClientRect();
                        const dist = Math.abs(r.top - containerMidY);
                        if (dist < minDistance) {
                            minDistance = dist;
                            closestPage = idx + 1;
                        }
                    });

                    window.dispatchEvent(new CustomEvent('document-page-change', { detail: { page: closestPage } }));
                };

                if (scrollContainer !== window) {
                    scrollContainer.addEventListener('scroll', updateCurrentPage, { passive: true });
                } else {
                    window.addEventListener('scroll', updateCurrentPage, { passive: true });
                }
                updateCurrentPage();
            })
            .catch((err) => {
                console.warn('PDF.js canvas rendering fallback to iframe:', err);
                renderIframeFallback();
            });
    });
}

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
        initializeDocxViewers();
        initializePdfViewers();
    }, { once: true });
} else {
    initializeWelcomePage();
    initializePasswordToggles();
    initializeSmartHeaderScroll();
    initializeDocxViewers();
    initializePdfViewers();
}

document.addEventListener('livewire:navigated', () => {
    initializePasswordToggles();
    initializeSmartHeaderScroll();
    initializeDocxViewers();
    initializePdfViewers();
});
