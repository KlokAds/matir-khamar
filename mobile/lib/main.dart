import 'dart:async';

import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_inappwebview/flutter_inappwebview.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:permission_handler/permission_handler.dart';
import 'package:url_launcher/url_launcher.dart';

/// The farm's site, shown whole inside the app, so every change on the site is in the app at once.
const site = 'https://khamar.klokads.com';
const brown = Color(0xFF4A3726);
const paper = Color(0xFFF4EDE1);

final notifications = FlutterLocalNotificationsPlugin();

/// "khamar_alerts" pops up with sound like a chat message; "khamar_alarm" rings like a clock.
const alerts = AndroidNotificationChannel(
  'khamar_alerts',
  'খামারের খবর',
  description: 'কাজের মনে করানো, দুধ, টাকা — মেসেজের মতো শব্দ করে আসে',
  importance: Importance.max,
  playSound: true,
  enableVibration: true,
);
final alarm = AndroidNotificationChannel(
  'khamar_alarm',
  'জরুরি অ্যালার্ম',
  description: 'যে মনে করানোয় অ্যালার্ম দেওয়া — ঘড়ির মতো বাজে',
  importance: Importance.max,
  playSound: true,
  enableVibration: true,
  sound: const UriAndroidNotificationSound('content://settings/system/alarm_alert'),
  audioAttributesUsage: AudioAttributesUsage.alarm,
);

/// Where a tapped notification should open (set before the page is ready, read once it is).
final opened = StreamController<String>.broadcast();
String? openFirst;

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  SystemChrome.setSystemUIOverlayStyle(const SystemUiOverlayStyle(statusBarColor: brown, statusBarIconBrightness: Brightness.light));
  await Firebase.initializeApp();

  final android = notifications.resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>();
  await android?.createNotificationChannel(alerts);
  await android?.createNotificationChannel(alarm);
  await notifications.initialize(
    const InitializationSettings(android: AndroidInitializationSettings('ic_notification')),
    onDidReceiveNotificationResponse: (response) => go(response.payload),
  );

  // Tapped while the app was closed: open that page once it starts.
  final first = await FirebaseMessaging.instance.getInitialMessage();
  openFirst = first?.data['url'];
  final launch = await notifications.getNotificationAppLaunchDetails();
  if (launch?.didNotificationLaunchApp == true) {
    openFirst ??= launch!.notificationResponse?.payload;
  }

  FirebaseMessaging.onMessageOpenedApp.listen((message) => go(message.data['url']));

  // In the open app Android shows nothing by itself, so the app shows it.
  FirebaseMessaging.onMessage.listen((message) {
    final data = message.data;
    final title = message.notification?.title ?? data['title'] ?? 'মাটির খামার';
    final body = message.notification?.body ?? data['body'] ?? '';
    final ring = data['alarm'] == '1' || data['alarm'] == 'true';
    notifications.show(
      (data['tag'] ?? title).hashCode,
      title,
      body,
      NotificationDetails(
        android: AndroidNotificationDetails(
          ring ? alarm.id : alerts.id,
          ring ? alarm.name : alerts.name,
          importance: Importance.max,
          priority: Priority.max,
          icon: 'ic_notification',
          color: brown,
          styleInformation: BigTextStyleInformation(body),
          category: ring ? AndroidNotificationCategory.alarm : AndroidNotificationCategory.message,
          fullScreenIntent: ring,
        ),
      ),
      payload: data['url'],
    );
  });

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
                  applicationNameForUserAgent: 'MatirKhamarApp/2',
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
