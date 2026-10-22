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
import 'package:permission_handler/permission_handler.dart';
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

/// Shows a farm notification; an alarm rings on until touched, at most a minute each time
/// (the server sends it again every two minutes until it is marked done).
Future<void> showFarm(RemoteMessage message) async {
  final data = message.data;
  final title = message.notification?.title ?? data['title'] ?? 'মাটির খামার';
  final body = message.notification?.body ?? data['body'] ?? '';
  final ring = data['alarm'] == '1' || data['alarm'] == 'true';
  final buttons = <AndroidNotificationAction>[];
  try {
    for (final a in (jsonDecode(data['actions'] ?? '[]') as List)) {
      if (a is Map && a['url'] is String && a['title'] is String) {
        buttons.add(AndroidNotificationAction(a['url'] as String, a['title'] as String, cancelNotification: true));
      }
    }
  } catch (_) {}

  await notifications.show(
    (data['tag'] ?? title).hashCode,
    title,
    body,
    NotificationDetails(
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
    ),
    payload: data['url'],
  );
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
      home: const SitePage(),
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
                  applicationNameForUserAgent: 'MatirKhamarApp/3',
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
