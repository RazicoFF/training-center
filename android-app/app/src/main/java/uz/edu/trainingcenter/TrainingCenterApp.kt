package uz.edu.trainingcenter

import android.app.Application
import androidx.appcompat.app.AppCompatDelegate
import androidx.core.os.LocaleListCompat
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.runBlocking

class TrainingCenterApp : Application() {
    override fun onCreate() {
        super.onCreate()
        ServiceLocator.init(this)

        // Apply the persisted language once at startup so it survives process death.
        // A one-time blocking read of a small DataStore preference is acceptable here;
        // it's off the critical UI-rendering path.
        val language = runBlocking { ServiceLocator.dataStore.languageFlow().first() }
        AppCompatDelegate.setApplicationLocales(LocaleListCompat.forLanguageTags(language))
    }
}
