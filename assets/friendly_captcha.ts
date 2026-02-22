import { FriendlyCaptchaSDK } from '@friendlycaptcha/sdk';

document.addEventListener('DOMContentLoaded', function () {
    const captchaElement = document.getElementById('frc-captcha') as HTMLDivElement | null;
    const submitButton = document.getElementById('login-submit-button') as HTMLButtonElement | null;

    if (captchaElement && submitButton) {
        // Disable submit button until CAPTCHA is solved
        submitButton.disabled = true;
        submitButton.innerHTML = '<em class="fa-solid fa-spinner fa-spin me-2"></em>Vérification en cours...';
        submitButton.classList.remove('btn-primary');
        submitButton.classList.add('btn-secondary');

        const sdk = new FriendlyCaptchaSDK();
        const widget = sdk.createWidget({
            element: captchaElement,
            sitekey: captchaElement.dataset.sitekey ?? '',
        });

        widget.addEventListener('frc:widget.complete', () => {
            submitButton.disabled = false;
            submitButton.innerHTML = '<em class="fa-solid fa-arrow-right-to-bracket me-2"></em>Se connecter';
            submitButton.classList.remove('btn-secondary');
            submitButton.classList.add('btn-primary');
        });

        widget.addEventListener('frc:widget.error', () => {
            submitButton.innerHTML = '<em class="fa-solid fa-exclamation-triangle me-2"></em>Erreur de vérification';
        });

        widget.addEventListener('frc:widget.expire', () => {
            submitButton.disabled = true;
            submitButton.innerHTML = '<em class="fa-solid fa-spinner fa-spin me-2"></em>Vérification en cours...';
            submitButton.classList.remove('btn-primary');
            submitButton.classList.add('btn-secondary');
        });
    }
});