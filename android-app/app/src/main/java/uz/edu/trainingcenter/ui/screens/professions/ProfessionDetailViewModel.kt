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

sealed interface ProfessionDetailUiState {
    data object Loading : ProfessionDetailUiState
    data class Success(val profession: ProfessionDto) : ProfessionDetailUiState
    data class Error(val error: UiError) : ProfessionDetailUiState
}

class ProfessionDetailViewModel(
    private val professionId: Int,
    private val repository: ProfessionRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow<ProfessionDetailUiState>(ProfessionDetailUiState.Loading)
    val uiState: StateFlow<ProfessionDetailUiState> = _uiState

    init {
        load()
    }

    fun load() {
        viewModelScope.launch {
            _uiState.value = ProfessionDetailUiState.Loading
            val result = repository.getProfessionDetail(professionId)
            _uiState.value = result.fold(
                onSuccess = { ProfessionDetailUiState.Success(it) },
                onFailure = { ProfessionDetailUiState.Error(it.toUiError()) }
            )
        }
    }
}
