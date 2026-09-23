package uz.edu.trainingcenter.ui.screens.tests

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
import org.junit.Assert.assertTrue
import org.junit.Before
import org.junit.Test
import uz.edu.trainingcenter.data.remote.dto.TestSummaryDto
import uz.edu.trainingcenter.data.repository.TestRepository

@OptIn(ExperimentalCoroutinesApi::class)
class TestsListViewModelTest {

    @Before
    fun setUp() { Dispatchers.setMain(StandardTestDispatcher()) }

    @After
    fun tearDown() { Dispatchers.resetMain() }

    @Test
    fun `load emits Success with tests`() = runTest {
        val repository = mockk<TestRepository>()
        coEvery { repository.getTests() } returns Result.success(listOf(TestSummaryDto(1, "Yakuniy", "Финал", 70)))

        val viewModel = TestsListViewModel(repository)

        viewModel.uiState.test {
            assertTrue(awaitItem() is TestsListUiState.Loading)
            assertTrue(awaitItem() is TestsListUiState.Success)
        }
    }
}
