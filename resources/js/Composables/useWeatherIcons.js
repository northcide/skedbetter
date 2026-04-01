/**
 * Weather icon SVGs and FullCalendar hook helpers.
 *
 * Usage:
 *   import { useWeatherIcons } from '@/Composables/useWeatherIcons';
 *   const { dayCellContent, dayHeaderContent } = useWeatherIcons(weatherData);
 *
 * weatherData is a reactive ref/object keyed by date string:
 *   { '2026-04-01': { high: 72, low: 55, icon: 'sunny' }, ... }
 */

const ICON_SVGS = {
    'sunny': `<svg viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2m-10-10h2m16 0h2m-3.3-6.7-1.4 1.4M6.7 17.3l-1.4 1.4m0-13.4 1.4 1.4m10.6 10.6 1.4 1.4"/></svg>`,
    'mostly-sunny': `<svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round"><circle cx="10" cy="10" r="3.5" stroke="#f59e0b"/><path d="M10 3v1.5m0 11v1.5m-7-7h1.5m11 0h1.5m-2.3-4.7-1 1M7.3 14.7l-1 1m0-9.4 1 1m7.4 7.4 1 1" stroke="#f59e0b"/><path d="M13 16.5a4 4 0 1 1 4.5-6.5" stroke="#9ca3af" fill="#f3f4f6"/></svg>`,
    'partly-cloudy': `<svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round"><circle cx="9" cy="9" r="3" stroke="#f59e0b"/><path d="M9 3v1m0 10v1m-7-6h1m10 0h1m-1.8-4.2-.7.7M6.5 13.5l-.7.7m0-8.4.7.7m5.3 5.3.7.7" stroke="#f59e0b"/><path d="M13 18H9a5 5 0 0 1-.5-10h.1a4 4 0 0 1 7.8 1 3.5 3.5 0 0 1-.4 7z" stroke="#9ca3af" fill="#f3f4f6"/></svg>`,
    'cloudy': `<svg viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" stroke-linecap="round"><path d="M17 18H7a5 5 0 0 1-.5-10h.1a6 6 0 0 1 11.8 1A4 4 0 0 1 17 18z" fill="#f3f4f6"/></svg>`,
    'fog': `<svg viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" stroke-linecap="round"><path d="M5 15h14M5 19h14m-1-8H7a4 4 0 0 1 0-8h.2a5 5 0 0 1 9.6 0A3.5 3.5 0 0 1 18 11z"/></svg>`,
    'drizzle': `<svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round"><path d="M17 14H7a5 5 0 0 1-.5-10h.1a6 6 0 0 1 11.8 1A4 4 0 0 1 17 14z" stroke="#93c5fd" fill="#eff6ff"/><path d="M8 18v1m4-3v1m4 1v1" stroke="#60a5fa"/></svg>`,
    'rain': `<svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round"><path d="M17 13H7a5 5 0 0 1-.5-10h.1a6 6 0 0 1 11.8 1A4 4 0 0 1 17 13z" stroke="#60a5fa" fill="#eff6ff"/><path d="M8 17v2m4-4v2m4 0v2" stroke="#3b82f6"/></svg>`,
    'heavy-rain': `<svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round"><path d="M17 12H7a5 5 0 0 1-.5-10h.1a6 6 0 0 1 11.8 1A4 4 0 0 1 17 12z" stroke="#3b82f6" fill="#dbeafe"/><path d="M7 16v3m5-5v3m5-1v3m-8 0v2m4-4v2" stroke="#2563eb"/></svg>`,
    'snow': `<svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round"><path d="M17 13H7a5 5 0 0 1-.5-10h.1a6 6 0 0 1 11.8 1A4 4 0 0 1 17 13z" stroke="#93c5fd" fill="#eff6ff"/><circle cx="8" cy="17" r="0.5" fill="#60a5fa"/><circle cx="12" cy="16" r="0.5" fill="#60a5fa"/><circle cx="16" cy="18" r="0.5" fill="#60a5fa"/><circle cx="10" cy="20" r="0.5" fill="#60a5fa"/><circle cx="14" cy="20" r="0.5" fill="#60a5fa"/></svg>`,
    'thunderstorm': `<svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round"><path d="M17 12H7a5 5 0 0 1-.5-10h.1a6 6 0 0 1 11.8 1A4 4 0 0 1 17 12z" stroke="#6b7280" fill="#e5e7eb"/><path d="M13 12l-2 5h3l-2 5" stroke="#eab308" stroke-width="2"/></svg>`,
};

// Colored background tints per icon for month cells
const ICON_COLORS = {
    'sunny': '#fef3c7',        // warm amber
    'mostly-sunny': '#fef9c3', // light yellow
    'partly-cloudy': '#f0f9ff', // light blue
    'cloudy': '#f3f4f6',       // gray
    'fog': '#f3f4f6',
    'drizzle': '#eff6ff',
    'rain': '#dbeafe',
    'heavy-rain': '#bfdbfe',
    'snow': '#e0e7ff',
    'thunderstorm': '#fef3c7',
};

function weatherHtml(w, compact = false) {
    if (!w) return '';
    const svg = ICON_SVGS[w.icon] || ICON_SVGS['cloudy'];
    if (compact) {
        return `<span class="fc-weather" title="${w.high}°/${w.low}°">${svg}</span>`;
    }
    return `<span class="fc-weather"><span class="fc-weather-icon">${svg}</span><span class="fc-weather-temp">${w.high}°</span></span>`;
}

function dateKey(date) {
    if (!date) return '';
    if (typeof date === 'string') return date.slice(0, 10);
    return date.toISOString().slice(0, 10);
}

export function useWeatherIcons(weather) {
    const getWeather = (date) => {
        const w = weather.value || weather;
        return w[dateKey(date)] || null;
    };

    /**
     * FullCalendar dayCellDidMount hook — injects weather icon into the day cell DOM.
     * Appends to .fc-daygrid-day-top as a separate element, positioned left via CSS.
     */
    const dayCellDidMount = (arg) => {
        if (arg.view.type !== 'dayGridMonth') return;
        const w = getWeather(arg.date);
        if (!w) return;
        const svg = ICON_SVGS[w.icon] || ICON_SVGS['cloudy'];
        const el = document.createElement('span');
        el.className = 'fc-weather-day';
        el.title = `${w.high}°/${w.low}°`;
        el.innerHTML = `<span class="fc-weather-icon">${svg}</span><span class="fc-weather-temp">${w.high}°</span>`;
        const top = arg.el.querySelector('.fc-daygrid-day-top');
        if (top) top.prepend(el);
    };

    /**
     * FullCalendar dayHeaderContent hook — used in week/day/list views.
     * Appends a weather icon after the day label.
     */
    const dayHeaderContent = (arg) => {
        const w = getWeather(arg.date);
        const defaultText = arg.text;
        if (!w) return { html: defaultText };
        const svg = ICON_SVGS[w.icon] || ICON_SVGS['cloudy'];
        return {
            html: `${defaultText} <span class="fc-weather-header" title="${w.high}°/${w.low}°"><span class="fc-weather-icon">${svg}</span><span class="fc-weather-temp">${w.high}°</span></span>`,
        };
    };

    return { dayCellDidMount, dayHeaderContent, getWeather };
}
