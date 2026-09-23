package uz.edu.trainingcenter.ui.screens.testtaking

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import uz.edu.trainingcenter.data.remote.dto.QuestionDto
import uz.edu.trainingcenter.data.repository.TestRepository
import uz.edu.trainingcenter.util.UiError
import uz.edu.trainingcenter.util.toUiError

sealed interface TestTakingUiState {
    data object Loading : TestTakingUiState
    data class InProgress(
        val questions: List<QuestionDto>,
        val currentIndex: Int,
        val selectedAnswers: Map<Int, Int> // questionId -> selected answerId
    ) : TestTakingUiState
    data object Submitting : TestTakingUiState
    data class Submitted(val score: Int, val passed: Boolean) : TestTakingUiState
    data class Error(val error: UiError, val previousState: InProgress) : TestTakingUiState
}

class TestTakingViewModel(
    private val testId: Int,
    private val repository: TestRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow<TestTakingUiState>(TestTakingUiState.Loading)
    val uiState: StateFlow<TestTakingUiState> = _uiState

    init {
        load()
    }

    private fun load() {
        viewModelScope.launch {
            _uiState.value = TestTakingUiState.Loading
            val result = repository.getQuestions(testId)
            _uiState.value = result.fold(
                onSuccess = { TestTakingUiState.InProgress(it, currentIndex = 0, selectedAnswers = emptyMap()) },
                onFailure = {
                    TestTakingUiState.Error(
                        it.toUiError(),
                        previousState = TestTakingUiState.InProgress(emptyList(), 0, emptyMap())
                    )
                }
            )
        }
    }

    fun selectAnswer(answerId: Int) {
        val current = _uiState.value as? TestTakingUiState.InProgress ?: return
        val questionId = current.questions[current.currentIndex].id
        _uiState.value = current.copy(selectedAnswers = current.selectedAnswers + (questionId to answerId))
    }

    fun nextQuestion() {
        val current = _uiState.value as? TestTakingUiState.InProgress ?: return
        if (current.currentIndex < current.questions.size - 1) {
            _uiState.value = current.copy(currentIndex = current.currentIndex + 1)
        }
    }

    fun submitTest() {
        val current = when (val state = _uiState.value) {
            is TestTakingUiState.InProgress -> state
            is TestTakingUiState.Error -> state.previousState
            else -> null
        } ?: return
        viewModelScope.launch {
            _uiState.value = TestTakingUiState.Submitting
            val answerIds = current.questions.mapNotNull { current.selectedAnswers[it.id] }
            val result = repository.submit(testId, answerIds)
            _uiState.value = result.fold(
                onSuccess = { TestTakingUiState.Submitted(it.score, it.passed) },
                onFailure = { TestTakingUiState.Error(it.toUiError(), previousState = current) }
            )
        }
    }
}
