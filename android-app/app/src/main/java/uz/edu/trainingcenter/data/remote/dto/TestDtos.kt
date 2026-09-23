package uz.edu.trainingcenter.data.remote.dto

import com.squareup.moshi.Json
import com.squareup.moshi.JsonClass

@JsonClass(generateAdapter = true)
data class TestSummaryDto(
    val id: Int,
    @Json(name = "title_uz") val titleUz: String,
    @Json(name = "title_ru") val titleRu: String,
    @Json(name = "passing_score") val passingScore: Int
)

@JsonClass(generateAdapter = true)
data class TestsResponse(val tests: List<TestSummaryDto>)

@JsonClass(generateAdapter = true)
data class AnswerDto(
    val id: Int,
    @Json(name = "text_uz") val textUz: String,
    @Json(name = "text_ru") val textRu: String
)

@JsonClass(generateAdapter = true)
data class QuestionDto(
    val id: Int,
    @Json(name = "text_uz") val textUz: String,
    @Json(name = "text_ru") val textRu: String,
    val answers: List<AnswerDto>
)

@JsonClass(generateAdapter = true)
data class QuestionsResponse(val questions: List<QuestionDto>)

@JsonClass(generateAdapter = true)
data class SubmitRequest(val answers: List<Int>)

@JsonClass(generateAdapter = true)
data class SubmitResponse(val score: Int, val passed: Boolean)
