package uz.edu.trainingcenter.util

import okhttp3.MediaType.Companion.toMediaType
import okhttp3.ResponseBody.Companion.toResponseBody
import org.junit.Assert.assertEquals
import org.junit.Test
import retrofit2.HttpException
import retrofit2.Response

class ErrorMapperTest {

    private fun httpException(code: Int, body: String): HttpException =
        HttpException(Response.error<Any>(code, body.toResponseBody("application/json".toMediaType())))

    @Test
    fun `toUserMessageOrNull parses the server-provided error message`() {
        val exception = httpException(
            400,
            """{"error":{"code":"VALIDATION_ERROR","message":"Test server message"}}"""
        )

        assertEquals("Test server message", exception.toUserMessageOrNull())
    }

    @Test
    fun `toUiError classifies a parseable server error as SERVER_MESSAGE`() {
        val exception = httpException(
            400,
            """{"error":{"code":"VALIDATION_ERROR","message":"Test server message"}}"""
        )

        val uiError = exception.toUiError()

        assertEquals(ErrorKind.SERVER_MESSAGE, uiError.kind)
        assertEquals("Test server message", uiError.serverMessage)
    }

    @Test
    fun `toUserMessageOrNull returns null for an unparseable body`() {
        val exception = httpException(500, "not json")

        assertEquals(null, exception.toUserMessageOrNull())
    }
}
