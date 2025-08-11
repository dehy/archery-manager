// @ts-check
const { test, expect } = require('@playwright/test');

test('homepage', async ({ page }) => {
  await page.goto('https://localhost/');
  await expect(page).toHaveTitle('Welcome to API Platform!');
});

test('swagger', async ({ page }) => {
  await page.goto('https://localhost/docs');
  await expect(page).toHaveTitle('Archery Manager - API Platform');
  await expect(page.locator('.operation-tag-content > span')).toHaveCount(100);
});
