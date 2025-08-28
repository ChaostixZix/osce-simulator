import { computed } from 'vue';

export interface OsceSession {
    id: number;
    user_id: number;
    osce_case_id: number;
    status: 'pending' | 'in_progress' | 'completed' | 'cancelled';
    started_at: string | null;
    completed_at: string | null;
    score: number | null;
    max_score: number | null;
    responses: any;
    feedback: any;
    created_at: string;
    updated_at: string;
    remaining_seconds?: number;
    duration_minutes?: number;
    time_status?: 'active' | 'expired' | 'completed';
}

/**
 * Composable for OSCE session management
 */
export function useSession(session: OsceSession) {
    const isSessionActive = computed(() => {
        return session.status === 'in_progress' && 
               (session.time_status === 'active' || session.time_status === undefined);
    });

    const isSessionCompleted = computed(() => {
        return session.status === 'completed';
    });

    const isSessionExpired = computed(() => {
        return session.time_status === 'expired';
    });

    const remainingTimeFormatted = computed(() => {
        if (!session.remaining_seconds) return '00:00';
        
        const minutes = Math.floor(session.remaining_seconds / 60);
        const seconds = session.remaining_seconds % 60;
        
        return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    });

    const sessionDuration = computed(() => {
        return session.duration_minutes || 30; // Default 30 minutes
    });

    const progressPercentage = computed(() => {
        if (!session.remaining_seconds || !session.duration_minutes) return 0;
        
        const totalSeconds = session.duration_minutes * 60;
        const elapsedSeconds = totalSeconds - session.remaining_seconds;
        
        return Math.min(100, Math.max(0, (elapsedSeconds / totalSeconds) * 100));
    });

    return {
        isSessionActive,
        isSessionCompleted,
        isSessionExpired,
        remainingTimeFormatted,
        sessionDuration,
        progressPercentage,
    };
}