package uz.edu.trainingcenter.ui.screens.news

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import uz.edu.trainingcenter.data.remote.dto.NewsDto
import uz.edu.trainingcenter.data.repository.NewsRepository
import uz.edu.trainingcenter.util.UiError
import uz.edu.trainingcenter.util.toUiError

sealed interface NewsListUiState {
    data object Loading : NewsListUiState
    data class Success(val news: List<NewsDto>) : NewsListUiState
    data class Error(val error: UiError) : NewsListUiState
}

class NewsListViewModel(private val repository: NewsRepository) : ViewModel() {

    private val _uiState = MutableStateFlow<NewsListUiState>(NewsListUiState.Loading)
    val uiState: StateFlow<NewsListUiState> = _uiState

    init {
        load()
    }

    fun load() {
        viewModelScope.launch {
            _uiState.value = NewsListUiState.Loading
            val result = repository.getNews()
            _uiState.value = result.fold(
                onSuccess = { NewsListUiState.Success(it) },
                onFailure = { NewsListUiState.Error(it.toUiError()) }
            )
        }
    }
}
