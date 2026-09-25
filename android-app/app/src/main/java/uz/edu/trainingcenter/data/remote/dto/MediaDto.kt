package uz.edu.trainingcenter.data.remote.dto

import com.squareup.moshi.Json
import com.squareup.moshi.JsonClass

@JsonClass(generateAdapter = true)
data class MediaDto(
    val id: Int,
    val type: String, // "image" or "video"
    @Json(name = "file_url") val fileUrl: String?,
    @Json(name = "youtube_url") val youtubeUrl: String?,
    @Json(name = "title_uz") val titleUz: String?,
    @Json(name = "title_ru") val titleRu: String?
)

@JsonClass(generateAdapter = true)
data class MediaResponse(val media: List<MediaDto>)
