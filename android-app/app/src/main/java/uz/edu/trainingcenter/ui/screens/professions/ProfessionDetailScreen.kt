package uz.edu.trainingcenter.ui.screens.professions

import android.content.Intent
import android.net.Uri
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
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.unit.dp
import coil.compose.AsyncImage
import uz.edu.trainingcenter.R
import uz.edu.trainingcenter.ServiceLocator
import uz.edu.trainingcenter.data.local.PreferencesDataStore
import uz.edu.trainingcenter.data.remote.dto.ProfessionDto
import uz.edu.trainingcenter.data.remote.dto.ProfessionTestSummaryDto
import uz.edu.trainingcenter.data.remote.dto.ProfessionVideoDto
import uz.edu.trainingcenter.ui.common.asString
import uz.edu.trainingcenter.util.resolveMediaUrl

@Composable
fun ProfessionDetailScreen(viewModel: ProfessionDetailViewModel) {
    val state by viewModel.uiState.collectAsState()
    val language by ServiceLocator.dataStore.languageFlow().collectAsState(initial = "uz")
    val baseUrl by ServiceLocator.dataStore.baseUrlFlow().collectAsState(initial = PreferencesDataStore.DEFAULT_BASE_URL)
    val context = LocalContext.current

    Box(modifier = Modifier.fillMaxSize()) {
        when (val s = state) {
            is ProfessionDetailUiState.Loading -> CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
            is ProfessionDetailUiState.Error -> Text(s.error.asString(), modifier = Modifier.align(Alignment.Center))
            is ProfessionDetailUiState.Success -> {
                ProfessionDetailContent(s.profession, language, baseUrl, onOpenUrl = { url ->
                    context.startActivity(Intent(Intent.ACTION_VIEW, Uri.parse(url)))
                })
            }
        }
    }
}

@Composable
private fun ProfessionDetailContent(
    profession: ProfessionDto,
    language: String,
    baseUrl: String,
    onOpenUrl: (String) -> Unit
) {
    LazyColumn(modifier = Modifier.fillMaxSize().padding(16.dp)) {
        item {
            profession.imageUrl?.let { path ->
                AsyncImage(
                    model = resolveMediaUrl(baseUrl, path),
                    contentDescription = null,
                    modifier = Modifier.fillMaxWidth().height(200.dp),
                    contentScale = ContentScale.Crop
                )
                Spacer(Modifier.height(12.dp))
            }
            Text(
                if (language == "ru") profession.nameRu else profession.nameUz,
                style = MaterialTheme.typography.headlineSmall
            )
            Spacer(Modifier.height(8.dp))
            Text(if (language == "ru") profession.descriptionRu else profession.descriptionUz)
            Spacer(Modifier.height(8.dp))
            Text(stringResource(R.string.profession_duration_days, profession.durationDays))

            val careerInfo = if (language == "ru") profession.careerInfoRu else profession.careerInfoUz
            if (!careerInfo.isNullOrBlank()) {
                Spacer(Modifier.height(8.dp))
                Text(stringResource(R.string.profession_career_info_label), style = MaterialTheme.typography.titleSmall)
                Text(careerInfo)
            }

            profession.pdfUrl?.let { pdfPath ->
                Spacer(Modifier.height(12.dp))
                Button(onClick = { onOpenUrl(resolveMediaUrl(baseUrl, pdfPath)) }) {
                    Text(stringResource(R.string.profession_open_pdf))
                }
            }
        }

        val videos = profession.videos.orEmpty()
        if (videos.isNotEmpty()) {
            item {
                Spacer(Modifier.height(20.dp))
                Text(stringResource(R.string.profession_videos_title), style = MaterialTheme.typography.titleMedium)
            }
            items(videos) { video -> VideoRow(video, language, onClick = { onOpenUrl(video.youtubeUrl) }) }
        }

        val tests = profession.tests.orEmpty()
        if (tests.isNotEmpty()) {
            item {
                Spacer(Modifier.height(20.dp))
                Text(stringResource(R.string.tab_tests), style = MaterialTheme.typography.titleMedium)
            }
            items(tests) { test -> TestSummaryRow(test, language) }
        }
    }
}

@Composable
private fun VideoRow(video: ProfessionVideoDto, language: String, onClick: () -> Unit) {
    val title = (if (language == "ru") video.titleRu else video.titleUz) ?: video.youtubeUrl
    Card(modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp), onClick = onClick) {
        Text(title, modifier = Modifier.padding(12.dp))
    }
}

@Composable
private fun TestSummaryRow(test: ProfessionTestSummaryDto, language: String) {
    Card(modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp)) {
        Text(if (language == "ru") test.titleRu else test.titleUz, modifier = Modifier.padding(12.dp))
    }
}
