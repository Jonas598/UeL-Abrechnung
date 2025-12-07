import axios from 'axios';

const apiClient = axios.create({
    baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
    // withCredentials: true, // Entfernt: Für Token-Auth brauchen wir keine Cookies zwingend
});

// 1. Request Interceptor: Token vor jeder Anfrage automatisch einfügen
apiClient.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem('auth_token');
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

// 2. Response Interceptor: Globale Fehlerbehandlung (z.B. Auto-Logout)
apiClient.interceptors.response.use(
    (response) => response,
    (error) => {
        // Wenn der Token ungültig oder abgelaufen ist (401 Unauthorized)
        if (error.response && error.response.status === 401) {
            // Token entfernen, um "Endlosschleifen" bei ungültigem Token zu vermeiden
            localStorage.removeItem('auth_token');

            // Optional: Seite neu laden oder User-Store resetten (passiert meist beim nächsten App-Start)
            // window.location.reload();
        }
        return Promise.reject(error);
    }
);

export default apiClient;