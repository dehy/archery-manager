import { Controller } from '@hotwired/stimulus';

/**
 * Handles dynamic cascading between license age category, category, and type fields.
 * 
 * FFTA Rules:
 * - Age Category determines Category (U11→Poussin, U13-U21→Jeunes, S1-S3→Adultes)
 * - Category determines available License Types
 * - User can only select valid combinations
 */
export default class LicenseFormController extends Controller {
    static readonly targets = ['ageCategory', 'category', 'type'];

    declare readonly ageCategoryTarget: HTMLSelectElement;
    declare readonly categoryTarget: HTMLSelectElement;
    declare readonly typeTarget: HTMLSelectElement;

    private ageCategoryMapping: Record<string, string> = {};
    private categoryTypesMapping: Record<string, string[]> = {};

    connect(): void {
        // Load mappings from data attributes
        const ageCategoryEl = this.ageCategoryTarget;
        const categoryEl = this.categoryTarget;

        if (ageCategoryEl.dataset.ageCategoryMapping) {
            this.ageCategoryMapping = JSON.parse(ageCategoryEl.dataset.ageCategoryMapping);
        }

        if (categoryEl.dataset.categoryTypesMapping) {
            this.categoryTypesMapping = JSON.parse(categoryEl.dataset.categoryTypesMapping);
        }

        // Add event listeners
        this.ageCategoryTarget.addEventListener('change', () => this.updateCategoryFromAgeCategory());
        this.categoryTarget.addEventListener('change', () => this.updateTypeFromCategory());

        // Initialize on load (in case form has existing data)
        this.updateTypeFromCategory();
    }

    /**
     * When age category changes, automatically set the category and update available types.
     */
    private updateCategoryFromAgeCategory(): void {
        const selectedAgeCategory = this.ageCategoryTarget.value;
        
        if (!selectedAgeCategory) {
            return;
        }

        // Find the category for this age category
        const category = this.ageCategoryMapping[selectedAgeCategory];
        
        if (category) {
            // Set the category
            this.categoryTarget.value = category;
            
            // Trigger change event to update types
            this.categoryTarget.dispatchEvent(new Event('change'));
        }
    }

    /**
     * When category changes, filter available license types.
     */
    private updateTypeFromCategory(): void {
        const selectedCategory = this.categoryTarget.value;
        
        if (!selectedCategory) {
            // No category selected, enable all types
            this.enableAllTypeOptions();
            return;
        }

        const validTypes = this.categoryTypesMapping[selectedCategory] || [];
        
        // Disable/enable type options based on category
        Array.from(this.typeTarget.options).forEach((option: HTMLOptionElement) => {
            if (option.value === '') {
                // Keep placeholder/empty option enabled
                option.disabled = false;
            } else if (validTypes.includes(option.value)) {
                option.disabled = false;
            } else {
                option.disabled = true;
            }
        });

        // If current selection is invalid, clear it
        if (this.typeTarget.value && !validTypes.includes(this.typeTarget.value)) {
            this.typeTarget.value = '';
        }

        // If only one valid type, auto-select it
        if (validTypes.length === 1) {
            this.typeTarget.value = validTypes[0];
        }
    }

    /**
     * Enable all type options (used when no category is selected).
     */
    private enableAllTypeOptions(): void {
        Array.from(this.typeTarget.options).forEach((option: HTMLOptionElement) => {
            option.disabled = false;
        });
    }
}
