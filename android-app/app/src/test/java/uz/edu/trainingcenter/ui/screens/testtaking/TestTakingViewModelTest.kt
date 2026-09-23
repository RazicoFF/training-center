package uz.edu.trainingcenter.ui.screens.testtaking

import app.cash.turbine.test
import io.mockk.coEvery
import io.mockk.mockk
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.test.StandardTestDispatcher
import kotlinx.coroutines.test.resetMain
import kotlinx.coroutines.test.runTest
import kotlinx.coroutines.test.setMain
import org.junit.After
import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Before
import org.junit.Test
import uz.edu.trainingcenter.data.remote.dto.AnswerDto
import uz.edu.trainingcenter.data.remote.dto.QuestionDto
import uz.edu.trainingcenter.data.remote.dto.SubmitResponse
import uz.edu.trainingcenter.data.repository.TestRepository

@OptIn(ExperimentalCoroutinesApi::class)
class TestTakingViewModelTest {

    private val testDispatcher = StandardTestDispatcher()

    @Before
    fun setUp() { Dispatchers.setMain(testDispatcher) }

    @After
    fun tearDown() { Dispatchers.resetMain() }

    private fun twoQuestions() = listOf(
        QuestionDto(1, "Savol 1", "Вопрос 1", listOf(AnswerDto(1, "A", "А"), AnswerDto(2, "B", "Б"))),
        QuestionDto(2, "Savol 2", "Вопрос 2", listOf(AnswerDto(3, "C", "В"), AnswerDto(4, "D", "Г")))
    )

    @Test
    fun `load emits InProgress at question 0`() = runTest(testDispatcher) {
        val repository = mockk<TestRepository>()
        coEvery { repository.getQuestions(1) } returns Result.success(twoQuestions())

        val viewModel = TestTakingViewModel(testId = 1, repository = repository)

        viewModel.uiState.test {
            assertTrue(awaitItem() is TestTakingUiState.Loading)
            val inProgress = awaitItem() as TestTakingUiState.InProgress
            assertEquals(0, inProgress.currentIndex)
        }
    }

    @Test
    fun `selectAnswer then nextQuestion advances index and records answer`() = runTest(testDispatcher) {
        val repository = mockk<TestRepository>()
        coEvery { repository.getQuestions(1) } returns Result.success(twoQuestions())

        val viewModel = TestTakingViewModel(testId = 1, repository = repository)

        viewModel.uiState.test {
            awaitItem() // Loading
            awaitItem() // InProgress index 0
            viewModel.selectAnswer(1)
            awaitItem() // InProgress index 0, answer recorded
            viewModel.nextQuestion()
            val next = awaitItem() as TestTakingUiState.InProgress
            assertEquals(1, next.currentIndex)
            assertEquals(1, next.selectedAnswers[1])
        }
    }

    @Test
    fun `submitTest on last question emits Submitted`() = runTest(testDispatcher) {
        val repository = mockk<TestRepository>()
        coEvery { repository.getQuestions(1) } returns Result.success(twoQuestions())
        coEvery { repository.submit(1, listOf(1, 3)) } returns Result.success(SubmitResponse(100, true))

        val viewModel = TestTakingViewModel(testId = 1, repository = repository)

        viewModel.uiState.test {
            awaitItem() // Loading
            awaitItem() // InProgress index 0
            viewModel.selectAnswer(1)
            awaitItem() // InProgress index 0, answer recorded
            viewModel.nextQuestion()
            awaitItem() // InProgress index 1
            viewModel.selectAnswer(3)
            awaitItem() // InProgress index 1, answer recorded
            viewModel.submitTest()
            assertTrue(awaitItem() is TestTakingUiState.Submitting)
            val submitted = awaitItem() as TestTakingUiState.Submitted
            assertEquals(100, submitted.score)
            assertTrue(submitted.passed)
        }
    }

    @Test
    fun `load failure emits Error with null previousState`() = runTest(testDispatcher) {
        val repository = mockk<TestRepository>()
        coEvery { repository.getQuestions(1) } returns Result.failure(java.io.IOException("network down"))

        val viewModel = TestTakingViewModel(testId = 1, repository = repository)

        viewModel.uiState.test {
            awaitItem() // Loading
            val error = awaitItem() as TestTakingUiState.Error
            assertEquals(null, error.previousState)
        }
    }

    @Test
    fun `submit failure emits Error with real InProgress previousState`() = runTest(testDispatcher) {
        val repository = mockk<TestRepository>()
        coEvery { repository.getQuestions(1) } returns Result.success(twoQuestions())
        coEvery { repository.submit(1, listOf(1, 3)) } returns Result.failure(java.io.IOException("network down"))

        val viewModel = TestTakingViewModel(testId = 1, repository = repository)

        viewModel.uiState.test {
            awaitItem() // Loading
            awaitItem() // InProgress index 0
            viewModel.selectAnswer(1)
            awaitItem() // InProgress index 0, answer recorded
            viewModel.nextQuestion()
            awaitItem() // InProgress index 1
            viewModel.selectAnswer(3)
            awaitItem() // InProgress index 1, answer recorded
            viewModel.submitTest()
            assertTrue(awaitItem() is TestTakingUiState.Submitting)
            val error = awaitItem() as TestTakingUiState.Error
            val previousState = error.previousState
            assertTrue(previousState != null)
            assertEquals(2, previousState!!.questions.size)
            assertEquals(2, previousState.selectedAnswers.size)
        }
    }
}
