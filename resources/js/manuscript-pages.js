export function manuscriptPageLabel(mapping, previewPage) {
    if (!mapping) return String(previewPage);
    const start = Number(mapping.body_start);
    return previewPage >= start
        ? String(previewPage - start + 1)
        : String(mapping.preliminary_labels?.[previewPage - 1] ?? 'Unnumbered');
}

export function visibleManuscriptPage(pages, bounds) {
    let selected = 1;
    let largestVisibleHeight = -1;
    pages.forEach((page, index) => {
        const rect = page.getBoundingClientRect();
        const visibleHeight = Math.max(0, Math.min(rect.bottom, bounds.bottom) - Math.max(rect.top, bounds.top));
        if (visibleHeight > largestVisibleHeight) {
            largestVisibleHeight = visibleHeight;
            selected = index + 1;
        }
    });
    return selected;
}
