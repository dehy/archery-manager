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
export default class extends Controller<HTMLCanvasElement> {
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
        // The chart is not yet created
        const options = (event as CustomEvent).detail.options;
    }

    _onConnect(event: Event) {
        // The chart was just created
        const chart = (event as CustomEvent).detail.chart;
    }
}