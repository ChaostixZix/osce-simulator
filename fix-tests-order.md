# Fix for OSCE Test Order and Result Display Issues

## 1. Context

Users are experiencing two primary issues with the OSCE test ordering feature:
1.  **Delayed Results**: Test results take much longer to appear than the configured turnaround time.
2.  **Inconsistent UI Updates**: The UI does not update reliably to show new test results, often requiring multiple manual page refreshes.
3.  **Static Results Display**: The current results view is static and does not allow for expanding/collapsing individual test results for better readability.

The root causes are a bug in the backend time calculation for result availability and inefficient data refreshing on the frontend. This document outlines the required fixes.

## 2. Files to Edit

1.  `webapp/app/Http/Controllers/OsceController.php`
2.  `webapp/resources/js/pages/OsceChat.jsx`

## 3. Solution

### Backend Fix: Correct Time Calculation

In `webapp/app/Http/Controllers/OsceController.php`, the `orderTests` method incorrectly uses `addSeconds` instead of `addMinutes` when setting the `results_available_at` timestamp.

**Change this:**
```php
'results_available_at' => now()->addSeconds($test->turnaround_minutes),
```

**To this:**
```php
'results_available_at' => now()->addMinutes($test->turnaround_minutes),
```

### Frontend Fix: UI Refresh and Expandable Results

In `webapp/resources/js/pages/OsceChat.jsx`, we will implement two changes: a reliable manual refresh and an expandable UI for test results.

#### A. Reliable Refresh Logic

Update the `refreshResults` function to use Inertia's `router.reload()` to ensure fresh session data is pulled from the server.

**Change this:**
```javascript
const refreshResults = async () => {
  try {
    setResultsLoading(true);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const res = await fetch(`/api/osce/refresh-results/${session.id}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf ?? '' },
      credentials: 'same-origin'
    });
    const data = await res.json();
    if (res.ok) {
      const updated = data?.ordered_tests || data?.session?.ordered_tests || data?.session?.orderedTests || [];
      setOrderedTestsView(updated);
      setHasLoadedResults(true);
    }
  } catch (e) {
    console.warn('Failed to refresh results');
  } finally {
    setResultsLoading(false);
  }
};
```

**To this:**
```javascript
const refreshResults = async () => {
    setResultsLoading(true);
    setError('');
    try {
      // The API call triggers the job on the backend.
      await fetch(`/api/osce/refresh-results/${session.id}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '' },
        credentials: 'same-origin'
      });
      
      // router.reload() fetches fresh props from the server.
      router.reload({ 
        only: ['session', 'sessionData'], 
        preserveScroll: true,
        onSuccess: () => setResultsLoading(false),
        onError: () => {
          setError('Failed to refresh session data.');
          setResultsLoading(false);
        }
      });
    } catch (e) {
      setError('An error occurred while refreshing results.');
      setResultsLoading(false);
    }
  };
```

#### B. Expandable Results UI

We need to add state to manage which results are expanded and modify the JSX to render them accordingly.

**1. Add a new state variable for tracking expanded items:**
```javascript
// Add this near the other useState declarations
const [expandedResults, setExpandedResults] = useState({});

const toggleResultExpansion = (testId) => {
  setExpandedResults(prev => ({
    ...prev,
    [testId]: !prev[testId]
  }));
};
```

**2. Modify the results modal to use this state:**

Replace the existing mapping in the "Ordered Tests / Results Modal" with this new structure that includes a button to toggle the details.

**Replace this section:**
```javascript
orderedTestsView.map((t, idx) => (
  <div key={idx} className="p-4 border rounded-lg">
    {/* ... current content ... */}
    {t.results?.message && (
      <div className="mt-1 text-xs text-slate-600">{t.results.message}</div>
    )}
    {Array.isArray(t.results?.values) && t.results.values.length > 0 && (
      <div className="mt-2">
        <div className="text-xs font-medium mb-1">Values:</div>
        <ul className="pl-4 list-disc text-xs text-slate-700">
          {t.results.values.map((v, i) => (
            <li key={i}>{typeof v === 'string' ? v : JSON.stringify(v)}</li>
          ))}
        </ul>
      </div>
    )}
  </div>
))
```

**With this new expandable structure:**
```javascript
orderedTestsView.map((t, idx) => {
  const testId = t.id || idx;
  const isExpanded = expandedResults[testId];

  return (
    <div key={testId} className="p-4 border rounded-lg transition-all duration-300">
      <div className="flex items-center justify-between">
        <div className="font-mono font-semibold text-sm">{t.test_name || t.testName}</div>
        <button onClick={() => toggleResultExpansion(testId)} className="text-sm text-blue-500">
          {isExpanded ? 'Collapse' : 'Expand'}
        </button>
      </div>
      <div className="text-xs text-slate-500">{formatDate(t.ordered_at || t.orderedAt)}</div>
      
      {/* Expandable Content */}
      <div className={`transition-all duration-500 ease-in-out overflow-hidden ${isExpanded ? 'max-h-96 mt-2' : 'max-h-0'}`}>
        <div className="pt-2 border-t">
          {t.results?.message && (
            <div className="mt-1 text-sm text-slate-600">{t.results.message}</div>
          )}
          {Array.isArray(t.results?.values) && t.results.values.length > 0 ? (
            <div className="mt-2">
              <div className="text-xs font-medium mb-1">Values:</div>
              <ul className="pl-4 list-disc text-xs text-slate-700">
                {t.results.values.map((v, i) => (
                  <li key={i}>{v.name}: {v.value} {v.unit || ''} {v.flag ? `(${v.flag})` : ''}</li>
                ))}
              </ul>
            </div>
          ) : (
            <div className="text-xs text-slate-500 mt-2">No detailed values available.</div>
          )}
          {t.results?.interpretation && (
            <div className="mt-2">
              <div className="text-xs font-medium mb-1">Interpretation:</div>
              <p className="text-xs text-slate-700">{t.results.interpretation}</p>
            </div>
          )}
        </div>
      </div>
    </div>
  );
})
```
