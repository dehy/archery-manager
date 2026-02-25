import 'vanilla-cookieconsent/dist/cookieconsent.css';
import * as CookieConsent from 'vanilla-cookieconsent';
import axios from 'axios';

import config from './config';

const POLICY_VERSION = '2026-02-25';

declare global {
    interface Window {
        _paq?: unknown[][];
    }
}

// ---------------------------------------------------------------------------
// Matomo initialisation
// ---------------------------------------------------------------------------

let matomoInitialized = false;

function initMatomo(cookiesEnabled: boolean): void {
    const matomoUrl = config.get('matomoUrl') as string | undefined;
    const matomoSiteId = config.get('matomoSiteId') as string | undefined;

    if (!matomoUrl || !matomoSiteId) {
        console.log('Matomo URL or Site ID not configured; skipping Matomo initialization.');
        return;
    }

    window._paq = window._paq ?? [];
    const paq = window._paq;

    if (!cookiesEnabled) {
        paq.push(['disableCookies']);
    } else {
        paq.push(['rememberConsentGiven']);
    }

    const matomoUserId = config.get('matomoUserId') as string | undefined;
    if (matomoUserId) {
        paq.push(['setUserId', matomoUserId]);
    }

    paq.push(['trackPageView']);
    paq.push(['enableLinkTracking']);

    const baseUrl = matomoUrl.replace(/\/$/, '') + '/';
    paq.push(['setTrackerUrl', baseUrl + 'matomo.php']);
    paq.push(['setSiteId', matomoSiteId]);

    // Only inject the tracker script once.
    if (!matomoInitialized) {
        matomoInitialized = true;
        const script = document.createElement('script');
        script.async = true;
        script.src = baseUrl + 'matomo.js';
        const firstScript = document.getElementsByTagName('script')[0];
        if (firstScript?.parentNode) {
            firstScript.parentNode.insertBefore(script, firstScript);
        }
    }
}

// ---------------------------------------------------------------------------
// Consent log
// ---------------------------------------------------------------------------

function postConsentLog(): void {
    const prefs = CookieConsent.getUserPreferences();
    const acceptedServices = (Object.values(prefs.acceptedServices) as string[][]).flat();

    let action: string;
    if (prefs.acceptType === 'all') {
        action = 'accepted';
    } else if (prefs.acceptType === 'necessary') {
        action = 'declined';
    } else {
        action = 'partial';
    }

    axios
        .post('/api/consent', {
            services: acceptedServices,
            action,
            policyVersion: POLICY_VERSION,
        })
        .catch((error: unknown) => {
            console.error('Failed to log consent:', error);
        });
}

// ---------------------------------------------------------------------------
// Bootstrap
// ---------------------------------------------------------------------------

document.addEventListener('DOMContentLoaded', () => {
    // Always start Matomo cookieless (CNIL-exempt — no consent needed).
    initMatomo(false);

    CookieConsent.run({
        revision: 1,

        cookie: {
            name: 'cc_cookie',
            expiresAfterDays: 365,
        },

        guiOptions: {
            consentModal: {
                layout: 'bar inline',
                position: 'bottom center',
            },
        },

        // Fired on first consent AND on every subsequent page load when consent is valid.
        onConsent: (): void => {
            if (CookieConsent.acceptedCategory('analytics')) {
                initMatomo(true);
            }
        },

        // Fired only the very first time the user expresses consent.
        onFirstConsent: (): void => {
            postConsentLog();
        },

        // Fired when the user changes their preferences.
        onChange: (): void => {
            postConsentLog();
            if (!CookieConsent.acceptedCategory('analytics')) {
                // Matomo will keep running cookieless; _pk_* cookies are cleared by autoClear.
                return;
            }
            initMatomo(true);
        },

        categories: {
            necessary: {
                enabled: true,
                readOnly: true,
            },
            analytics: {
                enabled: false,
                autoClear: {
                    cookies: [{ name: /^_pk_/ }],
                },
            },
        },

        language: {
            default: 'fr',
            translations: {
                fr: {
                    consentModal: {
                        title: 'Nous utilisons des cookies',
                        description:
                            'Ce site utilise Matomo pour mesurer l\'audience. Par défaut, il fonctionne sans cookies (exempté CNIL). En acceptant, vous autorisez les cookies de suivi pour une analyse plus précise.',
                        acceptAllBtn: 'Tout accepter',
                        acceptNecessaryBtn: 'Refuser',
                        showPreferencesBtn: 'Gérer les préférences',
                        footer: '<a href="/politique-de-confidentialite">Politique de confidentialité</a>',
                    },
                    preferencesModal: {
                        title: 'Gérer les cookies',
                        acceptAllBtn: 'Tout accepter',
                        acceptNecessaryBtn: 'Tout refuser',
                        savePreferencesBtn: 'Enregistrer mes choix',
                        closeIconLabel: 'Fermer',
                        sections: [
                            {
                                description:
                                    'Gérez vos préférences cookies. Vous pouvez modifier votre choix à tout moment.',
                            },
                            {
                                title: 'Cookies nécessaires',
                                description:
                                    'Ces cookies sont indispensables au fonctionnement du site et ne peuvent pas être désactivés.',
                                linkedCategory: 'necessary',
                                cookieTable: {
                                    headers: {
                                        name: 'Nom',
                                        description: 'Description',
                                        duration: 'Durée',
                                    },
                                    body: [
                                        {
                                            name: 'PHPSESSID',
                                            description: 'Session utilisateur',
                                            duration: 'Session',
                                        },
                                        {
                                            name: 'REMEMBERME',
                                            description: 'Maintien de la connexion',
                                            duration: '7 jours',
                                        },
                                        {
                                            name: 'cc_cookie',
                                            description: 'Préférences de consentement',
                                            duration: '365 jours',
                                        },
                                    ],
                                },
                            },
                            {
                                title: "Cookies de mesure d'audience",
                                description:
                                    "Matomo Analytics, hébergé en France. Sans consentement, aucun cookie n'est déposé (mode exempté CNIL). Avec consentement, des cookies de suivi <code>_pk_*</code> sont utilisés.",
                                linkedCategory: 'analytics',
                                cookieTable: {
                                    headers: {
                                        name: 'Nom',
                                        description: 'Description',
                                        duration: 'Durée',
                                    },
                                    body: [
                                        {
                                            name: '_pk_id.*',
                                            description: 'Identifiant visiteur Matomo',
                                            duration: '13 mois',
                                        },
                                        {
                                            name: '_pk_ses.*',
                                            description: 'Session Matomo',
                                            duration: '30 minutes',
                                        },
                                    ],
                                },
                            },
                            {
                                title: 'Plus d\'informations',
                                description:
                                    'Consultez notre <a href="/politique-de-confidentialite">politique de confidentialité</a> pour plus de détails.',
                            },
                        ],
                    },
                },
            },
        },
    });
});
