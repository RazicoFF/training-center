package uz.edu.trainingcenter.ui.screens.home

import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.CalendarMonth
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.Quiz
import androidx.compose.material.icons.filled.WorkspacePremium
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.ui.res.stringResource
import androidx.navigation.NavHostController
import androidx.navigation.compose.currentBackStackEntryAsState
import uz.edu.trainingcenter.R
import uz.edu.trainingcenter.navigation.Routes

private data class BottomTab(val route: String, val labelRes: Int, val icon: androidx.compose.ui.graphics.vector.ImageVector)

private val tabs = listOf(
    BottomTab(Routes.SCHEDULE, R.string.tab_schedule, Icons.Filled.CalendarMonth),
    BottomTab(Routes.TESTS_LIST, R.string.tab_tests, Icons.Filled.Quiz),
    BottomTab(Routes.CERTIFICATES, R.string.tab_certificates, Icons.Filled.WorkspacePremium),
    BottomTab(Routes.PROFILE, R.string.tab_profile, Icons.Filled.Person)
)

@Composable
fun HomeScaffold(navController: NavHostController, content: @Composable (androidx.compose.foundation.layout.PaddingValues) -> Unit) {
    val currentEntry by navController.currentBackStackEntryAsState()
    val currentRoute = currentEntry?.destination?.route

    Scaffold(
        bottomBar = {
            NavigationBar {
                tabs.forEach { tab ->
                    NavigationBarItem(
                        selected = currentRoute == tab.route,
                        onClick = {
                            if (currentRoute != tab.route) {
                                navController.navigate(tab.route) {
                                    popUpTo(Routes.SCHEDULE) { saveState = true }
                                    launchSingleTop = true
                                    restoreState = true
                                }
                            }
                        },
                        icon = { Icon(tab.icon, contentDescription = null) },
                        label = { Text(stringResource(tab.labelRes)) }
                    )
                }
            }
        }
    ) { padding -> content(padding) }
}
