package uz.edu.trainingcenter.ui.screens.certificates

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import okhttp3.ResponseBody
import uz.edu.trainingcenter.data.remote.dto.CertificateDto
import uz.edu.trainingcenter.data.repository.CertificateRepository

sealed interface CertificatesUiState {
    data object Loading : CertificatesUiState
    data class Success(val certificates: List<CertificateDto>) : CertificatesUiState
    data class Error(val message: String) : CertificatesUiState
}

class CertificatesViewModel(private val repository: CertificateRepository) : ViewModel() {

    private val _uiState = MutableStateFlow<CertificatesUiState>(CertificatesUiState.Loading)
    val uiState: StateFlow<CertificatesUiState> = _uiState

    init {
        load()
    }

    fun load() {
        viewModelScope.launch {
            _uiState.value = CertificatesUiState.Loading
            val result = repository.getCertificates()
            _uiState.value = result.fold(
                onSuccess = { CertificatesUiState.Success(it) },
                onFailure = { CertificatesUiState.Error(it.message ?: "Failed to load certificates") }
            )
        }
    }

    suspend fun download(certificateId: Int): Result<ResponseBody> {
        return repository.downloadCertificate(certificateId)
    }
}
