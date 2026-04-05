import { Controller } from '@hotwired/stimulus';

type GroupActionResponse = {
    success: boolean;
    message?: string;
    error?: string;
};

type MemberTransferConfig = {
    form: HTMLFormElement;
    loadingLabel: string;
    loadingIcon: string;
    idleLabel: string;
    idleIcon: string;
    nextLabel: string;
    nextIcon: string;
    nextButtonClass: string;
    targetContainer: () => HTMLElement | null;
    targetContainerError: string;
    defaultSuccessMessage: string;
};

/**
 * Handles the interactive group-membership management page.
 *
 * The page shows two lists side-by-side:
 *   - **Group members** (`#group-members`) — licensees currently in the group
 *   - **Available licensees** (`#available-licensees`) — licensees not yet in
 *     the group
 *
 * Users can move licensees between the two lists by clicking "Ajouter" /
 * "Retirer" buttons. Each click submits a hidden Symfony form via `fetch` and,
 * on success, moves the list item in the DOM without a full page reload.
 *
 * Targets:
 *   - `alertContainer` — where dismissible alert messages are injected
 *   - `addForm`        — hidden `<form>` that POSTs the add-member endpoint
 *   - `removeForm`     — hidden `<form>` that POSTs the remove-member endpoint
 *
 * Public actions (bound via `data-action`):
 *   - `handleClick`     — event-delegation entry point on the lists container;
 *                         detects whether an add or remove button was clicked
 *   - `filterAvailable` — live-search filter on the available licensees list
 *
 * The shared `transferMember()` private method drives both add and remove
 * flows; directional differences are supplied via a `MemberTransferConfig`
 * object so there is no duplicated logic.
 */
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
        await this.transferMember(button, {
            form: this.addFormTarget,
            loadingLabel: 'Ajout...',
            loadingIcon: 'fa-spinner fa-spin',
            idleLabel: 'Ajouter',
            idleIcon: 'fa-plus',
            nextLabel: 'Retirer',
            nextIcon: 'fa-minus',
            nextButtonClass: 'btn btn-sm btn-outline-danger remove-member-btn',
            targetContainer: () => this.getGroupMembersContainer(),
            targetContainerError: 'Impossible de mettre à jour la liste des membres.',
            defaultSuccessMessage: 'Membre ajouté.',
        });
    }

    private async removeMember(button: HTMLButtonElement): Promise<void> {
        const licenseeName = button.dataset.licenseeName ?? '';
        if (!globalThis.confirm(`Êtes-vous sûr de vouloir retirer ${licenseeName} du groupe ?`)) {
            return;
        }

        await this.transferMember(button, {
            form: this.removeFormTarget,
            loadingLabel: 'Suppression...',
            loadingIcon: 'fa-spinner fa-spin',
            idleLabel: 'Retirer',
            idleIcon: 'fa-minus',
            nextLabel: 'Ajouter',
            nextIcon: 'fa-plus',
            nextButtonClass: 'btn btn-sm btn-outline-success add-member-btn',
            targetContainer: () => this.getAvailableLicenseesContainer(),
            targetContainerError: 'Impossible de mettre à jour la liste des licenciés.',
            defaultSuccessMessage: 'Membre retiré.',
        });
    }

    private async transferMember(button: HTMLButtonElement, config: MemberTransferConfig): Promise<void> {
        const licenseeId = button.dataset.licenseeId;
        const listItem = button.closest<HTMLElement>('.list-group-item');

        if (!licenseeId || !listItem) {
            return;
        }

        this.setButtonState(button, true, config.loadingIcon, config.loadingLabel);

        try {
            this.setFormLicenseeId(config.form, licenseeId);
            const response = await this.postForm(config.form);

            if (!response.success) {
                this.showAlert(response.error ?? 'Une erreur est survenue', 'danger');
                this.setButtonState(button, false, config.idleIcon, config.idleLabel);
                return;
            }

            const targetContainer = config.targetContainer();
            if (!targetContainer) {
                this.showAlert(config.targetContainerError, 'danger');
                this.setButtonState(button, false, config.idleIcon, config.idleLabel);
                return;
            }

            const movedItem = this.buildMovedItem(listItem, button, config);
            targetContainer.appendChild(movedItem);

            listItem.remove();
            this.showAlert(response.message ?? config.defaultSuccessMessage);
            this.updateCounts();
        } catch (error) {
            console.error('Erreur:', error);
            this.showAlert('Une erreur est survenue', 'danger');
            this.setButtonState(button, false, config.idleIcon, config.idleLabel);
        }
    }

    private buildMovedItem(
        listItem: HTMLElement,
        sourceButton: HTMLButtonElement,
        config: MemberTransferConfig,
    ): HTMLElement {
        const movedItem = listItem.cloneNode(true) as HTMLElement;
        const movedButton = movedItem.querySelector<HTMLButtonElement>('button');

        if (movedButton) {
            movedButton.className = config.nextButtonClass;
            movedButton.dataset.licenseeId = sourceButton.dataset.licenseeId;
            movedButton.dataset.licenseeName = sourceButton.dataset.licenseeName;
            this.setButtonLabel(movedButton, config.nextIcon, config.nextLabel);
        }

        return movedItem;
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
