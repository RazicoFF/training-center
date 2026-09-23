package uz.edu.trainingcenter.data.remote.dto

import com.squareup.moshi.Json
import com.squareup.moshi.JsonClass

@JsonClass(generateAdapter = true)
data class CertificateDto(
    val id: Int,
    @Json(name = "certificate_number") val certificateNumber: String,
    @Json(name = "issue_date") val issueDate: String
)

@JsonClass(generateAdapter = true)
data class CertificatesResponse(val certificates: List<CertificateDto>)
