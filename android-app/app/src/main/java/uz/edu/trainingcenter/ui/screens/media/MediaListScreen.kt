package uz.edu.trainingcenter.ui.screens.media

import android.content.Intent
import android.net.Uri
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.PlayCircle
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
import uz.edu.trainingcenter.data.remote.dto.MediaDto
import uz.edu.trainingcenter.ui.common.asString
import uz.edu.trainingcenter.util.resolveMediaUrl

@Composable
fun MediaListScreen(viewModel: MediaListViewModel, padding: PaddingValues) {
    val state by viewModel.uiState.collectAsState()
    val language by ServiceLocator.dataStore.languageFlow().collectAsState(initial = "uz")
    val baseUrl by ServiceLocator.dataStore.baseUrlFlow().collectAsState(initial = PreferencesDataStore.DEFAULT_BASE_URL)
    val context = LocalContext.current

    Box(modifier = Modifier.fillMaxSize().padding(padding)) {
        when (val s = state) {
            is MediaListUiState.Loading -> CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
            is MediaListUiState.Error -> Text(s.error.asString(), modifier = Modifier.align(Alignment.Center))
            is MediaListUiState.Success -> {
                if (s.media.isEmpty()) {
                    Text(stringResource(R.string.media_empty), modifier = Modifier.align(Alignment.Center))
                } else {
                    LazyColumn(modifier = Modifier.padding(16.dp)) {
                        items(s.media) { item ->
                            MediaRow(item, language, baseUrl, onOpenVideo = { url ->
                                context.startActivity(Intent(Intent.ACTION_VIEW, Uri.parse(url)))
                            })
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun MediaRow(item: MediaDto, language: String, baseUrl: String, onOpenVideo: (String) -> Unit) {
    val title = if (language == "ru") item.titleRu else item.titleUz

    if (item.type == "video" && item.youtubeUrl != null) {
        // Videos link out to YouTube/browser directly rather than embedding, since
        // in-app embeds unreliably show "video unavailable" for some channels.
        Card(
            modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp),
            onClick = { onOpenVideo(item.youtubeUrl) }
        ) {
            Row(modifier = Modifier.padding(12.dp), verticalAlignment = Alignment.CenterVertically) {
                Icon(Icons.Filled.PlayCircle, contentDescription = null)
                Spacer(Modifier.width(12.dp))
                Text(title ?: item.youtubeUrl)
            }
        }
    } else if (item.fileUrl != null) {
        Card(modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp)) {
            Column {
                AsyncImage(
                    model = resolveMediaUrl(baseUrl, item.fileUrl),
                    contentDescription = null,
                    modifier = Modifier.fillMaxWidth().height(180.dp),
                    contentScale = ContentScale.Crop
                )
                if (!title.isNullOrBlank()) {
                    Text(title, modifier = Modifier.padding(12.dp))
                }
            }
        }
    }
}
