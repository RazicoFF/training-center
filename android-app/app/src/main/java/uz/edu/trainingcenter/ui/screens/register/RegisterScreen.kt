package uz.edu.trainingcenter.ui.screens.register

import android.net.Uri
import android.util.Base64
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.layout.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.unit.dp
import uz.edu.trainingcenter.R
import uz.edu.trainingcenter.ServiceLocator
import uz.edu.trainingcenter.data.remote.dto.ProfessionBrandDto
import uz.edu.trainingcenter.data.remote.dto.ProfessionDto
import uz.edu.trainingcenter.ui.common.asString

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun RegisterScreen(viewModel: RegisterViewModel, onSubmitted: () -> Unit) {
    var fullName by remember { mutableStateOf("") }
    var phone by remember { mutableStateOf("") }
    var selectedProfession by remember { mutableStateOf<ProfessionDto?>(null) }
    var selectedBrand by remember { mutableStateOf<ProfessionBrandDto?>(null) }
    var expanded by remember { mutableStateOf(false) }
    var brandExpanded by remember { mutableStateOf(false) }
    var photoBase64 by remember { mutableStateOf<String?>(null) }
    val state by viewModel.uiState.collectAsState()
    val brands by viewModel.brands.collectAsState()
    val language by ServiceLocator.dataStore.languageFlow().collectAsState(initial = "uz")
    val context = LocalContext.current

    val photoPicker = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.PickVisualMedia()
    ) { uri: Uri? ->
        if (uri != null) {
            photoBase64 = encodeImageToDataUri(uri, context)
        }
    }

    LaunchedEffect(Unit) { viewModel.loadProfessions() }
    LaunchedEffect(state) { if (state is RegisterUiState.Submitted) onSubmitted() }
    LaunchedEffect(selectedProfession) {
        selectedBrand = null
        selectedProfession?.let { viewModel.onProfessionSelected(it.id) }
    }

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
            val selectedName = selectedProfession?.let { if (language == "ru") it.nameRu else it.nameUz } ?: ""
            OutlinedTextField(
                value = selectedName,
                onValueChange = {},
                readOnly = true,
                label = { Text(stringResource(R.string.register_profession)) },
                trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = expanded) },
                modifier = Modifier.menuAnchor().fillMaxWidth()
            )
            ExposedDropdownMenu(
                expanded = expanded,
                onDismissRequest = { expanded = false },
                modifier = Modifier.exposedDropdownSize()
            ) {
                professions.forEach { profession ->
                    DropdownMenuItem(
                        text = { Text(if (language == "ru") profession.nameRu else profession.nameUz) },
                        onClick = { selectedProfession = profession; expanded = false }
                    )
                }
            }
        }

        if (brands.isNotEmpty()) {
            Spacer(Modifier.height(8.dp))
            ExposedDropdownMenuBox(expanded = brandExpanded, onExpandedChange = { brandExpanded = it }) {
                OutlinedTextField(
                    value = selectedBrand?.name ?: "",
                    onValueChange = {},
                    readOnly = true,
                    label = { Text(stringResource(R.string.register_brand)) },
                    trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = brandExpanded) },
                    modifier = Modifier.menuAnchor().fillMaxWidth()
                )
                ExposedDropdownMenu(
                    expanded = brandExpanded,
                    onDismissRequest = { brandExpanded = false },
                    modifier = Modifier.exposedDropdownSize()
                ) {
                    brands.forEach { brand ->
                        DropdownMenuItem(
                            text = { Text(brand.name) },
                            onClick = { selectedBrand = brand; brandExpanded = false }
                        )
                    }
                }
            }
        }

        Spacer(Modifier.height(8.dp))
        Text(stringResource(R.string.register_photo), style = MaterialTheme.typography.labelLarge)
        Spacer(Modifier.height(4.dp))
        OutlinedButton(
            onClick = { photoPicker.launch(androidx.activity.result.PickVisualMediaRequest(ActivityResultContracts.PickVisualMedia.ImageOnly)) },
            modifier = Modifier.fillMaxWidth()
        ) {
            Text(
                if (photoBase64 != null) stringResource(R.string.register_photo_selected)
                else stringResource(R.string.register_photo_pick)
            )
        }

        Spacer(Modifier.height(16.dp))
        if (state is RegisterUiState.Error) {
            Text((state as RegisterUiState.Error).error.asString(), color = MaterialTheme.colorScheme.error)
            Spacer(Modifier.height(8.dp))
        }
        Button(
            onClick = {
                selectedProfession?.let {
                    viewModel.submit(fullName, phone, it.id, selectedBrand?.id, photoBase64)
                }
            },
            enabled = state !is RegisterUiState.Submitting && selectedProfession != null,
            modifier = Modifier.fillMaxWidth()
        ) {
            Text(stringResource(R.string.register_submit))
        }
    }
}

/**
 * Reads the picked image into a base64 data URI (e.g. "data:image/jpeg;base64,...") for the
 * JSON API's photo_base64 field, since there's no multipart upload on this endpoint.
 */
private fun encodeImageToDataUri(uri: Uri, context: android.content.Context): String? {
    val bytes = context.contentResolver.openInputStream(uri)?.use { it.readBytes() } ?: return null
    val mimeType = context.contentResolver.getType(uri) ?: "image/jpeg"
    val base64 = Base64.encodeToString(bytes, Base64.NO_WRAP)
    return "data:$mimeType;base64,$base64"
}
