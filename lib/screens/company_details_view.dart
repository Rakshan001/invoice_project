import 'package:flutter/material.dart';
import '../components/generic_webview.dart';

/// WebView screen for company details and management
/// This uses the generic WebView component to load the company details page
class CompanyDetailsView extends StatelessWidget {
  const CompanyDetailsView({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return const GenericWebView(
      phpEndpoint: 'mobile_company_details.php',
      title: 'Company Details',
    );
  }
}
