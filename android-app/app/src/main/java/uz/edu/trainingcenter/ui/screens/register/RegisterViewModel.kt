package uz.edu.trainingcenter.ui.screens.register

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import uz.edu.trainingcenter.data.remote.dto.ProfessionDto
import uz.edu.trainingcenter.data.repository.ProfessionRepository
import uz.edu.trainingcenter.util.UiError
import uz.edu.trainingcenter.util.toUiError

sealed interface RegisterUiState {
    data object Idle : RegisterUiState
    data object LoadingProfessions : RegisterUiState
    data class ProfessionsLoaded(val professions: List<ProfessionDto>) : RegisterUiState
    data object Submitting : RegisterUiState
    data object Submitted : RegisterUiState
    data class Error(val error: UiError) : RegisterUiState
}

class RegisterViewModel(private val repository: ProfessionRepository) : ViewModel() {

    private val _uiState = MutableStateFlow<RegisterUiState>(RegisterUiState.Idle)
    val uiState: StateFlow<RegisterUiState> = _uiState

    fun loadProfessions() {
        viewModelScope.launch {
            _uiState.value = RegisterUiState.LoadingProfessions
            val result = repository.getProfessions()
            _uiState.value = result.fold(
                onSuccess = { RegisterUiState.ProfessionsLoaded(it) },
                onFailure = { RegisterUiState.Error(it.toUiError()) }
            )
        }
    }

    fun submit(fullName: String, phone: String, professionId: Int) {
        viewModelScope.launch {
            _uiState.value = RegisterUiState.Submitting
            val result = repository.submitApplication(fullName, phone, professionId)
            _uiState.value = result.fold(
                onSuccess = { RegisterUiState.Submitted },
                onFailure = { RegisterUiState.Error(it.toUiError()) }
            )
        }
    }
}
