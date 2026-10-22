#!/usr/bin/env bash
# Makes and builds the Android app on the GitHub runner (run from mobile/). On a failure the
# last lines of the log become an annotation, readable without signing in to GitHub.
set -o pipefail

log="$RUNNER_TEMP/build.log"

fail() {
    echo "::error title=APK build failed at: $1::$(tail -n 60 "$log" | grep -v '^\s*at ' | tail -n 35 | sed 's/%/%25/g' | awk '{printf "%s%%0A", $0}')"
    exit 1
}

run() {
    local step="$1"
    shift
    echo "== $step"
    "$@" 2>&1 | tee -a "$log" || fail "$step"
}

: > "$log"
run "flutter create" flutter create --org com.klokads --project-name matirkhamar --platforms android .
rm -f test/widget_test.dart
run "patch android" python3 tool/patch_android.py
mkdir -p assets
cp ../public/icons/icon-512.png assets/icon.png
echo "$GOOGLE_SERVICES_JSON" | base64 -d > android/app/google-services.json || fail "google-services.json"
echo "$KEYSTORE_BASE64" | base64 -d > "$KEYSTORE_FILE" || fail "keystore"
run "pub get" flutter pub get
run "icons" dart run flutter_launcher_icons
run "build apk" flutter build apk --release --build-number=$((100 + GITHUB_RUN_NUMBER)) --build-name="2.0.$GITHUB_RUN_NUMBER"
cp build/app/outputs/flutter-apk/app-release.apk matir-khamar.apk
