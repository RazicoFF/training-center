package uz.edu.trainingcenter.data.remote.dto

import com.squareup.moshi.JsonClass

@JsonClass(generateAdapter = true)
data class LoginRequest(val phone: String, val password: String)

@JsonClass(generateAdapter = true)
data class LoginResponse(val token: String)
