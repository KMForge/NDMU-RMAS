import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';

export function renderCalendar(element, options = {}) {
    const calendar = new Calendar(element, {
        plugins: [dayGridPlugin, interactionPlugin],
        ...options,
    });

    calendar.render();

    return calendar;
}
