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
