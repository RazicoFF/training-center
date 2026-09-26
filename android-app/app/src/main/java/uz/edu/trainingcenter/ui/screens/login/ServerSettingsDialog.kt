package uz.edu.trainingcenter.ui.screens.login

import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.unit.dp
import kotlinx.coroutines.launch
import uz.edu.trainingcenter.R
import uz.edu.trainingcenter.data.local.PreferencesDataStore
import uz.edu.trainingcenter.util.normalizeApiBaseUrl

/**
 * Lets the user change the API base URL before logging in. The Profile screen already offers
 * this, but it's only reachable after a successful login - which is impossible if the
 * configured server is wrong or unreachable in the first place (the app ships with an
 * emulator-only default, "http://10.0.2.2:8080/api/v1/"). This dialog is the pre-login escape
 * hatch for that case, reading/writing the same DataStore key so both entry points stay in sync.
 */
@Composable
fun ServerSettingsDialog(dataStore: PreferencesDataStore, onDismiss: () -> Unit) {
    var urlInput by remember { mutableStateOf("") }
    var isError by remember { mutableStateOf(false) }
    val scope = rememberCoroutineScope()

    LaunchedEffect(Unit) {
        urlInput = dataStore.getBaseUrl()
    }

    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text(stringResource(R.string.profile_server_url)) },
        text = {
            Column {
                OutlinedTextField(
                    value = urlInput,
                    onValueChange = {
                        urlInput = it
                        isError = false
                    },
                    isError = isError,
                    placeholder = { Text(stringResource(R.string.profile_server_url_hint)) },
                    supportingText = if (isError) {
                        { Text(stringResource(R.string.profile_invalid_url)) }
                    } else null,
                    modifier = Modifier.padding(top = 4.dp)
                )
            }
        },
        confirmButton = {
            TextButton(onClick = {
                val normalized = normalizeApiBaseUrl(urlInput)
                if (normalized == null) {
                    isError = true
                } else {
                    scope.launch {
                        dataStore.setBaseUrl(normalized)
                        onDismiss()
                    }
                }
            }) {
                Text(stringResource(R.string.profile_save))
            }
        },
        dismissButton = {
            TextButton(onClick = onDismiss) {
                Text(stringResource(R.string.login_server_settings_cancel))
            }
        }
    )
}
