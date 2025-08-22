# Web-Based OSCE Training Platform

A modern web application for Objective Structured Clinical Examination (OSCE) training, featuring an AI-powered patient simulator, a community forum, and knowledge assessment tools.
Built with **Laravel** and **Vue 3** via [Inertia](https://inertiajs.com), it provides a comprehensive platform for medical learners.

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

- **AI Patient Simulation**: Engage in context-aware conversations with an AI patient powered by Google's Gemini.
- **OSCE Case Library**: Access a range of clinical cases with structured objectives and multiple difficulty levels.
- **Community Forum**: Discuss cases, share knowledge, and collaborate with other learners.
- **MCQ Assessment**: Test your knowledge with a multiple-choice question section.
- **Session Tracking**: Monitor your progress and performance across different cases.
- **Secure Authentication**: Ensures private and isolated training sessions for each user.

## 🧱 Project Structure

```
webapp/
├── app/                      # Laravel backend code
│   ├── Http/
│   │   ├── Controllers/      # Application controllers
│   │   └── Middleware/
│   ├── Models/               # Eloquent models
│   └── Providers/
├── config/                   # Configuration files
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── public/                   # Publicly accessible assets
├── resources/
│   ├── css/                  # Compiled CSS
│   ├── js/                   # Vue components and scripts
│   │   ├── Components/       # Reusable Vue components
│   │   ├── Layouts/          # Application layouts
│   │   └── Pages/            # Inertia page components
│   └── views/                # Blade views
├── routes/                   # Web & API routes
└── tests/                    # PHPUnit tests
```

### Models

The application's core data structures are defined using Eloquent models:

- **`User`**: Manages user data and authentication.
- **`OsceCase`**: Represents a clinical case scenario.
- **`OsceSession`**: Tracks a user's progress through a case.
- **`OsceChatMessage`**: Stores messages from the AI patient chat.
- **`MedicalTest`**: Defines available medical tests.
- **`SessionOrderedTest`**: Logs tests ordered during a session.
- **`SessionExamination`**: Records examinations performed.
- **`Post`**: Represents a forum post.
- **`Comment`**: A comment on a forum post.
- **`PostInteraction`**: Tracks user engagement (e.g., likes).
- **`Notification`**: Manages user notifications.
- **`Patient`**: Represents a patient entity for SOAP notes.
- **`SoapNote`**: A SOAP note associated with a patient.
- **`SoapAttachment`**: File attachments for SOAP notes.
- **`SoapComment`**: Comments on SOAP notes.
- **`McqTest`**: A multiple-choice question test.
- **`McqQuestion`**: A single question within a test.
- **`McqOption`**: An answer option for an MCQ question.

### Controllers

Controllers handle the application's logic and route requests:

- **`LandingController`**: Manages the public-facing landing page.
- **`DashboardController`**: Handles the main user dashboard.
- **`OsceController`**: Core logic for OSCE sessions.
- **`OsceChatController`**: Manages the AI chat functionality.
- **`MedicalTestController`**: API for medical test data.
- **`PostController`**: CRUD operations for forum posts.
- **`CommentController`**: Manages comments on posts.
- **`MCQController`**: Handles the MCQ assessment feature.
- **`PatientController`**: Manages patient records.
- **`SoapBoardController`**, **`SoapPageController`**, **`SoapNoteController`**: Manage the SOAP notes feature.
- **`Settings/ProfileController`**: Manages user profile settings.
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
