package uz.edu.trainingcenter.data.remote

import okhttp3.ResponseBody
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.Path
import retrofit2.http.Streaming
import uz.edu.trainingcenter.data.remote.dto.*

interface ApiService {
    @GET("professions")
    suspend fun getProfessions(): ProfessionsResponse

    @GET("professions/{id}")
    suspend fun getProfessionDetail(@Path("id") professionId: Int): ProfessionDto

    @GET("news")
    suspend fun getNews(): NewsResponse

    @GET("media")
    suspend fun getMedia(): MediaResponse

    @POST("applications")
    suspend fun submitApplication(@Body request: ApplicationRequest): ApplicationResponse

    @POST("auth/login")
    suspend fun login(@Body request: LoginRequest): LoginResponse

    @GET("me")
    suspend fun getMe(): MeDto

    @GET("me/schedule")
    suspend fun getSchedule(): ScheduleResponse

    @GET("me/tests")
    suspend fun getTests(): TestsResponse

    @GET("me/tests/{id}")
    suspend fun getTestQuestions(@Path("id") testId: Int): QuestionsResponse

    @POST("me/tests/{id}/submit")
    suspend fun submitTest(@Path("id") testId: Int, @Body request: SubmitRequest): SubmitResponse

    @GET("me/certificates")
    suspend fun getCertificates(): CertificatesResponse

    @Streaming
    @GET("certificates/{id}/download")
    suspend fun downloadCertificate(@Path("id") certificateId: Int): ResponseBody
}
