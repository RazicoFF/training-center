package uz.edu.trainingcenter.ui.screens.profile

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.stateIn
import kotlinx.coroutines.launch
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

    init {
        viewModelScope.launch {
            authRepository.getMe().onSuccess { _me.value = it }
        }
        viewModelScope.launch {
            _baseUrl.value = dataStore.getBaseUrl()
        }
    }

    fun setLanguage(lang: String) {
        viewModelScope.launch { dataStore.setLanguage(lang) }
    }

    fun setTheme(theme: String) {
        viewModelScope.launch { dataStore.setTheme(theme) }
    }

    fun setBaseUrl(url: String) {
        viewModelScope.launch {
            dataStore.setBaseUrl(url)
            _baseUrl.value = url
        }
    }

    fun logout() {
        viewModelScope.launch { authRepository.logout() }
    }
}
