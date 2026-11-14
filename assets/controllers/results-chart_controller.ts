import { Controller } from '@hotwired/stimulus';

/**
 * Extension point for the Symfony UX Chart.js integration on the archer
 * results page.
 *
 * The controller listens for the `chartjs:pre-connect` and `chartjs:connect`
 * custom events dispatched by the `symfony/ux-chartjs` Stimulus bridge.
 * These hooks allow customising Chart.js options before the chart is created
 * and accessing the `Chart` instance after it is mounted.
 *
 * Currently the hooks are stubs; add configuration under `_onPreConnect` (to
 * tweak options such as plugins or scales) or interactions under `_onConnect`
 * (e.g. registering click handlers on the chart instance).
 *
 * Use on a `<canvas>` element rendered by the `chart()` Twig helper:
 *   {{ render_chart(chart, {'data-controller': 'results-chart'}) }}
 */
export default class ResultsChartController extends Controller<HTMLCanvasElement> {
    connect() {
        this.element.addEventListener('chartjs:pre-connect', this._onPreConnect);
        this.element.addEventListener('chartjs:connect', this._onConnect);
    }

    disconnect() {
        // You should always remove listeners when the controller is disconnected to avoid side effects
        this.element.removeEventListener('chartjs:pre-connect', this._onPreConnect);
        this.element.removeEventListener('chartjs:connect', this._onConnect);
    }

    _onPreConnect(event: Event) {
        // The chart is not yet created. In the redesigned interface, its axes take the colours of the current look
        // (tokens of assets/styles/next/_themes.scss): today's interface has no tokens and keeps Chart.js defaults.
        const styles = getComputedStyle(event.currentTarget as HTMLElement);
        const text = styles.getPropertyValue('--am-soft').trim();
        const grid = styles.getPropertyValue('--am-row').trim();
        if (!text || !grid) {
            return;
        }

        const options = (event as CustomEvent).detail.options;
        options.scales ??= {};
        for (const axis of ['x', 'y']) {
            options.scales[axis] ??= {};
            options.scales[axis].ticks = { ...options.scales[axis].ticks, color: text };
            options.scales[axis].grid = { ...options.scales[axis].grid, color: grid };
            options.scales[axis].border = { ...options.scales[axis].border, color: grid };
        }
    }

    _onConnect(event: Event) {
        // The chart was just created
        const chart = (event as CustomEvent).detail.chart;
    }
}