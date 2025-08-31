import { useEffect, useRef } from 'react';
import { useToast } from '../Components/Notifications/ToastContainer';

// Initialize Echo only if broadcast is configured
let Echo = null;

try {
  // Check if broadcasting is configured (not 'log' driver)
  if (window?.Laravel?.broadcasting?.driver && window.Laravel.broadcasting.driver !== 'log') {
    import('laravel-echo')
      .then((module) => {
        const LaravelEcho = module.default;
        
        // Import Pusher if needed
        if (window.Laravel.broadcasting.driver === 'pusher') {
          import('pusher-js').then((PusherModule) => {
            const Pusher = PusherModule.default;
            
            Echo = new LaravelEcho({
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
          });
        } else if (window.Laravel.broadcasting.driver === 'reverb') {
          Echo = new LaravelEcho({
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
      });
  }
} catch (error) {
  console.warn('Laravel Echo initialization failed:', error);
}

export function useOsceSessionRealtime(sessionId) {
  const { toast } = useToast();
  const channelRef = useRef(null);
  const listenerAttachedRef = useRef(false);

  useEffect(() => {
    // Skip if no Echo instance or no session ID
    if (!Echo || !sessionId) {
      return;
    }

    // Prevent duplicate subscriptions
    if (channelRef.current || listenerAttachedRef.current) {
      return;
    }

    try {
      // Subscribe to the private channel
      const channel = Echo.private(`osce.sessions.${sessionId}`);
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

      listenerAttachedRef.current = true;

      console.log(`Subscribed to OSCE session ${sessionId} realtime notifications`);

    } catch (error) {
      console.warn('Failed to subscribe to OSCE session channel:', error);
    }

    // Cleanup function
    return () => {
      if (channelRef.current) {
        try {
          channelRef.current.stopListening('TestOrderReady');
          Echo.leave(`osce.sessions.${sessionId}`);
          console.log(`Unsubscribed from OSCE session ${sessionId} realtime notifications`);
        } catch (error) {
          console.warn('Error during realtime cleanup:', error);
        }
        
        channelRef.current = null;
        listenerAttachedRef.current = false;
      }
    };
  }, [sessionId, toast]);

  // Return connection status for debugging
  return {
    isConnected: !!channelRef.current,
    hasEcho: !!Echo
  };
}