import { Controller } from '@hotwired/stimulus';
import TomSelect from 'tom-select';

type UserSearchResult = {
    id: number;
    label: string;
};

type UserSearchResponse = {
    users: UserSearchResult[];
};

type PrincipalChangedDetail = {
    group: string;
    name: string;
    rowIndex: number;
};

type AccountOption = {
    label: string;
    value: string;
};

export default class UserAutocompleteController extends Controller {
    static readonly targets = ['relationship', 'select'];
    static readonly values = {
        group: String,
        name: String,
        rowIndex: Number,
        url: String,
    };

    declare readonly relationshipTarget: HTMLElement;
    declare readonly hasRelationshipTarget: boolean;
    declare readonly selectTarget: HTMLSelectElement;
    declare readonly groupValue: string;
    declare readonly hasGroupValue: boolean;
    declare readonly nameValue: string;
    declare readonly rowIndexValue: number;
    declare readonly urlValue: string;

    private tomSelect?: TomSelect;

    connect(): void {
        this.tomSelect = new TomSelect(this.selectTarget, {
            closeAfterSelect: true,
            create: false,
            dropdownParent: 'body',
            labelField: 'label',
            load: (
                query: string,
                callback: (options: AccountOption[], optgroups: AccountOption[]) => void,
            ) => void this.loadUsers(query, callback),
            loadThrottle: 250,
            maxItems: 1,
            maxOptions: 20,
            openOnFocus: true,
            searchField: ['label'],
            shouldLoad: (query: string) => query.trim().length >= 2,
            valueField: 'value',
            onChange: (value: string | number) => this.handleSelection(String(value)),
        });

        window.addEventListener('user-autocomplete:principal-changed', this.handlePrincipalChanged);
    }

    disconnect(): void {
        window.removeEventListener('user-autocomplete:principal-changed', this.handlePrincipalChanged);
        this.tomSelect?.destroy();
    }

    private async loadUsers(
        query: string,
        callback: (options: AccountOption[], optgroups: AccountOption[]) => void,
    ): Promise<void> {
        const url = new URL(this.urlValue, window.location.origin);
        url.searchParams.set('q', query);

        try {
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            if (!response.ok) {
                throw new Error(`La recherche a échoué (${response.status}).`);
            }

            const data = await response.json() as UserSearchResponse;
            callback(data.users.map((user) => ({
                label: user.label,
                value: `user:${user.id}`,
            })), []);
        } catch (error) {
            console.error(error);
            callback([], []);
        }
    }

    private handleSelection(choice: string): void {
        if (!this.hasGroupValue || ('new' !== choice && !choice.startsWith('user:'))) {
            return;
        }

        window.dispatchEvent(new CustomEvent<PrincipalChangedDetail>('user-autocomplete:principal-changed', {
            detail: {
                group: this.groupValue,
                name: this.nameValue,
                rowIndex: this.rowIndexValue,
            },
        }));
    }

    private readonly handlePrincipalChanged = (event: Event): void => {
        const detail = (event as CustomEvent<PrincipalChangedDetail>).detail;
        if (!this.hasGroupValue || detail.group !== this.groupValue || !this.tomSelect) {
            return;
        }

        const isPrincipal = detail.rowIndex === this.rowIndexValue;
        this.removeShareOptions();

        if (isPrincipal) {
            this.updatePrincipalRelationship();

            return;
        }

        const option = {
            label: `Utiliser le compte créé pour ${detail.name}`,
            value: `share:${detail.rowIndex}`,
        };
        this.tomSelect.addOption(option);
        this.tomSelect.setValue(option.value, true);
        this.updateLinkedRelationship(detail.name);
    };

    private removeShareOptions(): void {
        if (!this.tomSelect) {
            return;
        }

        for (const value of Object.keys(this.tomSelect.options)) {
            if (value.startsWith('share:')) {
                this.tomSelect.removeOption(value, true);
            }
        }
    }

    private updatePrincipalRelationship(): void {
        if (!this.hasRelationshipTarget) {
            return;
        }

        const linkedCount = document.querySelectorAll(
            `[data-controller~="user-autocomplete"][data-user-autocomplete-group-value="${CSS.escape(this.groupValue)}"]`,
        ).length - 1;
        const suffix = linkedCount > 1 ? 's liés' : ' lié';
        this.setRelationship('fa-user-group', `Compte principal · ${linkedCount} profil${suffix}`);
    }

    private updateLinkedRelationship(principalName: string): void {
        if (this.hasRelationshipTarget) {
            this.setRelationship('fa-arrow-right', `Profil lié au compte principal : ${principalName}`);
        }
    }

    private setRelationship(iconClass: string, label: string): void {
        const icon = document.createElement('em');
        icon.classList.add('fa-solid', iconClass, 'me-1');
        this.relationshipTarget.replaceChildren(icon, document.createTextNode(label));
    }
}
