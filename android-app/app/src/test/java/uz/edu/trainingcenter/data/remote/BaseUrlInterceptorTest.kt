package uz.edu.trainingcenter.data.remote

import okhttp3.OkHttpClient
import okhttp3.Request
import okhttp3.mockwebserver.MockResponse
import okhttp3.mockwebserver.MockWebServer
import org.junit.After
import org.junit.Assert.assertEquals
import org.junit.Before
import org.junit.Test

class BaseUrlInterceptorTest {

    private lateinit var server: MockWebServer

    @Before
    fun setUp() {
        server = MockWebServer()
        server.enqueue(MockResponse().setResponseCode(200).setBody("ok"))
        server.start()
    }

    @After
    fun tearDown() {
        server.shutdown()
    }

    @Test
    fun `rewrites request host and port to the provided base URL`() {
        val realBaseUrl = server.url("/api/v1/").toString()
        val client = OkHttpClient.Builder()
            .addInterceptor(BaseUrlInterceptor { realBaseUrl })
            .build()

        val request = Request.Builder()
            .url("http://placeholder.invalid/api/v1/professions")
            .build()

        val response = client.newCall(request).execute()

        assertEquals(200, response.code)
        val recorded = server.takeRequest()
        assertEquals("/api/v1/professions", recorded.path)
    }
}
