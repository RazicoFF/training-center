package uz.edu.trainingcenter.ui.common

import androidx.compose.runtime.Composable
import androidx.compose.ui.res.stringResource
import uz.edu.trainingcenter.R
import uz.edu.trainingcenter.util.ErrorKind
import uz.edu.trainingcenter.util.UiError

/**
 * Renders a [UiError] as a localized, user-facing string: the translated
 * "can't connect" message for network errors, the backend-supplied message
 * when available, or a generic localized fallback otherwise.
 */
@Composable
fun UiError.asString(): String = when (kind) {
    ErrorKind.NETWORK -> stringResource(R.string.error_network)
    ErrorKind.SERVER_MESSAGE -> serverMessage ?: stringResource(R.string.error_generic)
    ErrorKind.GENERIC -> stringResource(R.string.error_generic)
}
