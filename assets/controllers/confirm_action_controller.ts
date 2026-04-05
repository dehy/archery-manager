import { Controller } from '@hotwired/stimulus';

export default class ConfirmActionController extends Controller {
    static readonly values = {
        message: String,
    };

    declare readonly messageValue: string;

    confirm(event: Event): void {
        if (window.confirm(this.messageValue)) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();
    }
}
