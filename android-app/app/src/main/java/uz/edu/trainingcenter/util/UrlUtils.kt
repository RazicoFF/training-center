package uz.edu.trainingcenter.util

import java.net.URI

/**
 * The API returns profession/news images and PDFs as server-relative paths
 * (e.g. "/uploads/professions/x.pdf"), while the configured base URL points at
 * the API prefix (e.g. "http://10.0.2.2:8080/api/v1/"). This resolves a relative
 * path against the API's origin (scheme+host+port), independent of that prefix.
 */
fun resolveMediaUrl(baseApiUrl: String, relativePath: String): String {
    if (relativePath.startsWith("http://") || relativePath.startsWith("https://")) {
        return relativePath
    }

    return try {
        val uri = URI(baseApiUrl)
        "${uri.scheme}://${uri.authority}$relativePath"
    } catch (e: Exception) {
        relativePath
    }
}
