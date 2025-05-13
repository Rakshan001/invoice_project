import 'package:flutter/material.dart';
import '../components/generic_webview.dart';

/// WebView screen for bank account management
/// This uses the generic WebView component to load the bank details page
class BankDetailsView extends StatelessWidget {
  const BankDetailsView({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return const GenericWebView(
      phpEndpoint: 'mobile_bank_details.php',
      title: 'Bank Details',
    );
  }
}
