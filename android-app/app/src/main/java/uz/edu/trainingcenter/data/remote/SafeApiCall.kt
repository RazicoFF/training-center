package uz.edu.trainingcenter.data.remote

import kotlinx.coroutines.CancellationException
import retrofit2.HttpException
import java.io.IOException

suspend fun <T> safeApiCall(sessionManager: SessionManager, block: suspend () -> T): Result<T> {
    return try {
        Result.success(block())
    } catch (e: HttpException) {
        if (e.code() == 401) {
            sessionManager.notifyLoggedOut()
        }
        Result.failure(e)
    } catch (e: IOException) {
        Result.failure(e)
    } catch (e: Exception) {
        if (e is CancellationException) throw e
        Result.failure(e)
    }
}
