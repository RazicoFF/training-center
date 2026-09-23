package uz.edu.trainingcenter.ui.screens.schedule

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
import uz.edu.trainingcenter.data.remote.dto.ScheduleItemDto
import uz.edu.trainingcenter.data.repository.ScheduleRepository

@OptIn(ExperimentalCoroutinesApi::class)
class ScheduleViewModelTest {

    @Before
    fun setUp() { Dispatchers.setMain(StandardTestDispatcher()) }

    @After
    fun tearDown() { Dispatchers.resetMain() }

    @Test
    fun `load emits Success with items`() = runTest {
        val repository = mockk<ScheduleRepository>()
        val item = ScheduleItemDto("2026-10-01", "09:00:00", "11:00:00", "101", "Ekskavator-1")
        coEvery { repository.getSchedule() } returns Result.success(listOf(item))

        val viewModel = ScheduleViewModel(repository)

        viewModel.uiState.test {
            assertTrue(awaitItem() is ScheduleUiState.Loading)
            val success = awaitItem()
            assertTrue(success is ScheduleUiState.Success)
        }
    }
}
