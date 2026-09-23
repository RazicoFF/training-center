package uz.edu.trainingcenter.ui.screens.login

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import uz.edu.trainingcenter.data.repository.AuthRepository
import uz.edu.trainingcenter.util.UiError
import uz.edu.trainingcenter.util.toUiError

sealed interface LoginUiState {
    data object Idle : LoginUiState
    data object Loading : LoginUiState
    data object Success : LoginUiState
    data class Error(val error: UiError) : LoginUiState
}

class LoginViewModel(private val repository: AuthRepository) : ViewModel() {

    private val _uiState = MutableStateFlow<LoginUiState>(LoginUiState.Idle)
    val uiState: StateFlow<LoginUiState> = _uiState

    fun login(phone: String, password: String) {
        viewModelScope.launch {
            _uiState.value = LoginUiState.Loading
            val result = repository.login(phone, password)
            _uiState.value = result.fold(
                onSuccess = { LoginUiState.Success },
                onFailure = { LoginUiState.Error(it.toUiError()) }
            )
        }
    }
}
