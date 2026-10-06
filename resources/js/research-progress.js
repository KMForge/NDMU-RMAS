// Keep milestone mutations on the monitoring page without resetting its scroll.
document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || form.method.toLowerCase() !== 'post') return;
    const monitoring = form.closest('[data-progress-monitoring]');
    const article = form.closest('[id^="progress-milestone-"]');
    if (!monitoring || !article) return;

    event.preventDefault();
    if (monitoring.dataset.saving === 'true') return;
    monitoring.dataset.saving = 'true';
    const body = new FormData(form);
    const buttons = [...monitoring.querySelectorAll('button[type="submit"], form[method="POST"] button')];
    const buttonStates = buttons.map((button) => button.disabled);
    buttons.forEach((button) => { button.disabled = true; });
    let saved = false;
    let currentMonitoring = monitoring;
    const message = (target, text, success = false) => {
        let feedback = target.querySelector('[data-progress-feedback]');
        if (!feedback) {
            feedback = document.createElement('p');
            feedback.dataset.progressFeedback = '';
            feedback.setAttribute('role', 'status');
            feedback.setAttribute('aria-live', 'polite');
            target.append(feedback);
        }
        feedback.className = `mt-3 rounded-xl border p-3 text-xs font-bold ${success ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-rose-200 bg-rose-50 text-rose-900'}`;
        feedback.textContent = text;
    };

    try {
        const response = await fetch(form.action, {
            method: 'POST', body, credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const result = await response.json();
        if (!response.ok) {
            const errors = Object.values(result.errors ?? {}).flat();
            message(form, errors.join(' ') || result.message || 'Could not save this milestone.');
            return;
        }
        saved = true;

        // Refresh authoritative counts and statuses, but not the sidebar/page shell.
        const refresh = await fetch(window.location.href, {
            credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'text/html' },
        });
        if (!refresh.ok || refresh.redirected) throw new Error('Monitoring refresh failed');
        const page = new DOMParser().parseFromString(await refresh.text(), 'text/html');
        const replacement = page.querySelector('[data-progress-monitoring]');
        if (!replacement) throw new Error('Monitoring section missing');

        const openDetails = [...monitoring.querySelectorAll('details[open]')]
            .map((details) => details.closest('[id^="progress-milestone-"]')?.id).filter(Boolean);
        const scrollPositions = [];
        for (let parent = monitoring.parentElement; parent; parent = parent.parentElement) {
            scrollPositions.push([parent, parent.scrollTop, parent.scrollLeft]);
        }
        const windowPosition = [window.scrollX, window.scrollY];
        monitoring.replaceWith(replacement);
        currentMonitoring = replacement;
        openDetails.forEach((id) => {
            const details = replacement.querySelector(`#${CSS.escape(id)} details`);
            if (details) details.open = true;
        });
        const updatedArticle = replacement.querySelector(`#${CSS.escape(article.id)}`);
        message(updatedArticle ?? replacement, result.message ?? 'Milestone updated.', true);
        history.replaceState(history.state, '', `${window.location.pathname}${window.location.search}#${article.id}`);
        const restoreScroll = () => {
            scrollPositions.forEach(([element, top, left]) => { element.scrollTop = top; element.scrollLeft = left; });
            window.scrollTo({ left: windowPosition[0], top: windowPosition[1], behavior: 'instant' });
        };
        restoreScroll();
        requestAnimationFrame(restoreScroll);
    } catch {
        message(form, saved
            ? 'Saved successfully, but the display could not refresh. Reload to see the updated status; do not submit again.'
            : 'Could not confirm the save. Check your connection and the milestone status before trying again.');
    } finally {
        delete currentMonitoring.dataset.saving;
        buttons.forEach((button, index) => { button.disabled = buttonStates[index]; });
    }
});
