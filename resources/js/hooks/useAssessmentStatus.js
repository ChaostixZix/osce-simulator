import { useState, useEffect, useRef, useCallback } from 'react';

/**
 * Custom hook for assessment status polling (WebSocket notifications handle completion)
 * Simplified version - no more SSE, just polling for status checks
 */
export default function useAssessmentStatus(sessionId, options = {}) {
  const {
    pollInterval = 3000, // 3 seconds polling for status checks
    maxRetries = 3,
    onStatusChange = null,
    enablePolling = true,
  } = options;

  const [status, setStatus] = useState(null);
  const [error, setError] = useState(null);
  const [isPolling, setIsPolling] = useState(false);

  const pollTimerRef = useRef(null);
  const retryCountRef = useRef(0);

  const updateStatus = useCallback((newStatus) => {
    setStatus(prev => {
      // Only update if different
      if (!prev || JSON.stringify(prev) !== JSON.stringify(newStatus)) {
        if (onStatusChange) {
          onStatusChange(newStatus, prev);
        }
        return newStatus;
      }
      return prev;
    });
  }, [onStatusChange]);

  const startPolling = useCallback(async () => {
    if (!sessionId || !enablePolling) return;

    setIsPolling(true);

    try {
      const response = await fetch(route('osce.status', sessionId));
      if (response.ok) {
        const data = await response.json();
        updateStatus(data);
        setError(null);
        retryCountRef.current = 0;

        // Stop polling if assessment is completed or failed
        if (data.status === 'completed' || data.status === 'failed') {
          console.log('Assessment finished, stopping polling');
          setIsPolling(false);
          return;
        }
      } else {
        throw new Error(`HTTP ${response.status}`);
      }
    } catch (err) {
      console.error('Polling error:', err);
      retryCountRef.current += 1;
      
      if (retryCountRef.current >= maxRetries) {
        setError('Failed to fetch status after multiple retries');
        setIsPolling(false);
        return;
      } else {
        setError(`Polling error (attempt ${retryCountRef.current}/${maxRetries})`);
      }
    }

    // Schedule next poll
    pollTimerRef.current = setTimeout(startPolling, pollInterval);
  }, [sessionId, pollInterval, updateStatus, enablePolling, maxRetries]);

  const stopPolling = useCallback(() => {
    if (pollTimerRef.current) {
      clearTimeout(pollTimerRef.current);
      pollTimerRef.current = null;
    }
    setIsPolling(false);
  }, []);

  // Initialize polling
  useEffect(() => {
    if (!sessionId || !enablePolling) return;

    startPolling();
    return stopPolling;
  }, [sessionId, enablePolling, startPolling, stopPolling]);

  // Cleanup on unmount
  useEffect(() => {
    return stopPolling;
  }, [stopPolling]);

  return {
    status,
    isPolling,
    error,
    retryCount: retryCountRef.current,
    startPolling,
    stopPolling,
  };
}