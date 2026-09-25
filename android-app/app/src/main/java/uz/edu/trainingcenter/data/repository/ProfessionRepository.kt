package uz.edu.trainingcenter.data.repository

import uz.edu.trainingcenter.data.remote.ApiService
import uz.edu.trainingcenter.data.remote.SessionManager
import uz.edu.trainingcenter.data.remote.dto.ApplicationRequest
import uz.edu.trainingcenter.data.remote.dto.ProfessionDto
import uz.edu.trainingcenter.data.remote.safeApiCall

class ProfessionRepository(
    private val api: ApiService,
    private val sessionManager: SessionManager
) {
    suspend fun getProfessions(): Result<List<ProfessionDto>> {
        return safeApiCall(sessionManager) { api.getProfessions() }.map { it.professions }
    }

    suspend fun getProfessionDetail(professionId: Int): Result<ProfessionDto> {
        return safeApiCall(sessionManager) { api.getProfessionDetail(professionId) }
    }

    suspend fun submitApplication(
        fullName: String,
        phone: String,
        professionId: Int,
        brandId: Int? = null,
        photoBase64: String? = null
    ): Result<Unit> {
        return safeApiCall(sessionManager) {
            api.submitApplication(ApplicationRequest(fullName, phone, professionId, brandId, photoBase64))
        }.map { }
    }
}
