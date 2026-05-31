# OSCE Simulator

## Overview

OSCE Simulator is an AI-powered Objective Structured Clinical Examination (OSCE) simulator for medical students, educators, and clinical training programs. It helps learners practice realistic clinical encounters, receive structured assessment, and turn each session into guided follow-up learning.

The project is built with Laravel 12, Inertia.js, React, and Tailwind CSS. It is used by ~400 medical students across 3 universities in Indonesia and is designed as a practical open-source foundation for scalable clinical skills training.

## Features

- AI-driven clinical case simulation for realistic OSCE practice.
- Real-time assessment and feedback during clinical sessions.
- Post-session learning workflows for review, replay, and rationalization.
- Microskills coaching to help learners improve specific clinical behaviors.
- Growth tracking across sessions and competencies.
- Patient visualization to make scenarios more engaging and contextual.

## Tech Stack

- Laravel 12
- Inertia.js
- React
- Tailwind CSS
- Vite
- Redis-backed session and queue services where configured
- External AI provider integration for simulation and feedback workflows

## Getting Started

### Prerequisites

- PHP and Composer compatible with Laravel 12
- Node.js with npm
- A database supported by Laravel
- Redis for features that use Redis-backed sessions or queues
- Credentials for configured external services, including WorkOS authentication and the configured AI provider

### Setup

```bash
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate
composer run dev
```

Set required environment values before running features that depend on external services, including WorkOS authentication, Redis-backed session/queue services, and the configured AI provider credentials.

## Contributing

Contributions are welcome. If you want to improve OSCE Simulator, open an issue or pull request with a clear description of the problem, proposed change, and any testing performed.

Please keep changes focused, follow the existing Laravel and React/Inertia conventions, and preserve the project design system where applicable.

## License

OSCE Simulator is open-source software licensed under the MIT License. See [LICENSE](LICENSE) for details.
