# মাটির খামার — Android app

The site (khamar.klokads.com) inside a Flutter app, with notifications through Firebase.

- Built only in GitHub Actions (`.github/workflows/android.yml`); no Java or Flutter needed locally.
- `flutter create` makes `android/` on the runner and `tool/patch_android.py` sets the package,
  Firebase, permissions and signing. Secrets: `KEYSTORE_BASE64`, `KEYSTORE_PASSWORD`,
  `GOOGLE_SERVICES_JSON` (base64).
- Each build is published as a release; the login page links to the latest APK.
- The server's Firebase key is in the `fcm.service_account` setting (encrypted), never in git.
