package uz.edu.trainingcenter.ui.screens.professions

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import uz.edu.trainingcenter.data.remote.dto.ProfessionDto
import uz.edu.trainingcenter.data.repository.ProfessionRepository
import uz.edu.trainingcenter.util.UiError
import uz.edu.trainingcenter.util.toUiError

sealed interface ProfessionsListUiState {
    data object Loading : ProfessionsListUiState
    data class Success(val professions: List<ProfessionDto>) : ProfessionsListUiState
    data class Error(val error: UiError) : ProfessionsListUiState
}

class ProfessionsListViewModel(private val repository: ProfessionRepository) : ViewModel() {

    private val _uiState = MutableStateFlow<ProfessionsListUiState>(ProfessionsListUiState.Loading)
    val uiState: StateFlow<ProfessionsListUiState> = _uiState

    init {
        load()
    }

    fun load() {
        viewModelScope.launch {
            _uiState.value = ProfessionsListUiState.Loading
            val result = repository.getProfessions()
            _uiState.value = result.fold(
                onSuccess = { ProfessionsListUiState.Success(it) },
                onFailure = { ProfessionsListUiState.Error(it.toUiError()) }
            )
        }
    }
}
