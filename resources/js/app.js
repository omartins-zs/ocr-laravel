import './bootstrap';
import Alpine from 'alpinejs';
import toastr from 'toastr';
import tippy from 'tippy.js';
import NProgress from 'nprogress';
import 'nprogress/nprogress.css';

window.Alpine = Alpine;
window.toastr = toastr;
window.tippy = tippy;
window.NProgress = NProgress;

NProgress.configure({
    showSpinner: false,
    trickleSpeed: 200,
    minimum: 0.1,
});

toastr.options = {
    closeButton: true,
    newestOnTop: true,
    progressBar: true,
    positionClass: 'toast-top-right',
    timeOut: 3500,
};

let chartModulePromise;
let filePondModulePromise;

const loadChart = async () => {
    if (!chartModulePromise) {
        chartModulePromise = import('chart.js/auto').then((module) => {
            const Chart = module.default;
            window.Chart = Chart;

            return Chart;
        });
    }

    return chartModulePromise;
};

const loadFilePond = async () => {
    if (!filePondModulePromise) {
        filePondModulePromise = import('filepond').then((module) => {
            window.FilePond = module;

            return module;
        });
    }

    return filePondModulePromise;
};

window.withChart = async (callback) => {
    const Chart = await loadChart();

    return callback(Chart);
};

const initializeFilePond = async () => {
    const elements = Array.from(document.querySelectorAll('[data-filepond]'))
        .filter((input) => input.dataset.filepondInitialized !== 'true');

    if (elements.length === 0) {
        return;
    }

    const FilePond = await loadFilePond();

    elements.forEach((input) => {
        FilePond.create(input, {
            credits: false,
            allowMultiple: input.multiple,
            storeAsFile: true,
            allowProcess: false,
            instantUpload: false,
            maxFileSize: input.dataset.maxFileSize || '50MB',
            labelIdle: 'Arraste e solte ou <span class="filepond--label-action">selecione</span> o arquivo',
        });

        input.dataset.filepondInitialized = 'true';
    });
};

const initializeTooltips = () => {
    tippy('[data-tippy-content]', {
        theme: 'light-border',
        duration: [140, 120],
        delay: [100, 0],
    });
};

const initializeThemeToggle = () => {
    const themeToggleBtn = document.getElementById('theme-toggle');
    const themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
    const themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');

    if (!themeToggleBtn || !themeToggleDarkIcon || !themeToggleLightIcon) {
        return;
    }

    const isDarkMode =
        localStorage.getItem('color-theme') === 'dark'
        || (!localStorage.getItem('color-theme') && window.matchMedia('(prefers-color-scheme: dark)').matches);

    if (isDarkMode) {
        themeToggleLightIcon.classList.remove('hidden');
        themeToggleDarkIcon.classList.add('hidden');
    } else {
        themeToggleDarkIcon.classList.remove('hidden');
        themeToggleLightIcon.classList.add('hidden');
    }

    if (themeToggleBtn.dataset.themeBound === 'true') {
        return;
    }

    themeToggleBtn.addEventListener('click', () => {
        themeToggleDarkIcon.classList.toggle('hidden');
        themeToggleLightIcon.classList.toggle('hidden');

        if (localStorage.getItem('color-theme')) {
            if (localStorage.getItem('color-theme') === 'light') {
                document.documentElement.classList.add('dark');
                localStorage.setItem('color-theme', 'dark');
            } else {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('color-theme', 'light');
            }
        } else if (document.documentElement.classList.contains('dark')) {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('color-theme', 'light');
        } else {
            document.documentElement.classList.add('dark');
            localStorage.setItem('color-theme', 'dark');
        }
    });

    themeToggleBtn.dataset.themeBound = 'true';
};

const initializeRefreshControls = () => {
    document.querySelectorAll('[data-refresh-now]').forEach((button) => {
        if (button.dataset.refreshBound === 'true') {
            return;
        }

        button.addEventListener('click', () => {
            NProgress.start();
            window.location.reload();
        });

        button.dataset.refreshBound = 'true';
    });

    const refreshMarker = document.querySelector('[data-auto-refresh-seconds]');
    const label = document.querySelector('[data-auto-refresh-label]');
    const seconds = Number.parseInt(refreshMarker?.dataset.autoRefreshSeconds || '0', 10);

    if (window.ocrRefreshIntervalId) {
        window.clearInterval(window.ocrRefreshIntervalId);
        window.ocrRefreshIntervalId = null;
    }

    if (!refreshMarker || Number.isNaN(seconds) || seconds <= 0) {
        if (label) {
            label.textContent = '';
        }

        return;
    }

    let remaining = seconds;
    if (label) {
        label.textContent = `Atualizacao em ${remaining}s`;
    }

    window.ocrRefreshIntervalId = window.setInterval(() => {
        remaining -= 1;
        if (remaining <= 0) {
            if (label) {
                label.textContent = 'Dados podem estar desatualizados. Clique em "Atualizar".';
                label.classList.add('text-amber-500', 'font-semibold');
            }

            window.clearInterval(window.ocrRefreshIntervalId);
            return;
        }

        if (label) {
            label.textContent = `Proximo check em ${remaining}s`;
        }
    }, 1000);
};

const OCR_BADGE_CLASS_GROUPS = {
    online: {
        badge: [
            'border-emerald-200',
            'bg-emerald-50',
            'text-emerald-700',
            'dark:border-emerald-500/40',
            'dark:bg-emerald-500/10',
            'dark:text-emerald-300',
        ],
        dot: ['bg-emerald-500'],
    },
    offline: {
        badge: [
            'border-rose-200',
            'bg-rose-50',
            'text-rose-700',
            'dark:border-rose-500/40',
            'dark:bg-rose-500/10',
            'dark:text-rose-300',
        ],
        dot: ['bg-rose-500'],
    },
    disabled: {
        badge: [
            'border-amber-200',
            'bg-amber-50',
            'text-amber-700',
            'dark:border-amber-500/40',
            'dark:bg-amber-500/10',
            'dark:text-amber-300',
        ],
        dot: ['bg-amber-500'],
    },
};

const OCR_BADGE_STATES = ['online', 'offline', 'disabled'];

const applyOcrBadgeState = (badge, state, details = {}) => {
    const resolvedState = OCR_BADGE_STATES.includes(state) ? state : 'offline';
    const dot = badge.querySelector('[data-ocr-status-dot]');
    const label = badge.querySelector('[data-ocr-status-label]');

    const badgeClasses = new Set(
        Object.values(OCR_BADGE_CLASS_GROUPS).flatMap((item) => item.badge),
    );
    const dotClasses = new Set(
        Object.values(OCR_BADGE_CLASS_GROUPS).flatMap((item) => item.dot),
    );

    badge.classList.remove(...badgeClasses);
    dot?.classList.remove(...dotClasses);

    badge.classList.add(...OCR_BADGE_CLASS_GROUPS[resolvedState].badge);
    dot?.classList.add(...OCR_BADGE_CLASS_GROUPS[resolvedState].dot);

    const stateLabel = resolvedState === 'online'
        ? 'ONLINE'
        : resolvedState === 'disabled'
            ? 'DESLIGADO'
            : 'OFFLINE';

    if (label) {
        label.textContent = `OCR ${stateLabel}`;
    }

    const tooltipParts = [`OCR externo: ${stateLabel}`];

    if (typeof details.httpStatus === 'number') {
        tooltipParts.push(`HTTP ${details.httpStatus}`);
    }

    if (typeof details.latencyMs === 'number') {
        tooltipParts.push(`${details.latencyMs}ms`);
    }

    if (details.baseUrl) {
        tooltipParts.push(String(details.baseUrl));
    }

    if (details.error) {
        tooltipParts.push(String(details.error));
    }

    const tooltip = tooltipParts.join(' | ');
    badge.setAttribute('data-tippy-content', tooltip);

    if (badge._tippy) {
        badge._tippy.setContent(tooltip);
    }
};

const initializeOcrStatusBadge = () => {
    const badge = document.querySelector('[data-ocr-status-badge]');

    if (!badge) {
        return;
    }

    const endpointUrl = badge.dataset.ocrStatusUrl;
    const pollMsRaw = Number.parseInt(badge.dataset.ocrStatusPollMs || '10000', 10);
    const pollMs = Number.isNaN(pollMsRaw) || pollMsRaw <= 0 ? 10000 : pollMsRaw;
    const requestTimeoutMsRaw = Number.parseInt(badge.dataset.ocrStatusRequestTimeoutMs || '5000', 10);
    const requestTimeoutMs = Number.isNaN(requestTimeoutMsRaw) || requestTimeoutMsRaw <= 0
        ? 5000
        : requestTimeoutMsRaw;
    const shouldLogToConsole = badge.dataset.ocrStatusConsoleLog === '1';

    if (window.ocrStatusPollIntervalId) {
        window.clearInterval(window.ocrStatusPollIntervalId);
        window.ocrStatusPollIntervalId = null;
    }

    if (!endpointUrl) {
        const details = {
            error: 'Rota de status OCR nao configurada.',
        };
        applyOcrBadgeState(badge, 'offline', details);

        return;
    }

    let statusRequestInFlight = false;
    let lastConsoleSignature = '';

    const logStatusInConsole = (state, details = {}) => {
        if (!shouldLogToConsole) {
            return;
        }

        const signature = JSON.stringify({
            state,
            httpStatus: details.httpStatus ?? null,
            latencyMs: details.latencyMs ?? null,
            error: details.error ?? null,
            baseUrl: details.baseUrl ?? null,
        });

        if (signature === lastConsoleSignature) {
            return;
        }

        lastConsoleSignature = signature;

        const payload = {
            state,
            httpStatus: details.httpStatus ?? null,
            latencyMs: details.latencyMs ?? null,
            baseUrl: details.baseUrl ?? null,
            error: details.error ?? null,
            checkedAt: new Date().toISOString(),
        };

        if (state === 'online') {
            console.info('[OCR STATUS]', payload);
        } else {
            console.warn('[OCR STATUS]', payload);
        }
    };

    const fetchStatus = async () => {
        if (statusRequestInFlight) {
            return;
        }

        statusRequestInFlight = true;
        const controller = new AbortController();
        const timeoutId = window.setTimeout(() => controller.abort(), requestTimeoutMs);

        try {
            const response = await fetch(endpointUrl, {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                cache: 'no-store',
                signal: controller.signal,
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const payload = await response.json();
            const data = payload?.data ?? {};
            const state = OCR_BADGE_STATES.includes(data.state) ? data.state : 'offline';
            const details = {
                httpStatus: data.http_status ?? null,
                latencyMs: data.latency_ms ?? null,
                baseUrl: data.base_url ?? null,
                error: data.error ?? null,
            };

            applyOcrBadgeState(badge, state, details);
            logStatusInConsole(state, details);
        } catch (error) {
            if (error instanceof DOMException && error.name === 'AbortError') {
                const details = {
                    error: `Timeout ao consultar OCR (${requestTimeoutMs}ms).`,
                };
                applyOcrBadgeState(badge, 'offline', details);
                logStatusInConsole('offline', details);
                return;
            }

            const details = {
                error: error instanceof Error
                    ? error.message
                    : 'Falha ao consultar status OCR.',
            };
            applyOcrBadgeState(badge, 'offline', details);
            logStatusInConsole('offline', details);
        } finally {
            window.clearTimeout(timeoutId);
            statusRequestInFlight = false;
        }
    };

    fetchStatus();
    window.ocrStatusPollIntervalId = window.setInterval(fetchStatus, pollMs);
};

window.initUiEnhancements = async () => {
    initializeTooltips();
    initializeOcrStatusBadge();
    initializeThemeToggle();
    initializeRefreshControls();

    await initializeFilePond();
};

window.addEventListener('toast', (event) => {
    const { type = 'info', message = '' } = event.detail ?? {};
    if (!message) {
        return;
    }

    toastr[type]?.(message) ?? toastr.info(message);
});

window.addEventListener('beforeunload', () => {
    NProgress.start();
});

document.addEventListener('DOMContentLoaded', () => {
    void window.initUiEnhancements().finally(() => {
        const ocrBadge = document.querySelector('[data-ocr-status-badge]');
        if (ocrBadge?.dataset.ocrStatusConsoleLog === '1') {
            console.info('[OCR SERVICE]', {
                message: 'Monitor OCR iniciado e escutando status na UI.',
                endpoint: ocrBadge.dataset.ocrStatusUrl ?? null,
                pollMs: Number.parseInt(ocrBadge.dataset.ocrStatusPollMs || '10000', 10),
                requestTimeoutMs: Number.parseInt(ocrBadge.dataset.ocrStatusRequestTimeoutMs || '5000', 10),
                checkedAt: new Date().toISOString(),
            });
        }

        NProgress.done();
    });
});

Alpine.start();
