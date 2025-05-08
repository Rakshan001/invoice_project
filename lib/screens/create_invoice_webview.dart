import 'package:flutter/material.dart';
import '../components/generic_webview.dart';

/// WebView screen for creating invoices
/// This uses the generic WebView component to load the mobile invoice creation page
class CreateInvoiceWebView extends StatelessWidget {
  const CreateInvoiceWebView({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return const GenericWebView(
      phpEndpoint: 'mobile_create_invoice.php',
      title: 'Create Invoice',
    );
  }
} 