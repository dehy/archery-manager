import { Controller } from '@hotwired/stimulus';

/**
 * Toggles the visibility of the FFTA member-code input field on the licensee
 * creation / management form.
 *
 * When the "sync from FFTA" radio button is selected, the FFTA member-code
 * field is shown and marked as required. When any other option is selected,
 * the field is hidden and no longer required.
 *
 * Targets:
 *   - `syncRadio` (HTMLInputElement) — the radio button that represents
 *                  "sync from FFTA" mode
 *   - `field`     (HTMLElement)      — wrapper element containing the FFTA
 *                  member-code input (shown/hidden as a block)
 *   - `input`     (HTMLInputElement) — the actual text input whose `required`
 *                  attribute is toggled
 *
 * Usage:
 *   <div data-controller="licensee-choice">
 *     <input type="radio" data-licensee-choice-target="syncRadio"
 *            data-action="change->licensee-choice#update">
 *     <div data-licensee-choice-target="field">
 *       <input data-licensee-choice-target="input" type="text">
 *     </div>
 *   </div>
 */
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
