package uz.edu.trainingcenter.ui.screens.schedule

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import uz.edu.trainingcenter.data.remote.dto.ScheduleItemDto
import uz.edu.trainingcenter.data.repository.ScheduleRepository

sealed interface ScheduleUiState {
    data object Loading : ScheduleUiState
    data class Success(val items: List<ScheduleItemDto>) : ScheduleUiState
    data class Error(val message: String) : ScheduleUiState
}

class ScheduleViewModel(private val repository: ScheduleRepository) : ViewModel() {

    private val _uiState = MutableStateFlow<ScheduleUiState>(ScheduleUiState.Loading)
    val uiState: StateFlow<ScheduleUiState> = _uiState

    init {
        load()
    }

    fun load() {
        viewModelScope.launch {
            _uiState.value = ScheduleUiState.Loading
            val result = repository.getSchedule()
            _uiState.value = result.fold(
                onSuccess = { ScheduleUiState.Success(it) },
                onFailure = { ScheduleUiState.Error(it.message ?: "Failed to load schedule") }
            )
        }
    }
}
