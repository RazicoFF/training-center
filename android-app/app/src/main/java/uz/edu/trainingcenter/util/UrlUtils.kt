package uz.edu.trainingcenter.util

import okhttp3.HttpUrl.Companion.toHttpUrlOrNull
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

/**
 * A user typing a server address is very easy to get subtly wrong - typing just
 * "https://example.com" instead of the exact "https://example.com/api/v1/" the app
 * requires produces a URL that silently 404s on every request ("Route not found") rather
 * than an obvious error. This normalizes whatever the user typed into a valid API base URL:
 * - trims whitespace
 * - adds "https://" if no scheme was given
 * - appends "api/v1/" if the path doesn't already contain it
 * - collapses the path to end in exactly one trailing slash
 *
 * Returns null if the input is blank or isn't parseable as a URL at all (scheme+host),
 * so callers can fall back to their existing invalid-URL error state.
 */
fun normalizeApiBaseUrl(rawInput: String): String? {
    val trimmed = rawInput.trim()
    if (trimmed.isEmpty()) return null

    val withScheme = if (trimmed.startsWith("http://", ignoreCase = true) ||
        trimmed.startsWith("https://", ignoreCase = true)
    ) {
        trimmed
    } else {
        "https://$trimmed"
    }

    val httpUrl = withScheme.toHttpUrlOrNull() ?: return null

    val basePath = httpUrl.encodedPath.trim('/')
    val finalPath = when {
        basePath.lowercase().contains("api/v1") -> basePath
        basePath.isEmpty() -> "api/v1"
        else -> "$basePath/api/v1"
    }

    return httpUrl.newBuilder()
        .encodedPath("/$finalPath/")
        .build()
        .toString()
}
