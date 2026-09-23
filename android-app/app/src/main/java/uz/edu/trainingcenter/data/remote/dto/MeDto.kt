package uz.edu.trainingcenter.data.remote.dto

import com.squareup.moshi.Json
import com.squareup.moshi.JsonClass

@JsonClass(generateAdapter = true)
data class MeDto(
    val id: Int,
    @Json(name = "full_name") val fullName: String,
    val phone: String,
    val role: String,
    val language: String
)
