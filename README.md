# Web-Based OSCE Training Platform

A modern web application for Objective Structured Clinical Examination (OSCE) training.  
Built with **Laravel** and **Vue 3** via [Inertia](https://inertiajs.com), it offers
AI‑powered patient simulation and structured clinical cases for medical learners.

## 🚀 Quick Start

1. **Enter the webapp directory**
   ```bash
   cd webapp
   ```
2. **Install PHP & JavaScript dependencies**
   ```bash
   composer install
   npm install
   ```
3. **Configure environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Set your AI credentials in `.env`:
   ```env
   GEMINI_API_KEY=your_gemini_api_key
   GEMINI_MODEL=gemini-1.5-flash
   ```
4. **Prepare the database**
   ```bash
   php artisan migrate
   php artisan db:seed --class=OsceCaseSeeder # optional sample cases
   ```
5. **Build assets & start the server**
   ```bash
   npm run build   # or: npm run dev
   php artisan serve
   ```

Visit `http://localhost:8000` to access the application.

## 🏥 Features

- **AI Patient Simulation** using Google's Gemini for context‑aware conversations
- **OSCE Case Library** with multiple difficulty levels and structured objectives
- **Session Tracking** to monitor progress across cases
- **Secure Authentication** and isolated training sessions

## 🧱 Project Structure

```
webapp/
├── app/         # Laravel backend code
├── database/    # Migrations and seeders
├── resources/   # Vue components and assets
├── routes/      # Web & API routes
├── tests/       # PHPUnit tests
└── ...
```

## 🧪 Testing

Run the test suite from the `webapp` directory:

```bash
composer test
```

## 📦 Legacy CLI (Archived)

The original Node.js CLI prototype and an earlier PHP experiment have been
archived under `legacy/cli-prototype/`. They are no longer maintained. The
Laravel + Vue app in `webapp/` is the active project.

If you need to explore the prototype, see `legacy/cli-prototype/README.md`.

## 📄 License

Released under the MIT License.
