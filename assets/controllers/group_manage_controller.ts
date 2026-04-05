import { Controller } from '@hotwired/stimulus';

type GroupActionResponse = {
    success: boolean;
    message?: string;
    error?: string;
};

export default class GroupManageController extends Controller {
    static readonly targets = [
        'alertContainer',
        'addForm',
        'removeForm',
    ];

    declare readonly alertContainerTarget: HTMLElement;
    declare readonly addFormTarget: HTMLFormElement;
    declare readonly removeFormTarget: HTMLFormElement;

    async handleClick(event: Event): Promise<void> {
        const target = event.target as HTMLElement | null;
        if (!target) {
            return;
        }

        const addButton = target.closest<HTMLButtonElement>('.add-member-btn');
        if (addButton) {
            await this.addMember(addButton);
            return;
        }

        const removeButton = target.closest<HTMLButtonElement>('.remove-member-btn');
        if (removeButton) {
            await this.removeMember(removeButton);
        }
    }

    filterAvailable(event: Event): void {
        const input = event.currentTarget as HTMLInputElement | null;
        const availableContainer = this.getAvailableLicenseesContainer();

        if (!input || !availableContainer) {
            return;
        }

        const searchTerm = input.value.toLowerCase();
        const items = availableContainer.querySelectorAll<HTMLElement>('.licensee-item');

        items.forEach((item: HTMLElement) => {
            const searchText = item.dataset.searchText ?? '';
            item.classList.toggle('d-none', !searchText.includes(searchTerm));
        });

        this.updateCounts();
    }

    private async addMember(button: HTMLButtonElement): Promise<void> {
        const licenseeId = button.dataset.licenseeId;
        const listItem = button.closest<HTMLElement>('.list-group-item');

        if (!licenseeId || !listItem) {
            return;
        }

        this.setButtonState(button, true, 'fa-spinner fa-spin', 'Ajout...');

        try {
            this.setFormLicenseeId(this.addFormTarget, licenseeId);
            const response = await this.postForm(this.addFormTarget);

            if (!response.success) {
                this.showAlert(response.error ?? 'Une erreur est survenue', 'danger');
                this.setButtonState(button, false, 'fa-plus', 'Ajouter');
                return;
            }

            const movedItem = listItem.cloneNode(true) as HTMLElement;
            const movedButton = movedItem.querySelector<HTMLButtonElement>('button');
            if (movedButton) {
                movedButton.className = 'btn btn-sm btn-outline-danger remove-member-btn';
                movedButton.dataset.licenseeId = button.dataset.licenseeId;
                movedButton.dataset.licenseeName = button.dataset.licenseeName;
                this.setButtonLabel(movedButton, 'fa-minus', 'Retirer');
            }

            const groupMembersContainer = this.getGroupMembersContainer();
            if (!groupMembersContainer) {
                this.showAlert('Impossible de mettre a jour la liste des membres.', 'danger');
                this.setButtonState(button, false, 'fa-plus', 'Ajouter');
                return;
            }

            groupMembersContainer.appendChild(movedItem);
            listItem.remove();
            this.showAlert(response.message ?? 'Membre ajouté.');
            this.updateCounts();
        } catch (error) {
            console.error('Erreur:', error);
            this.showAlert('Une erreur est survenue', 'danger');
            this.setButtonState(button, false, 'fa-plus', 'Ajouter');
        }
    }

    private async removeMember(button: HTMLButtonElement): Promise<void> {
        const licenseeId = button.dataset.licenseeId;
        const licenseeName = button.dataset.licenseeName ?? '';
        const listItem = button.closest<HTMLElement>('.list-group-item');

        if (!licenseeId || !listItem) {
            return;
        }

        if (!globalThis.confirm(`Êtes-vous sûr de vouloir retirer ${licenseeName} du groupe ?`)) {
            return;
        }

        this.setButtonState(button, true, 'fa-spinner fa-spin', 'Suppression...');

        try {
            this.setFormLicenseeId(this.removeFormTarget, licenseeId);
            const response = await this.postForm(this.removeFormTarget);

            if (!response.success) {
                this.showAlert(response.error ?? 'Une erreur est survenue', 'danger');
                this.setButtonState(button, false, 'fa-minus', 'Retirer');
                return;
            }

            const movedItem = listItem.cloneNode(true) as HTMLElement;
            const movedButton = movedItem.querySelector<HTMLButtonElement>('button');
            if (movedButton) {
                movedButton.className = 'btn btn-sm btn-outline-success add-member-btn';
                movedButton.dataset.licenseeId = button.dataset.licenseeId;
                movedButton.dataset.licenseeName = button.dataset.licenseeName;
                this.setButtonLabel(movedButton, 'fa-plus', 'Ajouter');
            }

            const availableContainer = this.getAvailableLicenseesContainer();
            if (!availableContainer) {
                this.showAlert('Impossible de mettre a jour la liste des licencies.', 'danger');
                this.setButtonState(button, false, 'fa-minus', 'Retirer');
                return;
            }

            availableContainer.appendChild(movedItem);
            listItem.remove();
            this.showAlert(response.message ?? 'Membre retiré.');
            this.updateCounts();
        } catch (error) {
            console.error('Erreur:', error);
            this.showAlert('Une erreur est survenue', 'danger');
            this.setButtonState(button, false, 'fa-minus', 'Retirer');
        }
    }

    private showAlert(message: string, type: 'success' | 'danger' = 'success'): void {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.setAttribute('role', 'alert');

        const messageNode = document.createTextNode(message);
        const closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.className = 'btn-close';
        closeButton.dataset.bsDismiss = 'alert';
        closeButton.setAttribute('aria-label', 'Close');

        alert.appendChild(messageNode);
        alert.appendChild(closeButton);
        this.alertContainerTarget.appendChild(alert);

        globalThis.setTimeout(() => {
            alert.remove();
        }, 5000);
    }

    private updateCounts(): void {
        const groupMembersContainer = this.getGroupMembersContainer();
        const availableContainer = this.getAvailableLicenseesContainer();
        const membersCountElement = document.getElementById('members-count');
        const availableCountElement = document.getElementById('available-count');

        if (!groupMembersContainer || !availableContainer || !membersCountElement || !availableCountElement) {
            return;
        }

        const membersCount = groupMembersContainer.querySelectorAll('.list-group-item').length;
        const availableCount = availableContainer.querySelectorAll('.list-group-item:not(.d-none)').length;

        membersCountElement.textContent = String(membersCount);
        availableCountElement.textContent = String(availableCount);
    }

    private setFormLicenseeId(form: HTMLFormElement, licenseeId: string): void {
        const field = form.querySelector<HTMLInputElement>('[name="group_member_action[licenseeId]"]');
        if (field) {
            field.value = licenseeId;
        }
    }

    private async postForm(form: HTMLFormElement): Promise<GroupActionResponse> {
        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
        });

        return response.json() as Promise<GroupActionResponse>;
    }

    private setButtonState(button: HTMLButtonElement, disabled: boolean, iconClass: string, label: string): void {
        button.disabled = disabled;
        this.setButtonLabel(button, iconClass, label);
    }

    private setButtonLabel(button: HTMLButtonElement, iconClass: string, label: string): void {
        button.replaceChildren();

        const icon = document.createElement('em');
        icon.className = `fa-solid ${iconClass}`;

        button.appendChild(icon);
        button.appendChild(document.createTextNode(` ${label}`));
    }

    private getGroupMembersContainer(): HTMLElement | null {
        return document.getElementById('group-members');
    }

    private getAvailableLicenseesContainer(): HTMLElement | null {
        return document.getElementById('available-licensees');
    }
}
