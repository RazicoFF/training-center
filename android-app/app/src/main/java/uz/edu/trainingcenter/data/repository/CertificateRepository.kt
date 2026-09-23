package uz.edu.trainingcenter.data.repository

import okhttp3.ResponseBody
import uz.edu.trainingcenter.data.remote.ApiService
import uz.edu.trainingcenter.data.remote.SessionManager
import uz.edu.trainingcenter.data.remote.dto.CertificateDto
import uz.edu.trainingcenter.data.remote.safeApiCall

class CertificateRepository(
    private val api: ApiService,
    private val sessionManager: SessionManager
) {
    suspend fun getCertificates(): Result<List<CertificateDto>> {
        return safeApiCall(sessionManager) { api.getCertificates() }.map { it.certificates }
    }

    suspend fun downloadCertificate(certificateId: Int): Result<ResponseBody> {
        return safeApiCall(sessionManager) { api.downloadCertificate(certificateId) }
    }
}
