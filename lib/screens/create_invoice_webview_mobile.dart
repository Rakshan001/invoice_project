import 'package:flutter/material.dart';
import 'package:webview_flutter/webview_flutter.dart';
import 'package:shared_preferences/shared_preferences.dart';

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

  @override
  void initState() {
    super.initState();
    _initializeWebView();
  }

  void _initializeWebView() {
    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setNavigationDelegate(
        NavigationDelegate(
          onPageStarted: (String url) {
            print('Loading page: $url');
            setState(() {
              _isLoading = true;
            });
          },
          onPageFinished: (String url) {
            print('Page loaded: $url');
            _injectMobileAppData();
            setState(() {
              _isLoading = false;
            });
          },
          onWebResourceError: (WebResourceError error) {
            print('WebView error: ${error.description}');
            setState(() {
              _isLoading = false;
              _errorMessage = 'Error: ${error.description}';
            });
          },
          onNavigationRequest: (NavigationRequest request) {
            print('Navigation request to: ${request.url}');
            return NavigationDecision.navigate;
          },
        ),
      )
      ..setUserAgent('Flutter InvoiceApp Mobile WebView')
      ..loadRequest(Uri.parse(widget.url));
  }
  
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
      print('Error injecting mobile app data: $e');
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
                        _isLoading = true;
                      });
                      _controller.reload();
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