import { Controller } from '@hotwired/stimulus';

/**
 * Toggles between the "existing user" and "new user" field groups on the
 * licensee-management step-4 form.
 *
 * When the "existing" radio button is selected, `existingField` is shown and
 * `newField` is hidden (and vice-versa). The visibility is kept in sync both
 * on initial connect and on every subsequent change.
 *
 * Targets:
 *   - `existingRadio` (HTMLInputElement)  — the "existing" radio input
 *   - `existingField` (HTMLElement)       — the field group shown for existing users
 *   - `newField`      (HTMLElement)       — the field group shown for new users
 *
 * Usage:
 *   <form data-controller="user-choice"
 *         data-action="change->user-choice#update">
 *     <input type="radio" value="existing"
 *            data-user-choice-target="existingRadio"> Compte existant
 *     <input type="radio" value="new"> Nouveau compte
 *     <div data-user-choice-target="existingField">…</div>
 *     <div data-user-choice-target="newField">…</div>
 *   </form>
 */
export default class UserChoiceController extends Controller {
    static readonly targets = ['existingRadio', 'existingField', 'newField'];

    declare readonly existingRadioTarget: HTMLInputElement;
    declare readonly hasExistingRadioTarget: boolean;
    declare readonly existingFieldTarget: HTMLElement;
    declare readonly hasExistingFieldTarget: boolean;
    declare readonly newFieldTarget: HTMLElement;
    declare readonly hasNewFieldTarget: boolean;

    connect(): void {
        this.update();
    }

    update(): void {
        const shouldShowExisting = this.hasExistingRadioTarget && this.existingRadioTarget.checked;

        if (this.hasExistingFieldTarget) {
            this.existingFieldTarget.classList.toggle('d-none', !shouldShowExisting);
        }

        if (this.hasNewFieldTarget) {
            this.newFieldTarget.classList.toggle('d-none', shouldShowExisting);
        }
    }
}
