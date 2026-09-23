package uz.edu.trainingcenter.data.repository

import io.mockk.coEvery
import io.mockk.coVerify
import io.mockk.mockk
import kotlinx.coroutines.test.runTest
import org.junit.Assert.assertTrue
import org.junit.Test
import uz.edu.trainingcenter.data.local.PreferencesDataStore
import uz.edu.trainingcenter.data.remote.ApiService
import uz.edu.trainingcenter.data.remote.SessionManager
import uz.edu.trainingcenter.data.remote.dto.LoginRequest
import uz.edu.trainingcenter.data.remote.dto.LoginResponse

class AuthRepositoryTest {

    @Test
    fun `login stores token on success`() = runTest {
        val api = mockk<ApiService>()
        val dataStore = mockk<PreferencesDataStore>(relaxed = true)
        val sessionManager = SessionManager(mockk(relaxed = true))
        coEvery { api.login(LoginRequest("+998900000000", "pass1234")) } returns LoginResponse("jwt-token")

        val repository = AuthRepository(api, dataStore, sessionManager)
        val result = repository.login("+998900000000", "pass1234")

        assertTrue(result.isSuccess)
        coVerify { dataStore.setToken("jwt-token") }
    }

    @Test
    fun `login returns failure when API throws`() = runTest {
        val api = mockk<ApiService>()
        val dataStore = mockk<PreferencesDataStore>(relaxed = true)
        val sessionManager = SessionManager(mockk(relaxed = true))
        coEvery { api.login(any()) } throws java.io.IOException("network down")

        val repository = AuthRepository(api, dataStore, sessionManager)
        val result = repository.login("+998900000000", "wrong")

        assertTrue(result.isFailure)
    }

    @Test
    fun `logout clears the stored token`() = runTest {
        val api = mockk<ApiService>()
        val dataStore = mockk<PreferencesDataStore>(relaxed = true)
        val sessionManager = SessionManager(mockk(relaxed = true))

        AuthRepository(api, dataStore, sessionManager).logout()

        coVerify { dataStore.setToken(null) }
    }
}
