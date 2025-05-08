import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../auth_provider.dart';
import 'package:webview_flutter/webview_flutter.dart';

/// A WebView implementation for creating invoices in the mobile app.
/// This widget loads the mobile_create_invoice.php page in a WebView
/// and handles authentication and mobile app specific functionality.
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
  late final WebViewController _controller;

  @override
  void initState() {
    super.initState();
    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setNavigationDelegate(
        NavigationDelegate(
          onPageStarted: (String url) {
            print('Loading page: $url');
            setState(() => isLoading = true);
          },
          onPageFinished: (String url) {
            print('Page loaded: $url');
            _injectMobileAppData();
            setState(() => isLoading = false);
          },
          onWebResourceError: (WebResourceError error) {
            print('WebView error: ${error.description}');
            setState(() {
              isLoading = false;
            });
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(content: Text('Error: ${error.description}')),
            );
          },
          onNavigationRequest: (NavigationRequest request) {
            print('Navigation request to: ${request.url}');
            return NavigationDecision.navigate;
          },
        ),
      )
      ..setUserAgent('Flutter InvoiceApp Mobile WebView');
    _prepareWebView();
  }

  /// Prepares the WebView by getting authentication info and setting up the URL
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
    
    // Connect directly to mobile_create_invoice.php with mobile app flag
    url = "$baseUrl/invoice_management_project/mobile_create_invoice.php?user_id=$userId&mobile_app=true";
    
    await _controller.loadRequest(Uri.parse(url));
    
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
        : WebViewWidget(controller: _controller),
    );
  }

  /// Injects mobile app specific data and ensures forms have required fields
  Future<void> _injectMobileAppData() async {
    try {
      await _controller.runJavaScript('''
        // Set mobile app flag
        window.mobileApp = true;
        
        // Ensure user_id is set in all forms
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
          if (!form.querySelector('[name="user_id"]')) {
            const hiddenField = document.createElement('input');
            hiddenField.type = 'hidden';
            hiddenField.name = 'user_id';
            hiddenField.value = '${userId}';
            form.appendChild(hiddenField);
          }
          
          if (!form.querySelector('[name="mobile_app"]')) {
            const mobileAppField = document.createElement('input');
            mobileAppField.type = 'hidden';
            mobileAppField.name = 'mobile_app';
            mobileAppField.value = 'true';
            form.appendChild(mobileAppField);
          }
        });
      ''');
    } catch (e) {
      print('Error injecting mobile app data: $e');
    }
  }
} 