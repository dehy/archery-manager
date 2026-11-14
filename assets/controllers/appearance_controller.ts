import { Controller } from '@hotwired/stimulus';

/**
 * Live preview of the Apparence page (user/appearance_next.html.twig): as the theme or mode radios change, the page
 * takes the chosen look at once (data-am-theme / data-am-mode on <html>, read by assets/styles/next/_themes.scss)
 * and the theme previews follow the mode. Nothing is saved until the form is sent; without JavaScript the form still
 * works with a normal post.
 *
 * Targets:
 *   - `preview` (HTMLElement, many) — the theme previews, carrying their own data-am-theme
 *   - `help`    (HTMLElement)       — the sentence under the mode, from the radio's data-help
 *
 * Usage:
 *   <form data-controller="appearance" data-action="change->appearance#apply">
 *     <input type="radio" name="theme" value="soft"> …
 *     <input type="radio" name="mode" value="system" data-help="…"> …
 *     <div data-appearance-target="preview" data-am-theme="soft" data-am-mode="system"></div>
 *     <p data-appearance-target="help"></p>
 *   </form>
 */
export default class AppearanceController extends Controller<HTMLFormElement> {
    static readonly targets = ['preview', 'help'];

    declare readonly previewTargets: HTMLElement[];
    declare readonly helpTarget: HTMLElement;
    declare readonly hasHelpTarget: boolean;

    apply(): void {
        const theme = this.checked('theme');
        const mode = this.checked('mode');
        const root = document.documentElement;

        if (theme) {
            root.dataset.amTheme = theme.value;
        }

        if (mode) {
            root.dataset.amMode = mode.value;
            this.previewTargets.forEach((preview) => {
                preview.dataset.amMode = mode.value;
            });
            if (this.hasHelpTarget && mode.dataset.help) {
                this.helpTarget.textContent = mode.dataset.help;
            }
        }
    }

    private checked(name: string): HTMLInputElement | null {
        return this.element.querySelector<HTMLInputElement>(`input[name="${name}"]:checked`);
    }
}
