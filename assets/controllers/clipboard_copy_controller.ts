import { Controller } from '@hotwired/stimulus';

/**
 * Copies a text value to the system clipboard and gives the user visual feedback.
 *
 * Targets:
 *   - `source` (HTMLInputElement) — the input whose value is copied
 *   - `label`  (HTMLSpanElement)  — the button label that temporarily shows "Copié"
 *
 * Usage:
 *   <div data-controller="clipboard-copy">
 *     <input data-clipboard-copy-target="source" value="..." readonly>
 *     <button data-action="click->clipboard-copy#copy">
 *       <span data-clipboard-copy-target="label">Copier</span>
 *     </button>
 *   </div>
 *
 * Flow:
 *   1. User clicks the button → `copy()` is called.
 *   2. The source value is written to the clipboard via the Clipboard API.
 *   3. On success the label changes to "Copié" for 2 seconds, then resets.
 *   4. On failure (e.g. no clipboard permission) the label silently resets.
 *   5. A pending reset timer is always cancelled before starting a new one so
 *      rapid repeated clicks do not overlap.
 */
export default class ClipboardCopyController extends Controller {
    static readonly targets = ['source', 'label'];

    declare readonly sourceTarget: HTMLInputElement;
    declare readonly labelTarget: HTMLSpanElement;

    private resetTimeoutId?: ReturnType<typeof globalThis.setTimeout>;

    disconnect(): void {
        if (this.resetTimeoutId !== undefined) {
            globalThis.clearTimeout(this.resetTimeoutId);
            this.resetTimeoutId = undefined;
        }
    }

    async copy(): Promise<void> {
        if (this.resetTimeoutId !== undefined) {
            globalThis.clearTimeout(this.resetTimeoutId);
            this.resetTimeoutId = undefined;
        }

        try {
            await navigator.clipboard.writeText(this.sourceTarget.value);
            this.labelTarget.textContent = 'Copié';
            this.resetTimeoutId = globalThis.setTimeout(() => {
                this.labelTarget.textContent = 'Copier';
                this.resetTimeoutId = undefined;
            }, 2000);
        } catch {
            this.labelTarget.textContent = 'Copier';
        }
    }
}
