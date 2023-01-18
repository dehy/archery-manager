import {ActionEvent, Controller} from "@hotwired/stimulus";
import {Modal} from 'bootstrap';

export default class extends Controller {
    static targets = ["modal", "title", "body"];

    declare readonly modalTarget: HTMLDivElement;
    declare readonly titleTarget: HTMLHeadingElement;
    declare readonly bodyTarget: HTMLDivElement;

    connect() {
    }

    open(event: ActionEvent) {
        event.preventDefault();
        event.stopImmediatePropagation();

        const clickedElement = event.currentTarget as HTMLAnchorElement;

        this.titleTarget.innerHTML = clickedElement.dataset.title ?? '';
        this.bodyTarget.innerHTML = '';

        const url = clickedElement.href;
        if (url) {
            const modal = new Modal(this.modalTarget);
            modal.show();
            fetch(url).then(response => response.text()).then(html => {
                this.bodyTarget.innerHTML = html;
            });
        }
    }
}
