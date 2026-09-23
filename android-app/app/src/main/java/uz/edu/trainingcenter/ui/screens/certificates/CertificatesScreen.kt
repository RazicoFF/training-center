package uz.edu.trainingcenter.ui.screens.certificates

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.unit.dp
import kotlinx.coroutines.launch
import uz.edu.trainingcenter.R
import uz.edu.trainingcenter.data.remote.dto.CertificateDto

@Composable
fun CertificatesScreen(viewModel: CertificatesViewModel, padding: PaddingValues) {
    val state by viewModel.uiState.collectAsState()
    val context = LocalContext.current
    val scope = rememberCoroutineScope()

    Box(modifier = Modifier.fillMaxSize().padding(padding)) {
        when (val s = state) {
            is CertificatesUiState.Loading -> CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
            is CertificatesUiState.Error -> Text(s.message, modifier = Modifier.align(Alignment.Center))
            is CertificatesUiState.Success -> {
                if (s.certificates.isEmpty()) {
                    Text(stringResource(R.string.certificates_empty), modifier = Modifier.align(Alignment.Center))
                } else {
                    LazyColumn(modifier = Modifier.padding(16.dp)) {
                        items(s.certificates) { certificate ->
                            CertificateRow(certificate) {
                                scope.launch {
                                    viewModel.download(certificate.id).onSuccess { body ->
                                        CertificateDownloader.saveAndOpen(context, certificate.certificateNumber, body)
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun CertificateRow(certificate: CertificateDto, onDownload: () -> Unit) {
    Card(modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp)) {
        Row(
            modifier = Modifier.padding(12.dp).fillMaxWidth(),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            Column {
                Text(certificate.certificateNumber, style = MaterialTheme.typography.titleMedium)
                Text(certificate.issueDate)
            }
            Button(onClick = onDownload) {
                Text(stringResource(R.string.certificates_download))
            }
        }
    }
}
