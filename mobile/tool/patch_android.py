"""Turns the android/ folder that `flutter create` makes into the farm app's: the package name,
Firebase, signing with the farm's key (so it installs over the earlier app), permissions and
the notification icon. Run from mobile/ after `flutter create`."""

import re
from pathlib import Path


def patch(path, old, new, count=1):
    text = Path(path).read_text(encoding='utf8')
    if not re.search(old, text):
        raise SystemExit(f'{path}: pattern not found: {old}')
    Path(path).write_text(re.sub(old, new, text, count=count), encoding='utf8')


settings = 'android/settings.gradle.kts'
patch(settings, r'(\n(\s*)id\("com\.android\.application"\)[^\n]*)',
      r'\1\n\2id("com.google.gms.google-services") version "4.4.2" apply false')

app = 'android/app/build.gradle.kts'
patch(app, r'(\n(\s*)id\("com\.android\.application"\))', r'\1\n\2id("com.google.gms.google-services")')
patch(app, r'minSdk = [^\n]+', 'minSdk = 23')
patch(app, r'compileOptions \{', 'compileOptions {\n        isCoreLibraryDesugaringEnabled = true')
patch(app, r'(\n(\s*)buildTypes \{)', r'''
    signingConfigs {
        create("release") {
            storeFile = file(System.getenv("KEYSTORE_FILE"))
            storePassword = System.getenv("KEYSTORE_PASSWORD")
            keyAlias = "khamar"
            keyPassword = System.getenv("KEYSTORE_PASSWORD")
        }
    }
\1''')
patch(app, r'signingConfig = signingConfigs\.getByName\("debug"\)', 'signingConfig = signingConfigs.getByName("release")')
Path(app).write_text(Path(app).read_text(encoding='utf8') + '''
dependencies {
    coreLibraryDesugaring("com.android.tools:desugar_jdk_libs:2.1.4")
}
''', encoding='utf8')

manifest = 'android/app/src/main/AndroidManifest.xml'
patch(manifest, r'(<manifest[^>]*>)', r'''\1
    <uses-permission android:name="android.permission.INTERNET" />
    <uses-permission android:name="android.permission.CAMERA" />
    <uses-permission android:name="android.permission.POST_NOTIFICATIONS" />
    <uses-permission android:name="android.permission.VIBRATE" />
    <uses-permission android:name="android.permission.USE_FULL_SCREEN_INTENT" />
    <uses-permission android:name="android.permission.WAKE_LOCK" />
    <uses-permission android:name="android.permission.USE_BIOMETRIC" />''')

# The fingerprint prompt needs a FragmentActivity.
activity = next(Path('android/app/src/main/kotlin').rglob('MainActivity.kt'))
patch(activity, r'io\.flutter\.embedding\.android\.FlutterActivity', 'io.flutter.embedding.android.FlutterFragmentActivity')
patch(activity, r':\s*FlutterActivity\(\)', ': FlutterFragmentActivity()')
patch(manifest, r'android:label="[^"]*"', 'android:label="মাটির খামার"')
patch(manifest, r'(\n(\s*)<activity)', r'''
        <meta-data android:name="com.google.firebase.messaging.default_notification_channel_id" android:value="khamar_alerts" />
        <meta-data android:name="com.google.firebase.messaging.default_notification_icon" android:resource="@drawable/ic_notification" />
        <meta-data android:name="com.google.firebase.messaging.default_notification_color" android:resource="@color/khamar_brown" />\1''')
# Camera for photo uploads, and other apps (phone, WhatsApp, browser) for outside links.
patch(manifest, r'(<queries>)', r'''\1
        <intent>
            <action android:name="android.media.action.IMAGE_CAPTURE" />
        </intent>
        <intent>
            <action android:name="android.intent.action.VIEW" />
            <data android:scheme="https" />
        </intent>
        <intent>
            <action android:name="android.intent.action.DIAL" />
        </intent>''')

res = Path('android/app/src/main/res')
(res / 'drawable').mkdir(parents=True, exist_ok=True)
# A white leaf on a transparent ground: Android tints small notification icons itself.
(res / 'drawable' / 'ic_notification.xml').write_text('''<vector xmlns:android="http://schemas.android.com/apk/res/android"
    android:width="24dp" android:height="24dp" android:viewportWidth="24" android:viewportHeight="24">
    <path android:fillColor="#FFFFFFFF"
        android:pathData="M17,8C8,10 5.9,16.17 3.82,21.34L5.71,22L6.66,19.7C7.14,19.87 7.64,20 8,20C19,20 22,3 22,3C21,5 14,5.25 9,6.25C4,7.25 2,11.5 2,13.5C2,15.5 3.75,17.25 3.75,17.25C7,8 17,8 17,8Z" />
</vector>
''', encoding='utf8')
(res / 'values').mkdir(parents=True, exist_ok=True)
(res / 'values' / 'khamar_colors.xml').write_text('''<?xml version="1.0" encoding="utf-8"?>
<resources>
    <color name="khamar_brown">#4A3726</color>
</resources>
''', encoding='utf8')
print('android patched')
