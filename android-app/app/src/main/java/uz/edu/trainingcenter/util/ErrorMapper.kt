package uz.edu.trainingcenter.util

import com.squareup.moshi.Moshi
import com.squareup.moshi.kotlin.reflect.KotlinJsonAdapterFactory
import retrofit2.HttpException
import uz.edu.trainingcenter.data.remote.dto.ErrorResponse
import java.io.IOException

/**
 * Coarse classification of a failure so screens can render a localized,
 * user-facing message instead of a raw exception message.
 */
enum class ErrorKind { NETWORK, SERVER_MESSAGE, GENERIC }

/**
 * A UI-safe representation of a failure. [serverMessage] is only populated
 * when [kind] is [ErrorKind.SERVER_MESSAGE] and the backend supplied one.
 */
data class UiError(val kind: ErrorKind, val serverMessage: String? = null)

/**
 * Attempts to pull a human-readable message out of the backend's error body
 * (see ErrorResponse/ErrorBody). Returns null if this isn't an HttpException,
 * has no body, or the body doesn't parse as the expected shape.
 */
fun Throwable.toUserMessageOrNull(): String? {
    return when (this) {
        is IOException -> null // caller substitutes the network-error string resource
        is HttpException -> {
            val errorBody = response()?.errorBody()?.string()
            if (errorBody != null) {
                try {
                    val moshi = Moshi.Builder().add(KotlinJsonAdapterFactory()).build()
                    val parsed = moshi.adapter(ErrorResponse::class.java).fromJson(errorBody)
                    parsed?.error?.message
                } catch (e: Exception) {
                    null
                }
            } else null
        }
        else -> null
    }
}

/** Classifies a Throwable into a [UiError] for display by a Composable screen. */
fun Throwable.toUiError(): UiError {
    if (this is IOException) return UiError(ErrorKind.NETWORK)
    val serverMessage = toUserMessageOrNull()
    return if (serverMessage != null) {
        UiError(ErrorKind.SERVER_MESSAGE, serverMessage)
    } else {
        UiError(ErrorKind.GENERIC)
    }
}
