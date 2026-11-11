import { Controller } from '@hotwired/stimulus';

/**
 * Shows the picture chosen in a file input before it is uploaded.
 *
 * Reads it as a data: URL, which the CSP (img-src) already allows; blob: URLs are not.
 *
 * Usage:
 *   <div data-controller="picture-preview">
 *     <img data-picture-preview-target="image">
 *     <input type="file" data-picture-preview-target="input" data-action="picture-preview#preview">
 *   </div>
 */
export default class PicturePreviewController extends Controller<HTMLElement> {
    static readonly targets = ['input', 'image'];

    declare readonly inputTarget: HTMLInputElement;
    declare readonly imageTarget: HTMLImageElement;

    preview(): void {
        const file = this.inputTarget.files?.[0];
        if (!file?.type.startsWith('image/')) {
            return;
        }

        const reader = new FileReader();
        reader.addEventListener('load', () => {
            if (typeof reader.result === 'string') {
                this.imageTarget.src = reader.result;
            }
        });
        reader.readAsDataURL(file);
    }
}
