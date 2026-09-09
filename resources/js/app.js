import './bootstrap';
import * as docx from 'docx-preview';
import * as pdfjsLib from 'pdfjs-dist/legacy/build/pdf.mjs';
import PdfJsWorker from 'pdfjs-dist/legacy/build/pdf.worker.mjs?worker';
import { PDFDocument, StandardFonts, rgb } from 'pdf-lib';

pdfjsLib.GlobalWorkerOptions.workerPort = new PdfJsWorker();
window.docx = docx;
window.pdfjsLib = pdfjsLib;
window.initializeDocxViewers = initializeDocxViewers;
window.initializePdfViewers = initializePdfViewers;

function commentPageNumber(comment, fallback = 1) {
    const value = comment?.page_number ?? comment?.page;
    const parsed = Number.parseInt(String(value ?? '').replace(/\D/g, ''), 10);

    return Number.isInteger(parsed) && parsed > 0 ? parsed : fallback;
}

function appendReviewerNote(pageElement, comment, pageNumber = 1) {
    if (!(pageElement instanceof HTMLElement)) {
        return false;
    }

    const commentId = comment?.id ? String(comment.id) : null;
    if (commentId && pageElement.querySelector(`[data-review-comment-id="${CSS.escape(commentId)}"]`)) {
        return true;
    }

    const callout = document.createElement('div');
    callout.className = 'reviewer-note-callout';
    if (commentId) {
        callout.dataset.reviewCommentId = commentId;
    }

    const header = document.createElement('div');
    header.className = 'reviewer-note-header';
    header.innerHTML = `
        <svg class="reviewer-note-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="16" x2="12" y2="12"></line>
            <line x1="12" y1="8" x2="12.01" y2="8"></line>
        </svg>
    `;

    const author = comment?.name
        ? `${comment.name}${comment.role ? ` (${comment.role})` : ''}`
        : 'Reviewer Note';
    const title = document.createElement('span');
    title.className = 'reviewer-note-title';
    title.textContent = `REVIEWER NOTE - ${author}`;

    const pageTag = document.createElement('span');
    pageTag.className = 'reviewer-note-page-tag';
    pageTag.textContent = `Page ${pageNumber}`;

    const body = document.createElement('div');
    body.className = 'reviewer-note-body';
    body.textContent = comment?.text ?? comment?.comment ?? '';

    header.append(title, pageTag);
    callout.append(header, body);
    pageElement.appendChild(callout);

    return true;
}

function appendDocumentComment(comment, root = document) {
    const pageNumber = commentPageNumber(comment);
    const pageElement = root.querySelector(`#pdf-page-${pageNumber}, #doc-page-${pageNumber}`);

    return appendReviewerNote(pageElement, comment, pageNumber);
}

window.appendDocumentComment = appendDocumentComment;

function printablePdfText(value) {
    return String(value ?? '')
        .replace(/[\u2018\u2019]/g, "'")
        .replace(/[\u201C\u201D]/g, '"')
        .replace(/[\u2013\u2014]/g, '-')
        .normalize('NFKD')
        .replace(/[^\x20-\x7E\n]/g, '');
}

function wrapPdfText(text, font, size, maxWidth) {
    const lines = [];

    printablePdfText(text).split(/\r?\n/).forEach((paragraph) => {
        const words = paragraph.trim().split(/\s+/).filter(Boolean);
        if (words.length === 0) {
            lines.push('');
            return;
        }

        let line = '';
        words.forEach((word) => {
            const candidate = line ? `${line} ${word}` : word;
            if (font.widthOfTextAtSize(candidate, size) <= maxWidth || !line) {
                line = candidate;
            } else {
                lines.push(line);
                line = word;
            }
        });
        if (line) lines.push(line);
    });

    return lines;
}

async function downloadAnnotatedPdf({ downloadUrl, filename, comments = [] }) {
    if (!downloadUrl) {
        throw new Error('The original manuscript download URL is unavailable.');
    }

    const response = await fetch(downloadUrl, { credentials: 'same-origin' });
    if (!response.ok) {
        throw new Error(`Unable to retrieve the original manuscript (HTTP ${response.status}).`);
    }

    const sourcePdf = await PDFDocument.load(await response.arrayBuffer());
    const annotatedPdf = await PDFDocument.create();
    const regularFont = await annotatedPdf.embedFont(StandardFonts.Helvetica);
    const boldFont = await annotatedPdf.embedFont(StandardFonts.HelveticaBold);
    const sourcePages = await annotatedPdf.copyPages(sourcePdf, sourcePdf.getPageIndices());
    const pageComments = new Map();
    const generalComments = [];

    comments.forEach((comment) => {
        const pageNumber = commentPageNumber(comment, 0);
        if (pageNumber < 1 || pageNumber > sourcePages.length) {
            generalComments.push(comment);
            return;
        }

        const existing = pageComments.get(pageNumber) ?? [];
        existing.push(comment);
        pageComments.set(pageNumber, existing);
    });

    const addCommentSheets = (referencedPage, attachedComments, pageSize) => {
        if (attachedComments.length === 0) return;

        const [width, height] = pageSize;
        const margin = 48;
        const contentWidth = width - (margin * 2);
        let sheet;
        let y;

        const startSheet = () => {
            sheet = annotatedPdf.addPage([width, height]);
            sheet.drawRectangle({ x: 0, y: 0, width, height, color: rgb(1, 1, 1) });
            sheet.drawText('NDMU-RMAS REVIEW COPY', {
                x: margin,
                y: height - 55,
                size: 9,
                font: boldFont,
                color: rgb(0.06, 0.36, 0.23),
            });
            sheet.drawText(referencedPage ? `Reviewer Comments - Original Page ${referencedPage}` : 'General Reviewer Comments', {
                x: margin,
                y: height - 82,
                size: 17,
                font: boldFont,
                color: rgb(0.08, 0.12, 0.2),
            });
            sheet.drawLine({
                start: { x: margin, y: height - 96 },
                end: { x: width - margin, y: height - 96 },
                thickness: 2,
                color: rgb(0.96, 0.62, 0.04),
            });
            y = height - 125;
        };

        startSheet();

        attachedComments.forEach((comment) => {
            const bodyLines = wrapPdfText(comment.text ?? comment.comment, regularFont, 10, contentWidth - 32);
            const chunks = [];
            for (let index = 0; index < Math.max(bodyLines.length, 1); index += 30) {
                chunks.push(bodyLines.slice(index, index + 30));
            }

            chunks.forEach((lines, chunkIndex) => {
                const blockHeight = 58 + (Math.max(lines.length, 1) * 14);
                if (y - blockHeight < margin) startSheet();

                sheet.drawRectangle({
                    x: margin,
                    y: y - blockHeight,
                    width: contentWidth,
                    height: blockHeight,
                    color: rgb(1, 0.98, 0.89),
                    borderColor: rgb(0.98, 0.75, 0.25),
                    borderWidth: 1,
                });
                sheet.drawRectangle({
                    x: margin,
                    y: y - blockHeight,
                    width: 5,
                    height: blockHeight,
                    color: rgb(0.96, 0.62, 0.04),
                });

                const author = printablePdfText(comment.name || 'Reviewer');
                const role = printablePdfText(comment.role || 'Reviewer');
                const severity = printablePdfText(comment.severity || 'comment').toUpperCase();
                sheet.drawText(`${author} (${role})${chunkIndex ? ' - continued' : ''}`, {
                    x: margin + 16,
                    y: y - 22,
                    size: 10,
                    font: boldFont,
                    color: rgb(0.45, 0.25, 0.04),
                });
                sheet.drawText(severity, {
                    x: margin + 16,
                    y: y - 39,
                    size: 8,
                    font: boldFont,
                    color: rgb(0.72, 0.32, 0.02),
                });

                lines.forEach((line, lineIndex) => {
                    sheet.drawText(line, {
                        x: margin + 16,
                        y: y - 57 - (lineIndex * 14),
                        size: 10,
                        font: regularFont,
                        color: rgb(0.18, 0.12, 0.08),
                    });
                });
                y -= blockHeight + 14;
            });
        });
    };

    sourcePages.forEach((page, index) => {
        annotatedPdf.addPage(page);
        const pageNumber = index + 1;
        const size = [page.getWidth(), page.getHeight()];
        addCommentSheets(pageNumber, pageComments.get(pageNumber) ?? [], size);
    });

    if (generalComments.length > 0) {
        const lastPage = sourcePages.at(-1);
        addCommentSheets(null, generalComments, lastPage ? [lastPage.getWidth(), lastPage.getHeight()] : [612, 792]);
    }

    const bytes = await annotatedPdf.save();
    const blobUrl = URL.createObjectURL(new Blob([bytes], { type: 'application/pdf' }));
    const link = document.createElement('a');
    const baseName = printablePdfText(filename || 'research-manuscript').replace(/\.pdf$/i, '');
    link.href = blobUrl;
    link.download = `${baseName}-annotated.pdf`;
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.setTimeout(() => URL.revokeObjectURL(blobUrl), 1000);
}

window.downloadAnnotatedPdf = downloadAnnotatedPdf;

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

        fetch(url, { credentials: 'same-origin' })
            .then((res) => {
                if (!res.ok) throw new Error(`HTTP error ${res.status}`);
                return res.arrayBuffer();
            })
            .then(async (buffer) => {
                const loadingTask = pdfjsLib.getDocument({
                    data: new Uint8Array(buffer),
                    useSystemFonts: true,
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
                    // Render above the displayed CSS width so manuscript text stays sharp.
                    const scale = 1.5;
                    const viewport = page.getViewport({ scale });

                    const pageWrapper = document.createElement('div');
                    pageWrapper.className = 'pdf-page-container';
                    pageWrapper.dataset.pageNumber = String(pageNum);
                    pageWrapper.setAttribute('id', `pdf-page-${pageNum}`);

                    const canvas = document.createElement('canvas');
                    canvas.height = Math.ceil(viewport.height);
                    canvas.width = Math.ceil(viewport.width);
                    canvas.style.width = '100%';
                    canvas.style.height = 'auto';

                    const renderContext = {
                        canvas,
                        viewport,
                    };
                    try {
                        await page.render(renderContext).promise;
                    } catch (error) {
                        console.error(`Error rendering PDF page ${pageNum}:`, error);
                    }

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
                console.error('Error rendering annotatable PDF preview:', err);
                contentEl.classList.add('hidden');
                if (statusEl) {
                    statusEl.classList.remove('hidden');
                    statusEl.innerHTML = `
                        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 text-center text-amber-900">
                            <i class="ph ph-warning-circle text-3xl mb-2 block text-amber-600"></i>
                            <p class="font-bold text-sm">The annotatable PDF preview could not be loaded.</p>
                            <p class="text-xs text-amber-700 mt-1">Refresh the page to retry. The original manuscript remains available from the Download button.</p>
                            <p data-pdf-error-detail class="mt-2 text-[10px] font-mono text-amber-800"></p>
                        </div>
                    `;
                    const errorDetail = statusEl.querySelector('[data-pdf-error-detail]');
                    if (errorDetail) {
                        const errorName = err instanceof Error ? err.name : 'PDFError';
                        const errorMessage = err instanceof Error ? err.message : String(err);
                        errorDetail.textContent = `${errorName}: ${errorMessage}`;
                    }
                }
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
