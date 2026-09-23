package uz.edu.trainingcenter

import android.content.Context
import uz.edu.trainingcenter.data.local.PreferencesDataStore
import uz.edu.trainingcenter.data.remote.NetworkModule
import uz.edu.trainingcenter.data.remote.SessionManager
import uz.edu.trainingcenter.data.repository.AuthRepository

object ServiceLocator {
    private lateinit var appContext: Context

    fun init(context: Context) {
        appContext = context.applicationContext
    }

    val dataStore: PreferencesDataStore by lazy { PreferencesDataStore(appContext) }
    val sessionManager: SessionManager by lazy { SessionManager() }
    val apiService by lazy { NetworkModule.provideApiService(dataStore, sessionManager) }
    val authRepository: AuthRepository by lazy { AuthRepository(apiService, dataStore, sessionManager) }
}
