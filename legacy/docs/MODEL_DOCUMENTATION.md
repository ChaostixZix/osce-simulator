# Medical Training System - Model Documentation

This document provides comprehensive documentation for all Laravel Eloquent models in the Medical Training System. It's designed to help frontend developers understand how to interact with these models through API endpoints.

## Table of Contents

1. [Session Model](#session-model)
2. [ChatMessage Model](#chatmessage-model)
3. [OSCECase Model](#oscecase-model)
4. [OSCESession Model](#oscesession-model)
5. [SystemLog Model](#systemlog-model)
6. [Frontend Integration Guidelines](#frontend-integration-guidelines)
7. [Common API Patterns](#common-api-patterns)

---

## Session Model

### Overview
The `Session` model manages user sessions, tracking chat activity, OSCE performance, and session statistics. It serves as the central hub for all user interactions.

### Key Properties

| Field | Type | Description |
|-------|------|-------------|
| `session_id` | `string` | Unique session identifier (e.g., "med_abc123def456") |
| `user_id` | `integer` | Associated user ID (nullable for guest sessions) |
| `start_time` | `datetime` | Session start timestamp |
| `end_time` | `datetime` | Session end timestamp (null for active sessions) |
| `chat_messages` | `integer` | Total chat messages sent in this session |
| `osce_sessions_completed` | `integer` | Number of completed OSCE sessions |
| `total_osce_time` | `integer` | Total time spent in OSCE sessions (seconds) |
| `error_count` | `integer` | Number of errors encountered |
| `metadata` | `json` | Additional session data (user agent, IP, etc.) |

### Relationships

```php
// Has many chat messages
$session->chatMessages()

// Has many OSCE sessions  
$session->osceSessions()

// Has many system logs
$session->systemLogs()

// Belongs to user (optional)
$session->user()
```

### Key Methods

```php
// Check if session is active
$session->isActive(): bool

// Get session duration in minutes
$session->getDurationInMinutes(): int

// Get average OSCE time in minutes
$session->getAverageOsceTime(): int

// Increment counters
$session->incrementChatMessages(): void
$session->trackOsceSession(int $duration, float $score): void
$session->trackError(): void

// End session
$session->endSession(): void
```

### Frontend Usage Examples

```javascript
// Create new session
POST /api/sessions
Response: {
  "session_id": "med_abc123def456",
  "start_time": "2024-01-15T10:30:00Z",
  "is_active": true
}

// Get session statistics
GET /api/sessions/{sessionId}/stats
Response: {
  "session_id": "med_abc123def456",
  "duration_minutes": 45,
  "chat_messages": 12,
  "osce_sessions_completed": 2,
  "average_osce_time": 18,
  "error_count": 0
}

// End session
PUT /api/sessions/{sessionId}/end
Response: {
  "success": true,
  "summary": {
    "total_session_time": 45,
    "chat_messages": 12,
    "osce_sessions_completed": 2,
    "recent_performance": [...]
  }
}
```

---

## ChatMessage Model

### Overview
The `ChatMessage` model stores individual chat messages with support for automatic summarization to manage long conversation histories efficiently.

### Key Properties

| Field | Type | Description |
|-------|------|-------------|
| `session_id` | `string` | Reference to session |
| `role` | `enum` | Message role: 'user', 'assistant', 'system' |
| `content` | `text` | Message content |
| `is_summarized` | `boolean` | Whether message has been summarized |
| `summary` | `text` | Summarized version of content |
| `tokens_used` | `integer` | AI tokens consumed (nullable) |
| `response_time_ms` | `integer` | AI response time in milliseconds |
| `metadata` | `json` | Additional message data |

### Key Methods

```php
// Check message type
$message->isUserMessage(): bool
$message->isAssistantMessage(): bool
$message->isSystemMessage(): bool

// Mark as summarized
$message->markAsSummarized(string $summary): void

// Get display content (summary if available, otherwise content)
$message->getDisplayContent(): string
```

### Scopes Available

```php
// Filter by session
ChatMessage::bySession($sessionId)

// Filter by role
ChatMessage::byRole('user')

// Get unsummarized messages
ChatMessage::unsummarized()

// Get recent messages
ChatMessage::recent(10)
```

### Frontend Usage Examples

```javascript
// Send chat message
POST /api/chat/{sessionId}/message
Body: {
  "content": "What are the symptoms of STEMI?"
}
Response: {
  "success": true,
  "message": "STEMI symptoms typically include...",
  "response_time_ms": 1250,
  "tokens_used": 156,
  "history_count": 8
}

// Get chat history
GET /api/chat/{sessionId}/history?limit=50
Response: [
  {
    "id": 1,
    "role": "user",
    "content": "Hello",
    "timestamp": "2024-01-15T10:30:00Z",
    "is_summarized": false,
    "tokens_used": null,
    "response_time_ms": null
  },
  {
    "id": 2,
    "role": "assistant", 
    "content": "Hi! How can I help you today?",
    "timestamp": "2024-01-15T10:30:02Z",
    "tokens_used": 23,
    "response_time_ms": 850
  }
]

// Export chat history
GET /api/chat/{sessionId}/export?format=txt
Response: "Chat History Export\nSession ID: med_abc123...\n..."
```

---

## OSCECase Model

### Overview
The `OSCECase` model represents medical case scenarios for OSCE training, containing patient data, checklists, and scoring criteria.

### Key Properties

| Field | Type | Description |
|-------|------|-------------|
| `case_id` | `string` | Unique case identifier (e.g., "stemi-001") |
| `title` | `string` | Case title |
| `description` | `text` | Case description |
| `category` | `string` | Medical category (cardiology, emergency, etc.) |
| `difficulty` | `enum` | 'beginner', 'intermediate', 'advanced' |
| `expected_duration` | `integer` | Expected completion time (seconds) |
| `patient_data` | `json` | Complete patient information |
| `checklist` | `json` | Performance checklist items |
| `scoring_weights` | `json` | Category weights for scoring |
| `metadata` | `json` | Additional case information |
| `is_active` | `boolean` | Whether case is available |

### Patient Data Structure

```json
{
  "patient": {
    "name": "Ahmad Wijaya",
    "age": "58",
    "gender": "Male",
    "chief_complaint": "Chest pain for 2 hours",
    "history": "Patient presents with severe crushing chest pain..."
  },
  "vitals": {
    "blood_pressure": "160/95",
    "heart_rate": "110",
    "temperature": "37.2",
    "respiratory_rate": "22",
    "oxygen_saturation": "98%"
  },
  "physical_exam": {
    "general": "Patient appears anxious and diaphoretic",
    "cardiovascular": "S1, S2 normal, no murmurs",
    "respiratory": "Clear bilateral breath sounds"
  },
  "lab_results": {
    "troponin": "Elevated (0.8 ng/mL)",
    "creatinine": "Normal (1.0 mg/dL)",
    "glucose": "140 mg/dL"
  },
  "imaging": {
    "ecg": "ST elevation in leads II, III, aVF",
    "chest_xray": "Normal heart size, clear lungs"
  }
}
```

### Checklist Structure

```json
{
  "history": [
    "Asked about onset of pain",
    "Asked about pain characteristics", 
    "Asked about associated symptoms",
    "Obtained past medical history"
  ],
  "examination": [
    "Checked vital signs",
    "Performed cardiovascular examination",
    "Performed respiratory examination"
  ],
  "investigations": [
    "Ordered ECG",
    "Ordered cardiac enzymes",
    "Ordered chest X-ray"
  ],
  "diagnosis": [
    "Identified STEMI",
    "Explained diagnosis to patient"
  ],
  "management": [
    "Administered aspirin",
    "Arranged urgent cardiology consultation",
    "Discussed treatment plan"
  ]
}
```

### Key Methods

```php
// Get duration in minutes
$case->getExpectedDurationInMinutes(): int

// Get patient information sections
$case->getPatientInfo(): array
$case->getVitalSigns(): array
$case->getPhysicalExam(): array
$case->getLabResults(): array
$case->getImagingResults(): array

// Get checklist information
$case->getChecklistCategories(): array
$case->getChecklistItems(string $category): array
$case->getTotalChecklistItems(): int

// Scoring
$case->calculateMaxScore(): float
$case->getCompletionStats(): array
```

### Frontend Usage Examples

```javascript
// Get available cases
GET /api/osce/cases
Response: [
  {
    "id": 1,
    "case_id": "stemi-001",
    "title": "Acute ST-Elevation Myocardial Infarction",
    "description": "58-year-old male with acute chest pain",
    "category": "cardiology",
    "difficulty": "intermediate",
    "expected_duration_minutes": 20,
    "total_checklist_items": 18,
    "stats": {
      "total_attempts": 156,
      "completed_attempts": 142,
      "completion_rate": 91.0,
      "average_score": 78.5
    }
  }
]

// Get case details
GET /api/osce/cases/{caseId}
Response: {
  "case_id": "stemi-001",
  "title": "Acute ST-Elevation Myocardial Infarction",
  "patient_data": { /* full patient data */ },
  "checklist": { /* checklist structure */ },
  "scoring_weights": {
    "history": 3,
    "examination": 2,
    "investigations": 2,
    "diagnosis": 3,
    "management": 2
  }
}
```

---

## OSCESession Model

### Overview
The `OSCESession` model tracks active OSCE training sessions, managing conversation logs, checklist progress, and performance scoring.

### Key Properties

| Field | Type | Description |
|-------|------|-------------|
| `session_id` | `string` | Reference to main session |
| `case_id` | `integer` | Reference to OSCE case |
| `status` | `enum` | 'active', 'completed', 'abandoned' |
| `started_at` | `datetime` | OSCE session start time |
| `completed_at` | `datetime` | OSCE session completion time |
| `duration` | `integer` | Session duration in seconds |
| `score` | `decimal` | Final percentage score (0-100) |
| `checklist_progress` | `json` | Current checklist completion state |
| `conversation_log` | `json` | Complete conversation history |
| `performance_data` | `json` | Detailed performance metrics |
| `feedback` | `text` | AI-generated feedback |

### Conversation Log Structure

```json
[
  {
    "role": "user",
    "content": "Hello, can you tell me about your chest pain?",
    "timestamp": "2024-01-15T10:30:00Z",
    "metadata": {}
  },
  {
    "role": "assistant",
    "content": "Dokter, saya merasakan nyeri dada yang sangat berat sejak 2 jam yang lalu...",
    "timestamp": "2024-01-15T10:30:02Z", 
    "metadata": {
      "checklist_updates": ["Asked about onset of pain"]
    }
  }
]
```

### Checklist Progress Structure

```json
{
  "history": {
    "Asked about onset of pain": true,
    "Asked about pain characteristics": true,
    "Asked about associated symptoms": false,
    "Obtained past medical history": false
  },
  "examination": {
    "Checked vital signs": true,
    "Performed cardiovascular examination": false,
    "Performed respiratory examination": false
  }
}
```

### Key Methods

```php
// Status checks
$osceSession->isActive(): bool
$osceSession->isCompleted(): bool
$osceSession->isAbandoned(): bool

// Duration and progress
$osceSession->getDurationInMinutes(): int
$osceSession->getProgressPercentage(): float

// Conversation management
$osceSession->addConversationEntry(string $role, string $content, array $metadata = []): void

// Checklist management
$osceSession->updateChecklistItem(string $category, string $item, bool $completed = true): void
$osceSession->getCategoryProgress(string $category): array

// Session completion
$osceSession->markCompleted(float $score, string $feedback = null): void
$osceSession->markAbandoned(): void

// Analytics
$osceSession->getConversationSummary(): array
```

### Frontend Usage Examples

```javascript
// Start OSCE session
POST /api/osce/{sessionId}/start
Body: {
  "case_id": "stemi-001"
}
Response: {
  "success": true,
  "osce_session_id": 123,
  "case": {
    "id": "stemi-001",
    "title": "Acute ST-Elevation Myocardial Infarction",
    "category": "cardiology",
    "difficulty": "intermediate",
    "expected_duration_minutes": 20
  },
  "message": "OSCE session started successfully. You can now begin interacting with the patient."
}

// Send input to patient
POST /api/osce/{sessionId}/input
Body: {
  "content": "Can you describe your chest pain?"
}
Response: {
  "success": true,
  "patient_response": "Dokter, nyeri dada saya seperti ditekan benda berat. Sangat sakit sekali...",
  "progress": 15.8,
  "session_duration_minutes": 3,
  "checklist_categories": ["history", "examination", "investigations", "diagnosis", "management"]
}

// Get current OSCE status
GET /api/osce/{sessionId}/status
Response: {
  "active": true,
  "case": {
    "id": "stemi-001",
    "title": "Acute ST-Elevation Myocardial Infarction",
    "category": "cardiology",
    "difficulty": "intermediate"
  },
  "progress_percentage": 35.2,
  "duration_minutes": 8,
  "conversation_count": 14,
  "checklist_summary": {
    "history": {
      "total_items": 4,
      "completed_items": 3,
      "percentage": 75.0
    },
    "examination": {
      "total_items": 3,
      "completed_items": 1,
      "percentage": 33.3
    }
  }
}

// Complete OSCE session
POST /api/osce/{sessionId}/complete
Response: {
  "success": true,
  "score": 78.5,
  "feedback": "Good history taking skills. Consider more thorough cardiovascular examination...",
  "duration_minutes": 18,
  "progress_percentage": 87.5,
  "checklist_summary": { /* detailed breakdown */ },
  "conversation_summary": {
    "total_exchanges": 28,
    "user_messages": 14,
    "ai_responses": 14,
    "duration": 18
  }
}

// Abandon OSCE session
POST /api/osce/{sessionId}/abandon
Response: {
  "success": true,
  "message": "OSCE session has been abandoned."
}
```

---

## SystemLog Model

### Overview
The `SystemLog` model provides comprehensive logging for errors, performance metrics, health checks, and system monitoring.

### Key Properties

| Field | Type | Description |
|-------|------|-------------|
| `session_id` | `string` | Associated session (nullable) |
| `type` | `enum` | 'error', 'health_check', 'performance', 'api_call', 'system_status' |
| `level` | `enum` | 'debug', 'info', 'warning', 'error', 'critical' |
| `context` | `string` | Context/component that generated the log |
| `message` | `text` | Log message |
| `data` | `json` | Additional structured data |
| `user_agent` | `string` | User agent (nullable) |
| `ip_address` | `string` | IP address (nullable) |

### Static Helper Methods

```php
// Log different types of events
SystemLog::logError(string $sessionId, string $context, string $message, array $data = []): SystemLog
SystemLog::logHealthCheck(string $message, array $data = []): SystemLog
SystemLog::logApiCall(string $sessionId, string $message, array $data = [], int $responseTime = null): SystemLog
SystemLog::logPerformance(string $sessionId, string $context, string $message, array $data = []): SystemLog
```

### Scopes Available

```php
// Filter by type/level
SystemLog::byType('error')
SystemLog::byLevel('critical')
SystemLog::errors()
SystemLog::critical()

// Filter by time
SystemLog::recent(24) // last 24 hours

// Filter by context
SystemLog::byContext('Chat Service')
```

### Frontend Usage Examples

```javascript
// Get system health status
GET /api/system/health
Response: {
  "overall": "healthy",
  "issues": [],
  "components": {
    "ai_service": "healthy",
    "database": "healthy",
    "cache": "healthy"
  }
}

// Get system statistics
GET /api/system/stats
Response: {
  "total_sessions": 1250,
  "active_sessions": 23,
  "total_chat_messages": 15420,
  "total_osce_sessions": 890,
  "total_errors": 12,
  "average_session_duration_minutes": 42,
  "recent_sessions": [...]
}

// Get error logs
GET /api/system/logs?type=error&hours=24&limit=50
Response: [
  {
    "id": 1,
    "type": "error",
    "level": "error",
    "context": "AI Service",
    "message": "API timeout after 30 seconds",
    "timestamp": "2024-01-15T10:30:00Z",
    "session_id": "med_abc123",
    "data": {
      "api_url": "https://openrouter.ai/api/v1/chat/completions",
      "timeout_seconds": 30
    }
  }
]
```

---

## Frontend Integration Guidelines

### 1. Session Management

Always start by creating or retrieving a session:

```javascript
class MedicalTrainingService {
  constructor() {
    this.sessionId = localStorage.getItem('medical_session_id');
  }

  async initializeSession() {
    if (!this.sessionId) {
      const response = await fetch('/api/sessions', { method: 'POST' });
      const data = await response.json();
      this.sessionId = data.session_id;
      localStorage.setItem('medical_session_id', this.sessionId);
    }
    return this.sessionId;
  }
}
```

### 2. Chat Implementation

```javascript
class ChatService {
  async sendMessage(content) {
    const response = await fetch(`/api/chat/${this.sessionId}/message`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ content })
    });
    return response.json();
  }

  async getChatHistory(limit = 50) {
    const response = await fetch(`/api/chat/${this.sessionId}/history?limit=${limit}`);
    return response.json();
  }
}
```

### 3. OSCE Implementation

```javascript
class OSCEService {
  async getAvailableCases() {
    const response = await fetch('/api/osce/cases');
    return response.json();
  }

  async startOSCE(caseId) {
    const response = await fetch(`/api/osce/${this.sessionId}/start`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ case_id: caseId })
    });
    return response.json();
  }

  async sendInput(content) {
    const response = await fetch(`/api/osce/${this.sessionId}/input`, {
      method: 'POST', 
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ content })
    });
    return response.json();
  }

  async getStatus() {
    const response = await fetch(`/api/osce/${this.sessionId}/status`);
    return response.json();
  }

  async completeSession() {
    const response = await fetch(`/api/osce/${this.sessionId}/complete`, {
      method: 'POST'
    });
    return response.json();
  }
}
```

### 4. Real-time Updates

Consider implementing WebSocket or Server-Sent Events for:
- Real-time OSCE progress updates
- Chat message status (typing indicators, etc.)
- Session health monitoring
- Live statistics updates

### 5. Error Handling

Implement consistent error handling:

```javascript
class APIService {
  async request(url, options = {}) {
    try {
      const response = await fetch(url, options);
      
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
      
      const data = await response.json();
      
      if (!data.success && data.error) {
        throw new Error(data.error);
      }
      
      return data;
    } catch (error) {
      console.error('API Error:', error);
      // Log error to system (optional)
      this.logError(error, url, options);
      throw error;
    }
  }
}
```

### 6. State Management

For React/Vue applications, consider this state structure:

```javascript
// Example Redux/Vuex state
const initialState = {
  session: {
    id: null,
    isActive: false,
    stats: null
  },
  chat: {
    messages: [],
    isLoading: false,
    error: null
  },
  osce: {
    availableCases: [],
    currentSession: null,
    progress: 0,
    isActive: false
  },
  system: {
    health: 'unknown',
    stats: null
  }
};
```

---

## Common API Patterns

### 1. Response Format

All API responses follow this pattern:

```javascript
// Success response
{
  "success": true,
  "data": { /* response data */ },
  "message": "Optional success message"
}

// Error response  
{
  "success": false,
  "error": "Error message",
  "code": "ERROR_CODE" // Optional error code
}
```

### 2. Pagination

For list endpoints:

```javascript
GET /api/endpoint?page=1&limit=20&sort=created_at&order=desc

Response: {
  "data": [...],
  "pagination": {
    "current_page": 1,
    "total_pages": 5,
    "total_items": 100,
    "per_page": 20
  }
}
```

### 3. Filtering

Most endpoints support filtering:

```javascript
// Filter examples
GET /api/chat/messages?role=user&date_from=2024-01-01
GET /api/osce/cases?category=cardiology&difficulty=intermediate
GET /api/system/logs?type=error&level=critical&hours=24
```

### 4. Real-time Data

For frequently updated data, implement polling or WebSocket:

```javascript
// Polling example
setInterval(async () => {
  if (this.osceActive) {
    const status = await this.osceService.getStatus();
    this.updateOSCEProgress(status);
  }
}, 5000); // Poll every 5 seconds
```

This documentation should provide everything needed to implement the frontend integration with these models. Each model includes practical examples and common usage patterns that frontend developers will encounter.