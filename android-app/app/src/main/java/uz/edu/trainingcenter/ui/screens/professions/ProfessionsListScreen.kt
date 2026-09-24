package uz.edu.trainingcenter.ui.screens.professions

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.unit.dp
import coil.compose.AsyncImage
import uz.edu.trainingcenter.R
import uz.edu.trainingcenter.ServiceLocator
import uz.edu.trainingcenter.data.local.PreferencesDataStore
import uz.edu.trainingcenter.data.remote.dto.ProfessionDto
import uz.edu.trainingcenter.ui.common.asString
import uz.edu.trainingcenter.util.resolveMediaUrl

@Composable
fun ProfessionsListScreen(
    viewModel: ProfessionsListViewModel,
    padding: PaddingValues,
    onProfessionClick: (Int) -> Unit
) {
    val state by viewModel.uiState.collectAsState()
    val language by ServiceLocator.dataStore.languageFlow().collectAsState(initial = "uz")
    val baseUrl by ServiceLocator.dataStore.baseUrlFlow().collectAsState(initial = PreferencesDataStore.DEFAULT_BASE_URL)

    Box(modifier = Modifier.fillMaxSize().padding(padding)) {
        when (val s = state) {
            is ProfessionsListUiState.Loading -> CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
            is ProfessionsListUiState.Error -> Text(s.error.asString(), modifier = Modifier.align(Alignment.Center))
            is ProfessionsListUiState.Success -> {
                LazyColumn(modifier = Modifier.padding(16.dp)) {
                    items(s.professions) { profession ->
                        ProfessionRow(profession, language, baseUrl, onClick = { onProfessionClick(profession.id) })
                    }
                }
            }
        }
    }
}

@Composable
private fun ProfessionRow(profession: ProfessionDto, language: String, baseUrl: String, onClick: () -> Unit) {
    Card(modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp), onClick = onClick) {
        Column {
            profession.imageUrl?.let { path ->
                AsyncImage(
                    model = resolveMediaUrl(baseUrl, path),
                    contentDescription = null,
                    modifier = Modifier.fillMaxWidth().height(140.dp),
                    contentScale = ContentScale.Crop
                )
            }
            Column(modifier = Modifier.padding(12.dp)) {
                Text(
                    if (language == "ru") profession.nameRu else profession.nameUz,
                    style = MaterialTheme.typography.titleMedium
                )
                Text(stringResource(R.string.profession_duration_days, profession.durationDays))
            }
        }
    }
}
