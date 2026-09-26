package uz.edu.trainingcenter.util

import org.junit.Assert.assertEquals
import org.junit.Assert.assertNull
import org.junit.Test

class UrlUtilsTest {

    @Test
    fun `plain domain with no scheme gets https and api v1 appended`() {
        assertEquals(
            "https://training-center.up.railway.app/api/v1/",
            normalizeApiBaseUrl("training-center.up.railway.app")
        )
    }

    @Test
    fun `domain with scheme but no api v1 gets api v1 appended`() {
        assertEquals(
            "https://training-center.up.railway.app/api/v1/",
            normalizeApiBaseUrl("https://training-center.up.railway.app")
        )
    }

    @Test
    fun `domain that already has api v1 with trailing slash is left unchanged`() {
        assertEquals(
            "https://training-center.up.railway.app/api/v1/",
            normalizeApiBaseUrl("https://training-center.up.railway.app/api/v1/")
        )
    }

    @Test
    fun `domain with api v1 but no trailing slash is normalized consistently`() {
        assertEquals(
            "https://training-center.up.railway.app/api/v1/",
            normalizeApiBaseUrl("https://training-center.up.railway.app/api/v1")
        )
    }

    @Test
    fun `path prefix before api v1 is preserved`() {
        assertEquals(
            "http://example.uz/trainingcenter/api/v1/",
            normalizeApiBaseUrl("http://example.uz/trainingcenter/api/v1")
        )
    }

    @Test
    fun `whitespace is trimmed`() {
        assertEquals(
            "https://training-center.up.railway.app/api/v1/",
            normalizeApiBaseUrl("  training-center.up.railway.app  ")
        )
    }

    @Test
    fun `garbage input returns null`() {
        assertNull(normalizeApiBaseUrl(""))
        assertNull(normalizeApiBaseUrl("   "))
        assertNull(normalizeApiBaseUrl("not a url at all!!"))
    }
}
