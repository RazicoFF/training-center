package uz.edu.trainingcenter.ui.screens.profile

import androidx.compose.foundation.layout.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.unit.dp
import uz.edu.trainingcenter.R

@Composable
fun ProfileScreen(viewModel: ProfileViewModel, padding: PaddingValues, onLoggedOut: () -> Unit) {
    val me by viewModel.me.collectAsState()
    val language by viewModel.language.collectAsState()
    val theme by viewModel.theme.collectAsState()
    val baseUrl by viewModel.baseUrl.collectAsState()
    var baseUrlInput by remember(baseUrl) { mutableStateOf(baseUrl) }

    Column(modifier = Modifier.fillMaxSize().padding(padding).padding(24.dp)) {
        me?.let {
            Text(it.fullName, style = MaterialTheme.typography.headlineSmall)
            Text(it.phone)
            Spacer(Modifier.height(24.dp))
        }

        Text(stringResource(R.string.profile_language), style = MaterialTheme.typography.titleMedium)
        Row {
            FilterChip(selected = language == "uz", onClick = { viewModel.setLanguage("uz") }, label = { Text("O'zbekcha") })
            Spacer(Modifier.width(8.dp))
            FilterChip(selected = language == "ru", onClick = { viewModel.setLanguage("ru") }, label = { Text("Русский") })
        }

        Spacer(Modifier.height(16.dp))
        Text(stringResource(R.string.profile_theme), style = MaterialTheme.typography.titleMedium)
        Row {
            FilterChip(selected = theme == "light", onClick = { viewModel.setTheme("light") }, label = { Text(stringResource(R.string.profile_theme_light)) })
            Spacer(Modifier.width(8.dp))
            FilterChip(selected = theme == "dark", onClick = { viewModel.setTheme("dark") }, label = { Text(stringResource(R.string.profile_theme_dark)) })
            Spacer(Modifier.width(8.dp))
            FilterChip(selected = theme == "system", onClick = { viewModel.setTheme("system") }, label = { Text(stringResource(R.string.profile_theme_system)) })
        }

        Spacer(Modifier.height(16.dp))
        Text(stringResource(R.string.profile_server_url), style = MaterialTheme.typography.titleMedium)
        OutlinedTextField(
            value = baseUrlInput,
            onValueChange = { baseUrlInput = it },
            modifier = Modifier.fillMaxWidth()
        )
        Button(onClick = { viewModel.setBaseUrl(baseUrlInput) }, modifier = Modifier.padding(top = 8.dp)) {
            Text(stringResource(R.string.profile_save))
        }

        Spacer(Modifier.weight(1f))
        Button(
            onClick = { viewModel.logout(); onLoggedOut() },
            colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.error),
            modifier = Modifier.fillMaxWidth()
        ) {
            Text(stringResource(R.string.profile_logout))
        }
    }
}
