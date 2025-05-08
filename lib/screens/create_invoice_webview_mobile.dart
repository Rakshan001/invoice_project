import 'package:flutter/material.dart';
import 'package:webview_flutter/webview_flutter.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';

class InvoiceWebViewImpl extends StatefulWidget {
  final String url;
  final String? sessionToken;
  final String userId;

  const InvoiceWebViewImpl({
    Key? key,
    required this.url,
    required this.sessionToken,
    required this.userId,
  }) : super(key: key);

  @override
  State<InvoiceWebViewImpl> createState() => _InvoiceWebViewImplState();
}

class _InvoiceWebViewImplState extends State<InvoiceWebViewImpl> {
  late WebViewController _controller;
  bool _isLoading = true;
  String _errorMessage = '';
  String _currentUrl = '';

  @override
  void initState() {
    super.initState();
    _initializeWebView();
  }

  void _initializeWebView() {
    // Initialize the controller with advanced settings
    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setNavigationDelegate(
        NavigationDelegate(
          onPageStarted: (String url) {
            print('Navigating to: $url');
            setState(() {
              _isLoading = true;
              _currentUrl = url;
            });
          },
          onPageFinished: (String url) {
            _injectUserIdData();
            setState(() {
              _isLoading = false;
            });
          },
          onWebResourceError: (WebResourceError error) {
            setState(() {
              _isLoading = false;
              _errorMessage = 'Error: ${error.description}';
            });
          },
          // Allow all navigation but log it for debugging
          onNavigationRequest: (NavigationRequest request) {
            print('Navigation request to: ${request.url}');
            return NavigationDecision.navigate;
          },
        ),
      )
      ..setUserAgent('Flutter InvoiceApp Mobile WebView');
    
    // Load the authentication URL directly - it will redirect to create_invoice.php
    _loadAuthUrl();
  }

  // Load the authentication URL which will redirect to create_invoice.php
  Future<void> _loadAuthUrl() async {
    if (widget.userId.isEmpty) {
      setState(() {
        _errorMessage = 'Error: No user ID provided';
      });
      return;
    }

    try {
      await _controller.loadRequest(Uri.parse(widget.url));
    } catch (e) {
      setState(() {
        _errorMessage = 'Authentication error: $e';
      });
    }
  }
  
  Future<void> _injectUserIdData() async {
    try {
      await _controller.runJavaScript('''
        // Set a global variable with authentication data
        window.mobileAppAuth = {
          userId: '${widget.userId}',
          authenticated: true,
          mobileApp: true
        };
        
        // If there's a user_id field, set its value
        const userIdFields = document.querySelectorAll('[name="user_id"]');
        userIdFields.forEach(field => field.value = '${widget.userId}');
        
        // If there's a form, add user_id as hidden field if not present
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
          if (!form.querySelector('[name="user_id"]')) {
            const hiddenField = document.createElement('input');
            hiddenField.type = 'hidden';
            hiddenField.name = 'user_id';
            hiddenField.value = '${widget.userId}';
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
      print('Error injecting user data: $e');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Stack(
      children: [
        WebViewWidget(
          controller: _controller,
        ),
        if (_isLoading)
          const Center(
            child: CircularProgressIndicator(),
          ),
        if (_errorMessage.isNotEmpty)
          Center(
            child: Padding(
              padding: const EdgeInsets.all(16.0),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Icon(
                    Icons.error_outline,
                    color: Colors.red,
                    size: 60,
                  ),
                  const SizedBox(height: 16),
                  Text(
                    _errorMessage,
                    textAlign: TextAlign.center,
                    style: const TextStyle(
                      fontSize: 16,
                    ),
                  ),
                  const SizedBox(height: 16),
                  ElevatedButton(
                    onPressed: () {
                      setState(() {
                        _errorMessage = '';
                      });
                      _loadAuthUrl();
                    },
                    child: const Text('Try Again'),
                  ),
                ],
              ),
            ),
          ),
      ],
    );
  }
} 