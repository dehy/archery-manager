import { Controller } from '@hotwired/stimulus';

export default class ClubApplicationController extends Controller {
    static readonly targets = ['club', 'closureMessage'];

    declare readonly clubTarget: HTMLSelectElement;
    declare readonly closureMessageTarget: HTMLElement;

    connect(): void {
        this.updateClosureMessage();
    }

    updateClosureMessage(): void {
        const selectedOption = this.clubTarget.selectedOptions[0];
        const message = selectedOption?.dataset.closureMessage ?? '';

        this.closureMessageTarget.textContent = message;
        this.closureMessageTarget.classList.toggle('d-none', '' === message);
    }
}
