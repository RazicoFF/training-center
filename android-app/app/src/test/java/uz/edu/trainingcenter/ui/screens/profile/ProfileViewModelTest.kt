package uz.edu.trainingcenter.ui.screens.profile

import app.cash.turbine.test
import io.mockk.coEvery
import io.mockk.coVerify
import io.mockk.every
import io.mockk.mockk
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.flow.flowOf
import kotlinx.coroutines.test.StandardTestDispatcher
import kotlinx.coroutines.test.advanceUntilIdle
import kotlinx.coroutines.test.resetMain
import kotlinx.coroutines.test.runTest
import kotlinx.coroutines.test.setMain
import org.junit.After
import org.junit.Assert.assertEquals
import org.junit.Before
import org.junit.Test
import uz.edu.trainingcenter.data.local.PreferencesDataStore
import uz.edu.trainingcenter.data.remote.dto.MeDto
import uz.edu.trainingcenter.data.repository.AuthRepository

@OptIn(ExperimentalCoroutinesApi::class)
class ProfileViewModelTest {

    @Before
    fun setUp() { Dispatchers.setMain(StandardTestDispatcher()) }

    @After
    fun tearDown() { Dispatchers.resetMain() }

    @Test
    fun `loads me profile on init`() = runTest {
        val authRepository = mockk<AuthRepository>()
        val dataStore = mockk<PreferencesDataStore>()
        every { dataStore.languageFlow() } returns flowOf("uz")
        every { dataStore.themeFlow() } returns flowOf("system")
        coEvery { dataStore.getBaseUrl() } returns "http://10.0.2.2:8080/api/v1/"
        coEvery { authRepository.getMe() } returns Result.success(MeDto(1, "Ali Valiyev", "+998900000000", "student", "uz"))

        val viewModel = ProfileViewModel(authRepository, dataStore)

        viewModel.me.test {
            assertEquals(null, awaitItem())
            assertEquals("Ali Valiyev", awaitItem()?.fullName)
        }
    }

    @Test
    fun `setLanguage persists to dataStore`() = runTest {
        val authRepository = mockk<AuthRepository>()
        val dataStore = mockk<PreferencesDataStore>(relaxed = true)
        every { dataStore.languageFlow() } returns flowOf("uz")
        every { dataStore.themeFlow() } returns flowOf("system")
        coEvery { dataStore.getBaseUrl() } returns "http://10.0.2.2:8080/api/v1/"
        coEvery { authRepository.getMe() } returns Result.success(MeDto(1, "Ali", "+998900000000", "student", "uz"))

        val viewModel = ProfileViewModel(authRepository, dataStore)
        viewModel.setLanguage("ru")
        advanceUntilIdle()

        coVerify { dataStore.setLanguage("ru") }
    }

    @Test
    fun `logout calls authRepository logout`() = runTest {
        val authRepository = mockk<AuthRepository>(relaxed = true)
        val dataStore = mockk<PreferencesDataStore>(relaxed = true)
        every { dataStore.languageFlow() } returns flowOf("uz")
        every { dataStore.themeFlow() } returns flowOf("system")
        coEvery { dataStore.getBaseUrl() } returns "http://10.0.2.2:8080/api/v1/"
        coEvery { authRepository.getMe() } returns Result.success(MeDto(1, "Ali", "+998900000000", "student", "uz"))

        val viewModel = ProfileViewModel(authRepository, dataStore)
        viewModel.logout()
        advanceUntilIdle()

        coVerify { authRepository.logout() }
    }
}
