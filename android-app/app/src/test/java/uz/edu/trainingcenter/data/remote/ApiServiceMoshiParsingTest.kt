package uz.edu.trainingcenter.data.remote

import com.squareup.moshi.Moshi
import com.squareup.moshi.kotlin.reflect.KotlinJsonAdapterFactory
import kotlinx.coroutines.test.runTest
import okhttp3.mockwebserver.MockResponse
import okhttp3.mockwebserver.MockWebServer
import org.junit.After
import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Before
import org.junit.Test
import retrofit2.Retrofit
import retrofit2.converter.moshi.MoshiConverterFactory
import uz.edu.trainingcenter.data.remote.dto.LoginRequest
import uz.edu.trainingcenter.data.remote.dto.SubmitRequest

/**
 * Builds a real Retrofit + ApiService (with the app's actual reflection-based
 * Moshi setup) against a MockWebServer, and enqueues literal backend JSON
 * shapes matching the DTOs' @Json(name=...) field names. This proves Moshi's
 * reflection-based parsing (no moshi-kotlin-codegen) actually works against
 * real snake_case backend JSON, not just against mocked Kotlin objects.
 */
class ApiServiceMoshiParsingTest {

    private lateinit var server: MockWebServer
    private lateinit var api: ApiService

    @Before
    fun setUp() {
        server = MockWebServer()
        server.start()

        val moshi = Moshi.Builder().add(KotlinJsonAdapterFactory()).build()
        val retrofit = Retrofit.Builder()
            .baseUrl(server.url("/api/v1/"))
            .addConverterFactory(MoshiConverterFactory.create(moshi))
            .build()
        api = retrofit.create(ApiService::class.java)
    }

    @After
    fun tearDown() {
        server.shutdown()
    }

    @Test
    fun `login parses token from real backend JSON shape`() = runTest {
        server.enqueue(
            MockResponse().setResponseCode(200).setBody(
                """{"token":"eyJhbGciOiJIUzI1NiJ9.example.jwt"}"""
            )
        )

        val response = api.login(LoginRequest("+998900000000", "pass1234"))

        assertEquals("eyJhbGciOiJIUzI1NiJ9.example.jwt", response.token)
    }

    @Test
    fun `getProfessions parses snake_case fields into camelCase DTO`() = runTest {
        server.enqueue(
            MockResponse().setResponseCode(200).setBody(
                """
                {
                  "professions": [
                    {
                      "id": 1,
                      "name_uz": "Elektrik",
                      "name_ru": "Электрик",
                      "description_uz": "Elektr montaj ishlari",
                      "description_ru": "Электромонтажные работы",
                      "duration_days": 30,
                      "price": "1500000.00",
                      "image_url": null
                    }
                  ]
                }
                """.trimIndent()
            )
        )

        val response = api.getProfessions()

        assertEquals(1, response.professions.size)
        val profession = response.professions.first()
        assertEquals(1, profession.id)
        assertEquals("Elektrik", profession.nameUz)
        assertEquals("Электрик", profession.nameRu)
        assertEquals(30, profession.durationDays)
        assertEquals("1500000.00", profession.price)
        assertEquals(null, profession.imageUrl)
    }

    @Test
    fun `submitTest parses score and passed from real backend JSON shape`() = runTest {
        server.enqueue(
            MockResponse().setResponseCode(200).setBody("""{"score":85,"passed":true}""")
        )

        val response = api.submitTest(1, SubmitRequest(listOf(1, 3, 5)))

        assertEquals(85, response.score)
        assertTrue(response.passed)
    }
}
