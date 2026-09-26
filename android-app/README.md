# Training Center — Android app

Kotlin + Jetpack Compose student app for the training center. Consumes the backend's JSON
REST API, and gives admin/teacher accounts a WebView entry into the same backend's web
admin panel.

## Setup

1. Requires JDK 17 and Android SDK (platform 34, build-tools) — set `ANDROID_HOME` (or create
   `android-app/local.properties` with `sdk.dir=...`).
2. From `android-app/`: `gradlew.bat assembleDebug` (Windows) or `./gradlew assembleDebug`.
3. Install the APK on a device/emulator: `gradlew.bat installDebug`.
4. On first launch, go to Profile → Server manzili and set it to the backend's address,
   including the `/api/v1/` suffix (e.g. `https://training-center.up.railway.app/api/v1/`;
   an emulator talking to a local dev server on the host machine uses `10.0.2.2` instead of
   `localhost`).

## Tests

`gradlew.bat testDebugUnitTest` — unit tests only (ViewModels, Repositories, interceptors), no
emulator required.

## Features

- Student: registration (with technika brand selection and a photo upload), login, professions
  catalog, schedule, tests, certificates, news, media gallery, profile settings.
- Admin/teacher: a "Men admin yoki o'qituvchiman" entry point on the login screen opens the
  full web admin panel inside an in-app WebView.
