package uz.edu.trainingcenter.data.repository

import io.mockk.coEvery
import io.mockk.mockk
import kotlinx.coroutines.test.runTest
import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Test
import uz.edu.trainingcenter.data.remote.ApiService
import uz.edu.trainingcenter.data.remote.SessionManager
import uz.edu.trainingcenter.data.remote.dto.*

class ProfessionRepositoryTest {

    @Test
    fun `getProfessions returns the list on success`() = runTest {
        val api = mockk<ApiService>()
        val profession = ProfessionDto(1, "Ekskavator", "Экскаватор", "d1", "d1r", 30, "1500000.00", null)
        coEvery { api.getProfessions() } returns ProfessionsResponse(listOf(profession))

        val repository = ProfessionRepository(api, SessionManager(mockk(relaxed = true)))
        val result = repository.getProfessions()

        assertTrue(result.isSuccess)
        assertEquals(1, result.getOrThrow().size)
    }

    @Test
    fun `submitApplication returns success`() = runTest {
        val api = mockk<ApiService>()
        coEvery { api.submitApplication(ApplicationRequest("Ali Valiyev", "+998901112233", 1)) } returns ApplicationResponse(5)

        val repository = ProfessionRepository(api, SessionManager(mockk(relaxed = true)))
        val result = repository.submitApplication("Ali Valiyev", "+998901112233", 1)

        assertTrue(result.isSuccess)
    }
}
