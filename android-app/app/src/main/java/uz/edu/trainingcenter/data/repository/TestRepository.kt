package uz.edu.trainingcenter.data.repository

import uz.edu.trainingcenter.data.remote.ApiService
import uz.edu.trainingcenter.data.remote.SessionManager
import uz.edu.trainingcenter.data.remote.dto.QuestionDto
import uz.edu.trainingcenter.data.remote.dto.SubmitRequest
import uz.edu.trainingcenter.data.remote.dto.SubmitResponse
import uz.edu.trainingcenter.data.remote.dto.TestSummaryDto
import uz.edu.trainingcenter.data.remote.safeApiCall

class TestRepository(
    private val api: ApiService,
    private val sessionManager: SessionManager
) {
    suspend fun getTests(): Result<List<TestSummaryDto>> {
        return safeApiCall(sessionManager) { api.getTests() }.map { it.tests }
    }

    suspend fun getQuestions(testId: Int): Result<List<QuestionDto>> {
        return safeApiCall(sessionManager) { api.getTestQuestions(testId) }.map { it.questions }
    }

    suspend fun submit(testId: Int, answerIds: List<Int>): Result<SubmitResponse> {
        return safeApiCall(sessionManager) { api.submitTest(testId, SubmitRequest(answerIds)) }
    }
}
