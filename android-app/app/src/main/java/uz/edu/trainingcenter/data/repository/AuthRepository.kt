package uz.edu.trainingcenter.data.repository

import uz.edu.trainingcenter.data.local.PreferencesDataStore
import uz.edu.trainingcenter.data.remote.ApiService
import uz.edu.trainingcenter.data.remote.SessionManager
import uz.edu.trainingcenter.data.remote.dto.LoginRequest
import uz.edu.trainingcenter.data.remote.safeApiCall

class AuthRepository(
    private val api: ApiService,
    private val dataStore: PreferencesDataStore,
    private val sessionManager: SessionManager
) {
    suspend fun login(phone: String, password: String): Result<Unit> {
        val result = safeApiCall(sessionManager) { api.login(LoginRequest(phone, password)) }
        return result.map { response ->
            dataStore.setToken(response.token)
        }
    }

    suspend fun logout() {
        dataStore.setToken(null)
    }
}
