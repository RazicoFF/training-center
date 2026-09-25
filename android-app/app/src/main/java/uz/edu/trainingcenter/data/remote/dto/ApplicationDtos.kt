package uz.edu.trainingcenter.data.remote.dto

import com.squareup.moshi.Json
import com.squareup.moshi.JsonClass

@JsonClass(generateAdapter = true)
data class ApplicationRequest(
    @Json(name = "full_name") val fullName: String,
    val phone: String,
    @Json(name = "profession_id") val professionId: Int,
    @Json(name = "brand_id") val brandId: Int? = null,
    @Json(name = "photo_base64") val photoBase64: String? = null
)

@JsonClass(generateAdapter = true)
data class ApplicationResponse(val id: Int)
