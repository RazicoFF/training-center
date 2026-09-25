package uz.edu.trainingcenter.ui.screens.media

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import uz.edu.trainingcenter.data.remote.dto.MediaDto
import uz.edu.trainingcenter.data.repository.MediaRepository
import uz.edu.trainingcenter.util.UiError
import uz.edu.trainingcenter.util.toUiError

sealed interface MediaListUiState {
    data object Loading : MediaListUiState
    data class Success(val media: List<MediaDto>) : MediaListUiState
    data class Error(val error: UiError) : MediaListUiState
}

class MediaListViewModel(private val repository: MediaRepository) : ViewModel() {

    private val _uiState = MutableStateFlow<MediaListUiState>(MediaListUiState.Loading)
    val uiState: StateFlow<MediaListUiState> = _uiState

    init {
        load()
    }

    fun load() {
        viewModelScope.launch {
            _uiState.value = MediaListUiState.Loading
            val result = repository.getMedia()
            _uiState.value = result.fold(
                onSuccess = { MediaListUiState.Success(it) },
                onFailure = { MediaListUiState.Error(it.toUiError()) }
            )
        }
    }
}
