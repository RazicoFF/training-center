package uz.edu.trainingcenter.data.remote.dto

import com.squareup.moshi.Json
import com.squareup.moshi.JsonClass

@JsonClass(generateAdapter = true)
data class NewsDto(
    val id: Int,
    @Json(name = "title_uz") val titleUz: String,
    @Json(name = "title_ru") val titleRu: String,
    @Json(name = "body_uz") val bodyUz: String?,
    @Json(name = "body_ru") val bodyRu: String?,
    @Json(name = "image_url") val imageUrl: String?,
    @Json(name = "published_at") val publishedAt: String
)

@JsonClass(generateAdapter = true)
data class NewsResponse(val news: List<NewsDto>)
