package uz.edu.trainingcenter.data.remote.dto

import com.squareup.moshi.Json
import com.squareup.moshi.JsonClass

@JsonClass(generateAdapter = true)
data class ProfessionDto(
    val id: Int,
    @Json(name = "name_uz") val nameUz: String,
    @Json(name = "name_ru") val nameRu: String,
    @Json(name = "description_uz") val descriptionUz: String,
    @Json(name = "description_ru") val descriptionRu: String,
    @Json(name = "duration_days") val durationDays: Int,
    val price: String,
    @Json(name = "image_url") val imageUrl: String?,
    @Json(name = "pdf_url") val pdfUrl: String? = null,
    @Json(name = "career_info_uz") val careerInfoUz: String? = null,
    @Json(name = "career_info_ru") val careerInfoRu: String? = null,
    val videos: List<ProfessionVideoDto>? = null,
    val tests: List<ProfessionTestSummaryDto>? = null,
    val brands: List<ProfessionBrandDto>? = null
)

@JsonClass(generateAdapter = true)
data class ProfessionBrandDto(
    val id: Int,
    val name: String
)

@JsonClass(generateAdapter = true)
data class ProfessionVideoDto(
    val id: Int,
    @Json(name = "youtube_url") val youtubeUrl: String,
    @Json(name = "title_uz") val titleUz: String?,
    @Json(name = "title_ru") val titleRu: String?
)

@JsonClass(generateAdapter = true)
data class ProfessionTestSummaryDto(
    val id: Int,
    @Json(name = "title_uz") val titleUz: String,
    @Json(name = "title_ru") val titleRu: String,
    @Json(name = "question_count") val questionCount: Int
)

@JsonClass(generateAdapter = true)
data class ProfessionsResponse(val professions: List<ProfessionDto>)
