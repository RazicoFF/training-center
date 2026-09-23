package uz.edu.trainingcenter

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.navigation.compose.rememberNavController
import uz.edu.trainingcenter.navigation.AppNavHost
import uz.edu.trainingcenter.ui.theme.TrainingCenterTheme

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContent {
            val themeMode by ServiceLocator.dataStore.themeFlow().collectAsState(initial = "system")
            TrainingCenterTheme(themeMode = themeMode) {
                val navController = rememberNavController()
                AppNavHost(navController = navController)
            }
        }
    }
}
