package uz.edu.trainingcenter.ui.screens.testtaking

import androidx.compose.foundation.layout.*
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.unit.dp
import uz.edu.trainingcenter.R

@Composable
fun TestResultScreen(score: Int, passed: Boolean, onBackToTests: () -> Unit) {
    Column(
        modifier = Modifier.fillMaxSize().padding(24.dp),
        verticalArrangement = Arrangement.Center,
        horizontalAlignment = Alignment.CenterHorizontally
    ) {
        Text(
            stringResource(if (passed) R.string.test_result_passed else R.string.test_result_failed),
            style = MaterialTheme.typography.headlineMedium
        )
        Spacer(Modifier.height(16.dp))
        Text(stringResource(R.string.test_result_score, score))
        Spacer(Modifier.height(24.dp))
        Button(onClick = onBackToTests) {
            Text(stringResource(R.string.test_result_back))
        }
    }
}
