package uz.edu.trainingcenter.ui.screens.login

import androidx.compose.foundation.layout.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.unit.dp
import uz.edu.trainingcenter.R
import uz.edu.trainingcenter.ui.common.asString

@Composable
fun LoginScreen(
    viewModel: LoginViewModel,
    onLoginSuccess: () -> Unit,
    onRegisterClick: () -> Unit
) {
    var phone by remember { mutableStateOf("") }
    var password by remember { mutableStateOf("") }
    val state by viewModel.uiState.collectAsState()

    LaunchedEffect(state) {
        if (state is LoginUiState.Success) onLoginSuccess()
    }

    Column(
        modifier = Modifier.fillMaxSize().padding(24.dp),
        verticalArrangement = Arrangement.Center,
        horizontalAlignment = Alignment.CenterHorizontally
    ) {
        Text(stringResource(R.string.login_title), style = MaterialTheme.typography.headlineSmall)
        Spacer(Modifier.height(24.dp))
        OutlinedTextField(
            value = phone,
            onValueChange = { phone = it },
            label = { Text(stringResource(R.string.login_phone)) },
            modifier = Modifier.fillMaxWidth()
        )
        Spacer(Modifier.height(8.dp))
        OutlinedTextField(
            value = password,
            onValueChange = { password = it },
            label = { Text(stringResource(R.string.login_password)) },
            visualTransformation = androidx.compose.ui.text.input.PasswordVisualTransformation(),
            modifier = Modifier.fillMaxWidth()
        )
        Spacer(Modifier.height(16.dp))
        if (state is LoginUiState.Error) {
            Text((state as LoginUiState.Error).error.asString(), color = MaterialTheme.colorScheme.error)
            Spacer(Modifier.height(8.dp))
        }
        Button(
            onClick = { viewModel.login(phone, password) },
            enabled = state !is LoginUiState.Loading,
            modifier = Modifier.fillMaxWidth()
        ) {
            if (state is LoginUiState.Loading) {
                CircularProgressIndicator(modifier = Modifier.size(20.dp))
            } else {
                Text(stringResource(R.string.login_submit))
            }
        }
        Spacer(Modifier.height(12.dp))
        TextButton(onClick = onRegisterClick) {
            Text(stringResource(R.string.login_register_link))
        }
    }
}
