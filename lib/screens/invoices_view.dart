import 'package:flutter/material.dart';
import '../components/generic_webview.dart';

/// WebView screen for viewing and managing invoices
/// This uses the generic WebView component to load the mobile invoices page
class InvoicesView extends StatelessWidget {
  const InvoicesView({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return const GenericWebView(
      phpEndpoint: 'mobile_invoices.php',
      title: 'Invoices',
    );
  }
} 