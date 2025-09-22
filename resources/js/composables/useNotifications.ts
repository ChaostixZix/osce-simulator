import { toast } from 'vue-sonner';

export interface NotificationOptions {
    title: string;
    description?: string;
    variant?: 'success' | 'error' | 'warning' | 'info';
    duration?: number;
}

/**
 * Composable for unified notification system
 */
export function useNotifications() {
    const showNotification = (options: NotificationOptions) => {
        const { title, description, variant = 'info', duration } = options;
        
        switch (variant) {
            case 'success':
                toast.success(title, { description, duration });
                break;
            case 'error':
                toast.error(title, { description, duration });
                break;
            case 'warning':
                toast.warning(title, { description, duration });
                break;
            case 'info':
            default:
                toast.info(title, { description, duration });
                break;
        }
    };

    const showSuccess = (title: string, description?: string) => {
        showNotification({ title, description, variant: 'success' });
    };

    const showError = (title: string, description?: string) => {
        showNotification({ title, description, variant: 'error' });
    };

    const showWarning = (title: string, description?: string) => {
        showNotification({ title, description, variant: 'warning' });
    };

    const showInfo = (title: string, description?: string) => {
        showNotification({ title, description, variant: 'info' });
    };

    return {
        showNotification,
        showSuccess,
        showError,
        showWarning,
        showInfo,
    };
}