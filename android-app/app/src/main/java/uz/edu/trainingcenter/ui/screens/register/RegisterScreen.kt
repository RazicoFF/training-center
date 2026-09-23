package uz.edu.trainingcenter.ui.screens.register

import androidx.compose.foundation.layout.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.unit.dp
import uz.edu.trainingcenter.R
import uz.edu.trainingcenter.data.remote.dto.ProfessionDto

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun RegisterScreen(viewModel: RegisterViewModel, onSubmitted: () -> Unit) {
    var fullName by remember { mutableStateOf("") }
    var phone by remember { mutableStateOf("") }
    var selectedProfession by remember { mutableStateOf<ProfessionDto?>(null) }
    var expanded by remember { mutableStateOf(false) }
    val state by viewModel.uiState.collectAsState()

    LaunchedEffect(Unit) { viewModel.loadProfessions() }
    LaunchedEffect(state) { if (state is RegisterUiState.Submitted) onSubmitted() }

    Column(modifier = Modifier.fillMaxSize().padding(24.dp)) {
        Text(stringResource(R.string.register_title), style = MaterialTheme.typography.headlineSmall)
        Spacer(Modifier.height(16.dp))
        OutlinedTextField(
            value = fullName,
            onValueChange = { fullName = it },
            label = { Text(stringResource(R.string.register_full_name)) },
            modifier = Modifier.fillMaxWidth()
        )
        Spacer(Modifier.height(8.dp))
        OutlinedTextField(
            value = phone,
            onValueChange = { phone = it },
            label = { Text(stringResource(R.string.register_phone)) },
            modifier = Modifier.fillMaxWidth()
        )
        Spacer(Modifier.height(8.dp))

        val professions = (state as? RegisterUiState.ProfessionsLoaded)?.professions.orEmpty()
        ExposedDropdownMenuBox(expanded = expanded, onExpandedChange = { expanded = it }) {
            OutlinedTextField(
                value = selectedProfession?.nameUz ?: "",
                onValueChange = {},
                readOnly = true,
                label = { Text(stringResource(R.string.register_profession)) },
                modifier = Modifier.fillMaxWidth()
            )
            ExposedDropdownMenu(expanded = expanded, onDismissRequest = { expanded = false }) {
                professions.forEach { profession ->
                    DropdownMenuItem(
                        text = { Text(profession.nameUz) },
                        onClick = { selectedProfession = profession; expanded = false }
                    )
                }
            }
        }

        Spacer(Modifier.height(16.dp))
        if (state is RegisterUiState.Error) {
            Text((state as RegisterUiState.Error).message, color = MaterialTheme.colorScheme.error)
            Spacer(Modifier.height(8.dp))
        }
        Button(
            onClick = { selectedProfession?.let { viewModel.submit(fullName, phone, it.id) } },
            enabled = state !is RegisterUiState.Submitting && selectedProfession != null,
            modifier = Modifier.fillMaxWidth()
        ) {
            Text(stringResource(R.string.register_submit))
        }
    }
}
