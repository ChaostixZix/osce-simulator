import { useState, useEffect, useRef, useCallback } from 'react';

/**
 * Custom hook for real-time assessment status tracking
 * Supports both Server-Sent Events (SSE) and polling fallback
 */
export default function useAssessmentStatus(sessionId, options = {}) {
  const {
    enableSSE = true,
    pollInterval = 5000,
    maxRetries = 3,
    onStatusChange = null,
  } = options;

  const [status, setStatus] = useState(null);
  const [isConnected, setIsConnected] = useState(false);
  const [error, setError] = useState(null);
  const [retryCount, setRetryCount] = useState(0);

  const eventSourceRef = useRef(null);
  const pollTimerRef = useRef(null);
  const retryTimerRef = useRef(null);

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

  const connectSSE = useCallback(() => {
    if (!enableSSE || !sessionId) return;

    try {
      const eventSource = new EventSource(route('osce.status.stream', sessionId));
      eventSourceRef.current = eventSource;

      eventSource.onopen = () => {
        console.log('SSE connection opened');
        setIsConnected(true);
        setError(null);
        setRetryCount(0);
      };

      eventSource.addEventListener('status-update', (event) => {
        try {
          const data = JSON.parse(event.data);
          updateStatus(data);
        } catch (err) {
          console.error('Failed to parse SSE data:', err);
        }
      });

      eventSource.onerror = (event) => {
        console.error('SSE error:', event);
        setIsConnected(false);
        
        if (eventSource.readyState === EventSource.CLOSED) {
          // Connection closed, attempt retry
          if (retryCount < maxRetries) {
            const delay = Math.min(1000 * Math.pow(2, retryCount), 30000);
            console.log(`SSE reconnecting in ${delay}ms (attempt ${retryCount + 1}/${maxRetries})`);
            
            retryTimerRef.current = setTimeout(() => {
              setRetryCount(prev => prev + 1);
              connectSSE();
            }, delay);
          } else {
            console.log('SSE max retries reached, falling back to polling');
            setError('Real-time connection failed, using polling');
            startPolling();
          }
        }
      };

    } catch (err) {
      console.error('Failed to create SSE connection:', err);
      setError('Failed to establish real-time connection');
      startPolling();
    }
  }, [sessionId, enableSSE, retryCount, maxRetries, updateStatus]);

  const startPolling = useCallback(async () => {
    if (!sessionId) return;

    try {
      const response = await fetch(route('osce.status', sessionId));
      if (response.ok) {
        const data = await response.json();
        updateStatus(data);
        setError(null);
      } else {
        throw new Error(`HTTP ${response.status}`);
      }
    } catch (err) {
      console.error('Polling error:', err);
      setError('Failed to fetch status');
    }

    // Schedule next poll
    pollTimerRef.current = setTimeout(startPolling, pollInterval);
  }, [sessionId, pollInterval, updateStatus]);

  const disconnect = useCallback(() => {
    // Close SSE connection
    if (eventSourceRef.current) {
      eventSourceRef.current.close();
      eventSourceRef.current = null;
    }

    // Clear timers
    if (pollTimerRef.current) {
      clearTimeout(pollTimerRef.current);
      pollTimerRef.current = null;
    }

    if (retryTimerRef.current) {
      clearTimeout(retryTimerRef.current);
      retryTimerRef.current = null;
    }

    setIsConnected(false);
  }, []);

  // Initialize connection
  useEffect(() => {
    if (!sessionId) return;

    // Try SSE first, fall back to polling
    if (enableSSE && typeof EventSource !== 'undefined') {
      connectSSE();
    } else {
      startPolling();
    }

    return disconnect;
  }, [sessionId, enableSSE, connectSSE, startPolling, disconnect]);

  // Cleanup on unmount
  useEffect(() => {
    return disconnect;
  }, [disconnect]);

  return {
    status,
    isConnected,
    error,
    retryCount,
    reconnect: connectSSE,
    disconnect,
  };
}