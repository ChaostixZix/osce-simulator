import { ref } from 'vue';

interface ApiRequestOptions {
    method?: 'GET' | 'POST' | 'PUT' | 'DELETE';
    headers?: Record<string, string>;
    body?: any;
}

/**
 * Composable for making API requests with loading states
 */
export function useApiRequest() {
    const isLoading = ref(false);
    const error = ref<string | null>(null);

    const getCSRFToken = (): string => {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    };

    const makeRequest = async <T = any>(
        url: string, 
        options: ApiRequestOptions = {}
    ): Promise<T | null> => {
        isLoading.value = true;
        error.value = null;

        try {
            const { method = 'GET', headers = {}, body } = options;
            
            const defaultHeaders: Record<string, string> = {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            };

            if (method !== 'GET') {
                defaultHeaders['X-CSRF-TOKEN'] = getCSRFToken();
            }

            const fetchOptions: RequestInit = {
                method,
                headers: { ...defaultHeaders, ...headers },
            };

            if (body && method !== 'GET') {
                fetchOptions.body = typeof body === 'string' ? body : JSON.stringify(body);
            }

            const response = await fetch(url, fetchOptions);

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({ message: 'Unknown error occurred' }));
                throw new Error(errorData.message || `HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            return data;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error occurred';
            console.error('API Request failed:', err);
            return null;
        } finally {
            isLoading.value = false;
        }
    };

    const get = <T = any>(url: string, headers?: Record<string, string>): Promise<T | null> => {
        return makeRequest<T>(url, { method: 'GET', headers });
    };

    const post = <T = any>(url: string, body?: any, headers?: Record<string, string>): Promise<T | null> => {
        return makeRequest<T>(url, { method: 'POST', body, headers });
    };

    const put = <T = any>(url: string, body?: any, headers?: Record<string, string>): Promise<T | null> => {
        return makeRequest<T>(url, { method: 'PUT', body, headers });
    };

    const del = <T = any>(url: string, headers?: Record<string, string>): Promise<T | null> => {
        return makeRequest<T>(url, { method: 'DELETE', headers });
    };

    return {
        isLoading,
        error,
        makeRequest,
        get,
        post,
        put,
        delete: del,
    };
}