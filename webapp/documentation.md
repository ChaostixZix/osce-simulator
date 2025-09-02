# Vibe Kanban Documentation

This document provides a detailed overview of the Vibe Kanban application, including its architecture, features, and setup instructions.

## 1. Project Overview

Vibe Kanban is a web application designed for Objective Structured Clinical Examinations (OSCE). It provides a platform for medical students and professionals to practice and get assessed on their clinical skills in a simulated environment.

### Key Features

*   **OSCE Case Simulation:** Users can start and participate in simulated OSCE cases.
*   **AI-Powered Assessment:** The application uses AI to assess user performance and provide feedback.
*   **Real-time Interaction:** Real-time features like chat and session timers are available.
*   **Clinical Reasoning:** The system includes tools for ordering medical tests and procedures.
*   **Post-Session Rationalization:** Users can reflect on their performance and provide rationalizations for their decisions.
*   **User Management:** The application is integrated with WorkOS for user authentication and session management.

## 2. Architecture

The application follows a standard Laravel backend and a modern React frontend architecture.

*   **Backend:** Laravel 12
*   **Frontend:** React with Inertia.js
*   **UI Kit:** Vibe UI KIT
*   **Styling:** Tailwind CSS
*   **Database:** PostgreSQL (production), SQLite (development)
*   **Real-time:** Laravel Reverb (WebSocket server)
*   **Authentication:** WorkOS

### Key Directories

*   `app/Services`: Contains the core business logic of the application, including AI assessment services.
*   `app/Http/Controllers`: Handles incoming HTTP requests and interacts with services and models.
*   `resources/js/Pages`: Contains the React components for different pages of the application.
*   `routes/web.php`: Defines the web routes for the application.

## 3. Getting Started

### Prerequisites

*   PHP 8.2 or higher
*   Node.js 20.x
*   NPM 10.x
*   Composer
*   PostgreSQL (or SQLite for development)

### Installation

1.  **Clone the repository:**
    ```bash
    git clone https://github.com/your-repository/vibe-kanban.git
    cd vibe-kanban/webapp
    ```

2.  **Install PHP dependencies:**
    ```bash
    composer install
    ```

3.  **Install NPM dependencies:**
    ```bash
    npm install
    ```

4.  **Set up environment variables:**
    Copy the `.env.example` file to `.env` and configure your database and other services.
    ```bash
    cp .env.example .env
    ```
    Update the following variables in your `.env` file:
    ```
    DB_CONNECTION=pgsql
    DB_HOST=127.0.0.1
    DB_PORT=5432
    DB_DATABASE=your_database
    DB_USERNAME=your_username
    DB_PASSWORD=your_password

    WORKOS_API_KEY=your_workos_api_key
    WORKOS_CLIENT_ID=your_workos_client_id
    ```

5.  **Generate application key:**
    ```bash
    php artisan key:generate
    ```

6.  **Run database migrations:**
    ```bash
    php artisan migrate
    ```

### Development Server

To start the development server, which includes the Laravel server, queue worker, WebSocket server, and Vite development server, run the following command:

```bash
composer run dev
```

The application will be available at `http://localhost:8000`.

## 4. Key Functionality in Detail

### OSCE Sessions

*   **Starting a Session:** Users can start a new OSCE session from the dashboard. The `OsceController@startSessionInertia` method handles this functionality.
*   **Session Timer:** Each session has a timer, which is managed by the `OsceController@getSessionTimer` method.
*   **Completing a Session:** Users can complete a session, which triggers the assessment process.

### AI Assessment

*   The assessment process is handled by the `OsceAssessmentController`.
*   The `assess` method in `OsceAssessmentController` is responsible for initiating the AI-powered assessment.
*   The assessment status can be checked via the `status` method, and the results can be viewed via the `results` method.

### Real-time Chat

*   The application uses Laravel Reverb for real-time chat functionality.
*   The `OsceChatController` handles starting a chat, sending messages, and retrieving chat history.

## 5. API Endpoints

The application exposes several API endpoints for interacting with the OSCE features. These endpoints are defined in `routes/web.php`.

*   `/api/osce/cases`: Get a list of OSCE cases.
*   `/api/osce/sessions`: Get a list of user sessions.
*   `/api/osce/sessions/start`: Start a new OSCE session.
*   `/api/osce/sessions/{session}/assess`: Trigger an assessment for a session.
*   `/api/osce/sessions/{session}/status`: Get the assessment status for a session.
*   `/api/osce/sessions/{session}/results`: Get the assessment results for a session.
*   `/api/osce/chat/message`: Send a message in the chat.

## 6. Contributing

Please refer to the main `README.md` for contributing guidelines.
