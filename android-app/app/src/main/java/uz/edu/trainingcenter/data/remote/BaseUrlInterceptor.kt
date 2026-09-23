package uz.edu.trainingcenter.data.remote

import okhttp3.HttpUrl.Companion.toHttpUrl
import okhttp3.Interceptor
import okhttp3.Response
import uz.edu.trainingcenter.data.local.PreferencesDataStore

/**
 * Retrofit is built with a fixed compile-time placeholder baseUrl
 * (PreferencesDataStore.DEFAULT_BASE_URL, whose path is "/api/v1/"), so every
 * request's encodedPath always starts with that fixed prefix. This interceptor
 * swaps in the user-configured server address at request time.
 *
 * A naive scheme/host/port-only swap (the previous implementation) silently
 * drops any path prefix the user's address has, e.g. a shared-hosting-style
 * address like "http://example.uz/trainingcenter/api/v1/" would lose
 * "/trainingcenter". Instead, we strip the compile-time default's path
 * prefix ("/api/v1") off the original request path and re-append the
 * remainder to the configured base's own encoded path, so a base URL with an
 * extra path prefix is preserved.
 */
class BaseUrlInterceptor(private val baseUrlProvider: () -> String) : Interceptor {

    private val defaultPathPrefix: String =
        PreferencesDataStore.DEFAULT_BASE_URL.toHttpUrl().encodedPath.trimEnd('/')

    override fun intercept(chain: Interceptor.Chain): Response {
        val original = chain.request()
        val newBase = baseUrlProvider().toHttpUrl()

        val originalPath = original.url.encodedPath
        val relativePath = if (defaultPathPrefix.isNotEmpty() && originalPath.startsWith(defaultPathPrefix)) {
            originalPath.removePrefix(defaultPathPrefix)
        } else {
            originalPath
        }

        val newBasePath = newBase.encodedPath.trimEnd('/')
        val newUrl = original.url.newBuilder()
            .scheme(newBase.scheme)
            .host(newBase.host)
            .port(newBase.port)
            .encodedPath(newBasePath + relativePath)
            .build()
        return chain.proceed(original.newBuilder().url(newUrl).build())
    }
}
