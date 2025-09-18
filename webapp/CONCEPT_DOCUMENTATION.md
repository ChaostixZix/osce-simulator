# Vibe Kanban - Complete Application Mechanism & Architecture Guide

This document provides a comprehensive deep-dive into how the Vibe Kanban OSCE training application works, covering every mechanism from high-level user flows to low-level implementation details. Use this as a complete reference for understanding or replicating the application architecture.

## Table of Contents
1. [Application Overview](#application-overview)
2. [Core Mechanisms Deep Dive](#core-mechanisms-deep-dive)
3. [Technical Architecture](#technical-architecture)
4. [Data Flow & State Management](#data-flow--state-management)
5. [Implementation Patterns](#implementation-patterns)
6. [Security & Performance](#security--performance)
7. [Development Workflow](#development-workflow)

## Application Overview

Vibe Kanban is a sophisticated OSCE (Objective Structured Clinical Examination) training platform that combines:
- **Laravel 12** backend with Inertia.js SPA architecture
- **React 19** frontend with legacy Vue components
- **AI-powered assessment** using Gemini/Azure OpenAI
- **Real-time communication** via Laravel Reverb WebSockets
- **WorkOS authentication** with enterprise SSO
- **Gaming design system** for engaging UX

### Core Value Proposition
The application simulates realistic clinical examination scenarios where medical students interact with AI patients, perform virtual examinations, order tests, and receive comprehensive AI-driven feedback on their clinical reasoning and decision-making.

## Core Mechanisms Deep Dive

### 1. OSCE Session Lifecycle Management

The OSCE session represents the core business logic of the application, managing a complete examination cycle from start to assessment.

#### State Transitions & Database Schema
```php
// Core session states in OsceSession model
'in_progress' → 'completed' → 'finalized' → 'assessed'

// Key timestamps for state tracking
started_at       // Session timer reference point
completed_at     // When user ends session
finalized_at     // When diagnosis/plan submitted
rationalization_completed_at  // When reflection complete
```

#### Flow Implementation Details

**1. Session Initialization (`OsceController@startSessionInertia`)**
```php
// webapp/app/Http/Controllers/OsceController.php:712
$session = OsceSession::create([
    'user_id' => auth()->id(),
    'osce_case_id' => $request->osce_case_id,
    'status' => 'in_progress',
    'started_at' => now(),
    'duration_minutes' => $osceCase->duration_minutes
]);
```

**2. Timer Management**
- Server-side timer calculation prevents manipulation
- `GET /api/osce/sessions/{session}/timer` continuously calculates remaining time
- Frontend `SessionTimer.vue` polls this endpoint every second
- Time calculation: `duration_minutes * 60 - (now() - started_at)`

**3. Session Completion Flow**
```php
// OsceController@completeSession
$session->update([
    'status' => 'completed',
    'completed_at' => now()
]);
```

**4. Finalization Gate (`FinalizeSessionModal.jsx`)**
- Modal prevents access to rationalization until diagnosis provided
- Required fields: primary diagnosis, differential diagnoses, management plan
- Sets `finalized_at` timestamp enabling next phase

**5. Rationalization Process**
- Guided reflection on clinical reasoning
- Multiple sections with progressive disclosure
- Managed by `RationalizationController` and `RationalizationService`
- Completion sets `rationalization_completed_at`

#### Session Data Aggregation
Each session accumulates rich interaction data:
- **Chat Messages**: Full conversation transcript with AI patient
- **Ordered Tests**: Lab work, imaging with realistic results
- **Examinations**: Physical exam findings and techniques
- **Temporal Data**: Precise timing of all interactions

### 2. AI Assessment Pipeline Architecture

The AI assessment system is designed for scalability, reliability, and provider-agnostic implementation.

#### Queue-Based Processing Architecture
```
User Request → Assessment Queue → Orchestrator → Area Jobs → Provider Services → Result Aggregation
```

#### Implementation Flow

**1. Assessment Triggering (`OsceAssessmentController@assess`)**
```php
// Creates assessment run record
$run = AiAssessmentRun::create([
    'osce_session_id' => $session->id,
    'status' => 'queued',
    'provider' => config('services.ai.provider')
]);

// Dispatches orchestrator job
AiAssessorOrchestrator::dispatch($run);
```

**2. Orchestration Pattern (`AiAssessorOrchestrator.php`)**
```php
// Fan-out to individual area assessments
foreach (['history', 'examination', 'investigations', 'diagnosis', 'management'] as $area) {
    AssessAreaJob::dispatch($this->run, $area);
}

// Schedule finalization after delay
FinalizeAssessmentRunJob::dispatch($this->run)->delay(30);
```

**3. Provider Abstraction (`UniversalAIService.php`)**
```php
public function generateAssessment(string $prompt, array $context = []): array
{
    return match ($this->provider) {
        'gemini' => $this->geminiService->generateAssessment($prompt, $context),
        'azure-openai' => $this->openAIService->generateAssessment($prompt, $context),
        default => throw new InvalidArgumentException("Unsupported AI provider: {$this->provider}")
    };
}
```

**4. Result Aggregation (`ResultReducer.php`)**
- Waits for all area assessments to complete
- Combines individual scores using weighted averages
- Generates comprehensive feedback report
- Updates assessment run with final results

#### AI Provider Implementation

**Gemini Service Pattern:**
```php
// webapp/app/Services/GeminiService.php
protected function makeRequest(string $prompt, array $options = []): array
{
    $response = Http::withHeaders([
        'Content-Type' => 'application/json',
    ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro:generateContent?key={$this->apiKey}", [
        'contents' => [
            ['parts' => [['text' => $prompt]]]
        ],
        'generationConfig' => [
            'temperature' => 0.3,
            'candidateCount' => 1,
            'maxOutputTokens' => 8192,
        ]
    ]);

    return $this->parseResponse($response);
}
```

### 3. Real-time Communication System

Laravel Reverb provides WebSocket-based real-time updates for seamless user experience.

#### WebSocket Infrastructure
```php
// config/reverb.php - Server configuration
'apps' => [
    [
        'app_id' => env('REVERB_APP_ID'),
        'key' => env('REVERB_APP_KEY'),
        'secret' => env('REVERB_APP_SECRET'),
        'host' => env('REVERB_SERVER_HOST', '0.0.0.0'),
        'port' => env('REVERB_SERVER_PORT', 8080),
    ]
]
```

#### Channel Authorization
```php
// routes/channels.php
Broadcast::channel('osce.sessions.{session}', function ($user, OsceSession $session) {
    return $user->id === $session->user_id;
});
```

#### Event Broadcasting Pattern
```php
// app/Events/TestOrderReady.php
class TestOrderReady implements ShouldBroadcast
{
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('osce.sessions.' . $this->session->id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'message' => "Your {$this->testName} results are ready",
            'test_id' => $this->testId,
            'session_id' => $this->session->id
        ];
    }
}
```

#### Frontend WebSocket Integration
```javascript
// resources/js/hooks/useOsceSessionRealtime.js
import Echo from 'laravel-echo';

export function useOsceSessionRealtime(sessionId) {
    useEffect(() => {
        const channel = Echo.private(`osce.sessions.${sessionId}`)
            .listen('TestOrderReady', (event) => {
                toast.success(event.message);
                // Trigger UI updates
            });

        return () => channel.stopListening('TestOrderReady');
    }, [sessionId]);
}
```

### 4. Authentication & User Management

WorkOS integration provides enterprise-grade authentication with custom user management.

#### Authentication Flow
```php
// routes/auth.php
Route::get('/login', function () {
    return AuthKitLoginRequest::create()
        ->state(session()->token())
        ->redirectUrl(route('authenticate'))
        ->send();
})->name('login');

Route::get('/authenticate', function () {
    return AuthKitAuthenticationRequest::create()
        ->authenticateWithFallback(function ($profile) {
            return User::updateOrCreate(
                ['email' => $profile->email],
                [
                    'name' => $profile->firstName . ' ' . $profile->lastName,
                    'workos_id' => $profile->id,
                ]
            );
        })
        ->redirect('/dashboard');
})->name('authenticate');
```

#### Session Validation Middleware
```php
// Automatic WorkOS session validation
protected $middlewareGroups = [
    'web' => [
        ValidateSessionWithWorkOS::class,
        // ... other middleware
    ],
];
```

### 5. Frontend-Backend Communication (Inertia SPA)

Inertia.js provides seamless SPA experience without traditional API complexity.

#### Inertia Response Pattern
```php
// app/Http/Controllers/DashboardController.php
public function index(): Response
{
    return Inertia::render('Dashboard', [
        'stats' => [
            'total_sessions' => $user->osceSessions()->count(),
            'completed_sessions' => $user->osceSessions()->where('status', 'completed')->count(),
            'average_score' => $this->calculateAverageScore($user),
        ],
        'recent_sessions' => $user->osceSessions()
            ->with(['osceCase', 'aiAssessmentRuns'])
            ->latest()
            ->take(5)
            ->get(),
        'welcome' => $this->shouldShowWelcome($user),
    ]);
}
```

#### Frontend Component Integration
```jsx
// resources/js/pages/Dashboard.jsx
export default function Dashboard({ stats, recent_sessions, welcome }) {
    const { data, setData, post, processing } = useForm({
        osce_case_id: null
    });

    const startSession = (caseId) => {
        setData('osce_case_id', caseId);
        post('/api/osce/sessions/start', {
            onSuccess: () => router.visit(`/osce/chat/${data.osce_case_id}`)
        });
    };

    return (
        <Layout>
            <SessionStats stats={stats} />
            <RecentSessions sessions={recent_sessions} onStart={startSession} />
        </Layout>
    );
}
```

### 6. Database Architecture & Relationships

#### Core Entity Relationships
```php
// User Model Relationships
public function osceSessions(): HasMany
{
    return $this->hasMany(OsceSession::class);
}

// OsceSession Model Relationships
public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}

public function osceCase(): BelongsTo
{
    return $this->belongsTo(OsceCase::class);
}

public function chatMessages(): HasMany
{
    return $this->hasMany(OsceChatMessage::class);
}

public function orderedTests(): HasMany
{
    return $this->hasMany(SessionOrderedTest::class);
}

public function examinations(): HasMany
{
    return $this->hasMany(SessionExamination::class);
}

public function rationalization(): HasOne
{
    return $this->hasOne(OsceSessionRationalization::class);
}

public function aiAssessmentRuns(): HasMany
{
    return $this->hasMany(AiAssessmentRun::class);
}
```

#### Data Flow Patterns
1. **Session Creation**: `User` → `OsceSession` → `OsceCase` reference
2. **Interaction Logging**: All user actions create child records linked to `OsceSession`
3. **Assessment Pipeline**: `OsceSession` → `AiAssessmentRun` → `AiAssessmentAreaResult[]`
4. **Result Aggregation**: Multiple `AiAssessmentAreaResult` → Single `final_result` JSON

### 7. Gaming Design System Implementation

#### CSS Architecture
```css
/* webapp/resources/css/app.css */
.cyber-border {
    clip-path: polygon(0 0, calc(100% - 8px) 0, 100% 8px, 100% 100%, 8px 100%, 0 calc(100% - 8px));
}

.glow-text {
    text-shadow: 0 0 10px currentColor, 0 0 20px currentColor, 0 0 30px currentColor;
}

@keyframes scan {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}
```

#### Theme System
```css
:root {
    --background: hsl(0 0% 98%);
    --foreground: hsl(0 0% 9%);
    --primary: hsl(142 76% 36%);
    --emerald-500: #10b981;
}

.dark {
    --background: hsl(0 0% 5%);
    --foreground: hsl(0 0% 95%);
    --primary: hsl(142 86% 28%);
}
```

#### Component Pattern Implementation
```jsx
// Standard card component pattern
<div className={`
    cyber-border
    bg-gradient-to-br from-emerald-500/10 to-emerald-600/5
    border-emerald-500/30
    p-6
    hover:scale-[1.02]
    transition-all duration-300
    group
`}>
    <div className="absolute top-2 right-2 w-2 h-2 bg-gradient-to-br from-emerald-400 to-cyan-400 opacity-60 group-hover:opacity-100 transition-opacity"></div>
    {content}
</div>
```

## Technical Architecture

### Backend Services Layer
```
Controllers → Services → Models → Database
     ↓
Middleware (Auth, Inertia, CORS)
     ↓
Queue System (Redis)
     ↓
WebSocket Server (Reverb)
```

### Frontend Component Architecture
```
app.jsx → Pages → Layouts → Components
    ↓
Contexts (Theme, Auth) → Hooks → Utils
    ↓
Vibe UI Kit → Design System
```

### External Integrations
- **WorkOS**: Authentication & user management
- **Gemini API**: Primary AI provider for assessments
- **Azure OpenAI**: Alternative AI provider
- **Redis**: Caching, sessions, queues
- **PostgreSQL**: Primary database
- **Laravel Reverb**: WebSocket communications

## Data Flow & State Management

### Session State Flow
```
Session Creation → In Progress → Completed → Finalized → Rationalized → Assessed
```

### Assessment Data Pipeline
```
User Interactions → Session Artifact → AI Prompt → Provider API → Area Results → Final Score
```

### Real-time Communication Flow
```
Backend Event → Laravel Broadcasting → Reverb Server → WebSocket → Frontend Hook → UI Update
```

## Implementation Patterns

### Service Pattern
All complex business logic is encapsulated in service classes:
- `AiAssessorService`: Coordinates AI assessment workflow
- `AssessmentQueueService`: Manages queue operations
- `RationalizationService`: Handles reflection process
- `UniversalAIService`: Abstracts AI provider differences

### Repository Pattern
Models use Eloquent ORM with rich relationships and scopes:
```php
// OsceSession scopes
public function scopeCompleted($query)
{
    return $query->where('status', 'completed');
}

public function scopeWithAssessments($query)
{
    return $query->with(['aiAssessmentRuns.areaResults']);
}
```

### Observer Pattern
Model events trigger side effects:
```php
// OsceSession observer
protected static function booted()
{
    static::completed(function ($session) {
        // Trigger assessment workflow
        AssessmentQueueService::queueAssessment($session);
    });
}
```

## Security & Performance

### Security Measures
- **WorkOS Authentication**: Enterprise-grade SSO
- **CSRF Protection**: Automatic via Laravel
- **Authorization**: Policy-based access control
- **Input Validation**: Request validation classes
- **SQL Injection Protection**: Eloquent ORM
- **XSS Prevention**: Blade escaping

### Performance Optimizations
- **Eager Loading**: Prevent N+1 queries
- **Redis Caching**: Session, queue, and application cache
- **Queue Processing**: Async AI assessment
- **Database Indexing**: Optimized queries
- **Inertia SPA**: No full page reloads

## Development Workflow

### Environment Setup
```bash
# Backend setup
composer install
php artisan key:generate
php artisan migrate

# Frontend setup
bun install
bun run dev

# Services
php artisan queue:work --queue=assessments,management,default
php artisan reverb:start
```

### Code Quality
```bash
# PHP testing
php artisan test

# Frontend linting
bun run lint
bun run format
```

### Deployment Pipeline
1. **Build Assets**: `bun run build && bun run build:ssr`
2. **Run Migrations**: `php artisan migrate --force`
3. **Clear Caches**: `php artisan config:cache && php artisan route:cache`
4. **Start Services**: Queue workers, Reverb server
5. **Health Checks**: Database, Redis, AI providers

---

## Conclusion

This comprehensive guide provides a complete understanding of the Vibe Kanban application mechanism. The architecture demonstrates enterprise-grade patterns with modern development practices, creating a scalable, maintainable, and feature-rich OSCE training platform.

Key architectural strengths:
- **Separation of Concerns**: Clear service layer abstractions
- **Scalability**: Queue-based processing and provider abstractions
- **User Experience**: SPA with real-time updates
- **Maintainability**: Strong typing, comprehensive testing
- **Security**: Enterprise authentication and input validation

This foundation enables rapid feature development while maintaining code quality and system reliability.