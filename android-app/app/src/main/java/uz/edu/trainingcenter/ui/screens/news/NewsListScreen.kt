package uz.edu.trainingcenter.ui.screens.news

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
import androidx.compose.ui.unit.dp
import coil.compose.AsyncImage
import uz.edu.trainingcenter.R
import uz.edu.trainingcenter.ServiceLocator
import uz.edu.trainingcenter.data.local.PreferencesDataStore
import uz.edu.trainingcenter.data.remote.dto.NewsDto
import uz.edu.trainingcenter.ui.common.asString
import uz.edu.trainingcenter.util.resolveMediaUrl
import androidx.compose.ui.res.stringResource

@Composable
fun NewsListScreen(viewModel: NewsListViewModel, padding: PaddingValues) {
    val state by viewModel.uiState.collectAsState()
    val language by ServiceLocator.dataStore.languageFlow().collectAsState(initial = "uz")
    val baseUrl by ServiceLocator.dataStore.baseUrlFlow().collectAsState(initial = PreferencesDataStore.DEFAULT_BASE_URL)

    Box(modifier = Modifier.fillMaxSize().padding(padding)) {
        when (val s = state) {
            is NewsListUiState.Loading -> CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
            is NewsListUiState.Error -> Text(s.error.asString(), modifier = Modifier.align(Alignment.Center))
            is NewsListUiState.Success -> {
                if (s.news.isEmpty()) {
                    Text(stringResource(R.string.news_empty), modifier = Modifier.align(Alignment.Center))
                } else {
                    LazyColumn(modifier = Modifier.padding(16.dp)) {
                        items(s.news) { item -> NewsRow(item, language, baseUrl) }
                    }
                }
            }
        }
    }
}

@Composable
private fun NewsRow(item: NewsDto, language: String, baseUrl: String) {
    Card(modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp)) {
        Column {
            item.imageUrl?.let { path ->
                AsyncImage(
                    model = resolveMediaUrl(baseUrl, path),
                    contentDescription = null,
                    modifier = Modifier.fillMaxWidth().height(140.dp),
                    contentScale = ContentScale.Crop
                )
            }
            Column(modifier = Modifier.padding(12.dp)) {
                Text(if (language == "ru") item.titleRu else item.titleUz, style = MaterialTheme.typography.titleMedium)
                val body = if (language == "ru") item.bodyRu else item.bodyUz
                if (!body.isNullOrBlank()) {
                    Spacer(Modifier.height(4.dp))
                    Text(body)
                }
            }
        }
    }
}
