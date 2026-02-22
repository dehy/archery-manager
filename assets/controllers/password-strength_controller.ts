import { Controller } from "@hotwired/stimulus";
import zxcvbn from "zxcvbn";

/**
 * Stimulus controller for real-time password strength feedback.
 * 
 * Usage in template:
 * <div data-controller="password-strength">
 *   <input type="password" data-password-strength-target="input" data-action="input->password-strength#checkStrength">
 *   <div data-password-strength-target="meter" style="display: none;">
 *     <div class="progress" style="height: 10px;">
 *       <div data-password-strength-target="bar" class="progress-bar"></div>
 *     </div>
 *     <small data-password-strength-target="text" class="text-muted"></small>
 *   </div>
 * </div>
 */
export default class PasswordStrengthController extends Controller {
    static readonly targets = ["input", "meter", "bar", "text"];
    static readonly values = {
        minScore: { type: Number, default: 2 }
    };

    declare readonly inputTarget: HTMLInputElement;
    declare readonly meterTarget: HTMLDivElement;
    declare readonly barTarget: HTMLDivElement;
    declare readonly textTarget: HTMLElement;
    declare readonly minScoreValue: number;

    private readonly strengthLabels = ["Très faible", "Faible", "Moyen", "Bon", "Fort"];
    private readonly strengthColors = [
        "bg-danger",      // 0: Very weak (red)
        "bg-warning",     // 1: Weak (orange)
        "bg-warning",     // 2: Fair (yellow) 
        "bg-info",        // 3: Good (light blue)
        "bg-success"      // 4: Strong (green)
    ];

    connect(): void {
        // Initial check if field has value
        if (this.inputTarget.value) {
            this.checkStrength();
        }
    }

    checkStrength(): void {
        const password = this.inputTarget.value;

        // Hide meter if password is empty
        if (!password || password.length === 0) {
            this.meterTarget.style.display = "none";
            return;
        }

        // Show meter
        this.meterTarget.style.display = "block";

        // Get user inputs for personalized checking (optional)
        const userInputs = this.#getUserInputs();

        // Analyze password strength
        const result = zxcvbn(password, userInputs);
        const score = result.score; // 0-4

        // Update progress bar
        const width = ((score + 1) * 20); // 20%, 40%, 60%, 80%, 100%
        this.barTarget.style.width = `${width}%`;

        // Remove all color classes
        this.strengthColors.forEach(colorClass => {
            this.barTarget.classList.remove(colorClass);
        });

        // Add current color class
        this.barTarget.classList.add(this.strengthColors[score]);

        // Update text feedback
        let feedbackText = this.strengthLabels[score];

        // Add checkmark if meets minimum requirement
        if (score >= this.minScoreValue) {
            feedbackText += " ✓";
        }

        // Add warning if available
        if (result.feedback.warning) {
            feedbackText += ` — ${result.feedback.warning}`;
        }

        this.textTarget.textContent = feedbackText;
    }

    /**
     * Extract user inputs from form for personalized password checking.
     * This helps zxcvbn detect if password contains personal information.
     */
    #getUserInputs(): string[] {
        const inputs: string[] = [];
        const form = this.inputTarget.closest("form");

        if (!form) {
            return inputs;
        }

        // Try to get email
        const emailInput = form.querySelector<HTMLInputElement>("input[type='email']");
        if (emailInput?.value) {
            inputs.push(emailInput.value);
        }

        // Try to get first name
        const firstnameInput = form.querySelector<HTMLInputElement>("input[name*='firstname'], input[id*='firstname']");
        if (firstnameInput?.value) {
            inputs.push(firstnameInput.value);
        }

        // Try to get last name
        const lastnameInput = form.querySelector<HTMLInputElement>("input[name*='lastname'], input[id*='lastname']");
        if (lastnameInput?.value) {
            inputs.push(lastnameInput.value);
        }

        return inputs;
    }
}
