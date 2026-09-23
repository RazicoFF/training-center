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
    @Json(name = "image_url") val imageUrl: String?
)

@JsonClass(generateAdapter = true)
data class ProfessionsResponse(val professions: List<ProfessionDto>)
