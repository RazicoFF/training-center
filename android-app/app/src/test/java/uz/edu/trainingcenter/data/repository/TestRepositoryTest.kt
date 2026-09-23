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

class TestRepositoryTest {

    @Test
    fun `getTests returns list on success`() = runTest {
        val api = mockk<ApiService>()
        coEvery { api.getTests() } returns TestsResponse(listOf(TestSummaryDto(1, "Yakuniy", "Финал", 70)))

        val result = TestRepository(api, SessionManager(mockk(relaxed = true))).getTests()

        assertTrue(result.isSuccess)
        assertEquals(1, result.getOrThrow().size)
    }

    @Test
    fun `getQuestions returns list on success`() = runTest {
        val api = mockk<ApiService>()
        val answer = AnswerDto(1, "A", "А")
        val question = QuestionDto(1, "Savol?", "Вопрос?", listOf(answer))
        coEvery { api.getTestQuestions(1) } returns QuestionsResponse(listOf(question))

        val result = TestRepository(api, SessionManager(mockk(relaxed = true))).getQuestions(1)

        assertTrue(result.isSuccess)
        assertEquals(1, result.getOrThrow().size)
    }

    @Test
    fun `submit returns score and passed on success`() = runTest {
        val api = mockk<ApiService>()
        coEvery { api.submitTest(1, SubmitRequest(listOf(1, 3))) } returns SubmitResponse(100, true)

        val result = TestRepository(api, SessionManager(mockk(relaxed = true))).submit(1, listOf(1, 3))

        assertTrue(result.isSuccess)
        assertEquals(100, result.getOrThrow().score)
        assertTrue(result.getOrThrow().passed)
    }
}
