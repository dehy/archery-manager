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

    submit() {
        const modalForm = this.#getForm(this.bodyTarget);
        if (modalForm !== null) {
            fetch(modalForm.action, {
                method: modalForm.method,
                body: new FormData(modalForm)
            })
                .then(response => {
                    if (response.redirected) {
                        window.location.href = response.url;
                        throw new Error('Redirecting...');
                    }
                    return response.text();
                })
                .then(html => this.#setBody(html))
        } else {
            console.log("No form in the modal");
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
}
