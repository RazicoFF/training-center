package uz.edu.trainingcenter.data.repository

import uz.edu.trainingcenter.data.remote.ApiService
import uz.edu.trainingcenter.data.remote.SessionManager
import uz.edu.trainingcenter.data.remote.dto.ScheduleItemDto
import uz.edu.trainingcenter.data.remote.safeApiCall

class ScheduleRepository(
    private val api: ApiService,
    private val sessionManager: SessionManager
) {
    suspend fun getSchedule(): Result<List<ScheduleItemDto>> {
        return safeApiCall(sessionManager) { api.getSchedule() }.map { it.schedule }
    }
}
