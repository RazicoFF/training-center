package uz.edu.trainingcenter.data.repository

import uz.edu.trainingcenter.data.remote.ApiService
import uz.edu.trainingcenter.data.remote.SessionManager
import uz.edu.trainingcenter.data.remote.dto.NewsDto
import uz.edu.trainingcenter.data.remote.safeApiCall

class NewsRepository(
    private val api: ApiService,
    private val sessionManager: SessionManager
) {
    suspend fun getNews(): Result<List<NewsDto>> {
        return safeApiCall(sessionManager) { api.getNews() }.map { it.news }
    }
}
