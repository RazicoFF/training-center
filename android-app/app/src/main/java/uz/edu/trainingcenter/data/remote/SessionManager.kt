package uz.edu.trainingcenter.data.remote

import kotlinx.coroutines.flow.MutableSharedFlow
import kotlinx.coroutines.flow.SharedFlow
import kotlinx.coroutines.flow.asSharedFlow
import uz.edu.trainingcenter.data.local.PreferencesDataStore

class SessionManager(private val dataStore: PreferencesDataStore) {
    private val _loggedOut = MutableSharedFlow<Unit>(extraBufferCapacity = 1)
    val loggedOut: SharedFlow<Unit> = _loggedOut.asSharedFlow()

    suspend fun notifyLoggedOut() {
        dataStore.setToken(null)
        _loggedOut.tryEmit(Unit)
    }
}
