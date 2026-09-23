package uz.edu.trainingcenter.navigation

import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.navigation.NavHostController
import androidx.navigation.NavType
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.navArgument
import androidx.lifecycle.viewmodel.compose.viewModel
import uz.edu.trainingcenter.ServiceLocator
import uz.edu.trainingcenter.ViewModelFactory
import uz.edu.trainingcenter.ui.screens.home.HomeScaffold
import uz.edu.trainingcenter.ui.screens.login.LoginScreen
import uz.edu.trainingcenter.ui.screens.login.LoginViewModel
import uz.edu.trainingcenter.ui.screens.register.RegisterScreen
import uz.edu.trainingcenter.ui.screens.register.RegisterViewModel
import uz.edu.trainingcenter.ui.screens.schedule.ScheduleScreen
import uz.edu.trainingcenter.ui.screens.schedule.ScheduleViewModel
import uz.edu.trainingcenter.ui.screens.splash.SplashScreen
import uz.edu.trainingcenter.ui.screens.testtaking.TestResultScreen
import uz.edu.trainingcenter.ui.screens.testtaking.TestTakingScreen
import uz.edu.trainingcenter.ui.screens.testtaking.TestTakingViewModel

@Composable
fun AppNavHost(navController: NavHostController) {
    LaunchedEffect(Unit) {
        ServiceLocator.sessionManager.loggedOut.collect {
            navController.navigate(Routes.LOGIN) { popUpTo(0) }
        }
    }
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
        composable(Routes.HOME) {
            LaunchedEffect(Unit) {
                navController.navigate(Routes.SCHEDULE) { popUpTo(Routes.HOME) { inclusive = true } }
            }
        }
        composable(Routes.SCHEDULE) {
            HomeScaffold(navController) { padding ->
                val viewModel: ScheduleViewModel = viewModel(factory = ViewModelFactory { ScheduleViewModel(ServiceLocator.scheduleRepository) })
                ScheduleScreen(viewModel = viewModel, padding = padding)
            }
        }
        composable(Routes.TESTS_LIST) {
            HomeScaffold(navController) { padding ->
                val viewModel: uz.edu.trainingcenter.ui.screens.tests.TestsListViewModel =
                    viewModel(factory = ViewModelFactory { uz.edu.trainingcenter.ui.screens.tests.TestsListViewModel(ServiceLocator.testRepository) })
                uz.edu.trainingcenter.ui.screens.tests.TestsListScreen(
                    viewModel = viewModel,
                    padding = padding,
                    onTestClick = { testId -> navController.navigate(Routes.testTaking(testId)) }
                )
            }
        }
        composable(
            Routes.TEST_TAKING,
            arguments = listOf(navArgument("testId") { type = NavType.IntType })
        ) { backStackEntry ->
            val testId = backStackEntry.arguments?.getInt("testId") ?: return@composable
            val viewModel: TestTakingViewModel = viewModel(
                factory = ViewModelFactory { TestTakingViewModel(testId, ServiceLocator.testRepository) }
            )
            TestTakingScreen(
                viewModel = viewModel,
                onSubmitted = { score, passed ->
                    navController.navigate(Routes.testResult(score, passed)) {
                        popUpTo(Routes.TESTS_LIST)
                    }
                }
            )
        }
        composable(
            Routes.TEST_RESULT,
            arguments = listOf(
                navArgument("score") { type = NavType.IntType },
                navArgument("passed") { type = NavType.BoolType }
            )
        ) { backStackEntry ->
            val score = backStackEntry.arguments?.getInt("score") ?: 0
            val passed = backStackEntry.arguments?.getBoolean("passed") ?: false
            TestResultScreen(score = score, passed = passed, onBackToTests = { navController.popBackStack() })
        }
        composable(Routes.CERTIFICATES) {
            HomeScaffold(navController) { padding ->
                val viewModel: uz.edu.trainingcenter.ui.screens.certificates.CertificatesViewModel =
                    viewModel(factory = ViewModelFactory { uz.edu.trainingcenter.ui.screens.certificates.CertificatesViewModel(ServiceLocator.certificateRepository) })
                uz.edu.trainingcenter.ui.screens.certificates.CertificatesScreen(viewModel = viewModel, padding = padding)
            }
        }
        composable(Routes.PROFILE) {
            HomeScaffold(navController) { padding ->
                val viewModel: uz.edu.trainingcenter.ui.screens.profile.ProfileViewModel =
                    viewModel(factory = ViewModelFactory { uz.edu.trainingcenter.ui.screens.profile.ProfileViewModel(ServiceLocator.authRepository, ServiceLocator.dataStore) })
                uz.edu.trainingcenter.ui.screens.profile.ProfileScreen(
                    viewModel = viewModel,
                    padding = padding,
                    onLoggedOut = {
                        navController.navigate(Routes.LOGIN) { popUpTo(0) }
                    }
                )
            }
        }
    }
}
