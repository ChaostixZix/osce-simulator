# Deploy OSCE on the existing Oracle origin

## Chosen architecture

HTTPS browser -> existing Nginx, OSCE-only virtual host -> dedicated PHP-FPM pool on 127.0.0.1:19086 -> Laravel/Inertia -> separate SQLite database and file sessions in /var/lib/osce.

The existing unproxied A record for osce.bintangputra.dev already points to this host (168.110.206.157). Preserve it exactly. Do not create a tunnel, Pages project or DNS record. TLS is issued on the origin using an isolated Certbot configuration and HTTP-01 webroot.

The owner explicitly requested origin hosting after discovery of the existing A record. Add only the OSCE virtual-host file, then validate and gracefully reload Nginx to load it. Existing site files stay byte-for-byte unchanged. Do not stop or restart Nginx, existing databases, containers or tunnels. Record their configuration hashes/status before and after.

Dokploy MCP is the preferred management path and was inspected first. Its available production/staging projects do not manage this local Nginx/PHP origin. Use native systemd for this deployment rather than changing another project's infrastructure.

## Prerequisites and release gate

Run from /home/ubuntu/babu-out/osce-simulator-fix-20261024/repo. PHP 8.3 CLI/extensions, Nginx and Certbot are already installed. PHP-FPM is extracted from Ubuntu's matching package without invoking package-install service scripts. Node is build-time only.

Before deployment: run the tests, lint and build; complete QA at 1440 and 390; inspect screenshots; scan for credentials; commit and push main. Record the exact SHA. Never build assets while browser QA is running.

```sh
php artisan test
npm run lint
npm run build
npm run qa:guest
git diff --check
git push origin main
git rev-parse HEAD
node deploy/inventory-cloudflare.mjs ../cloudflare-before.json
df -h /
sudo ss -lntp '( sport = :19086 )'
```

The inventory utility is read-only, discovers the authorized account credential without displaying it, refuses to overwrite evidence, and compares complete Pages/DNS API lists. No Cloudflare mutation is necessary. Use a new evidence filename on subsequent deployments.

Stop expanding if free space drops below 2 GB. Do not occupy port 19086 if already used by another service.

## Runtime installation

The following commands are for this Ubuntu ARM64 host. Use its current security package version on future rebuilds and check compatibility with the installed PHP extensions.

```sh
mkdir -p ../runtime
cd ../runtime
apt-get download php8.3-fpm
dpkg-deb -x php8.3-fpm_8.3.6-0ubuntu0.24.04.13_arm64.deb php-fpm
cd ../repo
sudo install -d -m 755 /opt/osce-runtime /etc/osce
sudo install -m 755 ../runtime/php-fpm/usr/sbin/php-fpm8.3 /opt/osce-runtime/php-fpm8.3
sudo install -d -o ubuntu -g ubuntu -m 755 /var/lib/osce /var/lib/osce/acme
sudo install -d -o ubuntu -g ubuntu -m 700 /var/lib/osce/storage /var/lib/osce/storage/logs /var/lib/osce/storage/framework /var/lib/osce/storage/framework/cache /var/lib/osce/storage/framework/cache/data /var/lib/osce/storage/framework/sessions /var/lib/osce/storage/framework/views
sudo install -m 644 deploy/native/php-fpm.conf /etc/osce/php-fpm.conf
```

Generate APP_KEY directly into the protected environment file, without printing it. This is a **first-install-only** command; preserve the existing key on updates:

```sh
sudo php -r 'if (file_exists("/etc/osce/environment")) { exit("Environment already exists; preserve it.\n"); } $s=file_get_contents("deploy/native/environment.example"); $s=str_replace("APP_KEY=\n", "APP_KEY=base64:".base64_encode(random_bytes(32))."\n", $s); file_put_contents("/etc/osce/environment", $s); chmod("/etc/osce/environment", 0600);'
sudo -u ubuntu touch /var/lib/osce/database.sqlite
sudo chmod 600 /var/lib/osce/database.sqlite
sudo install -m 644 deploy/native/osce-php.service deploy/native/osce-prune.service deploy/native/osce-prune.timer deploy/native/osce-cert-renew.service deploy/native/osce-cert-renew.timer /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemd-run --unit=osce-migrate --wait --pipe --collect --uid=ubuntu --working-directory="$PWD" --property=EnvironmentFile=/etc/osce/environment /usr/bin/php artisan migrate --force
sudo systemd-run --unit=osce-seed --wait --pipe --collect --uid=ubuntu --working-directory="$PWD" --property=EnvironmentFile=/etc/osce/environment /usr/bin/php artisan db:seed --class=DemoSeeder --force
sudo -u ubuntu php -r '$db=new PDO("sqlite:/var/lib/osce/database.sqlite"); $db->exec("PRAGMA journal_mode=WAL");'
sudo systemctl enable --now osce-php osce-prune.timer
```

Nginx serves a small copy of public assets from /var/lib/osce/public because /home/ubuntu is private. Do not widen home-directory permissions.

```sh
sudo install -d -m 755 /var/lib/osce/public
sudo cp -a public/build public/fonts public/images public/favicon.ico public/favicon.svg public/apple-touch-icon.png public/robots.txt public/index.php /var/lib/osce/public/
sudo chmod -R a+rX /var/lib/osce/public
```

The source checkout remains the release location to avoid duplicate checkouts and dependency trees. Production data and secrets are separate. Do not run config:cache in this shared checkout: systemd supplies production variables while local QA uses its local .env.

## Origin TLS and virtual host

First install only an OSCE HTTP-01 webroot. Preserve all other Nginx files.

```sh
sudo install -m 644 deploy/native/nginx-http.conf /etc/nginx/sites-available/osce.bintangputra.dev
sudo ln -s /etc/nginx/sites-available/osce.bintangputra.dev /etc/nginx/sites-enabled/osce.bintangputra.dev
sudo nginx -t
sudo nginx -s reload
sudo certbot certonly --webroot -w /var/lib/osce/acme -d osce.bintangputra.dev --cert-name osce.bintangputra.dev --config-dir /etc/osce/letsencrypt --work-dir /var/lib/osce/acme-work --logs-dir /var/log/osce-acme --non-interactive --agree-tos --register-unsafely-without-email
sudo install -m 644 deploy/native/nginx.conf /etc/nginx/sites-available/osce.bintangputra.dev
sudo nginx -t
sudo nginx -s reload
sudo systemctl enable --now osce-cert-renew.timer
curl --fail --silent --show-error -o /dev/null -w '%{http_code}\n' https://osce.bintangputra.dev
```

The ACME registration has no email contact. Add an owner-approved contact later; the timer still renews automatically. Validate the isolated renewal configuration with:

```sh
sudo certbot renew --dry-run --cert-name osce.bintangputra.dev --config-dir /etc/osce/letsencrypt --work-dir /var/lib/osce/acme-work --logs-dir /var/log/osce-acme
```

## Public acceptance and operations

```sh
QA_URL=https://osce.bintangputra.dev QA_LABEL=live npm run qa:guest
node deploy/inventory-cloudflare.mjs ../cloudflare-after.json ../cloudflare-before.json
sudo systemctl is-active osce-php
sudo systemctl list-timers osce-prune.timer osce-cert-renew.timer
sudo systemd-run --unit=osce-prune-check --wait --pipe --collect --uid=ubuntu --working-directory="$PWD" --property=EnvironmentFile=/etc/osce/environment /usr/bin/php artisan osce:prune-guests
```

Also complete a real journey from an external browser (the Aside Mac browser is outside this server). Confirm TLS validation without insecure flags, HTTP 200, persisted review reload and zero console/page/asset/network errors.

Use /up for liveness; it does not exercise the database. The browser station is the functional health check. Monitor disk, osce-php journal, certificate expiry and the retention timer. Production traffic capacity and disaster recovery are not established by functional QA.

Back up APP_KEY and SQLite securely before updates. A consistent snapshot can be created with SQLite's VACUUM INTO from PHP/PDO; never copy a live SQLite file without its WAL. Retain guest-data backups for no longer than seven days. Do not commit backups.

Updates: complete local QA first, push, then migrate with the production environment and restart only osce-php. Current deployment is a single checkout, so build updates need a short maintenance window; a future release-directory scheme can provide atomic asset swaps when disk permits.

Rollback: retain the current environment/database, restore the previously verified source revision using an owner-reviewed release procedure, rebuild assets and restart only osce-php. Do not run migrate:rollback blindly on production data. To withdraw only this site, unlink only /etc/nginx/sites-enabled/osce.bintangputra.dev, run nginx -t and gracefully reload, then stop osce-php and its OSCE timers. DNS remains unchanged.

## Alternatives

| Option | Fit and implications |
| --- | --- |
| Existing origin reverse proxy (chosen) | Reuses the existing A record and TLS-capable Nginx; only a small FPM binary and one app service are added. Same-origin sessions/CSRF and durable local data. No Cloudflare changes. |
| Dedicated Cloudflare tunnel | Useful when inbound ports cannot be exposed. Requires a tunnel credential/service and replacing or otherwise rerouting the existing OSCE DNS record. Not compatible with the owner's unchanged-DNS instruction here. |
| Dokploy containers + PostgreSQL | Good for a larger managed deployment; requires a new isolated project, persistent storage, queue/scheduler configuration and sufficient build disk. The reference compose file is optional and was not deployed or runtime-verified. |
| Static export + backend elsewhere | Landing assets alone can be static. The Inertia application cannot be exported as a functioning station: session creation, validation, patient replies and persisted reviews require Laravel. A separate static client would need a dedicated API, CSRF/CORS/session design, backend hosting and migration work. Not recommended for this restoration. |

Optional provider credentials affect legacy account tools only; the guest station remains free of provider calls. See documentation.md for the complete external-service list.
