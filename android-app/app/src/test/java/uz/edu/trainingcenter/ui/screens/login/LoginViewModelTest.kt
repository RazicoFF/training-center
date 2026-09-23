package uz.edu.trainingcenter.ui.screens.login

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
import uz.edu.trainingcenter.data.repository.AuthRepository

@OptIn(ExperimentalCoroutinesApi::class)
class LoginViewModelTest {

    @Before
    fun setUp() {
        Dispatchers.setMain(StandardTestDispatcher())
    }

    @After
    fun tearDown() {
        Dispatchers.resetMain()
    }

    @Test
    fun `successful login emits Success state`() = runTest {
        val repository = mockk<AuthRepository>()
        coEvery { repository.login("+998900000000", "pass1234") } returns Result.success(Unit)

        val viewModel = LoginViewModel(repository)

        viewModel.uiState.test {
            assertTrue(awaitItem() is LoginUiState.Idle)
            viewModel.login("+998900000000", "pass1234")
            assertTrue(awaitItem() is LoginUiState.Loading)
            assertTrue(awaitItem() is LoginUiState.Success)
        }
    }

    @Test
    fun `failed login emits Error state`() = runTest {
        val repository = mockk<AuthRepository>()
        coEvery { repository.login(any(), any()) } returns Result.failure(RuntimeException("bad credentials"))

        val viewModel = LoginViewModel(repository)

        viewModel.uiState.test {
            assertTrue(awaitItem() is LoginUiState.Idle)
            viewModel.login("+998900000000", "wrong")
            assertTrue(awaitItem() is LoginUiState.Loading)
            assertTrue(awaitItem() is LoginUiState.Error)
        }
    }
}
