package uz.edu.trainingcenter.data.repository

import io.mockk.coEvery
import io.mockk.mockk
import kotlinx.coroutines.test.runTest
import okhttp3.ResponseBody
import okhttp3.ResponseBody.Companion.toResponseBody
import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Test
import uz.edu.trainingcenter.data.remote.ApiService
import uz.edu.trainingcenter.data.remote.SessionManager
import uz.edu.trainingcenter.data.remote.dto.CertificateDto
import uz.edu.trainingcenter.data.remote.dto.CertificatesResponse

class CertificateRepositoryTest {

    @Test
    fun `getCertificates returns list on success`() = runTest {
        val api = mockk<ApiService>()
        coEvery { api.getCertificates() } returns CertificatesResponse(listOf(CertificateDto(1, "CERT-2026-00001-123", "2026-09-20")))

        val result = CertificateRepository(api, SessionManager(mockk(relaxed = true))).getCertificates()

        assertTrue(result.isSuccess)
        assertEquals(1, result.getOrThrow().size)
    }

    @Test
    fun `downloadCertificate returns the response body on success`() = runTest {
        val api = mockk<ApiService>()
        val body: ResponseBody = "pdf-bytes".toResponseBody(null)
        coEvery { api.downloadCertificate(1) } returns body

        val result = CertificateRepository(api, SessionManager(mockk(relaxed = true))).downloadCertificate(1)

        assertTrue(result.isSuccess)
    }
}
