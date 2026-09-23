package uz.edu.trainingcenter.data.remote.dto

import com.squareup.moshi.JsonClass

@JsonClass(generateAdapter = true)
data class ErrorResponse(val error: ErrorBody)

@JsonClass(generateAdapter = true)
data class ErrorBody(val code: String, val message: String)
