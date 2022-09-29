import {Controller} from "@hotwired/stimulus";
import {Modal} from 'bootstrap';

export default class extends Controller {
    static targets = ["modal", "title", "body"];

    connect() {
        console.log("hello modal");
    }

    open(event) {
        event.preventDefault();
        event.stopImmediatePropagation();
        
        this.titleTarget.innerHTML = event.currentTarget.dataset.title;
        this.bodyTarget.innerHTML = "";

        const url = event.currentTarget.href;
        if (url) {
            const modal = new Modal(this.modalTarget);
            modal.show();
            fetch(url).then(response => response.text()).then(html => {
                this.bodyTarget.innerHTML = html;
            });
        }
    }
}
