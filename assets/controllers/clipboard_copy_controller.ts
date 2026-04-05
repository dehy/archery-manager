import { Controller } from '@hotwired/stimulus';

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
        await navigator.clipboard.writeText(this.sourceTarget.value);

        this.labelTarget.textContent = 'Copie';
        this.resetTimeoutId = globalThis.setTimeout(() => {
            this.labelTarget.textContent = 'Copier';
            this.resetTimeoutId = undefined;
        }, 2000);
    }
}
