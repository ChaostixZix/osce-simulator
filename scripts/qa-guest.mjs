import { chromium } from 'playwright-core';
import fs from 'node:fs/promises';
import path from 'node:path';
import assert from 'node:assert/strict';

const base = process.env.QA_URL || 'http://100.100.22.72:8000';
const label = process.env.QA_LABEL || 'local';
const folder = path.resolve(process.env.QA_OUTPUT || '../screenshots');
const results = [];
await fs.mkdir(folder, { recursive: true });
const browser = await chromium.launch({
    executablePath: process.env.QA_BROWSER || '/home/ubuntu/.cache/ms-playwright/chromium-1217/chrome-linux/chrome',
    headless: true,
    args: ['--no-sandbox'],
});
try {
    for (const width of [1440, 390]) {
        const context = await browser.newContext({ viewport: { width, height: width === 1440 ? 1000 : 844 }, deviceScaleFactor: 1, colorScheme: 'light', reducedMotion: 'reduce' });
        const page = await context.newPage();
        const record = { label, width, url: base, consoleErrors: [], pageErrors: [], failedRequests: [], httpErrors: [], checks: [], screenshots: [] };
        results.push(record);
        page.on('console', message => { if (message.type() === 'error') record.consoleErrors.push(message.text()); });
        page.on('pageerror', error => record.pageErrors.push(error.message));
        page.on('requestfailed', request => record.failedRequests.push({ url: request.url().split('?')[0], error: request.failure()?.errorText }));
        page.on('response', response => { if (response.status() >= 400) record.httpErrors.push({ url: response.url().split('?')[0], status: response.status(), type: response.request().resourceType() }); });
        const shot = async (name, fullPage = true) => {
            await page.evaluate(() => document.fonts.ready);
            const filename = `${label}-${width}-${name}.png`;
            await page.screenshot({ path: path.join(folder, filename), fullPage });
            record.screenshots.push(filename);
        };
        const noOverflow = async () => assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), true, 'Horizontal overflow');
        const response = await page.goto(base, { waitUntil: 'networkidle' });
        assert.equal(response.status(), 200);
        if (base.startsWith('https:')) record.tls = await response.securityDetails();
        await noOverflow();
        await shot('landing');
        await page.keyboard.press('Tab');
        assert.equal(await page.locator(':focus').innerText(), 'Skip to content');
        record.checks.push('Keyboard skip link receives focus');
        await page.getByRole('button', { name: 'Use dark theme' }).click();
        assert.equal(await page.locator('html').getAttribute('class'), 'dark');
        await noOverflow();
        await shot('landing-dark');
        await page.getByRole('button', { name: 'Use light theme' }).click();
        await page.getByRole('link', { name: 'Privacy & your encounter' }).click();
        await page.getByRole('heading', { name: 'What the guest station stores' }).waitFor();
        await page.getByRole('link', { name: 'OSCE Simulator home' }).click();
        await page.getByRole('link', { name: 'Begin a guest station' }).click();
        await page.getByRole('heading', { name: 'Chest Pain Assessment' }).waitFor();
        await shot('briefing');
        await page.getByRole('button', { name: 'Start the encounter' }).click();
        await page.getByRole('heading', { name: 'Patient conversation' }).waitFor();
        record.sessionPath = new URL(page.url()).pathname;
        assert.equal(await page.getByRole('button', { name: 'Send', exact: true }).isDisabled(), true);
        record.checks.push('Landing to briefing to persisted guest encounter; empty send is disabled');
        const questions = [
            'Hello, I am the student doctor.',
            'Can you describe the pain?',
            'When did the pain start?',
            'Where does the pain spread?',
            'What is your medical history?',
            'Do you smoke?',
            'What other symptoms do you have?',
            'Do you have any allergies?',
        ];
        for (let i = 0; i < questions.length; i++) {
            await page.getByLabel('Your question', { exact: true }).fill(questions[i]);
            await page.getByRole('button', { name: 'Send', exact: true }).click();
            await page.waitForFunction(count => document.querySelectorAll('.from-patient').length === count, i + 1);
        }
        assert.match(await page.locator('.from-patient').last().innerText(), /not recorded/);
        assert.match(await page.locator('.transcript').innerText(), /2 hours ago while I was watching TV/);
        record.checks.push('Eight persisted question/reply pairs; case-specific onset; honest unknown allergy response');
        const beforeRefresh = await page.locator('.station-clock strong').innerText();
        await page.reload({ waitUntil: 'networkidle' });
        assert.equal(await page.locator('.from-patient').count(), questions.length);
        assert.ok((await page.locator('.station-clock strong').innerText()) <= beforeRefresh);
        record.checks.push('Refresh preserves transcript and does not restart timer');
        for (const action of ['Review vital signs', 'Troponin I', 'Electrocardiogram (ECG)', 'Chest X-Ray']) {
            const button = page.getByRole('button', { name: action, exact: true });
            await button.click();
            await page.waitForFunction(name => [...document.querySelectorAll('button')].find(button => button.textContent.trim() === name)?.disabled, action);
            await page.waitForFunction(() => !document.body.innerText.includes('Recording the request...'));
        }
        const findings = page.locator('.finding-detail');
        assert.equal(await findings.count(), 4);
        await findings.first().locator('summary').click();
        await noOverflow();
        await shot('encounter');
        await page.getByRole('button', { name: 'Use dark theme' }).click();
        await shot('encounter-dark');
        await page.getByRole('button', { name: 'Use light theme' }).click();
        record.checks.push('All four findings actions persist and prevent duplicate requests; disclosure opens');
        await page.getByRole('button', { name: 'Finish & write summary' }).click();
        await page.getByRole('button', { name: 'Submit & view evidence review' }).click();
        assert.equal(new URL(page.url()).pathname, record.sessionPath);
        assert.equal(await page.locator('#diagnosis').evaluate(element => element.validity.valueMissing), true);
        await page.getByLabel('Working diagnosis', { exact: true }).fill('Possible acute coronary syndrome, requiring urgent clinical assessment.');
        await page.getByLabel('Differential diagnoses', { exact: true }).fill('Other cardiac and non-cardiac causes of acute chest pain require consideration.');
        await page.getByLabel('Management plan', { exact: true }).fill('Escalate for urgent assessment. Review vital signs, ECG and troponin findings with the supervising clinician.');
        await shot('submission');
        await page.getByRole('button', { name: 'Submit & view evidence review' }).click();
        await page.getByRole('heading', { name: 'Your evidence review', exact: true }).waitFor();
        record.reviewPath = new URL(page.url()).pathname;
        assert.match(await page.locator('.review-summary').innerText(), /8 questions recorded/);
        assert.equal(await page.locator('.evidence-item').count(), 6);
        assert.equal(await page.locator('.clinical-checklist li').count(), 8);
        await noOverflow();
        await shot('review');
        await shot('review-viewport', false);
        await page.reload({ waitUntil: 'networkidle' });
        await page.getByRole('heading', { name: 'Your evidence review', exact: true }).waitFor();
        assert.match(await page.locator('.submitted-summary').innerText(), /Possible acute coronary syndrome/);
        await page.locator('.full-transcript summary').click();
        assert.equal(await page.locator('.transcript-line').count(), 16);
        await shot('review-transcript');
        record.checks.push('Summary validation; submission; review reload; unchanged eight-item checklist; complete saved transcript');
        await page.getByRole('button', { name: 'Use dark theme' }).click();
        await shot('review-dark');
        await page.getByRole('button', { name: 'Use light theme' }).click();
        for (const intermediate of [768, 1024, width]) {
            await page.setViewportSize({ width: intermediate, height: width === 1440 ? 1000 : 844 });
            await noOverflow();
        }
        await page.getByRole('button', { name: 'Practise this station again' }).click();
        await page.getByRole('heading', { name: 'Patient conversation' }).waitFor();
        assert.notEqual(new URL(page.url()).pathname, record.sessionPath);
        assert.equal(await page.locator('.from-patient').count(), 0);
        record.checks.push('Repeat creates a fresh encounter; intermediate widths do not overflow');
        record.failedAssetRequests = record.httpErrors.filter(item => ['script', 'stylesheet', 'image', 'font'].includes(item.type)).length;
        assert.equal(record.consoleErrors.length, 0, 'Console errors');
        assert.equal(record.pageErrors.length, 0, 'Uncaught page errors');
        assert.equal(record.failedRequests.length, 0, 'Failed network requests');
        assert.equal(record.httpErrors.length, 0, 'HTTP errors');
        await context.close();
    }
} catch (error) {
    results.push({ failure: error.message, stack: error.stack });
    process.exitCode = 1;
} finally {
    await fs.writeFile(path.join(folder, `${label}-qa.json`), JSON.stringify(results, null, 2));
    await browser.close();
}
console.log(JSON.stringify(results.map(({ width, failure, checks, consoleErrors, pageErrors, failedRequests, httpErrors, failedAssetRequests }) => ({
    width, failure, checks: checks?.length, consoleErrors: consoleErrors?.length, pageErrors: pageErrors?.length,
    failedRequests: failedRequests?.length, httpErrors: httpErrors?.length, failedAssetRequests,
})), null, 2));
