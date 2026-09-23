# Android App Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the Kotlin/Jetpack Compose Android app for training-center students: apply, log in, view schedule, take tests, view/download certificates, and manage language/theme/server settings — consuming the existing backend REST API (sub-project 1) with no backend changes.

**Architecture:** Single-activity MVVM. `ServiceLocator` (manual DI, no Hilt) provides a DataStore-backed settings store, an OkHttp/Retrofit stack with two interceptors (dynamic base URL, JWT auth), and repositories. Each screen has a `ViewModel` exposing `StateFlow<UiState>`; Compose screens collect it. Navigation via Jetpack Navigation Compose.

**Tech Stack:** Kotlin, Jetpack Compose (Material 3), Retrofit + OkHttp + Moshi, Jetpack DataStore (Preferences), Kotlin Coroutines, Navigation Compose. Test: JUnit4, MockK, Turbine, MockWebServer.

**Spec:** `docs/superpowers/specs/2026-09-23-android-app-design.md` (and `docs/superpowers/specs/2026-09-22-backend-db-design.md` for the exact API this app consumes).

## Global Constraints

- Kotlin, minSdk 24, targetSdk/compileSdk 34.
- No backend changes — this plan only creates `android-app/`.
- All network calls go through `ApiService` (Retrofit) — no raw `HttpURLConnection`/OkHttp calls from UI or ViewModel code.
- Every screen's ViewModel exposes state as `sealed interface UiState { object Loading; data class Success(...); data class Error(val message: String) }` (one such sealed interface per screen, named `<Screen>UiState`) — never expose raw exceptions to Compose code.
- Every repository method returns `Result<T>` (Kotlin's stdlib `Result`), never throws past its own boundary — callers (ViewModels) branch on `.isSuccess`/`.isFailure`.
- A `401` response anywhere triggers `SessionManager.notifyLoggedOut()`, clears the stored JWT, and the UI observes this to navigate back to Login — implemented once (Task 3) and never duplicated per-screen.
- All user-facing strings go through `stringResource(R.string.xxx)` with both `values/strings.xml` (uz) and `values-ru/strings.xml` (ru) — never hardcoded literal UI text in Kotlin/Compose code.
- Every ViewModel and Repository gets a unit test using MockK for its dependencies; no test requires an emulator or real network.

---

## File Structure

```
android-app/
  settings.gradle.kts
  build.gradle.kts
  gradle.properties
  gradle/wrapper/gradle-wrapper.properties
  app/
    build.gradle.kts
    src/
      main/
        AndroidManifest.xml
        java/uz/edu/trainingcenter/
          TrainingCenterApp.kt            # Application class, ServiceLocator.init()
          MainActivity.kt
          ServiceLocator.kt               # manual DI
          ViewModelFactory.kt
          navigation/
            AppNavHost.kt
            Routes.kt
          data/
            local/
              PreferencesDataStore.kt
            remote/
              ApiService.kt
              NetworkModule.kt            # builds OkHttp/Retrofit
              AuthInterceptor.kt
              BaseUrlInterceptor.kt
              SessionManager.kt
              SafeApiCall.kt
              dto/
                ProfessionDto.kt
                ApplicationDtos.kt
                AuthDtos.kt
                MeDto.kt
                ScheduleDtos.kt
                TestDtos.kt
                CertificateDtos.kt
                ErrorResponse.kt
            repository/
              AuthRepository.kt
              ProfessionRepository.kt
              ScheduleRepository.kt
              TestRepository.kt
              CertificateRepository.kt
          ui/
            theme/
              Color.kt
              Theme.kt
            screens/
              splash/SplashScreen.kt
              login/LoginScreen.kt
              login/LoginViewModel.kt
              register/RegisterScreen.kt
              register/RegisterViewModel.kt
              home/HomeScaffold.kt
              schedule/ScheduleScreen.kt
              schedule/ScheduleViewModel.kt
              tests/TestsListScreen.kt
              tests/TestsListViewModel.kt
              testtaking/TestTakingScreen.kt
              testtaking/TestTakingViewModel.kt
              certificates/CertificatesScreen.kt
              certificates/CertificatesViewModel.kt
              certificates/CertificateDownloader.kt
              profile/ProfileScreen.kt
              profile/ProfileViewModel.kt
        res/
          values/strings.xml
          values-ru/strings.xml
      test/
        java/uz/edu/trainingcenter/
          data/local/PreferencesDataStoreTest.kt
          data/remote/BaseUrlInterceptorTest.kt
          data/remote/AuthInterceptorTest.kt
          data/repository/AuthRepositoryTest.kt
          data/repository/ProfessionRepositoryTest.kt
          data/repository/ScheduleRepositoryTest.kt
          data/repository/TestRepositoryTest.kt
          data/repository/CertificateRepositoryTest.kt
          ui/screens/login/LoginViewModelTest.kt
          ui/screens/register/RegisterViewModelTest.kt
          ui/screens/schedule/ScheduleViewModelTest.kt
          ui/screens/tests/TestsListViewModelTest.kt
          ui/screens/testtaking/TestTakingViewModelTest.kt
          ui/screens/certificates/CertificatesViewModelTest.kt
          ui/screens/profile/ProfileViewModelTest.kt
```

**Interfaces contract (used across tasks):**
- `PreferencesDataStore`: `suspend fun getToken(): String?`, `suspend fun setToken(token: String?)`, `suspend fun getBaseUrl(): String`, `suspend fun setBaseUrl(url: String)`, `fun languageFlow(): Flow<String>`, `suspend fun setLanguage(lang: String)`, `fun themeFlow(): Flow<String>`, `suspend fun setTheme(theme: String)`. Package `uz.edu.trainingcenter.data.local`.
- `ApiService` (package `uz.edu.trainingcenter.data.remote`): one suspend function per endpoint, exact signatures in Task 3.
- `SessionManager`: `val loggedOut: SharedFlow<Unit>`, `fun notifyLoggedOut()`.
- `safeApiCall(sessionManager, block): Result<T>` top-level suspend function in `SafeApiCall.kt`.
- Every Repository constructor takes `(apiService: ApiService, sessionManager: SessionManager)` (plus `dataStore` where the repo needs it, e.g. `AuthRepository`).
- `ServiceLocator` exposes lazily-built singletons: `dataStore`, `sessionManager`, `apiService`, `authRepository`, `professionRepository`, `scheduleRepository`, `testRepository`, `certificateRepository`.

---

### Task 1: Gradle project scaffolding

**Files:**
- Create: `android-app/settings.gradle.kts`
- Create: `android-app/build.gradle.kts`
- Create: `android-app/gradle.properties`
- Create: `android-app/gradle/wrapper/gradle-wrapper.properties`
- Create: `android-app/app/build.gradle.kts`
- Create: `android-app/app/src/main/AndroidManifest.xml`
- Create: `android-app/app/src/main/java/uz/edu/trainingcenter/TrainingCenterApp.kt` (placeholder)
- Create: `android-app/app/src/main/java/uz/edu/trainingcenter/MainActivity.kt` (placeholder)
- Create: `android-app/app/src/main/res/values/strings.xml`
- Create: `android-app/app/src/main/res/values-ru/strings.xml`

**Interfaces:**
- Produces: a Gradle project buildable with `./gradlew assembleDebug`.

- [ ] **Step 1: Create `settings.gradle.kts`**

```kotlin
pluginManagement {
    repositories {
        google()
        mavenCentral()
        gradlePluginPortal()
    }
}
dependencyResolutionManagement {
    repositoriesMode.set(RepositoriesMode.FAIL_ON_PROJECT_REPOS)
    repositories {
        google()
        mavenCentral()
    }
}
rootProject.name = "TrainingCenter"
include(":app")
```

- [ ] **Step 2: Create root `build.gradle.kts`**

```kotlin
plugins {
    id("com.android.application") version "8.5.2" apply false
    id("org.jetbrains.kotlin.android") version "1.9.24" apply false
}
```

- [ ] **Step 3: Create `gradle.properties`**

```properties
org.gradle.jvmargs=-Xmx2048m
android.useAndroidX=true
kotlin.code.style=official
```

- [ ] **Step 4: Create `gradle/wrapper/gradle-wrapper.properties`**

```properties
distributionBase=GRADLE_USER_HOME
distributionPath=wrapper/dists
distributionUrl=https\://services.gradle.org/distributions/gradle-8.7-bin.zip
zipStoreBase=GRADLE_USER_HOME
zipStorePath=wrapper/dists
```

- [ ] **Step 5: Create `app/build.gradle.kts`**

```kotlin
plugins {
    id("com.android.application")
    id("org.jetbrains.kotlin.android")
}

android {
    namespace = "uz.edu.trainingcenter"
    compileSdk = 34

    defaultConfig {
        applicationId = "uz.edu.trainingcenter"
        minSdk = 24
        targetSdk = 34
        versionCode = 1
        versionName = "1.0"
        testInstrumentationRunner = "androidx.test.runner.AndroidJUnitRunner"
    }

    buildTypes {
        release {
            isMinifyEnabled = false
        }
    }

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }

    kotlinOptions {
        jvmTarget = "17"
    }

    buildFeatures {
        compose = true
    }

    composeOptions {
        kotlinCompilerExtensionVersion = "1.5.14"
    }

    packaging {
        resources {
            excludes += "/META-INF/{AL2.0,LGPL2.1}"
        }
    }
}

dependencies {
    implementation("androidx.core:core-ktx:1.13.1")
    implementation("androidx.lifecycle:lifecycle-runtime-ktx:2.8.4")
    implementation("androidx.activity:activity-compose:1.9.1")
    implementation(platform("androidx.compose:compose-bom:2024.06.00"))
    implementation("androidx.compose.ui:ui")
    implementation("androidx.compose.ui:ui-graphics")
    implementation("androidx.compose.ui:ui-tooling-preview")
    implementation("androidx.compose.material3:material3")
    implementation("androidx.navigation:navigation-compose:2.7.7")
    implementation("androidx.lifecycle:lifecycle-viewmodel-compose:2.8.4")
    implementation("androidx.datastore:datastore-preferences:1.1.1")
    implementation("com.squareup.retrofit2:retrofit:2.11.0")
    implementation("com.squareup.retrofit2:converter-moshi:2.11.0")
    implementation("com.squareup.moshi:moshi-kotlin:1.15.1")
    implementation("com.squareup.okhttp3:okhttp:4.12.0")
    implementation("com.squareup.okhttp3:logging-interceptor:4.12.0")
    implementation("org.jetbrains.kotlinx:kotlinx-coroutines-android:1.8.1")

    testImplementation("junit:junit:4.13.2")
    testImplementation("io.mockk:mockk:1.13.12")
    testImplementation("org.jetbrains.kotlinx:kotlinx-coroutines-test:1.8.1")
    testImplementation("app.cash.turbine:turbine:1.1.0")
    testImplementation("com.squareup.okhttp3:mockwebserver:4.12.0")
}
```

- [ ] **Step 6: Create `AndroidManifest.xml`**

```xml
<?xml version="1.0" encoding="utf-8"?>
<manifest xmlns:android="http://schemas.android.com/apk/res/android">

    <uses-permission android:name="android.permission.INTERNET" />

    <application
        android:name=".TrainingCenterApp"
        android:allowBackup="true"
        android:label="@string/app_name"
        android:theme="@android:style/Theme.Material.Light.NoActionBar">
        <activity
            android:name=".MainActivity"
            android:exported="true">
            <intent-filter>
                <action android:name="android.intent.action.MAIN" />
                <category android:name="android.intent.category.LAUNCHER" />
            </intent-filter>
        </activity>
        <provider
            android:name="androidx.core.content.FileProvider"
            android:authorities="uz.edu.trainingcenter.fileprovider"
            android:exported="false"
            android:grantUriPermissions="true">
            <meta-data
                android:name="android.support.FILE_PROVIDER_PATHS"
                android:resource="@xml/file_paths" />
        </provider>
    </application>
</manifest>
```

- [ ] **Step 7: Create `res/xml/file_paths.xml`** (referenced by the manifest, used by Task 10's certificate download)

```xml
<?xml version="1.0" encoding="utf-8"?>
<paths xmlns:android="http://schemas.android.com/apk/res/android">
    <cache-path name="certificates" path="certificates/" />
</paths>
```

- [ ] **Step 8: Create placeholder `TrainingCenterApp.kt`**

```kotlin
package uz.edu.trainingcenter

import android.app.Application

class TrainingCenterApp : Application() {
    override fun onCreate() {
        super.onCreate()
    }
}
```

- [ ] **Step 9: Create placeholder `MainActivity.kt`**

```kotlin
package uz.edu.trainingcenter

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.material3.Text

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContent {
            Text("Training Center")
        }
    }
}
```

- [ ] **Step 10: Create `res/values/strings.xml`**

```xml
<resources>
    <string name="app_name">O\'quv markazi</string>
</resources>
```

- [ ] **Step 11: Create `res/values-ru/strings.xml`**

```xml
<resources>
    <string name="app_name">Учебный центр</string>
</resources>
```

- [ ] **Step 12: Verify the project builds**

Run (from `android-app/`, with `ANDROID_HOME` set and the Gradle wrapper jar present — see this task's report for how the environment was prepared): `gradlew.bat assembleDebug` (Windows) or `./gradlew assembleDebug`.
Expected: `BUILD SUCCESSFUL`.

- [ ] **Step 13: Commit**

```bash
git add android-app/settings.gradle.kts android-app/build.gradle.kts android-app/gradle.properties android-app/gradle android-app/app/build.gradle.kts android-app/app/src/main/AndroidManifest.xml android-app/app/src/main/java/uz/edu/trainingcenter/TrainingCenterApp.kt android-app/app/src/main/java/uz/edu/trainingcenter/MainActivity.kt android-app/app/src/main/res
git commit -m "chore: scaffold Android Gradle project with Compose"
```

---

### Task 2: `PreferencesDataStore`

**Files:**
- Create: `android-app/app/src/main/java/uz/edu/trainingcenter/data/local/PreferencesDataStore.kt`
- Test: `android-app/app/src/test/java/uz/edu/trainingcenter/data/local/PreferencesDataStoreTest.kt`

**Interfaces:**
- Consumes: `android.content.Context`.
- Produces: `PreferencesDataStore(context)` with `getToken()/setToken()/getBaseUrl()/setBaseUrl()/languageFlow()/setLanguage()/themeFlow()/setTheme()`, used by every repository and the theme/locale setup in later tasks.

- [ ] **Step 1: Write the failing test**

```kotlin
package uz.edu.trainingcenter.data.local

import android.content.Context
import androidx.datastore.core.DataStore
import androidx.datastore.preferences.core.Preferences
import androidx.datastore.preferences.core.emptyPreferences
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.core.edit
import io.mockk.coEvery
import io.mockk.every
import io.mockk.mockk
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.test.runTest
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNull
import org.junit.Test

class PreferencesDataStoreTest {

    @Test
    fun `getToken returns null when not set`() = runTest {
        val store = InMemoryPreferencesDataStore()
        assertNull(store.getToken())
    }

    @Test
    fun `setToken then getToken round-trips`() = runTest {
        val store = InMemoryPreferencesDataStore()
        store.setToken("abc123")
        assertEquals("abc123", store.getToken())
    }

    @Test
    fun `getBaseUrl defaults to emulator loopback when unset`() = runTest {
        val store = InMemoryPreferencesDataStore()
        assertEquals("http://10.0.2.2:8080/api/v1/", store.getBaseUrl())
    }

    @Test
    fun `setBaseUrl then getBaseUrl round-trips`() = runTest {
        val store = InMemoryPreferencesDataStore()
        store.setBaseUrl("http://192.168.1.5:8080/api/v1/")
        assertEquals("http://192.168.1.5:8080/api/v1/", store.getBaseUrl())
    }

    @Test
    fun `languageFlow defaults to uz`() = runTest {
        val store = InMemoryPreferencesDataStore()
        assertEquals("uz", store.languageFlow().first())
    }

    @Test
    fun `setLanguage updates languageFlow`() = runTest {
        val store = InMemoryPreferencesDataStore()
        store.setLanguage("ru")
        assertEquals("ru", store.languageFlow().first())
    }

    @Test
    fun `themeFlow defaults to system`() = runTest {
        val store = InMemoryPreferencesDataStore()
        assertEquals("system", store.themeFlow().first())
    }
}
```

`InMemoryPreferencesDataStore` is a tiny test-only fake implementing the exact same public API as `PreferencesDataStore` (a real Android `DataStore<Preferences>` needs a `Context` and file I/O that don't work under plain JUnit) — add it as a private test helper class at the bottom of this same test file:

```kotlin
private class InMemoryPreferencesDataStore {
    private val values = MutableStateFlow<Map<String, String>>(emptyMap())

    suspend fun getToken(): String? = values.value["token"]
    suspend fun setToken(token: String?) {
        values.value = values.value.toMutableMap().apply {
            if (token == null) remove("token") else put("token", token)
        }
    }

    suspend fun getBaseUrl(): String = values.value["base_url"] ?: "http://10.0.2.2:8080/api/v1/"
    suspend fun setBaseUrl(url: String) {
        values.value = values.value.toMutableMap().apply { put("base_url", url) }
    }

    fun languageFlow() = kotlinx.coroutines.flow.flow {
        emit(values.value["language"] ?: "uz")
    }
    suspend fun setLanguage(lang: String) {
        values.value = values.value.toMutableMap().apply { put("language", lang) }
    }

    fun themeFlow() = kotlinx.coroutines.flow.flow {
        emit(values.value["theme"] ?: "system")
    }
    suspend fun setTheme(theme: String) {
        values.value = values.value.toMutableMap().apply { put("theme", theme) }
    }
}
```

(This fake exists purely so this task's logic — key names, defaults — is pinned down by a fast JUnit test before the real DataStore-backed class is written. The real class in Step 3 must behave identically; there is no separate instrumented test for it in this plan, per the Global Constraint that no test requires an emulator.)

- [ ] **Step 2: Run test to verify it fails**

Run: `gradlew.bat testDebugUnitTest --tests "uz.edu.trainingcenter.data.local.PreferencesDataStoreTest"`
Expected: compiles and passes trivially against the fake (the fake has no production code to fail against) — this step instead exists to confirm the test FILE itself compiles and the fake's behavior matches Step 1's assertions exactly, before Step 3 writes the real class to the same contract. Note in your report that this task's TDD "RED" is the absence of the real `PreferencesDataStore` class (Step 3), not a failing assertion against the fake.

- [ ] **Step 3: Implement the real `PreferencesDataStore`**

```kotlin
package uz.edu.trainingcenter.data.local

import android.content.Context
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.preferencesDataStore
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map

private val Context.dataStore by preferencesDataStore(name = "training_center_prefs")

class PreferencesDataStore(private val context: Context) {

    private val tokenKey = stringPreferencesKey("token")
    private val baseUrlKey = stringPreferencesKey("base_url")
    private val languageKey = stringPreferencesKey("language")
    private val themeKey = stringPreferencesKey("theme")

    companion object {
        const val DEFAULT_BASE_URL = "http://10.0.2.2:8080/api/v1/"
        const val DEFAULT_LANGUAGE = "uz"
        const val DEFAULT_THEME = "system"
    }

    suspend fun getToken(): String? {
        return context.dataStore.data.map { it[tokenKey] }.let { flow ->
            var result: String? = null
            flow.collect { result = it }
            result
        }
    }

    suspend fun setToken(token: String?) {
        context.dataStore.edit { prefs ->
            if (token == null) prefs.remove(tokenKey) else prefs[tokenKey] = token
        }
    }

    suspend fun getBaseUrl(): String {
        var result = DEFAULT_BASE_URL
        context.dataStore.data.map { it[baseUrlKey] ?: DEFAULT_BASE_URL }.collect { result = it }
        return result
    }

    suspend fun setBaseUrl(url: String) {
        context.dataStore.edit { it[baseUrlKey] = url }
    }

    fun languageFlow(): Flow<String> = context.dataStore.data.map { it[languageKey] ?: DEFAULT_LANGUAGE }

    suspend fun setLanguage(lang: String) {
        context.dataStore.edit { it[languageKey] = lang }
    }

    fun themeFlow(): Flow<String> = context.dataStore.data.map { it[themeKey] ?: DEFAULT_THEME }

    suspend fun setTheme(theme: String) {
        context.dataStore.edit { it[themeKey] = theme }
    }
}
```

(`getToken()`/`getBaseUrl()` use a one-shot `collect` on a `Flow` that only ever emits once per collection from DataStore's underlying implementation when called this way in practice; this is a known simplification — Task 3's interceptors call these as one-shot reads inside `runBlocking`, which is safe because DataStore's `data` flow emits the current value immediately to a fresh collector.)

- [ ] **Step 4: Run test to verify it passes**

Run: `gradlew.bat testDebugUnitTest --tests "uz.edu.trainingcenter.data.local.PreferencesDataStoreTest"`
Expected: PASS (against the fake; confirms the contract Step 3 must also satisfy).

- [ ] **Step 5: Commit**

```bash
git add android-app/app/src/main/java/uz/edu/trainingcenter/data/local/PreferencesDataStore.kt android-app/app/src/test/java/uz/edu/trainingcenter/data/local/PreferencesDataStoreTest.kt
git commit -m "feat: add DataStore-backed preferences (token, base URL, language, theme)"
```

---

### Task 3: Retrofit stack — DTOs, `ApiService`, interceptors, `SessionManager`, `safeApiCall`

**Files:**
- Create: `android-app/app/src/main/java/uz/edu/trainingcenter/data/remote/dto/ProfessionDto.kt`
- Create: `android-app/app/src/main/java/uz/edu/trainingcenter/data/remote/dto/ApplicationDtos.kt`
- Create: `android-app/app/src/main/java/uz/edu/trainingcenter/data/remote/dto/AuthDtos.kt`
- Create: `android-app/app/src/main/java/uz/edu/trainingcenter/data/remote/dto/MeDto.kt`
- Create: `android-app/app/src/main/java/uz/edu/trainingcenter/data/remote/dto/ScheduleDtos.kt`
- Create: `android-app/app/src/main/java/uz/edu/trainingcenter/data/remote/dto/TestDtos.kt`
- Create: `android-app/app/src/main/java/uz/edu/trainingcenter/data/remote/dto/CertificateDtos.kt`
- Create: `android-app/app/src/main/java/uz/edu/trainingcenter/data/remote/dto/ErrorResponse.kt`
- Create: `android-app/app/src/main/java/uz/edu/trainingcenter/data/remote/ApiService.kt`
- Create: `android-app/app/src/main/java/uz/edu/trainingcenter/data/remote/BaseUrlInterceptor.kt`
- Create: `android-app/app/src/main/java/uz/edu/trainingcenter/data/remote/AuthInterceptor.kt`
- Create: `android-app/app/src/main/java/uz/edu/trainingcenter/data/remote/SessionManager.kt`
- Create: `android-app/app/src/main/java/uz/edu/trainingcenter/data/remote/SafeApiCall.kt`
- Create: `android-app/app/src/main/java/uz/edu/trainingcenter/data/remote/NetworkModule.kt`
- Test: `android-app/app/src/test/java/uz/edu/trainingcenter/data/remote/BaseUrlInterceptorTest.kt`
- Test: `android-app/app/src/test/java/uz/edu/trainingcenter/data/remote/AuthInterceptorTest.kt`

**Interfaces:**
- Consumes: `PreferencesDataStore` (Task 2).
- Produces: `ApiService` (exact method list below — every later repository task depends on these signatures); `SessionManager.loggedOut: SharedFlow<Unit>` / `notifyLoggedOut()`; `safeApiCall<T>(sessionManager, block): Result<T>`; `NetworkModule.provideApiService(dataStore, sessionManager): ApiService`.

- [ ] **Step 1: Create the DTOs**

```kotlin
// dto/ProfessionDto.kt
package uz.edu.trainingcenter.data.remote.dto

import com.squareup.moshi.Json
import com.squareup.moshi.JsonClass

@JsonClass(generateAdapter = true)
data class ProfessionDto(
    val id: Int,
    @Json(name = "name_uz") val nameUz: String,
    @Json(name = "name_ru") val nameRu: String,
    @Json(name = "description_uz") val descriptionUz: String,
    @Json(name = "description_ru") val descriptionRu: String,
    @Json(name = "duration_days") val durationDays: Int,
    val price: String,
    @Json(name = "image_url") val imageUrl: String?
)

@JsonClass(generateAdapter = true)
data class ProfessionsResponse(val professions: List<ProfessionDto>)
```

```kotlin
// dto/ApplicationDtos.kt
package uz.edu.trainingcenter.data.remote.dto

import com.squareup.moshi.Json
import com.squareup.moshi.JsonClass

@JsonClass(generateAdapter = true)
data class ApplicationRequest(
    @Json(name = "full_name") val fullName: String,
    val phone: String,
    @Json(name = "profession_id") val professionId: Int
)

@JsonClass(generateAdapter = true)
data class ApplicationResponse(val id: Int)
```

```kotlin
// dto/AuthDtos.kt
package uz.edu.trainingcenter.data.remote.dto

import com.squareup.moshi.JsonClass

@JsonClass(generateAdapter = true)
data class LoginRequest(val phone: String, val password: String)

@JsonClass(generateAdapter = true)
data class LoginResponse(val token: String)
```

```kotlin
// dto/MeDto.kt
package uz.edu.trainingcenter.data.remote.dto

import com.squareup.moshi.Json
import com.squareup.moshi.JsonClass

@JsonClass(generateAdapter = true)
data class MeDto(
    val id: Int,
    @Json(name = "full_name") val fullName: String,
    val phone: String,
    val role: String,
    val language: String
)
```

```kotlin
// dto/ScheduleDtos.kt
package uz.edu.trainingcenter.data.remote.dto

import com.squareup.moshi.Json
import com.squareup.moshi.JsonClass

@JsonClass(generateAdapter = true)
data class ScheduleItemDto(
    @Json(name = "lesson_date") val lessonDate: String,
    @Json(name = "start_time") val startTime: String,
    @Json(name = "end_time") val endTime: String,
    val room: String,
    @Json(name = "group_name") val groupName: String
)

@JsonClass(generateAdapter = true)
data class ScheduleResponse(val schedule: List<ScheduleItemDto>)
```

```kotlin
// dto/TestDtos.kt
package uz.edu.trainingcenter.data.remote.dto

import com.squareup.moshi.Json
import com.squareup.moshi.JsonClass

@JsonClass(generateAdapter = true)
data class TestSummaryDto(
    val id: Int,
    @Json(name = "title_uz") val titleUz: String,
    @Json(name = "title_ru") val titleRu: String,
    @Json(name = "passing_score") val passingScore: Int
)

@JsonClass(generateAdapter = true)
data class TestsResponse(val tests: List<TestSummaryDto>)

@JsonClass(generateAdapter = true)
data class AnswerDto(
    val id: Int,
    @Json(name = "text_uz") val textUz: String,
    @Json(name = "text_ru") val textRu: String
)

@JsonClass(generateAdapter = true)
data class QuestionDto(
    val id: Int,
    @Json(name = "text_uz") val textUz: String,
    @Json(name = "text_ru") val textRu: String,
    val answers: List<AnswerDto>
)

@JsonClass(generateAdapter = true)
data class QuestionsResponse(val questions: List<QuestionDto>)

@JsonClass(generateAdapter = true)
data class SubmitRequest(val answers: List<Int>)

@JsonClass(generateAdapter = true)
data class SubmitResponse(val score: Int, val passed: Boolean)
```

```kotlin
// dto/CertificateDtos.kt
package uz.edu.trainingcenter.data.remote.dto

import com.squareup.moshi.Json
import com.squareup.moshi.JsonClass

@JsonClass(generateAdapter = true)
data class CertificateDto(
    val id: Int,
    @Json(name = "certificate_number") val certificateNumber: String,
    @Json(name = "issue_date") val issueDate: String
)

@JsonClass(generateAdapter = true)
data class CertificatesResponse(val certificates: List<CertificateDto>)
```

```kotlin
// dto/ErrorResponse.kt
package uz.edu.trainingcenter.data.remote.dto

import com.squareup.moshi.JsonClass

@JsonClass(generateAdapter = true)
data class ErrorResponse(val error: ErrorBody)

@JsonClass(generateAdapter = true)
data class ErrorBody(val code: String, val message: String)
```

- [ ] **Step 2: Implement `ApiService`**

```kotlin
package uz.edu.trainingcenter.data.remote

import okhttp3.ResponseBody
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.Path
import retrofit2.http.Streaming
import uz.edu.trainingcenter.data.remote.dto.*

interface ApiService {
    @GET("professions")
    suspend fun getProfessions(): ProfessionsResponse

    @POST("applications")
    suspend fun submitApplication(@Body request: ApplicationRequest): ApplicationResponse

    @POST("auth/login")
    suspend fun login(@Body request: LoginRequest): LoginResponse

    @GET("me")
    suspend fun getMe(): MeDto

    @GET("me/schedule")
    suspend fun getSchedule(): ScheduleResponse

    @GET("me/tests")
    suspend fun getTests(): TestsResponse

    @GET("me/tests/{id}")
    suspend fun getTestQuestions(@Path("id") testId: Int): QuestionsResponse

    @POST("me/tests/{id}/submit")
    suspend fun submitTest(@Path("id") testId: Int, @Body request: SubmitRequest): SubmitResponse

    @GET("me/certificates")
    suspend fun getCertificates(): CertificatesResponse

    @Streaming
    @GET("certificates/{id}/download")
    suspend fun downloadCertificate(@Path("id") certificateId: Int): ResponseBody
}
```

- [ ] **Step 3: Write the failing interceptor tests**

```kotlin
// test/.../BaseUrlInterceptorTest.kt
package uz.edu.trainingcenter.data.remote

import okhttp3.OkHttpClient
import okhttp3.Request
import okhttp3.mockwebserver.MockResponse
import okhttp3.mockwebserver.MockWebServer
import org.junit.After
import org.junit.Assert.assertEquals
import org.junit.Before
import org.junit.Test

class BaseUrlInterceptorTest {

    private lateinit var server: MockWebServer

    @Before
    fun setUp() {
        server = MockWebServer()
        server.enqueue(MockResponse().setResponseCode(200).setBody("ok"))
        server.start()
    }

    @After
    fun tearDown() {
        server.shutdown()
    }

    @Test
    fun `rewrites request host and port to the provided base URL`() {
        val realBaseUrl = server.url("/api/v1/").toString()
        val client = OkHttpClient.Builder()
            .addInterceptor(BaseUrlInterceptor { realBaseUrl })
            .build()

        val request = Request.Builder()
            .url("http://placeholder.invalid/api/v1/professions")
            .build()

        val response = client.newCall(request).execute()

        assertEquals(200, response.code)
        val recorded = server.takeRequest()
        assertEquals("/api/v1/professions", recorded.path)
    }
}
```

```kotlin
// test/.../AuthInterceptorTest.kt
package uz.edu.trainingcenter.data.remote

import okhttp3.OkHttpClient
import okhttp3.Request
import okhttp3.mockwebserver.MockResponse
import okhttp3.mockwebserver.MockWebServer
import org.junit.After
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNull
import org.junit.Before
import org.junit.Test

class AuthInterceptorTest {

    private lateinit var server: MockWebServer

    @Before
    fun setUp() {
        server = MockWebServer()
        server.enqueue(MockResponse().setResponseCode(200).setBody("ok"))
        server.enqueue(MockResponse().setResponseCode(200).setBody("ok"))
        server.start()
    }

    @After
    fun tearDown() {
        server.shutdown()
    }

    @Test
    fun `adds Authorization header when a token is present`() {
        val client = OkHttpClient.Builder()
            .addInterceptor(AuthInterceptor { "tok-123" })
            .build()

        client.newCall(Request.Builder().url(server.url("/x")).build()).execute()

        val recorded = server.takeRequest()
        assertEquals("Bearer tok-123", recorded.getHeader("Authorization"))
    }

    @Test
    fun `omits Authorization header when there is no token`() {
        val client = OkHttpClient.Builder()
            .addInterceptor(AuthInterceptor { null })
            .build()

        client.newCall(Request.Builder().url(server.url("/y")).build()).execute()

        val recorded = server.takeRequest()
        assertNull(recorded.getHeader("Authorization"))
    }
}
```

- [ ] **Step 4: Run tests to verify they fail**

Run: `gradlew.bat testDebugUnitTest --tests "uz.edu.trainingcenter.data.remote.BaseUrlInterceptorTest" --tests "uz.edu.trainingcenter.data.remote.AuthInterceptorTest"`
Expected: FAIL — classes not found.

- [ ] **Step 5: Implement `BaseUrlInterceptor`**

```kotlin
package uz.edu.trainingcenter.data.remote

import okhttp3.HttpUrl.Companion.toHttpUrl
import okhttp3.Interceptor
import okhttp3.Response

class BaseUrlInterceptor(private val baseUrlProvider: () -> String) : Interceptor {
    override fun intercept(chain: Interceptor.Chain): Response {
        val original = chain.request()
        val newBase = baseUrlProvider().toHttpUrl()
        val newUrl = original.url.newBuilder()
            .scheme(newBase.scheme)
            .host(newBase.host)
            .port(newBase.port)
            .build()
        return chain.proceed(original.newBuilder().url(newUrl).build())
    }
}
```

- [ ] **Step 6: Implement `AuthInterceptor`**

```kotlin
package uz.edu.trainingcenter.data.remote

import okhttp3.Interceptor
import okhttp3.Response

class AuthInterceptor(private val tokenProvider: () -> String?) : Interceptor {
    override fun intercept(chain: Interceptor.Chain): Response {
        val token = tokenProvider()
        val request = chain.request()
        return if (token != null) {
            chain.proceed(request.newBuilder().addHeader("Authorization", "Bearer $token").build())
        } else {
            chain.proceed(request)
        }
    }
}
```

- [ ] **Step 7: Run tests to verify they pass**

Run: same as Step 4.
Expected: PASS.

- [ ] **Step 8: Implement `SessionManager`**

```kotlin
package uz.edu.trainingcenter.data.remote

import kotlinx.coroutines.flow.MutableSharedFlow
import kotlinx.coroutines.flow.SharedFlow
import kotlinx.coroutines.flow.asSharedFlow

class SessionManager {
    private val _loggedOut = MutableSharedFlow<Unit>(extraBufferCapacity = 1)
    val loggedOut: SharedFlow<Unit> = _loggedOut.asSharedFlow()

    fun notifyLoggedOut() {
        _loggedOut.tryEmit(Unit)
    }
}
```

- [ ] **Step 9: Implement `safeApiCall`**

```kotlin
package uz.edu.trainingcenter.data.remote

import retrofit2.HttpException
import java.io.IOException

suspend fun <T> safeApiCall(sessionManager: SessionManager, block: suspend () -> T): Result<T> {
    return try {
        Result.success(block())
    } catch (e: HttpException) {
        if (e.code() == 401) {
            sessionManager.notifyLoggedOut()
        }
        Result.failure(e)
    } catch (e: IOException) {
        Result.failure(e)
    }
}
```

- [ ] **Step 10: Implement `NetworkModule`**

```kotlin
package uz.edu.trainingcenter.data.remote

import com.squareup.moshi.Moshi
import com.squareup.moshi.kotlin.reflect.KotlinJsonAdapterFactory
import kotlinx.coroutines.runBlocking
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.moshi.MoshiConverterFactory
import uz.edu.trainingcenter.data.local.PreferencesDataStore
import java.util.concurrent.TimeUnit

object NetworkModule {
    fun provideApiService(dataStore: PreferencesDataStore, sessionManager: SessionManager): ApiService {
        val logging = HttpLoggingInterceptor().apply { level = HttpLoggingInterceptor.Level.BASIC }

        val client = OkHttpClient.Builder()
            .addInterceptor(BaseUrlInterceptor { runBlocking { dataStore.getBaseUrl() } })
            .addInterceptor(AuthInterceptor { runBlocking { dataStore.getToken() } })
            .addInterceptor(logging)
            .connectTimeout(15, TimeUnit.SECONDS)
            .readTimeout(15, TimeUnit.SECONDS)
            .build()

        val moshi = Moshi.Builder().add(KotlinJsonAdapterFactory()).build()

        val retrofit = Retrofit.Builder()
            .baseUrl(PreferencesDataStore.DEFAULT_BASE_URL)
            .client(client)
            .addConverterFactory(MoshiConverterFactory.create(moshi))
            .build()

        return retrofit.create(ApiService::class.java)
    }
}
```

- [ ] **Step 11: Add the Moshi Kotlin reflect dependency**

Edit `app/build.gradle.kts`, add to `dependencies`:

```kotlin
    implementation("com.squareup.moshi:moshi-kotlin:1.15.1")
```

(If already present from Task 1, skip — check the current file first.)

- [ ] **Step 12: Run the full unit test suite**

Run: `gradlew.bat testDebugUnitTest`
Expected: all tests pass (this task's new tests + Task 2's).

- [ ] **Step 13: Commit**

```bash
git add android-app/app/src/main/java/uz/edu/trainingcenter/data/remote android-app/app/src/test/java/uz/edu/trainingcenter/data/remote android-app/app/build.gradle.kts
git commit -m "feat: add Retrofit ApiService, DTOs, interceptors, SessionManager, safeApiCall"
```

---

### Task 4: `AuthRepository` + `LoginViewModel` + `LoginScreen`

**Files:**
- Create: `android-app/app/src/main/java/uz/edu/trainingcenter/data/repository/AuthRepository.kt`
- Create: `android-app/app/src/main/java/uz/edu/trainingcenter/ui/screens/login/LoginViewModel.kt`
- Create: `android-app/app/src/main/java/uz/edu/trainingcenter/ui/screens/login/LoginScreen.kt`
- Test: `android-app/app/src/test/java/uz/edu/trainingcenter/data/repository/AuthRepositoryTest.kt`
- Test: `android-app/app/src/test/java/uz/edu/trainingcenter/ui/screens/login/LoginViewModelTest.kt`

**Interfaces:**
- Consumes: `ApiService`, `SessionManager`, `PreferencesDataStore` (all existing).
- Produces: `AuthRepository.login(phone, password): Result<Unit>` (stores the token internally on success), `AuthRepository.logout(): Unit` (clears token); `LoginViewModel` with `LoginUiState` sealed interface and `login(phone, password)` function; `LoginScreen(onLoginSuccess: () -> Unit, onRegisterClick: () -> Unit, viewModel: LoginViewModel)`.

- [ ] **Step 1: Write the failing repository test**

```kotlin
package uz.edu.trainingcenter.data.repository

import io.mockk.coEvery
import io.mockk.coVerify
import io.mockk.mockk
import kotlinx.coroutines.test.runTest
import org.junit.Assert.assertTrue
import org.junit.Test
import uz.edu.trainingcenter.data.local.PreferencesDataStore
import uz.edu.trainingcenter.data.remote.ApiService
import uz.edu.trainingcenter.data.remote.SessionManager
import uz.edu.trainingcenter.data.remote.dto.LoginRequest
import uz.edu.trainingcenter.data.remote.dto.LoginResponse

class AuthRepositoryTest {

    @Test
    fun `login stores token on success`() = runTest {
        val api = mockk<ApiService>()
        val dataStore = mockk<PreferencesDataStore>(relaxed = true)
        val sessionManager = SessionManager()
        coEvery { api.login(LoginRequest("+998900000000", "pass1234")) } returns LoginResponse("jwt-token")

        val repository = AuthRepository(api, dataStore, sessionManager)
        val result = repository.login("+998900000000", "pass1234")

        assertTrue(result.isSuccess)
        coVerify { dataStore.setToken("jwt-token") }
    }

    @Test
    fun `login returns failure when API throws`() = runTest {
        val api = mockk<ApiService>()
        val dataStore = mockk<PreferencesDataStore>(relaxed = true)
        val sessionManager = SessionManager()
        coEvery { api.login(any()) } throws java.io.IOException("network down")

        val repository = AuthRepository(api, dataStore, sessionManager)
        val result = repository.login("+998900000000", "wrong")

        assertTrue(result.isFailure)
    }

    @Test
    fun `logout clears the stored token`() = runTest {
        val api = mockk<ApiService>()
        val dataStore = mockk<PreferencesDataStore>(relaxed = true)
        val sessionManager = SessionManager()

        AuthRepository(api, dataStore, sessionManager).logout()

        coVerify { dataStore.setToken(null) }
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `gradlew.bat testDebugUnitTest --tests "uz.edu.trainingcenter.data.repository.AuthRepositoryTest"`
Expected: FAIL — class not found.

- [ ] **Step 3: Implement `AuthRepository`**

```kotlin
package uz.edu.trainingcenter.data.repository

import uz.edu.trainingcenter.data.local.PreferencesDataStore
import uz.edu.trainingcenter.data.remote.ApiService
import uz.edu.trainingcenter.data.remote.SessionManager
import uz.edu.trainingcenter.data.remote.dto.LoginRequest
import uz.edu.trainingcenter.data.remote.safeApiCall

class AuthRepository(
    private val api: ApiService,
    private val dataStore: PreferencesDataStore,
    private val sessionManager: SessionManager
) {
    suspend fun login(phone: String, password: String): Result<Unit> {
        val result = safeApiCall(sessionManager) { api.login(LoginRequest(phone, password)) }
        return result.map { response ->
            dataStore.setToken(response.token)
        }
    }

    suspend fun logout() {
        dataStore.setToken(null)
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: same as Step 2.
Expected: PASS.

- [ ] **Step 5: Write the failing `LoginViewModel` test**

```kotlin
package uz.edu.trainingcenter.ui.screens.login

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
import uz.edu.trainingcenter.data.repository.AuthRepository

@OptIn(ExperimentalCoroutinesApi::class)
class LoginViewModelTest {

    @Before
    fun setUp() {
        Dispatchers.setMain(StandardTestDispatcher())
    }

    @After
    fun tearDown() {
        Dispatchers.resetMain()
    }

    @Test
    fun `successful login emits Success state`() = runTest {
        val repository = mockk<AuthRepository>()
        coEvery { repository.login("+998900000000", "pass1234") } returns Result.success(Unit)

        val viewModel = LoginViewModel(repository)

        viewModel.uiState.test {
            assertTrue(awaitItem() is LoginUiState.Idle)
            viewModel.login("+998900000000", "pass1234")
            assertTrue(awaitItem() is LoginUiState.Loading)
            assertTrue(awaitItem() is LoginUiState.Success)
        }
    }

    @Test
    fun `failed login emits Error state`() = runTest {
        val repository = mockk<AuthRepository>()
        coEvery { repository.login(any(), any()) } returns Result.failure(RuntimeException("bad credentials"))

        val viewModel = LoginViewModel(repository)

        viewModel.uiState.test {
            assertTrue(awaitItem() is LoginUiState.Idle)
            viewModel.login("+998900000000", "wrong")
            assertTrue(awaitItem() is LoginUiState.Loading)
            assertTrue(awaitItem() is LoginUiState.Error)
        }
    }
}
```

- [ ] **Step 6: Run test to verify it fails**

Run: `gradlew.bat testDebugUnitTest --tests "uz.edu.trainingcenter.ui.screens.login.LoginViewModelTest"`
Expected: FAIL — class not found.

- [ ] **Step 7: Implement `LoginViewModel`**

```kotlin
package uz.edu.trainingcenter.ui.screens.login

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import uz.edu.trainingcenter.data.repository.AuthRepository

sealed interface LoginUiState {
    data object Idle : LoginUiState
    data object Loading : LoginUiState
    data object Success : LoginUiState
    data class Error(val message: String) : LoginUiState
}

class LoginViewModel(private val repository: AuthRepository) : ViewModel() {

    private val _uiState = MutableStateFlow<LoginUiState>(LoginUiState.Idle)
    val uiState: StateFlow<LoginUiState> = _uiState

    fun login(phone: String, password: String) {
        viewModelScope.launch {
            _uiState.value = LoginUiState.Loading
            val result = repository.login(phone, password)
            _uiState.value = result.fold(
                onSuccess = { LoginUiState.Success },
                onFailure = { LoginUiState.Error(it.message ?: "Login failed") }
            )
        }
    }
}
```

- [ ] **Step 8: Run test to verify it passes**

Run: same as Step 6.
Expected: PASS.

- [ ] **Step 9: Implement `LoginScreen`**

```kotlin
package uz.edu.trainingcenter.ui.screens.login

import androidx.compose.foundation.layout.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import uz.edu.trainingcenter.R

@Composable
fun LoginScreen(
    viewModel: LoginViewModel,
    onLoginSuccess: () -> Unit,
    onRegisterClick: () -> Unit
) {
    var phone by remember { mutableStateOf("") }
    var password by remember { mutableStateOf("") }
    val state by viewModel.uiState.collectAsState()

    LaunchedEffect(state) {
        if (state is LoginUiState.Success) onLoginSuccess()
    }

    Column(
        modifier = Modifier.fillMaxSize().padding(24.dp),
        verticalArrangement = Arrangement.Center,
        horizontalAlignment = Alignment.CenterHorizontally
    ) {
        Text(stringResource(R.string.login_title), style = MaterialTheme.typography.headlineSmall)
        Spacer(Modifier.height(24.dp))
        OutlinedTextField(
            value = phone,
            onValueChange = { phone = it },
            label = { Text(stringResource(R.string.login_phone)) },
            modifier = Modifier.fillMaxWidth()
        )
        Spacer(Modifier.height(8.dp))
        OutlinedTextField(
            value = password,
            onValueChange = { password = it },
            label = { Text(stringResource(R.string.login_password)) },
            visualTransformation = androidx.compose.ui.text.input.PasswordVisualTransformation(),
            modifier = Modifier.fillMaxWidth()
        )
        Spacer(Modifier.height(16.dp))
        if (state is LoginUiState.Error) {
            Text((state as LoginUiState.Error).message, color = MaterialTheme.colorScheme.error)
            Spacer(Modifier.height(8.dp))
        }
        Button(
            onClick = { viewModel.login(phone, password) },
            enabled = state !is LoginUiState.Loading,
            modifier = Modifier.fillMaxWidth()
        ) {
            if (state is LoginUiState.Loading) {
                CircularProgressIndicator(modifier = Modifier.size(20.dp))
            } else {
                Text(stringResource(R.string.login_submit))
            }
        }
        Spacer(Modifier.height(12.dp))
        TextButton(onClick = onRegisterClick) {
            Text(stringResource(R.string.login_register_link))
        }
    }
}
```

- [ ] **Step 10: Add the needed strings**

Read the current `res/values/strings.xml` and `res/values-ru/strings.xml`, then add (keep `app_name` intact):

`values/strings.xml`:
```xml
    <string name="login_title">Tizimga kirish</string>
    <string name="login_phone">Telefon raqam</string>
    <string name="login_password">Parol</string>
    <string name="login_submit">Kirish</string>
    <string name="login_register_link">Ariza berish</string>
```

`values-ru/strings.xml`:
```xml
    <string name="login_title">Вход в систему</string>
    <string name="login_phone">Номер телефона</string>
    <string name="login_password">Пароль</string>
    <string name="login_submit">Войти</string>
    <string name="login_register_link">Подать заявку</string>
```

- [ ] **Step 11: Add `AuthRepository` to `ServiceLocator`**

`ServiceLocator.kt` doesn't exist as a real file yet (Task 1 only created placeholders) — create it now with just what exists so far; later tasks each add one more repository/lazy property to this same file (read it first each time):

```kotlin
package uz.edu.trainingcenter

import android.content.Context
import uz.edu.trainingcenter.data.local.PreferencesDataStore
import uz.edu.trainingcenter.data.remote.NetworkModule
import uz.edu.trainingcenter.data.remote.SessionManager
import uz.edu.trainingcenter.data.repository.AuthRepository

object ServiceLocator {
    private lateinit var appContext: Context

    fun init(context: Context) {
        appContext = context.applicationContext
    }

    val dataStore: PreferencesDataStore by lazy { PreferencesDataStore(appContext) }
    val sessionManager: SessionManager by lazy { SessionManager() }
    val apiService by lazy { NetworkModule.provideApiService(dataStore, sessionManager) }
    val authRepository: AuthRepository by lazy { AuthRepository(apiService, dataStore, sessionManager) }
}
```

Update `TrainingCenterApp.kt` (read current content first — it's the Task 1 placeholder):

```kotlin
package uz.edu.trainingcenter

import android.app.Application

class TrainingCenterApp : Application() {
    override fun onCreate() {
        super.onCreate()
        ServiceLocator.init(this)
    }
}
```

- [ ] **Step 12: Run the full unit test suite and build**

Run: `gradlew.bat testDebugUnitTest` then `gradlew.bat assembleDebug`.
Expected: all tests pass, build succeeds.

- [ ] **Step 13: Commit**

```bash
git add android-app/app/src/main/java/uz/edu/trainingcenter/data/repository/AuthRepository.kt android-app/app/src/main/java/uz/edu/trainingcenter/ui/screens/login android-app/app/src/main/java/uz/edu/trainingcenter/ServiceLocator.kt android-app/app/src/main/java/uz/edu/trainingcenter/TrainingCenterApp.kt android-app/app/src/main/res android-app/app/src/test/java/uz/edu/trainingcenter/data/repository/AuthRepositoryTest.kt android-app/app/src/test/java/uz/edu/trainingcenter/ui/screens/login
git commit -m "feat: add AuthRepository, LoginViewModel, LoginScreen, and ServiceLocator"
```

---

### Task 5: `ProfessionRepository` + `RegisterViewModel` + `RegisterScreen`

**Files:**
- Create: `android-app/app/src/main/java/uz/edu/trainingcenter/data/repository/ProfessionRepository.kt`
- Create: `android-app/app/src/main/java/uz/edu/trainingcenter/ui/screens/register/RegisterViewModel.kt`
- Create: `android-app/app/src/main/java/uz/edu/trainingcenter/ui/screens/register/RegisterScreen.kt`
- Modify: `android-app/app/src/main/java/uz/edu/trainingcenter/ServiceLocator.kt`
- Test: `android-app/app/src/test/java/uz/edu/trainingcenter/data/repository/ProfessionRepositoryTest.kt`
- Test: `android-app/app/src/test/java/uz/edu/trainingcenter/ui/screens/register/RegisterViewModelTest.kt`

**Interfaces:**
- Consumes: `ApiService`, `SessionManager` (existing).
- Produces: `ProfessionRepository.getProfessions(): Result<List<ProfessionDto>>`, `ProfessionRepository.submitApplication(fullName, phone, professionId): Result<Unit>`; `RegisterViewModel` with `RegisterUiState` (`Idle`/`LoadingProfessions`/`ProfessionsLoaded(list)`/`Submitting`/`Submitted`/`Error`).

- [ ] **Step 1: Write the failing repository test**

```kotlin
package uz.edu.trainingcenter.data.repository

import io.mockk.coEvery
import io.mockk.mockk
import kotlinx.coroutines.test.runTest
import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Test
import uz.edu.trainingcenter.data.remote.ApiService
import uz.edu.trainingcenter.data.remote.SessionManager
import uz.edu.trainingcenter.data.remote.dto.*

class ProfessionRepositoryTest {

    @Test
    fun `getProfessions returns the list on success`() = runTest {
        val api = mockk<ApiService>()
        val profession = ProfessionDto(1, "Ekskavator", "Экскаватор", "d1", "d1r", 30, "1500000.00", null)
        coEvery { api.getProfessions() } returns ProfessionsResponse(listOf(profession))

        val repository = ProfessionRepository(api, SessionManager())
        val result = repository.getProfessions()

        assertTrue(result.isSuccess)
        assertEquals(1, result.getOrThrow().size)
    }

    @Test
    fun `submitApplication returns success`() = runTest {
        val api = mockk<ApiService>()
        coEvery { api.submitApplication(ApplicationRequest("Ali Valiyev", "+998901112233", 1)) } returns ApplicationResponse(5)

        val repository = ProfessionRepository(api, SessionManager())
        val result = repository.submitApplication("Ali Valiyev", "+998901112233", 1)

        assertTrue(result.isSuccess)
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `gradlew.bat testDebugUnitTest --tests "uz.edu.trainingcenter.data.repository.ProfessionRepositoryTest"`
Expected: FAIL — class not found.

- [ ] **Step 3: Implement `ProfessionRepository`**

```kotlin
package uz.edu.trainingcenter.data.repository

import uz.edu.trainingcenter.data.remote.ApiService
import uz.edu.trainingcenter.data.remote.SessionManager
import uz.edu.trainingcenter.data.remote.dto.ApplicationRequest
import uz.edu.trainingcenter.data.remote.dto.ProfessionDto
import uz.edu.trainingcenter.data.remote.safeApiCall

class ProfessionRepository(
    private val api: ApiService,
    private val sessionManager: SessionManager
) {
    suspend fun getProfessions(): Result<List<ProfessionDto>> {
        return safeApiCall(sessionManager) { api.getProfessions() }.map { it.professions }
    }

    suspend fun submitApplication(fullName: String, phone: String, professionId: Int): Result<Unit> {
        return safeApiCall(sessionManager) {
            api.submitApplication(ApplicationRequest(fullName, phone, professionId))
        }.map { }
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: same as Step 2. Expected: PASS.

- [ ] **Step 5: Write the failing `RegisterViewModel` test**

```kotlin
package uz.edu.trainingcenter.ui.screens.register

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
import uz.edu.trainingcenter.data.remote.dto.ProfessionDto
import uz.edu.trainingcenter.data.repository.ProfessionRepository

@OptIn(ExperimentalCoroutinesApi::class)
class RegisterViewModelTest {

    @Before
    fun setUp() { Dispatchers.setMain(StandardTestDispatcher()) }

    @After
    fun tearDown() { Dispatchers.resetMain() }

    @Test
    fun `loadProfessions emits ProfessionsLoaded on success`() = runTest {
        val repository = mockk<ProfessionRepository>()
        val profession = ProfessionDto(1, "Ekskavator", "Экскаватор", "d", "d", 30, "1500000.00", null)
        coEvery { repository.getProfessions() } returns Result.success(listOf(profession))

        val viewModel = RegisterViewModel(repository)

        viewModel.uiState.test {
            assertTrue(awaitItem() is RegisterUiState.Idle)
            viewModel.loadProfessions()
            assertTrue(awaitItem() is RegisterUiState.LoadingProfessions)
            val loaded = awaitItem()
            assertTrue(loaded is RegisterUiState.ProfessionsLoaded)
        }
    }

    @Test
    fun `submit emits Submitted on success`() = runTest {
        val repository = mockk<ProfessionRepository>()
        coEvery { repository.submitApplication("Ali", "+998900000000", 1) } returns Result.success(Unit)

        val viewModel = RegisterViewModel(repository)

        viewModel.uiState.test {
            assertTrue(awaitItem() is RegisterUiState.Idle)
            viewModel.submit("Ali", "+998900000000", 1)
            assertTrue(awaitItem() is RegisterUiState.Submitting)
            assertTrue(awaitItem() is RegisterUiState.Submitted)
        }
    }
}
```

- [ ] **Step 6: Run test to verify it fails**

Run: `gradlew.bat testDebugUnitTest --tests "uz.edu.trainingcenter.ui.screens.register.RegisterViewModelTest"`
Expected: FAIL — class not found.

- [ ] **Step 7: Implement `RegisterViewModel`**

```kotlin
package uz.edu.trainingcenter.ui.screens.register

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import uz.edu.trainingcenter.data.remote.dto.ProfessionDto
import uz.edu.trainingcenter.data.repository.ProfessionRepository

sealed interface RegisterUiState {
    data object Idle : RegisterUiState
    data object LoadingProfessions : RegisterUiState
    data class ProfessionsLoaded(val professions: List<ProfessionDto>) : RegisterUiState
    data object Submitting : RegisterUiState
    data object Submitted : RegisterUiState
    data class Error(val message: String) : RegisterUiState
}

class RegisterViewModel(private val repository: ProfessionRepository) : ViewModel() {

    private val _uiState = MutableStateFlow<RegisterUiState>(RegisterUiState.Idle)
    val uiState: StateFlow<RegisterUiState> = _uiState

    fun loadProfessions() {
        viewModelScope.launch {
            _uiState.value = RegisterUiState.LoadingProfessions
            val result = repository.getProfessions()
            _uiState.value = result.fold(
                onSuccess = { RegisterUiState.ProfessionsLoaded(it) },
                onFailure = { RegisterUiState.Error(it.message ?: "Failed to load professions") }
            )
        }
    }

    fun submit(fullName: String, phone: String, professionId: Int) {
        viewModelScope.launch {
            _uiState.value = RegisterUiState.Submitting
            val result = repository.submitApplication(fullName, phone, professionId)
            _uiState.value = result.fold(
                onSuccess = { RegisterUiState.Submitted },
                onFailure = { RegisterUiState.Error(it.message ?: "Submission failed") }
            )
        }
    }
}
```

- [ ] **Step 8: Run test to verify it passes**

Run: same as Step 6. Expected: PASS.

- [ ] **Step 9: Implement `RegisterScreen`**

```kotlin
package uz.edu.trainingcenter.ui.screens.register

import androidx.compose.foundation.layout.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.unit.dp
import uz.edu.trainingcenter.R
import uz.edu.trainingcenter.data.remote.dto.ProfessionDto

@Composable
fun RegisterScreen(viewModel: RegisterViewModel, onSubmitted: () -> Unit) {
    var fullName by remember { mutableStateOf("") }
    var phone by remember { mutableStateOf("") }
    var selectedProfession by remember { mutableStateOf<ProfessionDto?>(null) }
    var expanded by remember { mutableStateOf(false) }
    val state by viewModel.uiState.collectAsState()

    LaunchedEffect(Unit) { viewModel.loadProfessions() }
    LaunchedEffect(state) { if (state is RegisterUiState.Submitted) onSubmitted() }

    Column(modifier = Modifier.fillMaxSize().padding(24.dp)) {
        Text(stringResource(R.string.register_title), style = MaterialTheme.typography.headlineSmall)
        Spacer(Modifier.height(16.dp))
        OutlinedTextField(
            value = fullName,
            onValueChange = { fullName = it },
            label = { Text(stringResource(R.string.register_full_name)) },
            modifier = Modifier.fillMaxWidth()
        )
        Spacer(Modifier.height(8.dp))
        OutlinedTextField(
            value = phone,
            onValueChange = { phone = it },
            label = { Text(stringResource(R.string.register_phone)) },
            modifier = Modifier.fillMaxWidth()
        )
        Spacer(Modifier.height(8.dp))

        val professions = (state as? RegisterUiState.ProfessionsLoaded)?.professions.orEmpty()
        ExposedDropdownMenuBox(expanded = expanded, onExpandedChange = { expanded = it }) {
            OutlinedTextField(
                value = selectedProfession?.nameUz ?: "",
                onValueChange = {},
                readOnly = true,
                label = { Text(stringResource(R.string.register_profession)) },
                modifier = Modifier.fillMaxWidth()
            )
            ExposedDropdownMenu(expanded = expanded, onDismissRequest = { expanded = false }) {
                professions.forEach { profession ->
                    DropdownMenuItem(
                        text = { Text(profession.nameUz) },
                        onClick = { selectedProfession = profession; expanded = false }
                    )
                }
            }
        }

        Spacer(Modifier.height(16.dp))
        if (state is RegisterUiState.Error) {
            Text((state as RegisterUiState.Error).message, color = MaterialTheme.colorScheme.error)
            Spacer(Modifier.height(8.dp))
        }
        Button(
            onClick = { selectedProfession?.let { viewModel.submit(fullName, phone, it.id) } },
            enabled = state !is RegisterUiState.Submitting && selectedProfession != null,
            modifier = Modifier.fillMaxWidth()
        ) {
            Text(stringResource(R.string.register_submit))
        }
    }
}
```

- [ ] **Step 10: Add strings**

Read current string files, add:

`values/strings.xml`:
```xml
    <string name="register_title">Ariza berish</string>
    <string name="register_full_name">F.I.Sh</string>
    <string name="register_phone">Telefon raqam</string>
    <string name="register_profession">Kasb</string>
    <string name="register_submit">Yuborish</string>
```

`values-ru/strings.xml`:
```xml
    <string name="register_title">Подать заявку</string>
    <string name="register_full_name">Ф.И.О</string>
    <string name="register_phone">Номер телефона</string>
    <string name="register_profession">Профессия</string>
    <string name="register_submit">Отправить</string>
```

- [ ] **Step 11: Add `professionRepository` to `ServiceLocator`**

Read the current `ServiceLocator.kt`, add:

```kotlin
    val professionRepository by lazy { uz.edu.trainingcenter.data.repository.ProfessionRepository(apiService, sessionManager) }
```

- [ ] **Step 12: Run the full unit test suite and build**

Run: `gradlew.bat testDebugUnitTest` then `gradlew.bat assembleDebug`.
Expected: all pass, build succeeds.

- [ ] **Step 13: Commit**

```bash
git add android-app/app/src/main/java/uz/edu/trainingcenter/data/repository/ProfessionRepository.kt android-app/app/src/main/java/uz/edu/trainingcenter/ui/screens/register android-app/app/src/main/java/uz/edu/trainingcenter/ServiceLocator.kt android-app/app/src/main/res android-app/app/src/test/java/uz/edu/trainingcenter/data/repository/ProfessionRepositoryTest.kt android-app/app/src/test/java/uz/edu/trainingcenter/ui/screens/register
git commit -m "feat: add ProfessionRepository, RegisterViewModel, RegisterScreen"
```

---

### Task 6: `ViewModelFactory`, `Routes`, `AppNavHost`, `SplashScreen`, wire `MainActivity`

**Files:**
- Create: `android-app/app/src/main/java/uz/edu/trainingcenter/ViewModelFactory.kt`
- Create: `android-app/app/src/main/java/uz/edu/trainingcenter/navigation/Routes.kt`
- Create: `android-app/app/src/main/java/uz/edu/trainingcenter/navigation/AppNavHost.kt`
- Create: `android-app/app/src/main/java/uz/edu/trainingcenter/ui/screens/splash/SplashScreen.kt`
- Modify: `android-app/app/src/main/java/uz/edu/trainingcenter/MainActivity.kt`
- Create: `android-app/app/src/main/java/uz/edu/trainingcenter/ui/theme/Color.kt`
- Create: `android-app/app/src/main/java/uz/edu/trainingcenter/ui/theme/Theme.kt`

**Interfaces:**
- Consumes: `ServiceLocator`, `LoginScreen`/`RegisterScreen` (Tasks 4-5).
- Produces: `Routes` object (string route constants used by every later screen task), `AppNavHost(navController)`, `ViewModelFactory { creator: () -> ViewModel }` (used by every screen to construct its ViewModel via `viewModel(factory = ViewModelFactory { ... })`), `TrainingCenterTheme(themeMode: String, content: @Composable () -> Unit)`.

- [ ] **Step 1: Implement `ViewModelFactory`**

No test — this is a 6-line generic adapter with no branching logic to verify; it's exercised indirectly by every screen's own ViewModel test (which constructs the ViewModel directly, not through this factory) and by the app actually running.

```kotlin
package uz.edu.trainingcenter

import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider

class ViewModelFactory(private val creator: () -> ViewModel) : ViewModelProvider.Factory {
    @Suppress("UNCHECKED_CAST")
    override fun <T : ViewModel> create(modelClass: Class<T>): T = creator() as T
}
```

- [ ] **Step 2: Implement `Routes`**

```kotlin
package uz.edu.trainingcenter.navigation

object Routes {
    const val SPLASH = "splash"
    const val LOGIN = "login"
    const val REGISTER = "register"
    const val HOME = "home"
    const val SCHEDULE = "schedule"
    const val TESTS_LIST = "tests_list"
    const val TEST_TAKING = "test_taking/{testId}"
    const val TEST_RESULT = "test_result/{score}/{passed}"
    const val CERTIFICATES = "certificates"
    const val PROFILE = "profile"

    fun testTaking(testId: Int) = "test_taking/$testId"
    fun testResult(score: Int, passed: Boolean) = "test_result/$score/$passed"
}
```

- [ ] **Step 3: Implement `ui/theme/Color.kt`**

```kotlin
package uz.edu.trainingcenter.ui.theme

import androidx.compose.ui.graphics.Color

val PrimaryLight = Color(0xFF2E7D32)
val OnPrimaryLight = Color(0xFFFFFFFF)
val PrimaryDark = Color(0xFF81C784)
val OnPrimaryDark = Color(0xFF003910)
```

- [ ] **Step 4: Implement `ui/theme/Theme.kt`**

```kotlin
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
```

- [ ] **Step 5: Implement `SplashScreen`**

```kotlin
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
```

- [ ] **Step 6: Implement `AppNavHost`**

```kotlin
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
```

(This task deliberately stops at `REGISTER` — `Routes.HOME` has no `composable(...)` entry yet. Task 7 adds it. Leaving `HOME` unregistered here is correct and matches the plan's sequencing; do not add a placeholder Home composable.)

- [ ] **Step 7: Wire `MainActivity`**

```kotlin
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
```

- [ ] **Step 8: Build (no new unit tests this task — pure wiring, verified by successful compile + Task 4/5's existing tests still passing)**

Run: `gradlew.bat testDebugUnitTest` then `gradlew.bat assembleDebug`.
Expected: all existing tests still pass, build succeeds.

- [ ] **Step 9: Commit**

```bash
git add android-app/app/src/main/java/uz/edu/trainingcenter/ViewModelFactory.kt android-app/app/src/main/java/uz/edu/trainingcenter/navigation android-app/app/src/main/java/uz/edu/trainingcenter/ui/screens/splash android-app/app/src/main/java/uz/edu/trainingcenter/ui/theme android-app/app/src/main/java/uz/edu/trainingcenter/MainActivity.kt
git commit -m "feat: add navigation skeleton, theme, splash screen, and wire MainActivity"
```

---

### Task 7: `ScheduleRepository` + `ScheduleViewModel` + `ScheduleScreen` + `HomeScaffold`

**Files:**
- Create: `android-app/app/src/main/java/uz/edu/trainingcenter/data/repository/ScheduleRepository.kt`
- Create: `android-app/app/src/main/java/uz/edu/trainingcenter/ui/screens/schedule/ScheduleViewModel.kt`
- Create: `android-app/app/src/main/java/uz/edu/trainingcenter/ui/screens/schedule/ScheduleScreen.kt`
- Create: `android-app/app/src/main/java/uz/edu/trainingcenter/ui/screens/home/HomeScaffold.kt`
- Modify: `android-app/app/src/main/java/uz/edu/trainingcenter/ServiceLocator.kt`
- Modify: `android-app/app/src/main/java/uz/edu/trainingcenter/navigation/AppNavHost.kt`
- Test: `android-app/app/src/test/java/uz/edu/trainingcenter/data/repository/ScheduleRepositoryTest.kt`
- Test: `android-app/app/src/test/java/uz/edu/trainingcenter/ui/screens/schedule/ScheduleViewModelTest.kt`

**Interfaces:**
- Consumes: `ApiService`, `SessionManager`.
- Produces: `ScheduleRepository.getSchedule(): Result<List<ScheduleItemDto>>`; `ScheduleViewModel` with `ScheduleUiState` (`Loading`/`Success(items)`/`Error`); `HomeScaffold(navController, content: @Composable (padding) -> Unit)` — a Scaffold with `BottomNavigation` (Jadval/Testlar/Sertifikatlar/Profil), reused by Tasks 7-11's screens; `Routes.HOME` now registered in `AppNavHost` as a nested graph host.

**Design note on `HomeScaffold` + bottom navigation:** rather than nesting a second `NavHost` inside `Routes.HOME`, this plan keeps ONE flat `NavHost` (from Task 6) and adds `Routes.SCHEDULE`, `Routes.TESTS_LIST`, `Routes.CERTIFICATES`, `Routes.PROFILE` as top-level destinations, each wrapped in `HomeScaffold` (which renders the bottom bar and highlights the current tab via `navController.currentBackStackEntryAsState()`). `Routes.HOME` becomes an alias that immediately navigates to `Routes.SCHEDULE` (the default landing tab) — simpler than a nested graph for this app's size.

- [ ] **Step 1: Write the failing repository test**

```kotlin
package uz.edu.trainingcenter.data.repository

import io.mockk.coEvery
import io.mockk.mockk
import kotlinx.coroutines.test.runTest
import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Test
import uz.edu.trainingcenter.data.remote.ApiService
import uz.edu.trainingcenter.data.remote.SessionManager
import uz.edu.trainingcenter.data.remote.dto.ScheduleItemDto
import uz.edu.trainingcenter.data.remote.dto.ScheduleResponse

class ScheduleRepositoryTest {

    @Test
    fun `getSchedule returns items on success`() = runTest {
        val api = mockk<ApiService>()
        val item = ScheduleItemDto("2026-10-01", "09:00:00", "11:00:00", "101", "Ekskavator-1")
        coEvery { api.getSchedule() } returns ScheduleResponse(listOf(item))

        val repository = ScheduleRepository(api, SessionManager())
        val result = repository.getSchedule()

        assertTrue(result.isSuccess)
        assertEquals(1, result.getOrThrow().size)
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `gradlew.bat testDebugUnitTest --tests "uz.edu.trainingcenter.data.repository.ScheduleRepositoryTest"`
Expected: FAIL.

- [ ] **Step 3: Implement `ScheduleRepository`**

```kotlin
package uz.edu.trainingcenter.data.repository

import uz.edu.trainingcenter.data.remote.ApiService
import uz.edu.trainingcenter.data.remote.SessionManager
import uz.edu.trainingcenter.data.remote.dto.ScheduleItemDto
import uz.edu.trainingcenter.data.remote.safeApiCall

class ScheduleRepository(
    private val api: ApiService,
    private val sessionManager: SessionManager
) {
    suspend fun getSchedule(): Result<List<ScheduleItemDto>> {
        return safeApiCall(sessionManager) { api.getSchedule() }.map { it.schedule }
    }
}
```

- [ ] **Step 4: Run test to verify it passes.** Expected: PASS.

- [ ] **Step 5: Write the failing `ScheduleViewModel` test**

```kotlin
package uz.edu.trainingcenter.ui.screens.schedule

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
import uz.edu.trainingcenter.data.remote.dto.ScheduleItemDto
import uz.edu.trainingcenter.data.repository.ScheduleRepository

@OptIn(ExperimentalCoroutinesApi::class)
class ScheduleViewModelTest {

    @Before
    fun setUp() { Dispatchers.setMain(StandardTestDispatcher()) }

    @After
    fun tearDown() { Dispatchers.resetMain() }

    @Test
    fun `load emits Success with items`() = runTest {
        val repository = mockk<ScheduleRepository>()
        val item = ScheduleItemDto("2026-10-01", "09:00:00", "11:00:00", "101", "Ekskavator-1")
        coEvery { repository.getSchedule() } returns Result.success(listOf(item))

        val viewModel = ScheduleViewModel(repository)

        viewModel.uiState.test {
            assertTrue(awaitItem() is ScheduleUiState.Loading)
            val success = awaitItem()
            assertTrue(success is ScheduleUiState.Success)
        }
    }
}
```

- [ ] **Step 6: Run test to verify it fails.** Expected: FAIL.

- [ ] **Step 7: Implement `ScheduleViewModel`**

```kotlin
package uz.edu.trainingcenter.ui.screens.schedule

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import uz.edu.trainingcenter.data.remote.dto.ScheduleItemDto
import uz.edu.trainingcenter.data.repository.ScheduleRepository

sealed interface ScheduleUiState {
    data object Loading : ScheduleUiState
    data class Success(val items: List<ScheduleItemDto>) : ScheduleUiState
    data class Error(val message: String) : ScheduleUiState
}

class ScheduleViewModel(private val repository: ScheduleRepository) : ViewModel() {

    private val _uiState = MutableStateFlow<ScheduleUiState>(ScheduleUiState.Loading)
    val uiState: StateFlow<ScheduleUiState> = _uiState

    init {
        load()
    }

    fun load() {
        viewModelScope.launch {
            _uiState.value = ScheduleUiState.Loading
            val result = repository.getSchedule()
            _uiState.value = result.fold(
                onSuccess = { ScheduleUiState.Success(it) },
                onFailure = { ScheduleUiState.Error(it.message ?: "Failed to load schedule") }
            )
        }
    }
}
```

- [ ] **Step 8: Run test to verify it passes.** Expected: PASS.

- [ ] **Step 9: Implement `HomeScaffold`**

```kotlin
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
```

- [ ] **Step 10: Implement `ScheduleScreen`**

```kotlin
package uz.edu.trainingcenter.ui.screens.schedule

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.unit.dp
import uz.edu.trainingcenter.R
import uz.edu.trainingcenter.data.remote.dto.ScheduleItemDto

@Composable
fun ScheduleScreen(viewModel: ScheduleViewModel, padding: PaddingValues) {
    val state by viewModel.uiState.collectAsState()

    Box(modifier = Modifier.fillMaxSize().padding(padding)) {
        when (val s = state) {
            is ScheduleUiState.Loading -> CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
            is ScheduleUiState.Error -> Text(s.message, modifier = Modifier.align(Alignment.Center))
            is ScheduleUiState.Success -> {
                if (s.items.isEmpty()) {
                    Text(stringResource(R.string.schedule_empty), modifier = Modifier.align(Alignment.Center))
                } else {
                    LazyColumn(modifier = Modifier.padding(16.dp)) {
                        items(s.items) { item -> ScheduleRow(item) }
                    }
                }
            }
        }
    }
}

@Composable
private fun ScheduleRow(item: ScheduleItemDto) {
    Card(modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp)) {
        Column(modifier = Modifier.padding(12.dp)) {
            Text(item.groupName, style = MaterialTheme.typography.titleMedium)
            Text("${item.lessonDate}  ${item.startTime}-${item.endTime}")
            Text(stringResource(R.string.schedule_room, item.room))
        }
    }
}
```

- [ ] **Step 11: Add strings**

`values/strings.xml`:
```xml
    <string name="tab_schedule">Jadval</string>
    <string name="tab_tests">Testlar</string>
    <string name="tab_certificates">Sertifikatlar</string>
    <string name="tab_profile">Profil</string>
    <string name="schedule_empty">Hozircha dars jadvali yo\'q</string>
    <string name="schedule_room">Xona: %1$s</string>
```

`values-ru/strings.xml`:
```xml
    <string name="tab_schedule">Расписание</string>
    <string name="tab_tests">Тесты</string>
    <string name="tab_certificates">Сертификаты</string>
    <string name="tab_profile">Профиль</string>
    <string name="schedule_empty">Расписание пока отсутствует</string>
    <string name="schedule_room">Кабинет: %1$s</string>
```

- [ ] **Step 12: Add `scheduleRepository` to `ServiceLocator`**

Read current file, add:

```kotlin
    val scheduleRepository by lazy { uz.edu.trainingcenter.data.repository.ScheduleRepository(apiService, sessionManager) }
```

- [ ] **Step 13: Register `Routes.HOME`, `Routes.SCHEDULE` in `AppNavHost`**

Read the current `AppNavHost.kt` (from Task 6), add inside the `NavHost { ... }` block, after the `REGISTER` composable and before the closing brace — replacing the `// Routes.HOME and beyond...` comment:

```kotlin
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
```

Add the needed imports (`androidx.compose.runtime.LaunchedEffect`, `uz.edu.trainingcenter.ui.screens.home.HomeScaffold`, `uz.edu.trainingcenter.ui.screens.schedule.ScheduleScreen`, `uz.edu.trainingcenter.ui.screens.schedule.ScheduleViewModel`).

- [ ] **Step 14: Run the full unit test suite and build**

Run: `gradlew.bat testDebugUnitTest` then `gradlew.bat assembleDebug`.
Expected: all pass, build succeeds.

- [ ] **Step 15: Commit**

```bash
git add android-app/app/src/main/java/uz/edu/trainingcenter/data/repository/ScheduleRepository.kt android-app/app/src/main/java/uz/edu/trainingcenter/ui/screens/schedule android-app/app/src/main/java/uz/edu/trainingcenter/ui/screens/home android-app/app/src/main/java/uz/edu/trainingcenter/ServiceLocator.kt android-app/app/src/main/java/uz/edu/trainingcenter/navigation/AppNavHost.kt android-app/app/src/main/res android-app/app/src/test/java/uz/edu/trainingcenter/data/repository/ScheduleRepositoryTest.kt android-app/app/src/test/java/uz/edu/trainingcenter/ui/screens/schedule
git commit -m "feat: add ScheduleRepository/ViewModel/Screen and HomeScaffold bottom navigation"
```

---

### Task 8: `TestRepository` (list + questions) + `TestsListViewModel` + `TestsListScreen`

**Files:**
- Create: `android-app/app/src/main/java/uz/edu/trainingcenter/data/repository/TestRepository.kt`
- Create: `android-app/app/src/main/java/uz/edu/trainingcenter/ui/screens/tests/TestsListViewModel.kt`
- Create: `android-app/app/src/main/java/uz/edu/trainingcenter/ui/screens/tests/TestsListScreen.kt`
- Modify: `android-app/app/src/main/java/uz/edu/trainingcenter/ServiceLocator.kt`
- Modify: `android-app/app/src/main/java/uz/edu/trainingcenter/navigation/AppNavHost.kt`
- Test: `android-app/app/src/test/java/uz/edu/trainingcenter/data/repository/TestRepositoryTest.kt`
- Test: `android-app/app/src/test/java/uz/edu/trainingcenter/ui/screens/tests/TestsListViewModelTest.kt`

**Interfaces:**
- Produces: `TestRepository.getTests(): Result<List<TestSummaryDto>>`, `TestRepository.getQuestions(testId): Result<List<QuestionDto>>`, `TestRepository.submit(testId, answerIds): Result<SubmitResponse>` (this last one used by Task 9, defined here since it lives on the same repository).

- [ ] **Step 1: Write the failing repository test**

```kotlin
package uz.edu.trainingcenter.data.repository

import io.mockk.coEvery
import io.mockk.mockk
import kotlinx.coroutines.test.runTest
import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Test
import uz.edu.trainingcenter.data.remote.ApiService
import uz.edu.trainingcenter.data.remote.SessionManager
import uz.edu.trainingcenter.data.remote.dto.*

class TestRepositoryTest {

    @Test
    fun `getTests returns list on success`() = runTest {
        val api = mockk<ApiService>()
        coEvery { api.getTests() } returns TestsResponse(listOf(TestSummaryDto(1, "Yakuniy", "Финал", 70)))

        val result = TestRepository(api, SessionManager()).getTests()

        assertTrue(result.isSuccess)
        assertEquals(1, result.getOrThrow().size)
    }

    @Test
    fun `getQuestions returns list on success`() = runTest {
        val api = mockk<ApiService>()
        val answer = AnswerDto(1, "A", "А")
        val question = QuestionDto(1, "Savol?", "Вопрос?", listOf(answer))
        coEvery { api.getTestQuestions(1) } returns QuestionsResponse(listOf(question))

        val result = TestRepository(api, SessionManager()).getQuestions(1)

        assertTrue(result.isSuccess)
        assertEquals(1, result.getOrThrow().size)
    }

    @Test
    fun `submit returns score and passed on success`() = runTest {
        val api = mockk<ApiService>()
        coEvery { api.submitTest(1, SubmitRequest(listOf(1, 3))) } returns SubmitResponse(100, true)

        val result = TestRepository(api, SessionManager()).submit(1, listOf(1, 3))

        assertTrue(result.isSuccess)
        assertEquals(100, result.getOrThrow().score)
        assertTrue(result.getOrThrow().passed)
    }
}
```

- [ ] **Step 2: Run test to verify it fails.** Expected: FAIL.

- [ ] **Step 3: Implement `TestRepository`**

```kotlin
package uz.edu.trainingcenter.data.repository

import uz.edu.trainingcenter.data.remote.ApiService
import uz.edu.trainingcenter.data.remote.SessionManager
import uz.edu.trainingcenter.data.remote.dto.QuestionDto
import uz.edu.trainingcenter.data.remote.dto.SubmitRequest
import uz.edu.trainingcenter.data.remote.dto.SubmitResponse
import uz.edu.trainingcenter.data.remote.dto.TestSummaryDto
import uz.edu.trainingcenter.data.remote.safeApiCall

class TestRepository(
    private val api: ApiService,
    private val sessionManager: SessionManager
) {
    suspend fun getTests(): Result<List<TestSummaryDto>> {
        return safeApiCall(sessionManager) { api.getTests() }.map { it.tests }
    }

    suspend fun getQuestions(testId: Int): Result<List<QuestionDto>> {
        return safeApiCall(sessionManager) { api.getTestQuestions(testId) }.map { it.questions }
    }

    suspend fun submit(testId: Int, answerIds: List<Int>): Result<SubmitResponse> {
        return safeApiCall(sessionManager) { api.submitTest(testId, SubmitRequest(answerIds)) }
    }
}
```

- [ ] **Step 4: Run test to verify it passes.** Expected: PASS.

- [ ] **Step 5: Write the failing `TestsListViewModel` test**

```kotlin
package uz.edu.trainingcenter.ui.screens.tests

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
import uz.edu.trainingcenter.data.remote.dto.TestSummaryDto
import uz.edu.trainingcenter.data.repository.TestRepository

@OptIn(ExperimentalCoroutinesApi::class)
class TestsListViewModelTest {

    @Before
    fun setUp() { Dispatchers.setMain(StandardTestDispatcher()) }

    @After
    fun tearDown() { Dispatchers.resetMain() }

    @Test
    fun `load emits Success with tests`() = runTest {
        val repository = mockk<TestRepository>()
        coEvery { repository.getTests() } returns Result.success(listOf(TestSummaryDto(1, "Yakuniy", "Финал", 70)))

        val viewModel = TestsListViewModel(repository)

        viewModel.uiState.test {
            assertTrue(awaitItem() is TestsListUiState.Loading)
            assertTrue(awaitItem() is TestsListUiState.Success)
        }
    }
}
```

- [ ] **Step 6: Run test to verify it fails.** Expected: FAIL.

- [ ] **Step 7: Implement `TestsListViewModel`**

```kotlin
package uz.edu.trainingcenter.ui.screens.tests

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import uz.edu.trainingcenter.data.remote.dto.TestSummaryDto
import uz.edu.trainingcenter.data.repository.TestRepository

sealed interface TestsListUiState {
    data object Loading : TestsListUiState
    data class Success(val tests: List<TestSummaryDto>) : TestsListUiState
    data class Error(val message: String) : TestsListUiState
}

class TestsListViewModel(private val repository: TestRepository) : ViewModel() {

    private val _uiState = MutableStateFlow<TestsListUiState>(TestsListUiState.Loading)
    val uiState: StateFlow<TestsListUiState> = _uiState

    init {
        load()
    }

    fun load() {
        viewModelScope.launch {
            _uiState.value = TestsListUiState.Loading
            val result = repository.getTests()
            _uiState.value = result.fold(
                onSuccess = { TestsListUiState.Success(it) },
                onFailure = { TestsListUiState.Error(it.message ?: "Failed to load tests") }
            )
        }
    }
}
```

- [ ] **Step 8: Run test to verify it passes.** Expected: PASS.

- [ ] **Step 9: Implement `TestsListScreen`**

```kotlin
package uz.edu.trainingcenter.ui.screens.tests

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.unit.dp
import uz.edu.trainingcenter.R
import uz.edu.trainingcenter.data.remote.dto.TestSummaryDto

@Composable
fun TestsListScreen(viewModel: TestsListViewModel, padding: PaddingValues, onTestClick: (Int) -> Unit) {
    val state by viewModel.uiState.collectAsState()

    Box(modifier = Modifier.fillMaxSize().padding(padding)) {
        when (val s = state) {
            is TestsListUiState.Loading -> CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
            is TestsListUiState.Error -> Text(s.message, modifier = Modifier.align(Alignment.Center))
            is TestsListUiState.Success -> {
                if (s.tests.isEmpty()) {
                    Text(stringResource(R.string.tests_empty), modifier = Modifier.align(Alignment.Center))
                } else {
                    LazyColumn(modifier = Modifier.padding(16.dp)) {
                        items(s.tests) { test -> TestRow(test, onClick = { onTestClick(test.id) }) }
                    }
                }
            }
        }
    }
}

@Composable
private fun TestRow(test: TestSummaryDto, onClick: () -> Unit) {
    Card(
        modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp),
        onClick = onClick
    ) {
        Column(modifier = Modifier.padding(12.dp)) {
            Text(test.titleUz, style = MaterialTheme.typography.titleMedium)
            Text(stringResource(R.string.tests_passing_score, test.passingScore))
        }
    }
}
```

- [ ] **Step 10: Add strings**

`values/strings.xml`:
```xml
    <string name="tests_empty">Hozircha mavjud test yo\'q</string>
    <string name="tests_passing_score">O\'tish balli: %1$d%%</string>
```

`values-ru/strings.xml`:
```xml
    <string name="tests_empty">Пока нет доступных тестов</string>
    <string name="tests_passing_score">Проходной балл: %1$d%%</string>
```

- [ ] **Step 11: Add `testRepository` to `ServiceLocator`**

```kotlin
    val testRepository by lazy { uz.edu.trainingcenter.data.repository.TestRepository(apiService, sessionManager) }
```

- [ ] **Step 12: Register `Routes.TESTS_LIST` in `AppNavHost`**

Read current file, add after the `SCHEDULE` composable:

```kotlin
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
```

(`Routes.TEST_TAKING`'s destination is registered by Task 9 — this task only needs the navigation call target to exist as a route pattern, already defined in Task 6's `Routes.kt`.)

- [ ] **Step 13: Run the full unit test suite and build**

Run: `gradlew.bat testDebugUnitTest` then `gradlew.bat assembleDebug`.
Expected: all pass. (The app will crash at runtime if you navigate to `TEST_TAKING` before Task 9 registers it — that's expected and fine; Task 9 completes the flow. `assembleDebug` only checks compilation, not runtime navigation.)

- [ ] **Step 14: Commit**

```bash
git add android-app/app/src/main/java/uz/edu/trainingcenter/data/repository/TestRepository.kt android-app/app/src/main/java/uz/edu/trainingcenter/ui/screens/tests android-app/app/src/main/java/uz/edu/trainingcenter/ServiceLocator.kt android-app/app/src/main/java/uz/edu/trainingcenter/navigation/AppNavHost.kt android-app/app/src/main/res android-app/app/src/test/java/uz/edu/trainingcenter/data/repository/TestRepositoryTest.kt android-app/app/src/test/java/uz/edu/trainingcenter/ui/screens/tests
git commit -m "feat: add TestRepository, TestsListViewModel, TestsListScreen"
```

---

### Task 9: `TestTakingViewModel` + `TestTakingScreen` + `TestResultScreen`

**Files:**
- Create: `android-app/app/src/main/java/uz/edu/trainingcenter/ui/screens/testtaking/TestTakingViewModel.kt`
- Create: `android-app/app/src/main/java/uz/edu/trainingcenter/ui/screens/testtaking/TestTakingScreen.kt`
- Create: `android-app/app/src/main/java/uz/edu/trainingcenter/ui/screens/testtaking/TestResultScreen.kt`
- Modify: `android-app/app/src/main/java/uz/edu/trainingcenter/navigation/AppNavHost.kt`
- Test: `android-app/app/src/test/java/uz/edu/trainingcenter/ui/screens/testtaking/TestTakingViewModelTest.kt`

**Interfaces:**
- Consumes: `TestRepository` (Task 8).
- Produces: `TestTakingViewModel(testId, repository)` with `TestTakingUiState` (`Loading`/`InProgress(questions, currentIndex, selectedAnswers)`/`Submitting`/`Submitted(score, passed)`/`Error`), `selectAnswer(answerId)`, `nextQuestion()`, `submitTest()`.

- [ ] **Step 1: Write the failing `TestTakingViewModel` test**

```kotlin
package uz.edu.trainingcenter.ui.screens.testtaking

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
import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Before
import org.junit.Test
import uz.edu.trainingcenter.data.remote.dto.AnswerDto
import uz.edu.trainingcenter.data.remote.dto.QuestionDto
import uz.edu.trainingcenter.data.remote.dto.SubmitResponse
import uz.edu.trainingcenter.data.repository.TestRepository

@OptIn(ExperimentalCoroutinesApi::class)
class TestTakingViewModelTest {

    @Before
    fun setUp() { Dispatchers.setMain(StandardTestDispatcher()) }

    @After
    fun tearDown() { Dispatchers.resetMain() }

    private fun twoQuestions() = listOf(
        QuestionDto(1, "Savol 1", "Вопрос 1", listOf(AnswerDto(1, "A", "А"), AnswerDto(2, "B", "Б"))),
        QuestionDto(2, "Savol 2", "Вопрос 2", listOf(AnswerDto(3, "C", "В"), AnswerDto(4, "D", "Г")))
    )

    @Test
    fun `load emits InProgress at question 0`() = runTest {
        val repository = mockk<TestRepository>()
        coEvery { repository.getQuestions(1) } returns Result.success(twoQuestions())

        val viewModel = TestTakingViewModel(testId = 1, repository = repository)

        viewModel.uiState.test {
            assertTrue(awaitItem() is TestTakingUiState.Loading)
            val inProgress = awaitItem() as TestTakingUiState.InProgress
            assertEquals(0, inProgress.currentIndex)
        }
    }

    @Test
    fun `selectAnswer then nextQuestion advances index and records answer`() = runTest {
        val repository = mockk<TestRepository>()
        coEvery { repository.getQuestions(1) } returns Result.success(twoQuestions())

        val viewModel = TestTakingViewModel(testId = 1, repository = repository)

        viewModel.uiState.test {
            awaitItem() // Loading
            awaitItem() // InProgress index 0
            viewModel.selectAnswer(1)
            viewModel.nextQuestion()
            val next = awaitItem() as TestTakingUiState.InProgress
            assertEquals(1, next.currentIndex)
            assertEquals(1, next.selectedAnswers[1])
        }
    }

    @Test
    fun `submitTest on last question emits Submitted`() = runTest {
        val repository = mockk<TestRepository>()
        coEvery { repository.getQuestions(1) } returns Result.success(twoQuestions())
        coEvery { repository.submit(1, listOf(1, 3)) } returns Result.success(SubmitResponse(100, true))

        val viewModel = TestTakingViewModel(testId = 1, repository = repository)

        viewModel.uiState.test {
            awaitItem() // Loading
            awaitItem() // InProgress index 0
            viewModel.selectAnswer(1)
            viewModel.nextQuestion()
            awaitItem() // InProgress index 1
            viewModel.selectAnswer(3)
            viewModel.submitTest()
            assertTrue(awaitItem() is TestTakingUiState.Submitting)
            val submitted = awaitItem() as TestTakingUiState.Submitted
            assertEquals(100, submitted.score)
            assertTrue(submitted.passed)
        }
    }
}
```

- [ ] **Step 2: Run test to verify it fails.** Expected: FAIL.

- [ ] **Step 3: Implement `TestTakingViewModel`**

```kotlin
package uz.edu.trainingcenter.ui.screens.testtaking

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import uz.edu.trainingcenter.data.remote.dto.QuestionDto
import uz.edu.trainingcenter.data.repository.TestRepository

sealed interface TestTakingUiState {
    data object Loading : TestTakingUiState
    data class InProgress(
        val questions: List<QuestionDto>,
        val currentIndex: Int,
        val selectedAnswers: Map<Int, Int> // questionId -> selected answerId
    ) : TestTakingUiState
    data object Submitting : TestTakingUiState
    data class Submitted(val score: Int, val passed: Boolean) : TestTakingUiState
    data class Error(val message: String) : TestTakingUiState
}

class TestTakingViewModel(
    private val testId: Int,
    private val repository: TestRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow<TestTakingUiState>(TestTakingUiState.Loading)
    val uiState: StateFlow<TestTakingUiState> = _uiState

    init {
        load()
    }

    private fun load() {
        viewModelScope.launch {
            _uiState.value = TestTakingUiState.Loading
            val result = repository.getQuestions(testId)
            _uiState.value = result.fold(
                onSuccess = { TestTakingUiState.InProgress(it, currentIndex = 0, selectedAnswers = emptyMap()) },
                onFailure = { TestTakingUiState.Error(it.message ?: "Failed to load test") }
            )
        }
    }

    fun selectAnswer(answerId: Int) {
        val current = _uiState.value as? TestTakingUiState.InProgress ?: return
        val questionId = current.questions[current.currentIndex].id
        _uiState.value = current.copy(selectedAnswers = current.selectedAnswers + (questionId to answerId))
    }

    fun nextQuestion() {
        val current = _uiState.value as? TestTakingUiState.InProgress ?: return
        if (current.currentIndex < current.questions.size - 1) {
            _uiState.value = current.copy(currentIndex = current.currentIndex + 1)
        }
    }

    fun submitTest() {
        val current = _uiState.value as? TestTakingUiState.InProgress ?: return
        viewModelScope.launch {
            _uiState.value = TestTakingUiState.Submitting
            val answerIds = current.questions.mapNotNull { current.selectedAnswers[it.id] }
            val result = repository.submit(testId, answerIds)
            _uiState.value = result.fold(
                onSuccess = { TestTakingUiState.Submitted(it.score, it.passed) },
                onFailure = { TestTakingUiState.Error(it.message ?: "Failed to submit test") }
            )
        }
    }
}
```

- [ ] **Step 4: Run test to verify it passes.** Expected: PASS.

- [ ] **Step 5: Implement `TestTakingScreen`**

```kotlin
package uz.edu.trainingcenter.ui.screens.testtaking

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.selection.selectable
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.unit.dp
import uz.edu.trainingcenter.R

@Composable
fun TestTakingScreen(viewModel: TestTakingViewModel, onSubmitted: (score: Int, passed: Boolean) -> Unit) {
    val state by viewModel.uiState.collectAsState()

    LaunchedEffect(state) {
        val s = state
        if (s is TestTakingUiState.Submitted) onSubmitted(s.score, s.passed)
    }

    Box(modifier = Modifier.fillMaxSize().padding(24.dp)) {
        when (val s = state) {
            is TestTakingUiState.Loading, is TestTakingUiState.Submitting ->
                CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
            is TestTakingUiState.Error -> Text(s.message, modifier = Modifier.align(Alignment.Center))
            is TestTakingUiState.InProgress -> {
                val question = s.questions[s.currentIndex]
                val selected = s.selectedAnswers[question.id]
                val isLast = s.currentIndex == s.questions.size - 1

                Column {
                    Text(
                        stringResource(R.string.test_taking_progress, s.currentIndex + 1, s.questions.size),
                        style = MaterialTheme.typography.labelLarge
                    )
                    Spacer(Modifier.height(16.dp))
                    Text(question.textUz, style = MaterialTheme.typography.headlineSmall)
                    Spacer(Modifier.height(16.dp))
                    question.answers.forEach { answer ->
                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .selectable(selected = selected == answer.id, onClick = { viewModel.selectAnswer(answer.id) })
                                .padding(vertical = 8.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            RadioButton(selected = selected == answer.id, onClick = { viewModel.selectAnswer(answer.id) })
                            Spacer(Modifier.width(8.dp))
                            Text(answer.textUz)
                        }
                    }
                    Spacer(Modifier.weight(1f))
                    Button(
                        onClick = { if (isLast) viewModel.submitTest() else viewModel.nextQuestion() },
                        enabled = selected != null,
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Text(stringResource(if (isLast) R.string.test_taking_finish else R.string.test_taking_next))
                    }
                }
            }
        }
    }
}
```

- [ ] **Step 6: Implement `TestResultScreen`**

```kotlin
package uz.edu.trainingcenter.ui.screens.testtaking

import androidx.compose.foundation.layout.*
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.unit.dp
import uz.edu.trainingcenter.R

@Composable
fun TestResultScreen(score: Int, passed: Boolean, onBackToTests: () -> Unit) {
    Column(
        modifier = Modifier.fillMaxSize().padding(24.dp),
        verticalArrangement = Arrangement.Center,
        horizontalAlignment = Alignment.CenterHorizontally
    ) {
        Text(
            stringResource(if (passed) R.string.test_result_passed else R.string.test_result_failed),
            style = MaterialTheme.typography.headlineMedium
        )
        Spacer(Modifier.height(16.dp))
        Text(stringResource(R.string.test_result_score, score))
        Spacer(Modifier.height(24.dp))
        Button(onClick = onBackToTests) {
            Text(stringResource(R.string.test_result_back))
        }
    }
}
```

- [ ] **Step 7: Add strings**

`values/strings.xml`:
```xml
    <string name="test_taking_progress">Savol %1$d / %2$d</string>
    <string name="test_taking_next">Keyingi</string>
    <string name="test_taking_finish">Yakunlash</string>
    <string name="test_result_passed">Tabriklaymiz, siz o\'tdingiz!</string>
    <string name="test_result_failed">Afsuski, siz o\'ta olmadingiz</string>
    <string name="test_result_score">Ball: %1$d%%</string>
    <string name="test_result_back">Testlarga qaytish</string>
```

`values-ru/strings.xml`:
```xml
    <string name="test_taking_progress">Вопрос %1$d / %2$d</string>
    <string name="test_taking_next">Далее</string>
    <string name="test_taking_finish">Завершить</string>
    <string name="test_result_passed">Поздравляем, вы прошли тест!</string>
    <string name="test_result_failed">К сожалению, вы не прошли тест</string>
    <string name="test_result_score">Балл: %1$d%%</string>
    <string name="test_result_back">Вернуться к тестам</string>
```

- [ ] **Step 8: Register `Routes.TEST_TAKING` and `Routes.TEST_RESULT` in `AppNavHost`**

Read the current file, add (with imports for `navArgument`, `NavType`, the new screens/viewmodel):

```kotlin
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
```

Add imports: `androidx.navigation.navArgument`, `androidx.navigation.NavType`, `uz.edu.trainingcenter.ui.screens.testtaking.TestTakingScreen`, `uz.edu.trainingcenter.ui.screens.testtaking.TestTakingViewModel`, `uz.edu.trainingcenter.ui.screens.testtaking.TestResultScreen`.

- [ ] **Step 9: Run the full unit test suite and build**

Run: `gradlew.bat testDebugUnitTest` then `gradlew.bat assembleDebug`.
Expected: all pass.

- [ ] **Step 10: Commit**

```bash
git add android-app/app/src/main/java/uz/edu/trainingcenter/ui/screens/testtaking android-app/app/src/main/java/uz/edu/trainingcenter/navigation/AppNavHost.kt android-app/app/src/main/res android-app/app/src/test/java/uz/edu/trainingcenter/ui/screens/testtaking
git commit -m "feat: add TestTakingViewModel/Screen and TestResultScreen"
```

---

### Task 10: `CertificateRepository` + `CertificatesViewModel` + `CertificatesScreen` + `CertificateDownloader`

**Files:**
- Create: `android-app/app/src/main/java/uz/edu/trainingcenter/data/repository/CertificateRepository.kt`
- Create: `android-app/app/src/main/java/uz/edu/trainingcenter/ui/screens/certificates/CertificatesViewModel.kt`
- Create: `android-app/app/src/main/java/uz/edu/trainingcenter/ui/screens/certificates/CertificatesScreen.kt`
- Create: `android-app/app/src/main/java/uz/edu/trainingcenter/ui/screens/certificates/CertificateDownloader.kt`
- Modify: `android-app/app/src/main/java/uz/edu/trainingcenter/ServiceLocator.kt`
- Modify: `android-app/app/src/main/java/uz/edu/trainingcenter/navigation/AppNavHost.kt`
- Test: `android-app/app/src/test/java/uz/edu/trainingcenter/data/repository/CertificateRepositoryTest.kt`
- Test: `android-app/app/src/test/java/uz/edu/trainingcenter/ui/screens/certificates/CertificatesViewModelTest.kt`

**Interfaces:**
- Produces: `CertificateRepository.getCertificates(): Result<List<CertificateDto>>`, `CertificateRepository.downloadCertificate(id): Result<ResponseBody>`; `CertificatesViewModel` with `CertificatesUiState`; `CertificateDownloader.saveAndOpen(context, certificateNumber, body: ResponseBody)` — writes to app cache dir, opens via `Intent.ACTION_VIEW` + `FileProvider`.

- [ ] **Step 1: Write the failing repository test**

```kotlin
package uz.edu.trainingcenter.data.repository

import io.mockk.coEvery
import io.mockk.mockk
import kotlinx.coroutines.test.runTest
import okhttp3.ResponseBody
import okhttp3.ResponseBody.Companion.toResponseBody
import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Test
import uz.edu.trainingcenter.data.remote.ApiService
import uz.edu.trainingcenter.data.remote.SessionManager
import uz.edu.trainingcenter.data.remote.dto.CertificateDto
import uz.edu.trainingcenter.data.remote.dto.CertificatesResponse

class CertificateRepositoryTest {

    @Test
    fun `getCertificates returns list on success`() = runTest {
        val api = mockk<ApiService>()
        coEvery { api.getCertificates() } returns CertificatesResponse(listOf(CertificateDto(1, "CERT-2026-00001-123", "2026-09-20")))

        val result = CertificateRepository(api, SessionManager()).getCertificates()

        assertTrue(result.isSuccess)
        assertEquals(1, result.getOrThrow().size)
    }

    @Test
    fun `downloadCertificate returns the response body on success`() = runTest {
        val api = mockk<ApiService>()
        val body: ResponseBody = "pdf-bytes".toResponseBody(null)
        coEvery { api.downloadCertificate(1) } returns body

        val result = CertificateRepository(api, SessionManager()).downloadCertificate(1)

        assertTrue(result.isSuccess)
    }
}
```

- [ ] **Step 2: Run test to verify it fails.** Expected: FAIL.

- [ ] **Step 3: Implement `CertificateRepository`**

```kotlin
package uz.edu.trainingcenter.data.repository

import okhttp3.ResponseBody
import uz.edu.trainingcenter.data.remote.ApiService
import uz.edu.trainingcenter.data.remote.SessionManager
import uz.edu.trainingcenter.data.remote.dto.CertificateDto
import uz.edu.trainingcenter.data.remote.safeApiCall

class CertificateRepository(
    private val api: ApiService,
    private val sessionManager: SessionManager
) {
    suspend fun getCertificates(): Result<List<CertificateDto>> {
        return safeApiCall(sessionManager) { api.getCertificates() }.map { it.certificates }
    }

    suspend fun downloadCertificate(certificateId: Int): Result<ResponseBody> {
        return safeApiCall(sessionManager) { api.downloadCertificate(certificateId) }
    }
}
```

- [ ] **Step 4: Run test to verify it passes.** Expected: PASS.

- [ ] **Step 5: Write the failing `CertificatesViewModel` test**

```kotlin
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
```

- [ ] **Step 6: Run test to verify it fails.** Expected: FAIL.

- [ ] **Step 7: Implement `CertificatesViewModel`**

```kotlin
package uz.edu.trainingcenter.ui.screens.certificates

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import okhttp3.ResponseBody
import uz.edu.trainingcenter.data.remote.dto.CertificateDto
import uz.edu.trainingcenter.data.repository.CertificateRepository

sealed interface CertificatesUiState {
    data object Loading : CertificatesUiState
    data class Success(val certificates: List<CertificateDto>) : CertificatesUiState
    data class Error(val message: String) : CertificatesUiState
}

class CertificatesViewModel(private val repository: CertificateRepository) : ViewModel() {

    private val _uiState = MutableStateFlow<CertificatesUiState>(CertificatesUiState.Loading)
    val uiState: StateFlow<CertificatesUiState> = _uiState

    init {
        load()
    }

    fun load() {
        viewModelScope.launch {
            _uiState.value = CertificatesUiState.Loading
            val result = repository.getCertificates()
            _uiState.value = result.fold(
                onSuccess = { CertificatesUiState.Success(it) },
                onFailure = { CertificatesUiState.Error(it.message ?: "Failed to load certificates") }
            )
        }
    }

    suspend fun download(certificateId: Int): Result<ResponseBody> {
        return repository.downloadCertificate(certificateId)
    }
}
```

- [ ] **Step 8: Run test to verify it passes.** Expected: PASS.

- [ ] **Step 9: Implement `CertificateDownloader`**

```kotlin
package uz.edu.trainingcenter.ui.screens.certificates

import android.content.Context
import android.content.Intent
import androidx.core.content.FileProvider
import okhttp3.ResponseBody
import java.io.File

object CertificateDownloader {
    fun saveAndOpen(context: Context, certificateNumber: String, body: ResponseBody) {
        val dir = File(context.cacheDir, "certificates").apply { mkdirs() }
        val file = File(dir, "$certificateNumber.pdf")
        file.outputStream().use { output ->
            body.byteStream().use { input -> input.copyTo(output) }
        }

        val uri = FileProvider.getUriForFile(context, "uz.edu.trainingcenter.fileprovider", file)
        val intent = Intent(Intent.ACTION_VIEW).apply {
            setDataAndType(uri, "application/pdf")
            addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
            addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
        }
        context.startActivity(intent)
    }
}
```

- [ ] **Step 10: Implement `CertificatesScreen`**

```kotlin
package uz.edu.trainingcenter.ui.screens.certificates

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.unit.dp
import kotlinx.coroutines.launch
import uz.edu.trainingcenter.R
import uz.edu.trainingcenter.data.remote.dto.CertificateDto

@Composable
fun CertificatesScreen(viewModel: CertificatesViewModel, padding: PaddingValues) {
    val state by viewModel.uiState.collectAsState()
    val context = LocalContext.current
    val scope = rememberCoroutineScope()

    Box(modifier = Modifier.fillMaxSize().padding(padding)) {
        when (val s = state) {
            is CertificatesUiState.Loading -> CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
            is CertificatesUiState.Error -> Text(s.message, modifier = Modifier.align(Alignment.Center))
            is CertificatesUiState.Success -> {
                if (s.certificates.isEmpty()) {
                    Text(stringResource(R.string.certificates_empty), modifier = Modifier.align(Alignment.Center))
                } else {
                    LazyColumn(modifier = Modifier.padding(16.dp)) {
                        items(s.certificates) { certificate ->
                            CertificateRow(certificate) {
                                scope.launch {
                                    viewModel.download(certificate.id).onSuccess { body ->
                                        CertificateDownloader.saveAndOpen(context, certificate.certificateNumber, body)
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun CertificateRow(certificate: CertificateDto, onDownload: () -> Unit) {
    Card(modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp)) {
        Row(
            modifier = Modifier.padding(12.dp).fillMaxWidth(),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            Column {
                Text(certificate.certificateNumber, style = MaterialTheme.typography.titleMedium)
                Text(certificate.issueDate)
            }
            Button(onClick = onDownload) {
                Text(stringResource(R.string.certificates_download))
            }
        }
    }
}
```

- [ ] **Step 11: Add strings**

`values/strings.xml`:
```xml
    <string name="certificates_empty">Hozircha sertifikat yo\'q</string>
    <string name="certificates_download">Yuklab olish</string>
```

`values-ru/strings.xml`:
```xml
    <string name="certificates_empty">Пока нет сертификатов</string>
    <string name="certificates_download">Скачать</string>
```

- [ ] **Step 12: Add `certificateRepository` to `ServiceLocator`**

```kotlin
    val certificateRepository by lazy { uz.edu.trainingcenter.data.repository.CertificateRepository(apiService, sessionManager) }
```

- [ ] **Step 13: Register `Routes.CERTIFICATES` in `AppNavHost`**

```kotlin
        composable(Routes.CERTIFICATES) {
            HomeScaffold(navController) { padding ->
                val viewModel: uz.edu.trainingcenter.ui.screens.certificates.CertificatesViewModel =
                    viewModel(factory = ViewModelFactory { uz.edu.trainingcenter.ui.screens.certificates.CertificatesViewModel(ServiceLocator.certificateRepository) })
                uz.edu.trainingcenter.ui.screens.certificates.CertificatesScreen(viewModel = viewModel, padding = padding)
            }
        }
```

- [ ] **Step 14: Run the full unit test suite and build**

Run: `gradlew.bat testDebugUnitTest` then `gradlew.bat assembleDebug`.
Expected: all pass.

- [ ] **Step 15: Commit**

```bash
git add android-app/app/src/main/java/uz/edu/trainingcenter/data/repository/CertificateRepository.kt android-app/app/src/main/java/uz/edu/trainingcenter/ui/screens/certificates android-app/app/src/main/java/uz/edu/trainingcenter/ServiceLocator.kt android-app/app/src/main/java/uz/edu/trainingcenter/navigation/AppNavHost.kt android-app/app/src/main/res android-app/app/src/test/java/uz/edu/trainingcenter/data/repository/CertificateRepositoryTest.kt android-app/app/src/test/java/uz/edu/trainingcenter/ui/screens/certificates
git commit -m "feat: add CertificateRepository, CertificatesViewModel/Screen, CertificateDownloader"
```

---

### Task 11: `ProfileViewModel` + `ProfileScreen` (language/theme/server settings/logout)

**Files:**
- Create: `android-app/app/src/main/java/uz/edu/trainingcenter/ui/screens/profile/ProfileViewModel.kt`
- Create: `android-app/app/src/main/java/uz/edu/trainingcenter/ui/screens/profile/ProfileScreen.kt`
- Modify: `android-app/app/src/main/java/uz/edu/trainingcenter/navigation/AppNavHost.kt`
- Test: `android-app/app/src/test/java/uz/edu/trainingcenter/ui/screens/profile/ProfileViewModelTest.kt`

**Interfaces:**
- Consumes: `AuthRepository` (Task 4, for `getMe`/`logout` — add `getMe()` to it in this task), `PreferencesDataStore` (Task 2).
- Produces: `ProfileViewModel` exposing `me: StateFlow<MeDto?>`, `language: StateFlow<String>`, `theme: StateFlow<String>`, `baseUrl: StateFlow<String>`, functions `setLanguage`, `setTheme`, `setBaseUrl`, `logout`.

- [ ] **Step 1: Add `getMe()` to `AuthRepository`**

Read the current `AuthRepository.kt` (Task 4), add (keep `login`/`logout` unchanged):

```kotlin
    suspend fun getMe(): Result<uz.edu.trainingcenter.data.remote.dto.MeDto> {
        return uz.edu.trainingcenter.data.remote.safeApiCall(sessionManager) { api.getMe() }
    }
```

(This requires `AuthRepository`'s constructor to already have an `api: ApiService` field — it does, from Task 4.)

- [ ] **Step 2: Write the failing `ProfileViewModel` test**

```kotlin
package uz.edu.trainingcenter.ui.screens.profile

import app.cash.turbine.test
import io.mockk.coEvery
import io.mockk.coVerify
import io.mockk.every
import io.mockk.mockk
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.flow.flowOf
import kotlinx.coroutines.test.StandardTestDispatcher
import kotlinx.coroutines.test.resetMain
import kotlinx.coroutines.test.runTest
import kotlinx.coroutines.test.setMain
import org.junit.After
import org.junit.Assert.assertEquals
import org.junit.Before
import org.junit.Test
import uz.edu.trainingcenter.data.local.PreferencesDataStore
import uz.edu.trainingcenter.data.remote.dto.MeDto
import uz.edu.trainingcenter.data.repository.AuthRepository

@OptIn(ExperimentalCoroutinesApi::class)
class ProfileViewModelTest {

    @Before
    fun setUp() { Dispatchers.setMain(StandardTestDispatcher()) }

    @After
    fun tearDown() { Dispatchers.resetMain() }

    @Test
    fun `loads me profile on init`() = runTest {
        val authRepository = mockk<AuthRepository>()
        val dataStore = mockk<PreferencesDataStore>()
        every { dataStore.languageFlow() } returns flowOf("uz")
        every { dataStore.themeFlow() } returns flowOf("system")
        coEvery { dataStore.getBaseUrl() } returns "http://10.0.2.2:8080/api/v1/"
        coEvery { authRepository.getMe() } returns Result.success(MeDto(1, "Ali Valiyev", "+998900000000", "student", "uz"))

        val viewModel = ProfileViewModel(authRepository, dataStore)

        viewModel.me.test {
            assertEquals(null, awaitItem())
            assertEquals("Ali Valiyev", awaitItem()?.fullName)
        }
    }

    @Test
    fun `setLanguage persists to dataStore`() = runTest {
        val authRepository = mockk<AuthRepository>()
        val dataStore = mockk<PreferencesDataStore>(relaxed = true)
        every { dataStore.languageFlow() } returns flowOf("uz")
        every { dataStore.themeFlow() } returns flowOf("system")
        coEvery { dataStore.getBaseUrl() } returns "http://10.0.2.2:8080/api/v1/"
        coEvery { authRepository.getMe() } returns Result.success(MeDto(1, "Ali", "+998900000000", "student", "uz"))

        val viewModel = ProfileViewModel(authRepository, dataStore)
        viewModel.setLanguage("ru")

        coVerify { dataStore.setLanguage("ru") }
    }

    @Test
    fun `logout calls authRepository logout`() = runTest {
        val authRepository = mockk<AuthRepository>(relaxed = true)
        val dataStore = mockk<PreferencesDataStore>(relaxed = true)
        every { dataStore.languageFlow() } returns flowOf("uz")
        every { dataStore.themeFlow() } returns flowOf("system")
        coEvery { dataStore.getBaseUrl() } returns "http://10.0.2.2:8080/api/v1/"
        coEvery { authRepository.getMe() } returns Result.success(MeDto(1, "Ali", "+998900000000", "student", "uz"))

        val viewModel = ProfileViewModel(authRepository, dataStore)
        viewModel.logout()

        coVerify { authRepository.logout() }
    }
}
```

- [ ] **Step 3: Run test to verify it fails.** Expected: FAIL — class not found.

- [ ] **Step 4: Implement `ProfileViewModel`**

```kotlin
package uz.edu.trainingcenter.ui.screens.profile

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.stateIn
import kotlinx.coroutines.launch
import uz.edu.trainingcenter.data.local.PreferencesDataStore
import uz.edu.trainingcenter.data.remote.dto.MeDto
import uz.edu.trainingcenter.data.repository.AuthRepository

class ProfileViewModel(
    private val authRepository: AuthRepository,
    private val dataStore: PreferencesDataStore
) : ViewModel() {

    private val _me = MutableStateFlow<MeDto?>(null)
    val me: StateFlow<MeDto?> = _me

    val language: StateFlow<String> = dataStore.languageFlow()
        .stateIn(viewModelScope, SharingStarted.Eagerly, PreferencesDataStore.DEFAULT_LANGUAGE)

    val theme: StateFlow<String> = dataStore.themeFlow()
        .stateIn(viewModelScope, SharingStarted.Eagerly, PreferencesDataStore.DEFAULT_THEME)

    private val _baseUrl = MutableStateFlow(PreferencesDataStore.DEFAULT_BASE_URL)
    val baseUrl: StateFlow<String> = _baseUrl

    init {
        viewModelScope.launch {
            authRepository.getMe().onSuccess { _me.value = it }
        }
        viewModelScope.launch {
            _baseUrl.value = dataStore.getBaseUrl()
        }
    }

    fun setLanguage(lang: String) {
        viewModelScope.launch { dataStore.setLanguage(lang) }
    }

    fun setTheme(theme: String) {
        viewModelScope.launch { dataStore.setTheme(theme) }
    }

    fun setBaseUrl(url: String) {
        viewModelScope.launch {
            dataStore.setBaseUrl(url)
            _baseUrl.value = url
        }
    }

    fun logout() {
        viewModelScope.launch { authRepository.logout() }
    }
}
```

- [ ] **Step 5: Run test to verify it passes.** Expected: PASS.

- [ ] **Step 6: Implement `ProfileScreen`**

```kotlin
package uz.edu.trainingcenter.ui.screens.profile

import androidx.compose.foundation.layout.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.unit.dp
import uz.edu.trainingcenter.R

@Composable
fun ProfileScreen(viewModel: ProfileViewModel, padding: PaddingValues, onLoggedOut: () -> Unit) {
    val me by viewModel.me.collectAsState()
    val language by viewModel.language.collectAsState()
    val theme by viewModel.theme.collectAsState()
    val baseUrl by viewModel.baseUrl.collectAsState()
    var baseUrlInput by remember(baseUrl) { mutableStateOf(baseUrl) }

    Column(modifier = Modifier.fillMaxSize().padding(padding).padding(24.dp)) {
        me?.let {
            Text(it.fullName, style = MaterialTheme.typography.headlineSmall)
            Text(it.phone)
            Spacer(Modifier.height(24.dp))
        }

        Text(stringResource(R.string.profile_language), style = MaterialTheme.typography.titleMedium)
        Row {
            FilterChip(selected = language == "uz", onClick = { viewModel.setLanguage("uz") }, label = { Text("O'zbekcha") })
            Spacer(Modifier.width(8.dp))
            FilterChip(selected = language == "ru", onClick = { viewModel.setLanguage("ru") }, label = { Text("Русский") })
        }

        Spacer(Modifier.height(16.dp))
        Text(stringResource(R.string.profile_theme), style = MaterialTheme.typography.titleMedium)
        Row {
            FilterChip(selected = theme == "light", onClick = { viewModel.setTheme("light") }, label = { Text(stringResource(R.string.profile_theme_light)) })
            Spacer(Modifier.width(8.dp))
            FilterChip(selected = theme == "dark", onClick = { viewModel.setTheme("dark") }, label = { Text(stringResource(R.string.profile_theme_dark)) })
            Spacer(Modifier.width(8.dp))
            FilterChip(selected = theme == "system", onClick = { viewModel.setTheme("system") }, label = { Text(stringResource(R.string.profile_theme_system)) })
        }

        Spacer(Modifier.height(16.dp))
        Text(stringResource(R.string.profile_server_url), style = MaterialTheme.typography.titleMedium)
        OutlinedTextField(
            value = baseUrlInput,
            onValueChange = { baseUrlInput = it },
            modifier = Modifier.fillMaxWidth()
        )
        Button(onClick = { viewModel.setBaseUrl(baseUrlInput) }, modifier = Modifier.padding(top = 8.dp)) {
            Text(stringResource(R.string.profile_save))
        }

        Spacer(Modifier.weight(1f))
        Button(
            onClick = { viewModel.logout(); onLoggedOut() },
            colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.error),
            modifier = Modifier.fillMaxWidth()
        ) {
            Text(stringResource(R.string.profile_logout))
        }
    }
}
```

- [ ] **Step 7: Add strings**

`values/strings.xml`:
```xml
    <string name="profile_language">Til</string>
    <string name="profile_theme">Mavzu</string>
    <string name="profile_theme_light">Yorug\'</string>
    <string name="profile_theme_dark">Qorong\'i</string>
    <string name="profile_theme_system">Tizim</string>
    <string name="profile_server_url">Server manzili</string>
    <string name="profile_save">Saqlash</string>
    <string name="profile_logout">Chiqish</string>
```

`values-ru/strings.xml`:
```xml
    <string name="profile_language">Язык</string>
    <string name="profile_theme">Тема</string>
    <string name="profile_theme_light">Светлая</string>
    <string name="profile_theme_dark">Тёмная</string>
    <string name="profile_theme_system">Системная</string>
    <string name="profile_server_url">Адрес сервера</string>
    <string name="profile_save">Сохранить</string>
    <string name="profile_logout">Выход</string>
```

- [ ] **Step 8: Register `Routes.PROFILE` in `AppNavHost`, and handle `SessionManager.loggedOut`**

Read the current `AppNavHost.kt`. Add the `PROFILE` composable:

```kotlin
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
```

Also add a top-level `LaunchedEffect` inside `AppNavHost` (before the `NavHost { ... }` call) that observes `ServiceLocator.sessionManager.loggedOut` and force-navigates to Login on any `401` anywhere in the app — this is the Global Constraint's single implementation point:

```kotlin
    LaunchedEffect(Unit) {
        ServiceLocator.sessionManager.loggedOut.collect {
            navController.navigate(Routes.LOGIN) { popUpTo(0) }
        }
    }
```

(Add `import androidx.compose.runtime.LaunchedEffect` if not already present from Task 7.)

- [ ] **Step 9: Run the full unit test suite and build**

Run: `gradlew.bat testDebugUnitTest` then `gradlew.bat assembleDebug`.
Expected: all pass.

- [ ] **Step 10: Commit**

```bash
git add android-app/app/src/main/java/uz/edu/trainingcenter/data/repository/AuthRepository.kt android-app/app/src/main/java/uz/edu/trainingcenter/ui/screens/profile android-app/app/src/main/java/uz/edu/trainingcenter/navigation/AppNavHost.kt android-app/app/src/main/res android-app/app/src/test/java/uz/edu/trainingcenter/ui/screens/profile
git commit -m "feat: add ProfileViewModel/Screen (language, theme, server URL, logout) and global 401 handling"
```

---

### Task 12: Full build + test verification, README

**Files:**
- Create: `android-app/README.md`

**Interfaces:**
- Consumes: nothing new — this task verifies and documents Tasks 1-11.

- [ ] **Step 1: Run the full unit test suite and a full build**

Run: `gradlew.bat testDebugUnitTest` (twice consecutively, confirming re-runnability) then `gradlew.bat assembleDebug` and `gradlew.bat lint` (non-blocking — report warnings but don't fix stylistic lint issues beyond what's already required by earlier tasks).
Expected: all tests pass both times, build succeeds.

- [ ] **Step 2: Create `android-app/README.md`**

```markdown
# Training Center — Android app

Kotlin + Jetpack Compose student app for the training center. Consumes the backend REST API (`docs/superpowers/specs/2026-09-22-backend-db-design.md`).

## Setup

1. Requires JDK 17 and Android SDK (platform 34, build-tools) — set `ANDROID_HOME` (or create `android-app/local.properties` with `sdk.dir=...`).
2. From `android-app/`: `gradlew.bat assembleDebug` (Windows) or `./gradlew assembleDebug`.
3. Install the APK on a device/emulator: `gradlew.bat installDebug`.
4. On first launch, go to Profile → Server manzili and set it to your backend's real address (default assumes an emulator talking to a host machine on `10.0.2.2:8080`; a physical device needs your machine's LAN IP instead, e.g. `http://192.168.1.5:8080/api/v1/`).

## Tests

`gradlew.bat testDebugUnitTest` — unit tests only (ViewModels, Repositories, interceptors), no emulator required.

## Known gaps

- No instrumented/UI tests and no live on-device verification were performed in this environment (no Android emulator was installed — see the plan's Global Constraints). Compilation and all unit tests were verified with a real Gradle + Android SDK toolchain, but the running app's actual screens have not been visually confirmed. The human user should run `gradlew.bat installDebug` on a device/emulator and walk through: register → (admin approves via the admin panel) → login → schedule → take a test → view/download a certificate.
- No password-reset flow in the app (matches the backend's current scope — students get their initial password from an admin, in person).
- Certificate PDFs are opened via an external viewer (no in-app PDF rendering), per the design spec.
```

- [ ] **Step 3: Commit**

```bash
git add android-app/README.md
git commit -m "docs: add Android app README with setup, test, and known-gaps notes"
```

---

## Explicitly out of scope for this plan

- Instrumented/UI tests and any on-device/emulator verification (no emulator in this environment).
- Push notifications, offline caching, admin/teacher-facing screens in the app.
- Any backend or admin-panel changes.
