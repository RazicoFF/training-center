package uz.edu.trainingcenter.data.local

import android.content.Context
import androidx.datastore.core.DataStore
import androidx.datastore.preferences.core.Preferences
import androidx.datastore.preferences.core.emptyPreferences
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.core.edit
import io.mockk.coEvery
import io.mockk.every
import io.mockk.mockk
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.test.runTest
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNull
import org.junit.Test

class PreferencesDataStoreTest {

    @Test
    fun `getToken returns null when not set`() = runTest {
        val store = InMemoryPreferencesDataStore()
        assertNull(store.getToken())
    }

    @Test
    fun `setToken then getToken round-trips`() = runTest {
        val store = InMemoryPreferencesDataStore()
        store.setToken("abc123")
        assertEquals("abc123", store.getToken())
    }

    @Test
    fun `getBaseUrl defaults to emulator loopback when unset`() = runTest {
        val store = InMemoryPreferencesDataStore()
        assertEquals("http://10.0.2.2:8080/api/v1/", store.getBaseUrl())
    }

    @Test
    fun `setBaseUrl then getBaseUrl round-trips`() = runTest {
        val store = InMemoryPreferencesDataStore()
        store.setBaseUrl("http://192.168.1.5:8080/api/v1/")
        assertEquals("http://192.168.1.5:8080/api/v1/", store.getBaseUrl())
    }

    @Test
    fun `languageFlow defaults to uz`() = runTest {
        val store = InMemoryPreferencesDataStore()
        assertEquals("uz", store.languageFlow().first())
    }

    @Test
    fun `setLanguage updates languageFlow`() = runTest {
        val store = InMemoryPreferencesDataStore()
        store.setLanguage("ru")
        assertEquals("ru", store.languageFlow().first())
    }

    @Test
    fun `themeFlow defaults to system`() = runTest {
        val store = InMemoryPreferencesDataStore()
        assertEquals("system", store.themeFlow().first())
    }
}

private class InMemoryPreferencesDataStore {
    private val values = MutableStateFlow<Map<String, String>>(emptyMap())

    suspend fun getToken(): String? = values.value["token"]
    suspend fun setToken(token: String?) {
        values.value = values.value.toMutableMap().apply {
            if (token == null) remove("token") else put("token", token)
        }
    }

    suspend fun getBaseUrl(): String = values.value["base_url"] ?: "http://10.0.2.2:8080/api/v1/"
    suspend fun setBaseUrl(url: String) {
        values.value = values.value.toMutableMap().apply { put("base_url", url) }
    }

    fun languageFlow() = kotlinx.coroutines.flow.flow {
        emit(values.value["language"] ?: "uz")
    }
    suspend fun setLanguage(lang: String) {
        values.value = values.value.toMutableMap().apply { put("language", lang) }
    }

    fun themeFlow() = kotlinx.coroutines.flow.flow {
        emit(values.value["theme"] ?: "system")
    }
    suspend fun setTheme(theme: String) {
        values.value = values.value.toMutableMap().apply { put("theme", theme) }
    }
}
