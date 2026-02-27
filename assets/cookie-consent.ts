import 'vanilla-cookieconsent/dist/cookieconsent.css';
import * as CookieConsent from 'vanilla-cookieconsent';
import axios from 'axios';

const http = axios.create({ baseURL: '' });

import config from './config';

const POLICY_VERSION: string = '2026-02-24';

declare global {
    var _paq: unknown[][] | undefined;
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

    globalThis._paq = globalThis._paq ?? [];
    const paq = globalThis._paq;

    // Always update the consent mode — this is the only call that legitimately
    // changes between invocations (cookieless → cookie or vice-versa).
    if (cookiesEnabled) {
        paq.push(['rememberConsentGiven']);
    } else {
        paq.push(['disableCookies']);
    }

    // One-time setup: tracking commands and script injection.
    // Guarded so that a second call (e.g. consent accepted after a cookieless
    // init) does not produce duplicate trackPageView / configuration pushes.
    if (!matomoInitialized) {
        matomoInitialized = true;

        const matomoUserId = config.get('matomoUserId') as string | undefined;
        if (matomoUserId) {
            paq.push(['setUserId', matomoUserId]);
        }

        paq.push(['trackPageView'], ['enableLinkTracking']);

        const baseUrl = matomoUrl.replace(/\/$/, '') + '/';
        paq.push(['setTrackerUrl', baseUrl + 'matomo.php'], ['setSiteId', matomoSiteId]);

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
    const acceptedServices = Object.values(prefs.acceptedServices).flat();

    let action: string;
    if (prefs.acceptType === 'all') {
        action = 'accepted';
    } else if (prefs.acceptType === 'necessary') {
        action = 'declined';
    } else {
        action = 'partial';
    }

    http
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
    // Start Matomo cookieless unless the user already has valid analytics consent.
    // When existing consent is valid, `onConsent` fires right after run() and
    // calls initMatomo(true), so we avoid a conflicting double-init.
    const hasValidAnalyticsConsent =
        CookieConsent.validConsent() && CookieConsent.acceptedCategory('analytics');

    if (!hasValidAnalyticsConsent) {
        initMatomo(false);
    }

    CookieConsent.run({
        revision: Number(POLICY_VERSION.replaceAll('-', '')),

        cookie: {
            name: 'cc_cookie',
            expiresAfterDays: 365,
        },

        guiOptions: {
            consentModal: {
                layout: 'box',
                position: 'middle center',
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
                // Revert Matomo to cookieless mode; _pk_* cookies are cleared by autoClear.
                globalThis._paq?.push(['forgetConsentGiven'], ['disableCookies']);
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
                services: {
                    matomo: {
                        label: 'Matomo Analytics',
                    },
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
