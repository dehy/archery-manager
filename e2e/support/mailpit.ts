import { expect, type APIRequestContext } from '@playwright/test';

/** Mailpit catches every e-mail the app sends during the run (docker-compose.e2e.yml) */
const MAILPIT_URL = process.env.MAILPIT_URL ?? 'http://localhost:8025';

interface MessageSummary {
    ID: string;
    Subject: string;
}

/** Waits for the latest e-mail sent to `to` (optionally with a subject containing `subject`) and returns its HTML */
export async function latestEmailTo(
    request: APIRequestContext,
    to: string,
    subject?: string,
): Promise<{ subject: string; html: string }> {
    let found: MessageSummary | undefined;
    await expect
        .poll(
            async () => {
                const response = await request.get(`${MAILPIT_URL}/api/v1/search`, {
                    params: { query: `to:"${to}"`, limit: 10 },
                });
                const { messages } = (await response.json()) as { messages: MessageSummary[] };
                found = messages.find((message) => !subject || message.Subject.includes(subject));
                return found?.ID;
            },
            { message: `an e-mail to ${to}${subject ? ` about "${subject}"` : ''}`, timeout: 15_000 },
        )
        .toBeTruthy();
    const message = await (await request.get(`${MAILPIT_URL}/api/v1/message/${found!.ID}`)).json();
    return { subject: message.Subject as string, html: message.HTML as string };
}

/** The first link of an e-mail whose address contains `path` (e.g. "/verify/email") */
export function linkIn(html: string, path: string): string {
    const href = [...html.matchAll(/href="([^"]+)"/g)].map((match) => match[1].replaceAll('&amp;', '&')).find((url) => url.includes(path));
    expect(href, `a link to ${path} in the e-mail`).toBeTruthy();
    // E-mails link to APP_URL; keep only the path so the browser stays on the tested host
    const url = new URL(href!, 'http://placeholder');
    return url.pathname + url.search;
}
