import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../auth_provider.dart';

// Import WebView conditionally since it's not supported on web
import 'create_invoice_webview_mobile.dart' if (dart.library.html) 'create_invoice_webview_web.dart';

class CreateInvoiceWebView extends StatefulWidget {
  const CreateInvoiceWebView({Key? key}) : super(key: key);

  @override
  State<CreateInvoiceWebView> createState() => _CreateInvoiceWebViewState();
}

class _CreateInvoiceWebViewState extends State<CreateInvoiceWebView> {
  late String url;
  String? sessionToken;
  String userId = "";
  bool isLoading = true;

  @override
  void initState() {
    super.initState();
    _prepareWebView();
  }

  Future<void> _prepareWebView() async {
    // Get authentication info from provider
    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final userInfo = authProvider.getCurrentUserInfo();
    
    userId = userInfo['userId'] ?? "";
    sessionToken = userInfo['sessionToken'];
    
    // Save to shared preferences for persistence
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('user_id', userId);
    if (sessionToken != null) {
      await prefs.setString('session_token', sessionToken!);
    }
    
    final baseUrl = authProvider.webServerUrl;
    
    // Use the mobile authentication endpoint instead of directly accessing create_invoice.php
    url = "$baseUrl/invoice_management_project/mobile_auth.php?user_id=$userId";
    
    setState(() {
      isLoading = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Create Invoice'),
        backgroundColor: const Color(0xFF4E73DF),
      ),
      body: isLoading 
        ? const Center(child: CircularProgressIndicator())
        : InvoiceWebViewImpl(
            url: url,
            sessionToken: sessionToken,
            userId: userId,
          ),
    );
  }
} 