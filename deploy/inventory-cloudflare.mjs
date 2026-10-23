import fs from 'node:fs/promises';
import path from 'node:path';
import crypto from 'node:crypto';
import { createRequire } from 'node:module';
const require = createRequire(import.meta.url);
const yaml = require('js-yaml');
const accountId = 'bd9022fb0550b6e8ba8c17b3712e935c';
const destination = path.resolve(process.argv[2] || '../cloudflare-before.json');
const comparison = process.argv[3];
const canonical = value => JSON.stringify(value, (_, item) => item && typeof item === 'object' && !Array.isArray(item) ? Object.fromEntries(Object.entries(item).sort(([a], [b]) => a.localeCompare(b))) : item);
try {
    const config = yaml.load(await fs.readFile(process.env.CF_CONFIG_PATH || '/home/ubuntu/.hermes/profiles/bob/config.yaml', 'utf8'));
    let authorization;
    let zone;
    let credentialName;
    for (const name of ['cf-personal', 'cf-hypajump']) {
        const candidate = config.mcp_servers?.[name]?.headers?.Authorization;
        if (!candidate) continue;
        const response = await fetch('https://api.cloudflare.com/client/v4/zones?name=bintangputra.dev', { headers: { Authorization: candidate } });
        const data = await response.json();
        const match = data.result?.find(item => item.account.id === accountId && item.name === 'bintangputra.dev');
        if (match) { authorization = candidate; zone = match; credentialName = name; break; }
    }
    if (!zone) throw new Error('No authorized zone credential');
    async function list(endpoint) {
        const records = [];
        const perPage = endpoint.includes('/pages/') ? 10 : 100;
        for (let page = 1; ; page++) {
            const response = await fetch(`https://api.cloudflare.com/client/v4/${endpoint}?per_page=${perPage}&page=${page}`, { headers: { Authorization: authorization } });
            const data = await response.json();
            if (!response.ok || !data.success || !Array.isArray(data.result)) { console.error(JSON.stringify({ endpoint, status: response.status, codes: data.errors?.map(item => item.code) })); throw new Error('Inventory request failed'); }
            records.push(...data.result);
            if (data.result.length < perPage || page >= data.result_info?.total_pages) break;
        }
        return records.sort((a, b) => String(a.id || a.name).localeCompare(String(b.id || b.name)));
    }
    const [listedPages, dns] = await Promise.all([list(`accounts/${accountId}/pages/projects`), list(`zones/${zone.id}/dns_records`)]);
    const pages = listedPages.map(({ id, name, subdomain, domains, created_on }) => ({ id, name, subdomain, domains, created_on }));
    const state = { capturedAt: new Date().toISOString(), accountId, zoneId: zone.id, credentialName, pages, dns };
    await fs.writeFile(destination, JSON.stringify(state, null, 2) + '\n', { mode: 0o600, flag: 'wx' });
    const result = { pages: pages.length, dns: dns.length, snapshot: destination };
    if (comparison) {
        const before = JSON.parse(await fs.readFile(comparison, 'utf8'));
        result.pagesUnchanged = canonical(before.pages) === canonical(pages);
        result.dnsUnchanged = canonical(before.dns) === canonical(dns);
        result.beforeSha256 = crypto.createHash('sha256').update(canonical({ pages: before.pages, dns: before.dns })).digest('hex');
        result.afterSha256 = crypto.createHash('sha256').update(canonical({ pages, dns })).digest('hex');
        await fs.writeFile(destination.replace(/\.json$/, '-comparison.json'), JSON.stringify(result, null, 2) + '\n');
        if (!result.pagesUnchanged || !result.dnsUnchanged) process.exitCode = 1;
    }
    console.log(JSON.stringify(result, null, 2));
} catch (error) {
    console.error('Failure category: ' + (error.code || error.name));
    console.error('Cloudflare inventory failed. No credentials or response details were printed.');
    process.exitCode = 1;
}
