# OSCE Timer Persistence Fix

This document explains the solution implemented to fix the OSCE timer issue where it was resetting to 25 minutes on page refresh.

## Problem Description

The original issue was that when a user refreshed the page or navigated away and back to an OSCE session, the timer would reset to the full duration (25 minutes) instead of continuing from where it left off.

## Root Cause

The timer calculation was always based on the original `started_at` timestamp, using `now()->diffInSeconds($this->started_at)` to calculate elapsed time. This meant that any interruption in the user's session (page refresh, tab switch, etc.) wasn't accounted for, causing the timer to "catch up" all at once.

## Solution Overview

The solution implements a **pause/resume timer system** that:

1. **Automatically pauses** the timer when the user leaves the page or tab
2. **Automatically resumes** the timer when the user returns
3. **Tracks paused time** separately from active time
4. **Persists timer state** in the database to survive page refreshes

## Implementation Details

### 1. Database Schema Changes

**New Migration**: `2025_01_17_000001_add_timer_persistence_fields_to_osce_sessions_table.php`

Added fields to `osce_sessions` table:
- `paused_at` - timestamp when timer was paused
- `resumed_at` - timestamp when timer was resumed
- `total_paused_seconds` - cumulative paused time
- `current_remaining_seconds` - stored remaining time when paused

### 2. Model Updates (`OsceSession.php`)

**New Methods:**
- `isPaused()` - checks if timer is currently paused
- `pauseTimer()` - pauses the timer and stores current state
- `resumeTimer()` - resumes timer and accumulates paused time
- `autoPauseTimer()` / `autoResumeTimer()` - automatic pause/resume
- `getActualElapsedSeconds()` - calculates elapsed time excluding paused periods

**Updated Logic:**
- `getRemainingSecondsAttribute()` now accounts for paused time
- `getElapsedSecondsAttribute()` uses actual elapsed time calculation

### 3. Controller Updates (`OsceController.php`)

**Updated Endpoints:**
- `getSessionTimer()` - auto-resumes timer when accessed, returns pause state
- Added `pauseSession()` - manual pause endpoint
- Added `resumeSession()` - manual resume endpoint  
- Added `autoPauseSession()` - automatic pause endpoint

**New Routes:**
```php
Route::post('api/osce/sessions/{session}/pause', [OsceController::class, 'pauseSession']);
Route::post('api/osce/sessions/{session}/resume', [OsceController::class, 'resumeSession']);
Route::post('api/osce/sessions/{session}/auto-pause', [OsceController::class, 'autoPauseSession']);
```

### 4. Frontend Updates (`SessionTimer.vue`)

**Auto-Pause Implementation:**
- `beforeunload` event listener to pause on page refresh/close
- `visibilitychange` event listener to pause when switching tabs
- Automatic cleanup of event listeners

**Server Sync Improvements:**
- Handles `is_paused` status from server
- Syncs local pause state with server state
- Shows appropriate UI messages for paused state

**Visual Feedback:**
- Shows "Timer paused - will resume automatically when you return" message
- Maintains all existing timer functionality

## How It Works

### Normal Flow
1. User starts OSCE session → Timer runs normally
2. User works on session → Timer counts down
3. Session completes or expires → Timer stops

### Interruption Flow
1. User starts OSCE session → Timer runs normally
2. User switches tabs/refreshes page → **Auto-pause triggered**
   - Current remaining time stored in database
   - `paused_at` timestamp recorded
3. User returns to session → **Auto-resume triggered**
   - Paused duration calculated and added to `total_paused_seconds`
   - Timer continues from where it left off
   - `resumed_at` timestamp recorded

### Calculation Logic

```php
// Actual elapsed time = Total time since start - Total paused time
$actualElapsed = $totalElapsedSinceStart - $totalPausedSeconds;

// If currently paused, add current pause duration
if ($isPaused) {
    $currentPauseDuration = now()->diffInSeconds($pausedAt);
    $totalPausedSeconds += $currentPauseDuration;
}

// Remaining time = Duration - Actual elapsed time
$remainingTime = ($durationMinutes * 60) - $actualElapsed;
```

## Testing

Comprehensive tests were created:

### Unit Tests (`OsceSessionTimerTest.php`)
- Timer calculation accuracy
- Pause/resume functionality
- Multiple pause cycles
- Edge cases and error conditions

### Feature Tests (`OsceSessionTimerApiTest.php`)
- API endpoint functionality
- Auto-pause/resume behavior
- Authentication and authorization
- Timer persistence across requests

### Frontend Tests (`SessionTimer.test.js`)
- Component rendering and state management
- Event handling (beforeunload, visibilitychange)
- Server synchronization
- User interface updates

## Benefits

1. **True Timer Persistence** - Timer survives page refreshes, tab switches, browser restarts
2. **Accurate Time Tracking** - Only counts time when user is actually active
3. **Better User Experience** - No lost time due to technical issues
4. **Automatic Operation** - No user intervention required
5. **Backward Compatible** - Existing sessions continue to work normally

## Migration Notes

- The migration adds nullable columns, so existing sessions are unaffected
- Default values ensure backward compatibility
- Indexes added for performance on common queries

## Security Considerations

- All timer endpoints require user authentication
- Users can only access their own sessions
- Auto-pause prevents timer manipulation
- Server-side validation of all timer operations

## Future Enhancements

Possible improvements:
- Add timer analytics (pause frequency, duration patterns)
- Implement timer warnings before auto-pause
- Add manual pause/resume controls for users
- Create timer history for audit purposes