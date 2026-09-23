package uz.edu.trainingcenter.ui.screens.tests

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import uz.edu.trainingcenter.data.remote.dto.TestSummaryDto
import uz.edu.trainingcenter.data.repository.TestRepository
import uz.edu.trainingcenter.util.UiError
import uz.edu.trainingcenter.util.toUiError

sealed interface TestsListUiState {
    data object Loading : TestsListUiState
    data class Success(val tests: List<TestSummaryDto>) : TestsListUiState
    data class Error(val error: UiError) : TestsListUiState
}

class TestsListViewModel(private val repository: TestRepository) : ViewModel() {

    private val _uiState = MutableStateFlow<TestsListUiState>(TestsListUiState.Loading)
    val uiState: StateFlow<TestsListUiState> = _uiState

    init {
        load()
    }

    fun load() {
        viewModelScope.launch {
            _uiState.value = TestsListUiState.Loading
            val result = repository.getTests()
            _uiState.value = result.fold(
                onSuccess = { TestsListUiState.Success(it) },
                onFailure = { TestsListUiState.Error(it.toUiError()) }
            )
        }
    }
}
