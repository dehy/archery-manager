import {ActionEvent, Controller} from "@hotwired/stimulus";
import {Modal} from 'bootstrap';

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

        this.titleTarget.innerHTML = clickedElement.dataset.title ?? '';
        this.bodyTarget.innerHTML = 'Chargement...';
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
                    window.location.href = response.url;
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
        const doc = new DOMParser().parseFromString(html, "text/html");
        const form = this.#getForm(doc);
        if (form !== null) {
            this.form = form;
            this.submitTarget?.classList.remove('d-none');
        } else {
            this.form = null;
            this.submitTarget.classList.add('d-none');
        }
        this.bodyTarget.innerHTML = doc.documentElement.outerHTML;
    }

    #getForm(element: HTMLElement|Document): HTMLFormElement | null {
        const forms = element.getElementsByTagName('form');
        return forms.length > 0 ? forms.item(0) : null;
    }

    #isSafeRedirectUrl(url: string): boolean {
        try {
            const redirectUrl = new URL(url, window.location.href);
            return redirectUrl.origin === window.location.origin;
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
