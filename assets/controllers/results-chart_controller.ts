import { Controller } from '@hotwired/stimulus';

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
        // TODO: configure chart options before creation via (event as CustomEvent).detail.options
    }

    _onConnect(event: Event) {
        // TODO: access the chart instance via (event as CustomEvent).detail.chart
    }
}