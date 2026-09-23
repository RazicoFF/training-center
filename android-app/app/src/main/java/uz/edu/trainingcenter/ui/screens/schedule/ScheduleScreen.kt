package uz.edu.trainingcenter.ui.screens.schedule

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.unit.dp
import uz.edu.trainingcenter.R
import uz.edu.trainingcenter.data.remote.dto.ScheduleItemDto

@Composable
fun ScheduleScreen(viewModel: ScheduleViewModel, padding: PaddingValues) {
    val state by viewModel.uiState.collectAsState()

    Box(modifier = Modifier.fillMaxSize().padding(padding)) {
        when (val s = state) {
            is ScheduleUiState.Loading -> CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
            is ScheduleUiState.Error -> Text(s.message, modifier = Modifier.align(Alignment.Center))
            is ScheduleUiState.Success -> {
                if (s.items.isEmpty()) {
                    Text(stringResource(R.string.schedule_empty), modifier = Modifier.align(Alignment.Center))
                } else {
                    LazyColumn(modifier = Modifier.padding(16.dp)) {
                        items(s.items) { item -> ScheduleRow(item) }
                    }
                }
            }
        }
    }
}

@Composable
private fun ScheduleRow(item: ScheduleItemDto) {
    Card(modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp)) {
        Column(modifier = Modifier.padding(12.dp)) {
            Text(item.groupName, style = MaterialTheme.typography.titleMedium)
            Text("${item.lessonDate}  ${item.startTime}-${item.endTime}")
            Text(stringResource(R.string.schedule_room, item.room))
        }
    }
}
