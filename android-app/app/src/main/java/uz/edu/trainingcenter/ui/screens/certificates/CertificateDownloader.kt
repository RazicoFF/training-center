package uz.edu.trainingcenter.ui.screens.certificates

import android.content.Context
import android.content.Intent
import androidx.core.content.FileProvider
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import okhttp3.ResponseBody
import java.io.File

object CertificateDownloader {
    suspend fun saveAndOpen(context: Context, certificateNumber: String, body: ResponseBody) {
        val uri = withContext(Dispatchers.IO) {
            val dir = File(context.cacheDir, "certificates").apply { mkdirs() }
            val file = File(dir, "$certificateNumber.pdf")
            file.outputStream().use { output ->
                body.byteStream().use { input -> input.copyTo(output) }
            }
            FileProvider.getUriForFile(context, "uz.edu.trainingcenter.fileprovider", file)
        }

        val intent = Intent(Intent.ACTION_VIEW).apply {
            setDataAndType(uri, "application/pdf")
            addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
            addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
        }
        context.startActivity(intent)
    }
}
