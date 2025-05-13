import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:webview_flutter/webview_flutter.dart';
import '../auth_provider.dart';
import 'dart:io';

/// A generic WebView component that can load different PHP endpoints
/// while handling authentication and mobile app specific functionality.
class GenericWebView extends StatefulWidget {
  final String phpEndpoint;
  final String title;
  final Map<String, String> additionalParams;
  
  const GenericWebView({
    Key? key, 
    required this.phpEndpoint,
    required this.title,
    this.additionalParams = const {},
  }) : super(key: key);

  @override
  State<GenericWebView> createState() => _GenericWebViewState();
}

class _GenericWebViewState extends State<GenericWebView> {
  late String url;
  String? sessionToken;
  String userId = "";
  bool isLoading = true;
  bool canGoBack = false;
  late final WebViewController _controller;

  @override
  void initState() {
    super.initState();
    _setupController();
    _prepareWebView();
  }

  void _setupController() {
    // Set up WebView
    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setBackgroundColor(const Color(0x00000000))
      ..setNavigationDelegate(
        NavigationDelegate(
          onPageStarted: (String url) {
            print('Loading page: $url');
            setState(() => isLoading = true);
          },
          onPageFinished: (String url) {
            print('Page loaded: $url');
            _injectMobileAppData();
            _checkCanGoBack();
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
        ),
      )
      // Set a custom user agent that indicates this is a mobile app
      ..setUserAgent('Flutter InvoiceApp Mobile WebView');
      
    // Enable DOM storage through JavaScript
    _controller.runJavaScript('''
      // Enable DOM storage
      try {
        window.localStorage.setItem('flutter_webview_test', 'test');
        console.log('Local storage is working');
        window.localStorage.removeItem('flutter_webview_test');
      } catch (e) {
        console.error('Local storage is not available:', e);
      }
    ''');
  }

  Future<void> _checkCanGoBack() async {
    final canGoBack = await _controller.canGoBack();
    setState(() {
      this.canGoBack = canGoBack;
    });
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
    
    // Set mobile_app cookie to ensure PHP detects it correctly
    await prefs.setBool('mobile_app', true);
    
    final baseUrl = authProvider.webServerUrl;
    
    // Build URL with required parameters
    final Map<String, String> params = {
      'user_id': userId,
      'mobile_app': 'true',
      'session_token': sessionToken ?? '',
      ...widget.additionalParams,
    };
    
    final queryString = params.entries
        .map((e) => '${e.key}=${Uri.encodeComponent(e.value)}')
        .join('&');
    
    url = "$baseUrl/invoice_management_project/${widget.phpEndpoint}?$queryString";
    
    await _controller.loadRequest(Uri.parse(url));
    
    setState(() {
      isLoading = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    return WillPopScope(
      onWillPop: () async {
        if (canGoBack) {
          await _controller.goBack();
          return false; // Prevent app from closing
        }
        return true; // Allow back button to close the page
      },
      child: Scaffold(
        appBar: AppBar(
          title: Text(widget.title),
          backgroundColor: const Color(0xFF4E73DF),
          actions: [
            if (canGoBack)
              IconButton(
                icon: const Icon(Icons.arrow_back),
                onPressed: () async {
                  if (canGoBack) {
                    await _controller.goBack();
                    _checkCanGoBack();
                  }
                },
              ),
            IconButton(
              icon: const Icon(Icons.refresh),
              onPressed: () {
                _controller.reload();
              },
            ),
          ],
        ),
        body: isLoading 
          ? const Center(child: CircularProgressIndicator())
          : WebViewWidget(controller: _controller),
      ),
    );
  }

  /// Injects mobile app specific data and ensures forms have required fields
  Future<void> _injectMobileAppData() async {
    try {
      await _controller.runJavaScript('''
        // Set mobile app flag
        window.mobileApp = true;
        
        // Fix any form issues
        function enhanceForms() {
          console.log("Enhancing forms for mobile app");
          const forms = document.querySelectorAll('form');
          
          forms.forEach(form => {
            // Add user_id field if missing
            if (!form.querySelector('[name="user_id"]')) {
              const hiddenField = document.createElement('input');
              hiddenField.type = 'hidden';
              hiddenField.name = 'user_id';
              hiddenField.value = '${userId}';
              form.appendChild(hiddenField);
            }
            
            // Add mobile_app field if missing
            if (!form.querySelector('[name="mobile_app"]')) {
              const mobileAppField = document.createElement('input');
              mobileAppField.type = 'hidden';
              mobileAppField.name = 'mobile_app';
              mobileAppField.value = 'true';
              form.appendChild(mobileAppField);
            }
            
            // Add session_token if available and missing
            if (!form.querySelector('[name="session_token"]') && '${sessionToken}' !== '') {
              const tokenField = document.createElement('input');
              tokenField.type = 'hidden';
              tokenField.name = 'session_token';
              tokenField.value = '${sessionToken}';
              form.appendChild(tokenField);
            }
          });
        }
        
        // Set cookies for PHP session handling
        document.cookie = 'user_id=${userId}; path=/';
        document.cookie = 'mobile_app=true; path=/';
        ${sessionToken != null ? "document.cookie = 'session_token=${sessionToken}; path=/';" : ''}
        
        // Enhance mobile UX
        document.body.style.fontSize = '16px';
        
        // Run enhancements
        enhanceForms();
      ''');
    } catch (e) {
      print('Error injecting mobile app data: $e');
    }
  }
} 