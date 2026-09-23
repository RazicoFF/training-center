package uz.edu.trainingcenter.ui.screens.register

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
import uz.edu.trainingcenter.data.remote.dto.ProfessionDto
import uz.edu.trainingcenter.data.repository.ProfessionRepository

@OptIn(ExperimentalCoroutinesApi::class)
class RegisterViewModelTest {

    @Before
    fun setUp() { Dispatchers.setMain(StandardTestDispatcher()) }

    @After
    fun tearDown() { Dispatchers.resetMain() }

    @Test
    fun `loadProfessions emits ProfessionsLoaded on success`() = runTest {
        val repository = mockk<ProfessionRepository>()
        val profession = ProfessionDto(1, "Ekskavator", "Экскаватор", "d", "d", 30, "1500000.00", null)
        coEvery { repository.getProfessions() } returns Result.success(listOf(profession))

        val viewModel = RegisterViewModel(repository)

        viewModel.uiState.test {
            assertTrue(awaitItem() is RegisterUiState.Idle)
            viewModel.loadProfessions()
            assertTrue(awaitItem() is RegisterUiState.LoadingProfessions)
            val loaded = awaitItem()
            assertTrue(loaded is RegisterUiState.ProfessionsLoaded)
        }
    }

    @Test
    fun `submit emits Submitted on success`() = runTest {
        val repository = mockk<ProfessionRepository>()
        coEvery { repository.submitApplication("Ali", "+998900000000", 1) } returns Result.success(Unit)

        val viewModel = RegisterViewModel(repository)

        viewModel.uiState.test {
            assertTrue(awaitItem() is RegisterUiState.Idle)
            viewModel.submit("Ali", "+998900000000", 1)
            assertTrue(awaitItem() is RegisterUiState.Submitting)
            assertTrue(awaitItem() is RegisterUiState.Submitted)
        }
    }
}
