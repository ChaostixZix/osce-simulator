import { useEffect, useRef, useState } from 'react';
import { useToast } from '../Components/Notifications/ToastContainer';

export function useOsceSessionRealtime(sessionId) {
  const { toast } = useToast();
  const channelRef = useRef(null);
  const echoRef = useRef(null);
  const [isInitialized, setIsInitialized] = useState(false);

  useEffect(() => {
    // Skip if no session ID
    if (!sessionId) {
      return;
    }

    // Initialize Echo if not already done
    const initializeEcho = async () => {
      // Check if broadcasting is configured (not 'log' driver)
      if (!window?.Laravel?.broadcasting?.driver || window.Laravel.broadcasting.driver === 'log') {
        console.log('Broadcasting not configured or using log driver, skipping Echo initialization');
        return;
      }

      // Skip if already initialized
      if (echoRef.current) {
        return;
      }

      try {
        const LaravelEcho = (await import('laravel-echo')).default;

        if (window.Laravel.broadcasting.driver === 'pusher') {
          const Pusher = (await import('pusher-js')).default;

          echoRef.current = new LaravelEcho({
            broadcaster: 'pusher',
            key: window.Laravel.broadcasting.key,
            cluster: window.Laravel.broadcasting.cluster,
            forceTLS: true,
            authorizer: (channel, options) => {
              return {
                authorize: (socketId, callback) => {
                  fetch('/broadcasting/auth', {
                    method: 'POST',
                    headers: {
                      'Content-Type': 'application/json',
                      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                      socket_id: socketId,
                      channel_name: channel.name,
                    })
                  })
                  .then(response => response.json())
                  .then(data => callback(null, data))
                  .catch(error => callback(error, null));
                }
              };
            }
          });
        } else if (window.Laravel.broadcasting.driver === 'reverb') {
          echoRef.current = new LaravelEcho({
            broadcaster: 'reverb',
            key: window.Laravel.broadcasting.key,
            wsHost: window.Laravel.broadcasting.wsHost,
            wsPort: window.Laravel.broadcasting.wsPort,
            wssPort: window.Laravel.broadcasting.wssPort,
            forceTLS: window.Laravel.broadcasting.forceTLS,
            enabledTransports: ['ws', 'wss'],
            authorizer: (channel, options) => {
              return {
                authorize: (socketId, callback) => {
                  fetch('/broadcasting/auth', {
                    method: 'POST',
                    headers: {
                      'Content-Type': 'application/json',
                      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                      socket_id: socketId,
                      channel_name: channel.name,
                    })
                  })
                  .then(response => response.json())
                  .then(data => callback(null, data))
                  .catch(error => callback(error, null));
                }
              };
            }
          });
        }

        setIsInitialized(true);
      } catch (error) {
        console.warn('Laravel Echo initialization failed:', error);
      }
    };

    initializeEcho();
  }, []);

  useEffect(() => {
    // Skip if not initialized or no session ID
    if (!isInitialized || !echoRef.current || !sessionId) {
      return;
    }

    // Prevent duplicate subscriptions
    if (channelRef.current) {
      return;
    }

    try {
      // Subscribe to the private channel
      const channel = echoRef.current.private(`osce.sessions.${sessionId}`);
      channelRef.current = channel;

      // Listen for test ready notifications
      channel.listen('TestOrderReady', (event) => {
        const testName = event?.test_name || 'pemeriksaan';
        toast({
          title: `Dok tes untuk ${testName} sudah siap`,
          type: 'success',
          duration: 6000 // Show for 6 seconds
        });
      });

      console.log(`Subscribed to OSCE session ${sessionId} realtime notifications`);

    } catch (error) {
      console.warn('Failed to subscribe to OSCE session channel:', error);
    }

    // Cleanup function
    return () => {
      if (channelRef.current && echoRef.current) {
        try {
          channelRef.current.stopListening('TestOrderReady');
          echoRef.current.leave(`osce.sessions.${sessionId}`);
          console.log(`Unsubscribed from OSCE session ${sessionId} realtime notifications`);
        } catch (error) {
          console.warn('Error during realtime cleanup:', error);
        }

        channelRef.current = null;
      }
    };
  }, [sessionId, toast, isInitialized]);

  // Return connection status for debugging
  return {
    isConnected: !!channelRef.current,
    hasEcho: !!echoRef.current,
    isInitialized
  };
}