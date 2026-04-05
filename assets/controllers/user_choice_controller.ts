import { Controller } from '@hotwired/stimulus';

export default class UserChoiceController extends Controller {
    connect(): void {
        this.update();
    }

    update(): void {
        const shouldShowExisting = this.existingRadio?.checked ?? false;
        this.existingField?.classList.toggle('d-none', !shouldShowExisting);
        this.newField?.classList.toggle('d-none', shouldShowExisting);
    }

    private get existingRadio(): HTMLInputElement | null {
        return this.element.querySelector('input[value="existing"]');
    }

    private get existingField(): HTMLElement | null {
        return this.element.querySelector('#existing_user_field');
    }

    private get newField(): HTMLElement | null {
        return this.element.querySelector('#new_user_field');
    }
}
