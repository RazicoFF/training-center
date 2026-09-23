package uz.edu.trainingcenter.ui.theme

import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable

@Composable
fun TrainingCenterTheme(themeMode: String, content: @Composable () -> Unit) {
    val useDark = when (themeMode) {
        "dark" -> true
        "light" -> false
        else -> isSystemInDarkTheme()
    }

    val colorScheme = if (useDark) {
        darkColorScheme(primary = PrimaryDark, onPrimary = OnPrimaryDark)
    } else {
        lightColorScheme(primary = PrimaryLight, onPrimary = OnPrimaryLight)
    }

    MaterialTheme(colorScheme = colorScheme, content = content)
}
