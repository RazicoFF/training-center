package uz.edu.trainingcenter.data.repository

import io.mockk.coEvery
import io.mockk.mockk
import kotlinx.coroutines.test.runTest
import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Test
import uz.edu.trainingcenter.data.remote.ApiService
import uz.edu.trainingcenter.data.remote.SessionManager
import uz.edu.trainingcenter.data.remote.dto.ScheduleItemDto
import uz.edu.trainingcenter.data.remote.dto.ScheduleResponse

class ScheduleRepositoryTest {

    @Test
    fun `getSchedule returns items on success`() = runTest {
        val api = mockk<ApiService>()
        val item = ScheduleItemDto("2026-10-01", "09:00:00", "11:00:00", "101", "Ekskavator-1")
        coEvery { api.getSchedule() } returns ScheduleResponse(listOf(item))

        val repository = ScheduleRepository(api, SessionManager())
        val result = repository.getSchedule()

        assertTrue(result.isSuccess)
        assertEquals(1, result.getOrThrow().size)
    }
}
