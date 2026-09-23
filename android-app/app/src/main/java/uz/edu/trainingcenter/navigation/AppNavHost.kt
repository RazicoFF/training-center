package uz.edu.trainingcenter.navigation

import androidx.compose.runtime.Composable
import androidx.navigation.NavHostController
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.lifecycle.viewmodel.compose.viewModel
import uz.edu.trainingcenter.ServiceLocator
import uz.edu.trainingcenter.ViewModelFactory
import uz.edu.trainingcenter.ui.screens.login.LoginScreen
import uz.edu.trainingcenter.ui.screens.login.LoginViewModel
import uz.edu.trainingcenter.ui.screens.register.RegisterScreen
import uz.edu.trainingcenter.ui.screens.register.RegisterViewModel
import uz.edu.trainingcenter.ui.screens.splash.SplashScreen

@Composable
fun AppNavHost(navController: NavHostController) {
    NavHost(navController = navController, startDestination = Routes.SPLASH) {
        composable(Routes.SPLASH) {
            SplashScreen(onDecided = { loggedIn ->
                val target = if (loggedIn) Routes.HOME else Routes.LOGIN
                navController.navigate(target) {
                    popUpTo(Routes.SPLASH) { inclusive = true }
                }
            })
        }
        composable(Routes.LOGIN) {
            val viewModel: LoginViewModel = viewModel(factory = ViewModelFactory { LoginViewModel(ServiceLocator.authRepository) })
            LoginScreen(
                viewModel = viewModel,
                onLoginSuccess = {
                    navController.navigate(Routes.HOME) { popUpTo(Routes.LOGIN) { inclusive = true } }
                },
                onRegisterClick = { navController.navigate(Routes.REGISTER) }
            )
        }
        composable(Routes.REGISTER) {
            val viewModel: RegisterViewModel = viewModel(factory = ViewModelFactory { RegisterViewModel(ServiceLocator.professionRepository) })
            RegisterScreen(viewModel = viewModel, onSubmitted = { navController.popBackStack() })
        }
        // Routes.HOME and beyond are added by Tasks 7-11.
    }
}
