import { Controller } from '@hotwired/stimulus';

export default class LicenseeChoiceController extends Controller {
    static readonly targets = ['syncRadio', 'field', 'input'];

    declare readonly syncRadioTarget: HTMLInputElement;
    declare readonly fieldTarget: HTMLElement;
    declare readonly inputTarget: HTMLInputElement;

    connect(): void {
        this.update();
    }

    update(): void {
        const shouldDisplayFfta = this.syncRadioTarget.checked;
        this.fieldTarget.classList.toggle('d-none', !shouldDisplayFfta);
        this.inputTarget.required = shouldDisplayFfta;
    }
}
