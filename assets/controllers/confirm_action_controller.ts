import { Controller } from '@hotwired/stimulus';

/**
 * Intercepts a click (or any other action) and shows a native confirmation
 * dialog before allowing the event to continue.
 *
 * If the user dismisses the dialog, `preventDefault()` and
 * `stopImmediatePropagation()` are called so that the underlying link/form
 * submission is cancelled.
 *
 * Values:
 *   - `message` (String) — the question shown in the confirm dialog
 *
 * Usage:
 *   <a href="/delete/42"
 *      data-controller="confirm-action"
 *      data-confirm-action-message-value="Êtes-vous sûr ?"
 *      data-action="click->confirm-action#confirm">
 *     Supprimer
 *   </a>
 */
export default class ConfirmActionController extends Controller {
    static readonly values = {
        message: String,
    };

    declare readonly messageValue: string;

    confirm(event: Event): void {
        if (globalThis.confirm(this.messageValue)) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();
    }
}
