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
# The Firebase file may be pasted as it is or in base64; both work.
if [[ "$(echo "$GOOGLE_SERVICES_JSON" | tr -d '[:space:]')" == \{* ]]; then
    printf '%s' "$GOOGLE_SERVICES_JSON" > android/app/google-services.json
else
    echo "$GOOGLE_SERVICES_JSON" | tr -d '[:space:]' | base64 -d > android/app/google-services.json 2>>"$log" || fail "google-services.json (not valid base64 or JSON)"
fi
python3 -c "import json,sys; json.load(open('android/app/google-services.json'))" 2>>"$log" || fail "google-services.json (not valid JSON)"
echo "$KEYSTORE_BASE64" | tr -d '[:space:]' | base64 -d > "$KEYSTORE_FILE" 2>>"$log" || fail "keystore (not valid base64)"
run "pub get" flutter pub get
run "icons" dart run flutter_launcher_icons
run "build apk" flutter build apk --release --build-number=$((100 + GITHUB_RUN_NUMBER)) --build-name="2.0.$GITHUB_RUN_NUMBER"
cp build/app/outputs/flutter-apk/app-release.apk matir-khamar.apk
