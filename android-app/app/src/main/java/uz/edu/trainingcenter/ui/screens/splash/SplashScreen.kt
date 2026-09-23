package uz.edu.trainingcenter.ui.screens.splash

import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import uz.edu.trainingcenter.ServiceLocator

@Composable
fun SplashScreen(onDecided: (loggedIn: Boolean) -> Unit) {
    LaunchedEffect(Unit) {
        val token = ServiceLocator.dataStore.getToken()
        onDecided(token != null)
    }
    Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
        CircularProgressIndicator()
    }
}
