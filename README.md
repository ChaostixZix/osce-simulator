# OSCE Simulator

Practise an OSCE history with a virtual patient, record clinical findings and submit a summary for an evidence review.

The public guest station runs without an account, AI credentials, Redis or WebSockets. Patient replies use the original Chest Pain Assessment case. Feedback shows recorded discussion topics, requested findings, the learner's summary and the unchanged examiner checklist. It does **not** award a clinical grade.

## Run locally

Requirements: PHP 8.3 with mbstring, XML, cURL, SQLite/PDO, fileinfo and OpenSSL; Composer 2; Node 22.12+ or 24; npm 10+.

Run from the repository root (there is no webapp subdirectory):

```sh
composer install
npm ci
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan db:seed --class=DemoSeeder
npm run build
php artisan serve --host=0.0.0.0 --port=8000
```

Open http://100.100.22.72:8000 on this host's Tailscale network, or http://localhost:8000 on your own machine. Change APP_URL if needed. For frontend development use `npm run dev` in another terminal; remove a stale `public/hot` file before serving production assets.

## Verify

```sh
php artisan test
npm run lint
npm run build
QA_BROWSER=/path/to/chromium npm run qa:guest
```

Build first and keep the server running before browser QA. Rebuilding during QA invalidates Inertia's asset version and temporarily removes the manifest. The browser script completes real stations at 1440px and 390px, reloads their persisted reviews and writes screenshots plus measured errors to `../screenshots`. Set QA_URL, QA_LABEL and QA_OUTPUT to override its target and output.

## Documentation

- [DESIGN.md](DESIGN.md): architecture, patient/review layers, visual direction and tokens.
- [documentation.md](documentation.md): behavior, configuration, data and maintenance.
- [deploy/README.md](deploy/README.md): origin HTTPS deployment, exact commands, alternatives and rollback.

Laravel 12 + Inertia 2 + React 19 + Vite. Account-based legacy features still require WorkOS and their configured services; they are separate from the guest station and are not advertised as verified public features.

## Contributing

Preserve authored clinical facts, checklists and scoring definitions. Report medical-content concerns for examiner review. Follow AGENTS.md and run the checks before publishing.

## License

[MIT](LICENSE).
