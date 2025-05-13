import 'package:flutter/material.dart';
import '../components/generic_webview.dart';

/// WebView screen for analytics and reporting
/// This uses the generic WebView component to load the mobile analytics page
/// with consistent authentication handling.
class AnalyticsView extends StatelessWidget {
  const AnalyticsView({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return const GenericWebView(
      phpEndpoint: 'mobile_analytics.php',
      title: 'Analytics',
      additionalParams: {
        'active_tab': 'monthly',  // Default to monthly tab
      },
    );
  }
} 