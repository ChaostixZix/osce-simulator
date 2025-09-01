import { useEffect, useCallback, useState } from 'react';
import { router } from '@inertiajs/react';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Initialize Echo with Reverb WebSocket - Lazy initialization
let echo = null;

function getEcho() {
    if (!echo) {
        echo = new Echo({
            broadcaster: 'reverb',
            key: import.meta.env.VITE_REVERB_APP_KEY,
            wsHost: import.meta.env.VITE_REVERB_HOST ?? 'dev.bintangputra.my.id',
            wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
            wssPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
            forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
            enabledTransports: ['ws', 'wss'],
        });
    }
    return echo;
}

/**
 * Hook for receiving WebSocket notifications about assessment completion
 * Replaces SSE streaming with simple event-based notifications
 */
export default function useAssessmentNotification(userId, options = {}) {
    const {
        onAssessmentCompleted = null,
        onAssessmentFailed = null,
        autoRedirect = true,
    } = options;

    const [notification, setNotification] = useState(null);
    const [isConnected, setIsConnected] = useState(false);

    const handleAssessmentCompleted = useCallback((data) => {
        console.log('Assessment completed:', data);
        
        setNotification({
            type: 'success',
            message: data.message || 'Assessment completed successfully!',
            data: data,
        });

        // Custom callback
        if (onAssessmentCompleted) {
            onAssessmentCompleted(data);
        }

        // Auto redirect to results
        if (autoRedirect && data.redirect_url) {
            setTimeout(() => {
                router.visit(data.redirect_url);
            }, 1500);
        }
    }, [onAssessmentCompleted, autoRedirect]);

    const handleAssessmentFailed = useCallback((data) => {
        console.error('Assessment failed:', data);
        
        setNotification({
            type: 'error',
            message: data.message || 'Assessment failed. Please try again.',
            data: data,
        });

        // Custom callback
        if (onAssessmentFailed) {
            onAssessmentFailed(data);
        }
    }, [onAssessmentFailed]);

    useEffect(() => {
        if (!userId) return;

        console.log('Connecting to assessment notifications for user:', userId);

        // Get Echo instance and listen to private channel for this user
        const echoInstance = getEcho();
        const channel = echoInstance.private(`assessment.${userId}`);

        channel
            .listen('.assessment.completed', handleAssessmentCompleted)
            .listen('.assessment.failed', handleAssessmentFailed);

        // Connection status
        echoInstance.connector.pusher.connection.bind('connected', () => {
            console.log('WebSocket connected');
            setIsConnected(true);
        });

        echoInstance.connector.pusher.connection.bind('disconnected', () => {
            console.log('WebSocket disconnected');
            setIsConnected(false);
        });

        echoInstance.connector.pusher.connection.bind('error', (error) => {
            console.error('WebSocket error:', error);
            setIsConnected(false);
        });

        // Cleanup
        return () => {
            console.log('Disconnecting from assessment notifications');
            channel.stopListening('.assessment.completed')
                   .stopListening('.assessment.failed');
            echoInstance.leaveChannel(`assessment.${userId}`);
        };
    }, [userId, handleAssessmentCompleted, handleAssessmentFailed]);

    const clearNotification = useCallback(() => {
        setNotification(null);
    }, []);

    return {
        notification,
        isConnected,
        clearNotification,
        echo: getEcho, // Expose for manual operations if needed
    };
}