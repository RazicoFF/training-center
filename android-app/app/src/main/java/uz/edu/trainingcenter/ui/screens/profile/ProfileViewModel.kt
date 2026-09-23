package uz.edu.trainingcenter.ui.screens.profile

import androidx.appcompat.app.AppCompatDelegate
import androidx.core.os.LocaleListCompat
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.stateIn
import kotlinx.coroutines.launch
import okhttp3.HttpUrl.Companion.toHttpUrlOrNull
import uz.edu.trainingcenter.data.local.PreferencesDataStore
import uz.edu.trainingcenter.data.remote.dto.MeDto
import uz.edu.trainingcenter.data.repository.AuthRepository

class ProfileViewModel(
    private val authRepository: AuthRepository,
    private val dataStore: PreferencesDataStore
) : ViewModel() {

    private val _me = MutableStateFlow<MeDto?>(null)
    val me: StateFlow<MeDto?> = _me

    val language: StateFlow<String> = dataStore.languageFlow()
        .stateIn(viewModelScope, SharingStarted.Eagerly, PreferencesDataStore.DEFAULT_LANGUAGE)

    val theme: StateFlow<String> = dataStore.themeFlow()
        .stateIn(viewModelScope, SharingStarted.Eagerly, PreferencesDataStore.DEFAULT_THEME)

    private val _baseUrl = MutableStateFlow(PreferencesDataStore.DEFAULT_BASE_URL)
    val baseUrl: StateFlow<String> = _baseUrl

    /** True when the last setBaseUrl() call was rejected for not being a valid URL. */
    private val _baseUrlError = MutableStateFlow(false)
    val baseUrlError: StateFlow<Boolean> = _baseUrlError

    init {
        viewModelScope.launch {
            authRepository.getMe().onSuccess { _me.value = it }
        }
        viewModelScope.launch {
            _baseUrl.value = dataStore.getBaseUrl()
        }
    }

    fun setLanguage(lang: String) {
        viewModelScope.launch {
            dataStore.setLanguage(lang)
            AppCompatDelegate.setApplicationLocales(LocaleListCompat.forLanguageTags(lang))
        }
    }

    fun setTheme(theme: String) {
        viewModelScope.launch { dataStore.setTheme(theme) }
    }

    fun setBaseUrl(url: String) {
        if (url.toHttpUrlOrNull() == null) {
            _baseUrlError.value = true
            return
        }
        _baseUrlError.value = false
        viewModelScope.launch {
            dataStore.setBaseUrl(url)
            _baseUrl.value = url
        }
    }

    /**
     * Suspends until the token is cleared, so the caller (ProfileScreen, from its own
     * coroutine scope) can navigate away only after logout actually completes -- not
     * racing a viewModelScope.launch that could get cancelled if the ViewModel is
     * destroyed by that same navigation.
     */
    suspend fun logout() {
        authRepository.logout()
    }
}
