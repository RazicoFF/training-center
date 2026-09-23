package uz.edu.trainingcenter.data.remote.dto

import com.squareup.moshi.Json
import com.squareup.moshi.JsonClass

@JsonClass(generateAdapter = true)
data class ScheduleItemDto(
    @Json(name = "lesson_date") val lessonDate: String,
    @Json(name = "start_time") val startTime: String,
    @Json(name = "end_time") val endTime: String,
    val room: String,
    @Json(name = "group_name") val groupName: String
)

@JsonClass(generateAdapter = true)
data class ScheduleResponse(val schedule: List<ScheduleItemDto>)
