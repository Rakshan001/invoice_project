import 'package:flutter/material.dart';
import '../components/generic_webview.dart';

/// WebView screen for client management
/// This uses the generic WebView component to load the mobile clients page
class ClientsView extends StatelessWidget {
  const ClientsView({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return const GenericWebView(
      phpEndpoint: 'mobile_clients.php',
      title: 'Clients',
    );
  }
} 