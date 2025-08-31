Feature slug: live-test-ready-notification

Objective

- Implement live notifications so that when an ordered test finishes processing, the user immediately sees a toast: "Dok tets untuk {nama tests} sudah siap". The toast should appear on the OSCE session UI without manual refresh and work with the current SPA stack (Laravel + Inertia + React preferred; keep Vue pages stable).

Reuse-First Checklist (do this before coding)

- Search for existing realtime setup (Laravel Echo, Reverb, Pusher) and existing private channels for OSCE (e.g., `osce.sessions.{sessionId}`) — reuse the same transport and patterns.
- Check for any existing events for queue/assessment status (e.g., "queue indicators" work) — piggyback on the same client bootstrap and helpers if present.
- Check if there is a generic Toast/Notification component/hook (e.g., `useToast`, `NotificationsProvider`) — reuse; otherwise add a minimal local toast.
- Identify where a test order transitions to "ready" (controller/job/service). Wire event dispatch precisely at the status flip.

Backend (Laravel)

- Event: `webapp/app/Events/TestOrderReady.php`
  - Implements `ShouldBroadcast`.
  - Payload: `session_id`, `order_id`, `test_name`, `ready_at` (timestamp).
  - Channel: private `osce.sessions.{sessionId}`.
  - Name the event `TestOrderReady` (class also determines broadcast name unless overridden).

- Channels: `webapp/routes/channels.php`
  - Authorize private channel `osce.sessions.{session}` so only the owner (or allowed roles) of the session can subscribe.
  - Example:
    - `Broadcast::channel('osce.sessions.{session}', fn(User $u, OsceSession $session) => $u->id === $session->user_id || $u->is_admin);`

- Dispatch location
  - Identify the exact spot where a test order becomes ready (e.g., `OrderTestJob` completion, service method, or controller). Immediately after persisting `status='ready'`, dispatch the event:
    - `TestOrderReady::dispatch(session_id: $session->id, order_id: $order->id, test_name: $order->name, ready_at: now());`

- Config
  - Reuse existing broadcast driver if already set (prefer `reverb` or `pusher`). If none:
    - `.env`: `BROADCAST_DRIVER=reverb` and configure Reverb per Laravel docs; local dev can run `php artisan reverb:start` alongside app.
  - Keep queueing consistent; broadcasting can be queued.

Frontend (Inertia React preferred)

- Subscription hook (reuse existing if present)
  - Create or extend a session-level hook, e.g., `webapp/resources/js/hooks/useOsceSessionRealtime.js`:
    - On mount, subscribe via Echo to `private-osce.sessions.${sessionId}` and listen for `TestOrderReady`.
    - On event, show toast: `Dok tets untuk ${test_name} sudah siap`.
    - Clean up subscription on unmount.

- Toast UI
  - If a global toast system exists (e.g., `useToast` from your UI kit), use it.
  - Else, add a minimal toast component under `webapp/resources/js/Components/Notifications/Toast.jsx` and a small provider to render stacked toasts.
  - Auto-dismiss after ~5 seconds, allow click to open results (optional enhancement).

- Integration point
  - In the OSCE session page/component (React), initialize the hook with the current `sessionId` once mounted. Ensure only one subscription per page.
  - If Vue page is still used for this screen, create a small Vue plugin or composition function to subscribe similarly (do not mix React+Vue on one page).

Example (React, pseudo-code)

```js
// hooks/useOsceSessionRealtime.js
import Echo from '../lib/echo'
import { useEffect } from 'react'
import { useToast } from '../components/ui/use-toast' // reuse if exists

export function useOsceSessionRealtime(sessionId){
  const { toast } = useToast()
  useEffect(()=>{
    if (!sessionId || !Echo) return
    const channel = Echo.private(`osce.sessions.${sessionId}`)
      .listen('TestOrderReady', (e)=>{
        const name = e?.test_name ?? 'pemeriksaan'
        toast({ title: `Dok tets untuk ${name} sudah siap` })
      })
    return ()=> { try { channel?.unsubscribe?.() } catch(_){} }
  },[sessionId])
}
```

Acceptance Criteria

1) When a test order flips to ready, backend broadcasts `TestOrderReady` on `private-osce.sessions.{sessionId}` with `test_name`.
2) Active session UI shows a toast instantly: `Dok tets untuk {nama tests} sudah siap`, no page refresh needed.
3) Only the session owner (or permitted roles) receives the event; others do not.
4) Subscriptions clean up on navigation (no memory leaks or duplicate toasts).
5) If realtime is not configured, the app remains functional (no errors); notification is simply not shown or can fall back to a light poll (optional future).

Notes

- Reuse existing Echo/Reverb bootstrap if available (usually in `resources/js/bootstrap.js` or a similar file). Avoid creating parallel clients.
- Message copy kept verbatim per request. If you prefer "tes" instead of "tets", update string centrally.
- Optional: clicking the toast can navigate to the results view for that order; wire `onClick` to open the results modal if available.

Files to Add/Touch

- Add: `webapp/app/Events/TestOrderReady.php`
- Touch: `webapp/routes/channels.php` (authorize channel)
- Touch: The job/service/controller that marks orders ready (dispatch event)
- Add or reuse: `webapp/resources/js/hooks/useOsceSessionRealtime.js`
- Reuse or add: toast UI under `webapp/resources/js/Components/Notifications/`

Quick Commands (backend)

```bash
cd webapp
php artisan make:event TestOrderReady
# ensure ShouldBroadcast and channel/payload implementation
```

