import 'dart:async';
import 'dart:convert';
import 'dart:typed_data';

import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_inappwebview/flutter_inappwebview.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:http/http.dart' as http;
import 'package:local_auth/local_auth.dart';
import 'package:permission_handler/permission_handler.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:timezone/data/latest_all.dart' as tzdata;
import 'package:timezone/timezone.dart' as tz;
import 'package:url_launcher/url_launcher.dart';

/// The farm's site, shown whole inside the app, so every change on the site is in the app at once.
const site = 'https://khamar.klokads.com';
const brown = Color(0xFF4A3726);
const paper = Color(0xFFF4EDE1);

final notifications = FlutterLocalNotificationsPlugin();

/// Pops up with sound like a chat message.
const alerts = AndroidNotificationChannel(
  'khamar_alerts',
  'খামারের খবর',
  description: 'কাজের মনে করানো, দুধ, টাকা — মেসেজের মতো শব্দ করে আসে',
  importance: Importance.max,
  playSound: true,
  enableVibration: true,
);

/// Alarm reminders: the phone's usual sound, played over and over until the notification is touched.
const ringing = AndroidNotificationChannel(
  'khamar_ring',
  'অ্যালার্ম রিমাইন্ডার',
  description: 'অ্যালার্ম দেওয়া রিমাইন্ডার — ছোঁয়া পর্যন্ত বাজতে থাকে',
  importance: Importance.max,
  playSound: true,
  enableVibration: true,
);

/// Android's "insistent" flag: the sound repeats until the notification is touched.
const flagInsistent = 4;

/// Where a tapped notification should open (set before the page is ready, read once it is).
final opened = StreamController<String>.broadcast();
String? openFirst;

Future<void> setUpNotifications() async {
  final android = notifications.resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>();
  await android?.createNotificationChannel(alerts);
  await android?.createNotificationChannel(ringing);
  await notifications.initialize(
    const InitializationSettings(android: AndroidInitializationSettings('ic_notification')),
    onDidReceiveNotificationResponse: (response) async {
      if (await pressButton(response)) {
        return;
      }
      go(response.payload);
    },
    onDidReceiveBackgroundNotificationResponse: onButtonInBackground,
  );
}

/// "হয়ে গেছে" / "১ ঘণ্টা পরে" on the notification: a signed link, so no sign-in is needed.
Future<bool> pressButton(NotificationResponse response) async {
  final url = response.actionId;
  if (url == null || !url.startsWith('http')) {
    return false;
  }
  try {
    await http.post(Uri.parse(url), headers: {'Accept': 'application/json'});
  } catch (_) {
    // No network: the reminder simply rings again later.
  }
  return true;
}

@pragma('vm:entry-point')
void onButtonInBackground(NotificationResponse response) {
  pressButton(response);
}

/// How a farm notification looks; an alarm rings on until touched, at most a minute each time
/// (the server sends it again every two minutes until it is marked done).
NotificationDetails farmDetails(Map<String, dynamic> data, String body) {
  final ring = data['alarm'] == true || data['alarm'] == '1' || data['alarm'] == 'true';
  final buttons = <AndroidNotificationAction>[];
  try {
    final actions = data['actions'] is String ? jsonDecode(data['actions'] as String) : data['actions'];
    for (final a in (actions as List? ?? [])) {
      if (a is Map && a['url'] is String && a['title'] is String) {
        buttons.add(AndroidNotificationAction(a['url'] as String, a['title'] as String, cancelNotification: true));
      }
    }
  } catch (_) {}

  return NotificationDetails(
    android: AndroidNotificationDetails(
      ring ? ringing.id : alerts.id,
      ring ? ringing.name : alerts.name,
      importance: Importance.max,
      priority: Priority.max,
      icon: 'ic_notification',
      color: brown,
      styleInformation: BigTextStyleInformation(body),
      category: ring ? AndroidNotificationCategory.alarm : AndroidNotificationCategory.message,
      fullScreenIntent: ring,
      ongoing: ring,
      timeoutAfter: ring ? 60000 : null,
      additionalFlags: ring ? Int32List.fromList([flagInsistent]) : null,
      actions: buttons,
    ),
  );
}

Future<void> showFarm(RemoteMessage message) async {
  final data = message.data;
  final title = message.notification?.title ?? data['title'] ?? 'মাটির খামার';
  final body = message.notification?.body ?? data['body'] ?? '';

  // The phone already rang this reminder by itself (scheduled while online): the server's
  // first send is not rung again. Later alarm rings still are.
  final id = data['reminder_id'];
  if (id != null && (data['rings'] ?? '0') == '0') {
    final prefs = await SharedPreferences.getInstance();
    await prefs.reload();
    final at = DateTime.tryParse(prefs.getString('rang.$id') ?? '');
    if (at != null && !at.isAfter(DateTime.now().add(const Duration(minutes: 1)))) {
      return;
    }
  }

  await notifications.show((data['tag'] ?? title).hashCode, title, body, farmDetails(data, body), payload: data['url']);
}

/// Puts the coming week's reminders on the phone itself, so they ring even without internet.
/// Run whenever the app is online and signed in; it replaces what it put there before, so
/// changed and deleted reminders follow.
Future<void> scheduleReminders(List<dynamic> items) async {
  final prefs = await SharedPreferences.getInstance();
  for (final id in prefs.getStringList('scheduled') ?? <String>[]) {
    await notifications.cancel(int.parse(id));
  }
  for (final key in prefs.getKeys().where((k) => k.startsWith('rang.')).toList()) {
    await prefs.remove(key);
  }

  final android = notifications.resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>();
  final exact = await android?.canScheduleExactNotifications() ?? false;
  final ids = <String>[];
  for (final item in items) {
    if (item is! Map<String, dynamic>) {
      continue;
    }
    final at = DateTime.tryParse(item['at'] as String? ?? '');
    if (at == null || at.isBefore(DateTime.now())) {
      continue;
    }
    final id = (item['tag'] as String? ?? '').hashCode;
    final body = item['body'] as String? ?? '';
    await notifications.zonedSchedule(
      id,
      item['title'] as String? ?? 'মাটির খামার',
      body,
      tz.TZDateTime.from(at.toUtc(), tz.UTC),
      farmDetails(item, body),
      androidScheduleMode: exact ? AndroidScheduleMode.exactAllowWhileIdle : AndroidScheduleMode.inexactAllowWhileIdle,
      payload: item['url'] as String?,
    );
    ids.add('$id');
    await prefs.setString('rang.${item['reminder_id']}', at.toIso8601String());
  }
  await prefs.setStringList('scheduled', ids);
}

/// A message while the app is closed or in the background. Plain ones Android shows by itself;
/// alarms come as data only and the app rings them.
@pragma('vm:entry-point')
Future<void> onBackgroundMessage(RemoteMessage message) async {
  if (message.notification != null) {
    return;
  }
  await Firebase.initializeApp();
  await setUpNotifications();
  await showFarm(message);
}

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  SystemChrome.setSystemUIOverlayStyle(const SystemUiOverlayStyle(statusBarColor: brown, statusBarIconBrightness: Brightness.light));
  await Firebase.initializeApp();
  tzdata.initializeTimeZones();
  FirebaseMessaging.onBackgroundMessage(onBackgroundMessage);
  await setUpNotifications();

  // Tapped while the app was closed: open that page once it starts.
  final first = await FirebaseMessaging.instance.getInitialMessage();
  openFirst = first?.data['url'];
  final launch = await notifications.getNotificationAppLaunchDetails();
  if (launch?.didNotificationLaunchApp == true) {
    openFirst ??= launch!.notificationResponse?.payload;
  }

  FirebaseMessaging.onMessageOpenedApp.listen((message) => go(message.data['url']));

  // In the open app Android shows nothing by itself, so the app shows it.
  FirebaseMessaging.onMessage.listen(showFarm);

  runApp(const KhamarApp());
}

void go(String? url) {
  if (url != null && url.isNotEmpty) {
    opened.add(url);
  }
}

class KhamarApp extends StatelessWidget {
  const KhamarApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'মাটির খামার',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(colorSchemeSeed: brown, scaffoldBackgroundColor: paper),
      home: const LockGate(child: SitePage()),
    );
  }
}

/// The app stays signed in, so it asks for the fingerprint (or the phone's PIN or pattern) when
/// opened, and again after five minutes away. A phone with no screen lock opens straight in.
class LockGate extends StatefulWidget {
  const LockGate({super.key, required this.child});

  final Widget child;

  @override
  State<LockGate> createState() => _LockGateState();
}

class _LockGateState extends State<LockGate> with WidgetsBindingObserver {
  final auth = LocalAuthentication();
  bool locked = true;
  bool asking = false;
  DateTime? leftAt;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    unlock();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    // The fingerprint prompt itself sends the app to the background for a moment.
    if (asking) {
      return;
    }
    if (state == AppLifecycleState.paused) {
      leftAt = DateTime.now();
    } else if (state == AppLifecycleState.resumed && leftAt != null) {
      if (DateTime.now().difference(leftAt!) > const Duration(minutes: 5)) {
        setState(() => locked = true);
        unlock();
      }
      leftAt = null;
    }
  }

  Future<void> unlock() async {
    if (asking) {
      return;
    }
    asking = true;
    var ok = false;
    try {
      if (!await auth.isDeviceSupported()) {
        ok = true;
      } else {
        ok = await auth.authenticate(
          localizedReason: 'মাটির খামার খুলতে আঙুলের ছাপ দিন',
          options: const AuthenticationOptions(stickyAuth: true),
        );
      }
    } on PlatformException catch (e) {
      // No screen lock set on the phone: nothing to ask for.
      ok = e.code == 'NotAvailable' || e.code == 'PasscodeNotSet' || e.code == 'NotEnrolled';
    } finally {
      asking = false;
    }
    if (mounted) {
      setState(() => locked = !ok);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Stack(
      children: [
        widget.child,
        if (locked)
          Positioned.fill(
            child: ColoredBox(
              color: paper,
              child: SafeArea(
                child: Center(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Icon(Icons.fingerprint, size: 72, color: brown),
                      const SizedBox(height: 16),
                      const Text('মাটির খামার', style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold, color: brown)),
                      const SizedBox(height: 24),
                      FilledButton.icon(
                        style: FilledButton.styleFrom(backgroundColor: brown),
                        onPressed: unlock,
                        icon: const Icon(Icons.lock_open),
                        label: const Text('আঙুলের ছাপ দিয়ে খুলুন'),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
      ],
    );
  }
}

class SitePage extends StatefulWidget {
  const SitePage({super.key});

  @override
  State<SitePage> createState() => _SitePageState();
}

class _SitePageState extends State<SitePage> {
  InAppWebViewController? web;
  PullToRefreshController? refresh;
  StreamSubscription<String>? openSub;
  String? token;
  String? handedOver;
  double progress = 0;
  DateTime? syncedAt;

  @override
  void initState() {
    super.initState();
    refresh = PullToRefreshController(
      settings: PullToRefreshSettings(color: brown),
      onRefresh: () async => web?.reload(),
    );
    openSub = opened.stream.listen(open);
    askPermissions();
  }

  @override
  void dispose() {
    openSub?.cancel();
    super.dispose();
  }

  Future<void> askPermissions() async {
    await FirebaseMessaging.instance.requestPermission(alert: true, badge: true, sound: true);
    // Exact times for reminders scheduled on the phone (Android asks once, in its settings).
    final android = notifications.resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>();
    if (!(await android?.canScheduleExactNotifications() ?? true)) {
      await android?.requestExactAlarmsPermission();
    }
    await [Permission.camera].request();
    token = await FirebaseMessaging.instance.getToken();
    FirebaseMessaging.instance.onTokenRefresh.listen((fresh) {
      token = fresh;
      handOver();
    });
    handOver();
  }

  void open(String url) {
    final target = url.startsWith('http') ? url : '$site${url.startsWith('/') ? '' : '/'}$url';
    web?.loadUrl(urlRequest: URLRequest(url: WebUri(target)));
  }

  /// Fetches the coming week's reminders (as the signed-in person) and schedules them here.
  Future<void> syncReminders() async {
    final controller = web;
    if (controller == null || (syncedAt != null && DateTime.now().difference(syncedAt!) < const Duration(minutes: 1))) {
      return;
    }
    final url = await controller.getUrl();
    if (url == null || url.host != Uri.parse(site).host || url.path.startsWith('/login') || url.path.startsWith('/two-factor')) {
      return;
    }
    final result = await controller.callAsyncJavaScript(functionBody: '''
      const r = await fetch('/reminders/upcoming', { credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
      return r.ok ? await r.text() : null;
    ''');
    final text = result?.value;
    if (text is String) {
      syncedAt = DateTime.now();
      await scheduleReminders(jsonDecode(text) as List<dynamic>);
    }
  }

  /// Gives the server this phone's Firebase token under whoever is signed in, once per sign-in.
  Future<void> handOver() async {
    final controller = web;
    final current = token;
    if (controller == null || current == null || handedOver == current) {
      return;
    }
    final url = await controller.getUrl();
    if (url == null || url.host != Uri.parse(site).host) {
      return;
    }
    if (url.path.startsWith('/login') || url.path.startsWith('/two-factor')) {
      // Signed out: whoever signs in next gets this phone's notifications.
      handedOver = null;
      return;
    }
    final result = await controller.callAsyncJavaScript(
      functionBody: '''
        const m = document.cookie.match(/(?:^|;\\s*)XSRF-TOKEN=([^;]+)/);
        const r = await fetch('/push/app', {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': m ? decodeURIComponent(m[1]) : '',
          },
          body: JSON.stringify({ token }),
        });
        return r.status;
      ''',
      arguments: {'token': current},
    );
    if (result?.value == 204) {
      handedOver = current;
    }
  }

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, _) async {
        if (didPop) {
          return;
        }
        if (await web?.canGoBack() ?? false) {
          await web?.goBack();
        } else {
          await SystemNavigator.pop();
        }
      },
      child: Scaffold(
        body: SafeArea(
          child: Stack(
            children: [
              InAppWebView(
                initialUrlRequest: URLRequest(url: WebUri('$site${openFirst ?? '/dashboard'}')),
                pullToRefreshController: refresh,
                initialSettings: InAppWebViewSettings(
                  applicationNameForUserAgent: 'MatirKhamarApp/4',
                  javaScriptEnabled: true,
                  domStorageEnabled: true,
                  databaseEnabled: true,
                  thirdPartyCookiesEnabled: true,
                  allowFileAccess: true,
                  mediaPlaybackRequiresUserGesture: false,
                  useShouldOverrideUrlLoading: true,
                  useOnDownloadStart: true,
                  supportZoom: false,
                ),
                onWebViewCreated: (controller) => web = controller,
                onPermissionRequest: (controller, request) async =>
                    PermissionResponse(resources: request.resources, action: PermissionResponseAction.GRANT),
                shouldOverrideUrlLoading: (controller, action) async {
                  final uri = action.request.url;
                  if (uri == null) {
                    return NavigationActionPolicy.ALLOW;
                  }
                  // Phone calls, WhatsApp, maps and other sites open in their own apps.
                  if (!['http', 'https'].contains(uri.scheme) || uri.host != Uri.parse(site).host) {
                    await launchUrl(uri, mode: LaunchMode.externalApplication);
                    return NavigationActionPolicy.CANCEL;
                  }
                  return NavigationActionPolicy.ALLOW;
                },
                onDownloadStartRequest: (controller, request) async {
                  await launchUrl(request.url, mode: LaunchMode.externalApplication);
                },
                onProgressChanged: (controller, value) {
                  setState(() => progress = value / 100);
                  if (value == 100) {
                    refresh?.endRefreshing();
                  }
                },
                onLoadStop: (controller, url) async {
                  refresh?.endRefreshing();
                  if (openFirst != null) {
                    openFirst = null;
                  }
                  await handOver();
                  await syncReminders();
                },
                onReceivedError: (controller, request, error) => refresh?.endRefreshing(),
              ),
              if (progress < 1) LinearProgressIndicator(value: progress, minHeight: 2, color: brown, backgroundColor: Colors.transparent),
            ],
          ),
        ),
      ),
    );
  }
}
