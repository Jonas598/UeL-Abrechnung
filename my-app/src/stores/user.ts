import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import apiClient from '../services/apiClient'
import type { User, LoginCredentials } from '../types/models'

export const useUserStore = defineStore('user', () => {
    // State
    const user = ref<User | null>(null)
    const loading = ref(false)
    const error = ref<string | null>(null)

    // Getter
    const isAuthenticated = computed(() => !!user.value)

    // Actions
    async function login(credentials: LoginCredentials) {
        loading.value = true
        error.value = null
        try {
            // 1. Login Request senden
            // Wir erwarten vom Backend: { message: '...', token: 'XYZ...', user: {...} }
            const response = await apiClient.post('/login', credentials)

            const token = response.data.token
            const userData = response.data.user

            // 2. Token speichern (LocalStorage)
            if (token) {
                localStorage.setItem('auth_token', token)
                // Setzen des Headers für sofortige nachfolgende Requests (optional, da Interceptor das auch macht)
                apiClient.defaults.headers.common['Authorization'] = `Bearer ${token}`
            } else {
                console.warn('Kein Token in der Antwort erhalten!')
            }

            // 3. User im State speichern
            user.value = userData

        } catch (e: any) {
            console.error('Login fehlgeschlagen:', e)
            // Fehlermeldung aus der API-Antwort extrahieren
            error.value = e.response?.data?.message || 'Anmeldung fehlgeschlagen. Bitte prüfen Sie Ihre Daten.'
            throw e // Fehler weiterwerfen, damit die Komponente darauf reagieren kann
        } finally {
            loading.value = false
        }
    }

    async function logout() {
        loading.value = true
        try {
            // API Logout (Token invalidieren auf Server)
            await apiClient.post('/logout')
        } catch (e) {
            console.error('Logout Fehler (wird ignoriert):', e)
        } finally {
            // Lokales Aufräumen (immer durchführen)
            user.value = null
            localStorage.removeItem('auth_token')
            delete apiClient.defaults.headers.common['Authorization']
            loading.value = false
        }
    }

    // Diese Funktion wird beim App-Start aufgerufen (in App.vue)
    async function checkAuth() {
        const token = localStorage.getItem('auth_token')
        if (!token) {
            return // Kein Token -> Nicht eingeloggt
        }

        loading.value = true
        try {
            // Versuchen, den User mit dem gespeicherten Token zu laden
            const response = await apiClient.get('/user')
            user.value = response.data
        } catch (e) {
            // Token ungültig oder abgelaufen -> Aufräumen
            console.warn('Gespeicherter Token ungültig.')
            localStorage.removeItem('auth_token')
            user.value = null
        } finally {
            loading.value = false
        }
    }

    return {
        user,
        loading,
        error,
        isAuthenticated,
        login,
        logout,
        checkAuth
    }
})