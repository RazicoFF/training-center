package uz.edu.trainingcenter.ui.screens.certificates

import app.cash.turbine.test
import io.mockk.coEvery
import io.mockk.mockk
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.test.StandardTestDispatcher
import kotlinx.coroutines.test.resetMain
import kotlinx.coroutines.test.runTest
import kotlinx.coroutines.test.setMain
import org.junit.After
import org.junit.Assert.assertTrue
import org.junit.Before
import org.junit.Test
import uz.edu.trainingcenter.data.remote.dto.CertificateDto
import uz.edu.trainingcenter.data.repository.CertificateRepository

@OptIn(ExperimentalCoroutinesApi::class)
class CertificatesViewModelTest {

    @Before
    fun setUp() { Dispatchers.setMain(StandardTestDispatcher()) }

    @After
    fun tearDown() { Dispatchers.resetMain() }

    @Test
    fun `load emits Success with certificates`() = runTest {
        val repository = mockk<CertificateRepository>()
        coEvery { repository.getCertificates() } returns Result.success(listOf(CertificateDto(1, "CERT-2026-00001-123", "2026-09-20")))

        val viewModel = CertificatesViewModel(repository)

        viewModel.uiState.test {
            assertTrue(awaitItem() is CertificatesUiState.Loading)
            assertTrue(awaitItem() is CertificatesUiState.Success)
        }
    }
}
