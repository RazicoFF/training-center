package uz.edu.trainingcenter.ui.screens.admin

import android.annotation.SuppressLint
import android.webkit.WebView
import android.webkit.WebViewClient
import androidx.activity.compose.BackHandler
import androidx.compose.foundation.layout.padding
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.viewinterop.AndroidView
import uz.edu.trainingcenter.R

/**
 * Admin and teacher accounts get the full, already-built web admin panel inside a WebView
 * rather than a native re-implementation - the user logs in again here (same login page a
 * desktop browser would show), independent of the native student JWT session.
 */
@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdminWebViewScreen(adminLoginUrl: String, onClose: () -> Unit) {
    var webViewRef by remember { mutableStateOf<WebView?>(null) }

    BackHandler {
        val webView = webViewRef
        if (webView != null && webView.canGoBack()) {
            webView.goBack()
        } else {
            onClose()
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text(stringResource(R.string.admin_panel_title)) },
                navigationIcon = {
                    IconButton(onClick = onClose) {
                        Icon(Icons.Filled.ArrowBack, contentDescription = null)
                    }
                }
            )
        }
    ) { padding ->
        AndroidView(
            modifier = androidx.compose.ui.Modifier.padding(padding),
            factory = { context ->
                WebView(context).apply {
                    @SuppressLint("SetJavaScriptEnabled")
                    settings.javaScriptEnabled = true
                    settings.domStorageEnabled = true
                    webViewClient = WebViewClient()
                    webViewRef = this
                    loadUrl(adminLoginUrl)
                }
            }
        )
    }
}

/**
 * The stored base URL is the API root (e.g. "https://host/api/v1/") - strip that suffix to
 * get the site root, then point at the admin login page.
 */
fun adminLoginUrlFrom(apiBaseUrl: String): String {
    val siteRoot = apiBaseUrl.removeSuffix("api/v1/").removeSuffix("api/v1")
    return siteRoot.trimEnd('/') + "/admin/login"
}
