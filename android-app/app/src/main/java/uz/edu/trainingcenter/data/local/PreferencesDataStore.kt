package uz.edu.trainingcenter.data.local

import android.content.Context
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.preferencesDataStore
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.map

private val Context.dataStore by preferencesDataStore(name = "training_center_prefs")

class PreferencesDataStore(private val context: Context) {

    private val tokenKey = stringPreferencesKey("token")
    private val baseUrlKey = stringPreferencesKey("base_url")
    private val languageKey = stringPreferencesKey("language")
    private val themeKey = stringPreferencesKey("theme")

    companion object {
        const val DEFAULT_BASE_URL = "http://10.0.2.2:8080/api/v1/"
        const val DEFAULT_LANGUAGE = "uz"
        const val DEFAULT_THEME = "system"
    }

    suspend fun getToken(): String? {
        return context.dataStore.data.first()[tokenKey]
    }

    suspend fun setToken(token: String?) {
        context.dataStore.edit { prefs ->
            if (token == null) prefs.remove(tokenKey) else prefs[tokenKey] = token
        }
    }

    suspend fun getBaseUrl(): String {
        return context.dataStore.data.first()[baseUrlKey] ?: DEFAULT_BASE_URL
    }

    suspend fun setBaseUrl(url: String) {
        context.dataStore.edit { it[baseUrlKey] = url }
    }

    fun languageFlow(): Flow<String> = context.dataStore.data.map { it[languageKey] ?: DEFAULT_LANGUAGE }

    suspend fun setLanguage(lang: String) {
        context.dataStore.edit { it[languageKey] = lang }
    }

    fun themeFlow(): Flow<String> = context.dataStore.data.map { it[themeKey] ?: DEFAULT_THEME }

    suspend fun setTheme(theme: String) {
        context.dataStore.edit { it[themeKey] = theme }
    }
}
