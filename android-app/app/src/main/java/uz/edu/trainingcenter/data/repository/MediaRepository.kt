package uz.edu.trainingcenter.data.repository

import uz.edu.trainingcenter.data.remote.ApiService
import uz.edu.trainingcenter.data.remote.SessionManager
import uz.edu.trainingcenter.data.remote.dto.MediaDto
import uz.edu.trainingcenter.data.remote.safeApiCall

class MediaRepository(
    private val api: ApiService,
    private val sessionManager: SessionManager
) {
    suspend fun getMedia(): Result<List<MediaDto>> {
        return safeApiCall(sessionManager) { api.getMedia() }.map { it.media }
    }
}
