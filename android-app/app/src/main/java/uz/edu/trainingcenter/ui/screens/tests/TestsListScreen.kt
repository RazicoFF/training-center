package uz.edu.trainingcenter.ui.screens.tests

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
import uz.edu.trainingcenter.ServiceLocator
import uz.edu.trainingcenter.data.remote.dto.TestSummaryDto
import uz.edu.trainingcenter.ui.common.asString

@Composable
fun TestsListScreen(viewModel: TestsListViewModel, padding: PaddingValues, onTestClick: (Int) -> Unit) {
    val state by viewModel.uiState.collectAsState()
    val language by ServiceLocator.dataStore.languageFlow().collectAsState(initial = "uz")

    Box(modifier = Modifier.fillMaxSize().padding(padding)) {
        when (val s = state) {
            is TestsListUiState.Loading -> CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
            is TestsListUiState.Error -> Text(s.error.asString(), modifier = Modifier.align(Alignment.Center))
            is TestsListUiState.Success -> {
                if (s.tests.isEmpty()) {
                    Text(stringResource(R.string.tests_empty), modifier = Modifier.align(Alignment.Center))
                } else {
                    LazyColumn(modifier = Modifier.padding(16.dp)) {
                        items(s.tests) { test -> TestRow(test, language, onClick = { onTestClick(test.id) }) }
                    }
                }
            }
        }
    }
}

@Composable
private fun TestRow(test: TestSummaryDto, language: String, onClick: () -> Unit) {
    Card(
        modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp),
        onClick = onClick
    ) {
        Column(modifier = Modifier.padding(12.dp)) {
            Text(if (language == "ru") test.titleRu else test.titleUz, style = MaterialTheme.typography.titleMedium)
            Text(stringResource(R.string.tests_passing_score, test.passingScore))
        }
    }
}
