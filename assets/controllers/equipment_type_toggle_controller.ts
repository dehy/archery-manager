import { Controller } from '@hotwired/stimulus';

export default class EquipmentTypeToggleController extends Controller {
    connect(): void {
        this.typeField?.addEventListener('change', this.update);
        this.update();
    }

    disconnect(): void {
        this.typeField?.removeEventListener('change', this.update);
    }

    readonly update = (): void => {
        const selectedType = this.typeField?.value;
        this.bowFields?.classList.toggle('d-none', selectedType !== 'bow');
        this.arrowFields?.classList.toggle('d-none', selectedType !== 'arrows');
    };

    private get typeField(): HTMLSelectElement | null {
        return this.element.querySelector('[name="club_equipment[type]"]');
    }

    private get bowFields(): HTMLElement | null {
        return this.element.querySelector('#bow-fields');
    }

    private get arrowFields(): HTMLElement | null {
        return this.element.querySelector('#arrow-fields');
    }
}
