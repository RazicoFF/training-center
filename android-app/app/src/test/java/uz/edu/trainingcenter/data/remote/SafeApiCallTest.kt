package uz.edu.trainingcenter.data.remote

import io.mockk.coVerify
import io.mockk.mockk
import kotlinx.coroutines.test.runTest
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.ResponseBody.Companion.toResponseBody
import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Test
import retrofit2.HttpException
import retrofit2.Response
import uz.edu.trainingcenter.data.local.PreferencesDataStore
import java.io.IOException

class SafeApiCallTest {

    private fun httpException(code: Int): HttpException =
        HttpException(Response.error<Any>(code, "{}".toResponseBody("application/json".toMediaType())))

    @Test
    fun `401 clears the token and returns failure`() = runTest {
        val dataStore = mockk<PreferencesDataStore>(relaxed = true)
        val sessionManager = SessionManager(dataStore)

        val result = safeApiCall(sessionManager) { throw httpException(401) }

        assertTrue(result.isFailure)
        coVerify { dataStore.setToken(null) }
    }

    @Test
    fun `401 notifies the session manager it logged out`() = runTest {
        val sessionManager = mockk<SessionManager>(relaxed = true)

        val result = safeApiCall(sessionManager) { throw httpException(401) }

        assertTrue(result.isFailure)
        coVerify { sessionManager.notifyLoggedOut() }
    }

    @Test
    fun `non-401 HttpException does not trigger logout`() = runTest {
        val dataStore = mockk<PreferencesDataStore>(relaxed = true)
        val sessionManager = SessionManager(dataStore)

        val result = safeApiCall(sessionManager) { throw httpException(500) }

        assertTrue(result.isFailure)
        coVerify(exactly = 0) { dataStore.setToken(null) }
    }

    @Test
    fun `IOException returns failure without triggering logout`() = runTest {
        val dataStore = mockk<PreferencesDataStore>(relaxed = true)
        val sessionManager = SessionManager(dataStore)

        val result = safeApiCall(sessionManager) { throw IOException("no network") }

        assertTrue(result.isFailure)
        assertFalse(result.exceptionOrNull() is HttpException)
        coVerify(exactly = 0) { dataStore.setToken(null) }
    }

    @Test
    fun `other exceptions are caught as failure instead of crashing`() = runTest {
        val dataStore = mockk<PreferencesDataStore>(relaxed = true)
        val sessionManager = SessionManager(dataStore)

        val result = safeApiCall(sessionManager) { throw IllegalArgumentException("bad base url") }

        assertTrue(result.isFailure)
        coVerify(exactly = 0) { dataStore.setToken(null) }
    }
}
