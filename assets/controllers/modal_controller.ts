import {ActionEvent, Controller} from "@hotwired/stimulus";
import {Modal} from 'bootstrap';
import DOMPurify from 'dompurify';

/**
 * Generic Bootstrap modal that loads its content from a remote URL and
 * optionally submits an embedded form without leaving the page.
 *
 * Targets:
 *   - `modal`  (HTMLDivElement)    — the Bootstrap `.modal` root element
 *   - `title`  (HTMLHeadingElement)— the modal title (`<h5>` inside `.modal-header`)
 *   - `body`   (HTMLDivElement)    — the modal body where remote HTML is injected
 *   - `submit` (HTMLButtonElement) — the footer "Submit" button (hidden when the
 *               loaded content contains no `<form>`)
 *
 * Usage on a trigger element:
 *   <a href="/event/42/participation"
 *      data-action="click->modal#open"
 *      data-title="Participation"
 *      data-size="lg">       ← optional: sm | lg | xl
 *     Modifier
 *   </a>
 *
 * Open flow:
 *   1. `open()` prevents the default navigation, sets the title, and shows the
 *      Bootstrap modal with a "Chargement…" placeholder.
 *   2. The URL from `href` is fetched; the response HTML is injected into the
 *      body via `#setBody()`, which also parses and finds any embedded form.
 *   3. If a form is found, the Submit button is shown.
 *
 * Submit flow:
 *   1. `submit()` serialises the embedded form as `FormData` and POSTs it.
 *   2. If the server responds with a redirect, the origin is validated
 *      (SSRF / open-redirect guard) and the browser navigates there.
 *   3. If the server returns HTML (e.g. validation errors), it replaces the
 *      modal body so the user can correct the form inline.
 */
export default class ModalController extends Controller {
    static readonly targets = ["modal", "title", "body", "submit"];

    declare readonly modalTarget: HTMLDivElement;
    declare readonly titleTarget: HTMLHeadingElement;
    declare readonly bodyTarget: HTMLDivElement;
    declare readonly submitTarget: HTMLButtonElement;

    form: HTMLFormElement | null = null;

    open(event: ActionEvent) {
        event.preventDefault();
        event.stopImmediatePropagation();

        const clickedElement = event.currentTarget as HTMLAnchorElement;
        this.form = null;

        this.titleTarget.textContent = clickedElement.dataset.title ?? '';
        this.bodyTarget.textContent = 'Chargement...';
        this.submitTarget.classList.add('d-none');

        const modalSize: string|null = clickedElement.dataset.size ?? null;
        if (modalSize === 'sm' || modalSize === 'lg' || modalSize === 'xl') {
            this.modalTarget.className = 'modal modal-' + modalSize;
        } else {
            this.modalTarget.className = 'modal';
        }

        const url = clickedElement.href;
        if (url) {
            const modal = new Modal(this.modalTarget);
            modal.show();
            fetch(url).then(response => response.text()).then(html => {
                this.#setBody(html);
                if (null === this.#getForm(this.bodyTarget)) {
                    this.submitTarget.classList.add('d-none');
                } else {
                    this.submitTarget.classList.remove('d-none');
                }
            });
        }
    }

    async submit() {
        const modalForm = this.#getForm(this.bodyTarget);
        if (modalForm === null) {
            return;
        }

        try {
            const response = await fetch(modalForm.action, {
                method: modalForm.method,
                body: new FormData(modalForm),
            });

            if (response.redirected) {
                if (this.#isSafeRedirectUrl(response.url)) {
                    globalThis.location.href = response.url;
                    return;
                }
                console.warn('Blocked unsafe redirect URL:', response.url);
                this.#setBody(this.#renderBlockedRedirectError());
                return;
            }

            const html = await response.text();
            this.#setBody(html);
        } catch (error) {
            console.error('Modal submit failed', error);
            this.#setBody(this.#renderSubmitError());
        }
    }

    #setBody(html: string) {
        const clean = DOMPurify.sanitize(html, {RETURN_DOM_FRAGMENT: true});
        const form = this.#getForm(clean);
        if (form !== null) {
            this.form = form;
            this.submitTarget.classList.remove('d-none');
        } else {
            this.form = null;
            this.submitTarget.classList.add('d-none');
        }
        this.bodyTarget.replaceChildren(
            ...Array.from(clean.childNodes).map(node => document.adoptNode(node)),
        );
    }

    #getForm(element: HTMLElement|Document|DocumentFragment): HTMLFormElement | null {
        return element.querySelector('form');
    }

    #isSafeRedirectUrl(url: string): boolean {
        try {
            const redirectUrl = new URL(url, globalThis.location.href);
            return redirectUrl.origin === globalThis.location.origin;
        } catch {
            return false;
        }
    }

    #renderBlockedRedirectError(): string {
        return '<div class="alert alert-danger mb-0" role="alert">Redirection bloquée pour des raisons de sécurité. Merci de réessayer depuis la page principale.</div>';
    }

    #renderSubmitError(): string {
        return '<div class="alert alert-danger mb-0" role="alert">Une erreur est survenue pendant la soumission du formulaire. Merci de réessayer.</div>';
    }
}
