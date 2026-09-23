package uz.edu.trainingcenter.ui.screens.testtaking

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.selection.selectable
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.unit.dp
import uz.edu.trainingcenter.R
import uz.edu.trainingcenter.ServiceLocator
import uz.edu.trainingcenter.data.remote.dto.QuestionDto
import uz.edu.trainingcenter.ui.common.asString

@Composable
fun TestTakingScreen(viewModel: TestTakingViewModel, onSubmitted: (score: Int, passed: Boolean) -> Unit) {
    val state by viewModel.uiState.collectAsState()
    val language by ServiceLocator.dataStore.languageFlow().collectAsState(initial = "uz")

    LaunchedEffect(state) {
        val s = state
        if (s is TestTakingUiState.Submitted) onSubmitted(s.score, s.passed)
    }

    Box(modifier = Modifier.fillMaxSize().padding(24.dp)) {
        when (val s = state) {
            is TestTakingUiState.Loading, is TestTakingUiState.Submitting ->
                CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
            is TestTakingUiState.Error -> {
                Column(modifier = Modifier.align(Alignment.Center)) {
                    Text(s.error.asString())
                    Spacer(Modifier.height(16.dp))
                    Button(onClick = { viewModel.submitTest() }) {
                        Text(stringResource(R.string.test_taking_retry))
                    }
                }
            }
            is TestTakingUiState.Submitted -> Unit
            is TestTakingUiState.InProgress -> {
                InProgressContent(s, language, viewModel)
            }
        }
    }
}

@Composable
private fun InProgressContent(s: TestTakingUiState.InProgress, language: String, viewModel: TestTakingViewModel) {
    val question = s.questions[s.currentIndex]
    val selected = s.selectedAnswers[question.id]
    val isLast = s.currentIndex == s.questions.size - 1

    Column {
        Text(
            stringResource(R.string.test_taking_progress, s.currentIndex + 1, s.questions.size),
            style = MaterialTheme.typography.labelLarge
        )
        Spacer(Modifier.height(16.dp))
        Text(question.textOf(language), style = MaterialTheme.typography.headlineSmall)
        Spacer(Modifier.height(16.dp))
        question.answers.forEach { answer ->
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .selectable(selected = selected == answer.id, onClick = { viewModel.selectAnswer(answer.id) })
                    .padding(vertical = 8.dp),
                verticalAlignment = Alignment.CenterVertically
            ) {
                RadioButton(selected = selected == answer.id, onClick = { viewModel.selectAnswer(answer.id) })
                Spacer(Modifier.width(8.dp))
                Text(if (language == "ru") answer.textRu else answer.textUz)
            }
        }
        Spacer(Modifier.weight(1f))
        Button(
            onClick = { if (isLast) viewModel.submitTest() else viewModel.nextQuestion() },
            enabled = selected != null,
            modifier = Modifier.fillMaxWidth()
        ) {
            Text(stringResource(if (isLast) R.string.test_taking_finish else R.string.test_taking_next))
        }
    }
}

private fun QuestionDto.textOf(language: String): String = if (language == "ru") textRu else textUz
