import 'dart:html' as html;
import 'package:flutter/material.dart';
import 'dart:ui_web' as ui;
import 'dart:convert';
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
  late String viewId;
  bool _isLoading = true;
  String _authUrl = '';

  @override
  void initState() {
    super.initState();
    _prepareAuthUrl();
  }

  void _prepareAuthUrl() {
    // Extract base URL from the target URL
    Uri uri = Uri.parse(widget.url);
    String baseUrl = '${uri.scheme}://${uri.host}';
    if (uri.port != 80 && uri.port != 443) {
      baseUrl += ':${uri.port}';
    }
    
    // Create the auth URL
    _authUrl = '$baseUrl/invoice_management_project/mobile_auth.php?user_id=${widget.userId}';
    
    // Initialize the WebView
    _initializeWebView();
  }

  void _initializeWebView() {
    // Register iframe view
    viewId = 'invoice-iframe-${DateTime.now().millisecondsSinceEpoch}';
    
    // Store authentication data in local storage for the main window
    html.window.localStorage['user_id'] = widget.userId;
    html.window.localStorage['authenticated'] = 'true';
    html.window.localStorage['mobile_app'] = 'true';
    
    // Set up iframe with the auth URL
    ui.platformViewRegistry.registerViewFactory(viewId, (int viewId) {
      final iframe = html.IFrameElement()
        ..src = _authUrl
        ..style.border = 'none'
        ..style.height = '100%'
        ..style.width = '100%'
        ..setAttribute('frameborder', '0')
        ..setAttribute('allow', 'fullscreen')
        ..id = 'invoice-iframe';
      
      // Add load event listener to hide loading indicator and handle redirects
      iframe.onLoad.listen((event) {
        _checkCurrentPageAndRedirect(iframe);
        setState(() {
          _isLoading = false;
        });
      });
      
      return iframe;
    });
  }
  
  void _checkCurrentPageAndRedirect(html.IFrameElement iframe) {
    try {
      // Try to access iframe content location (may fail due to same-origin policy)
      final contentWindow = iframe.contentWindow;
      if (contentWindow != null) {
        // If we have access to the iframe's window, we can check its URL
        try {
          final location = contentWindow.location.href;
          
          // If redirected to login page, redirect back to invoice page
          if (location.contains('login.php')) {
            // Extract base URL
            Uri uri = Uri.parse(location);
            String baseUrl = '${uri.scheme}://${uri.host}';
            if (uri.port != 80 && uri.port != 443) {
              baseUrl += ':${uri.port}';
            }
            
            // Build invoice URL with authentication parameters
            final invoiceUrl = '$baseUrl/invoice_management_project/create_invoice.php?user_id=${widget.userId}&mobile_app=true';
            contentWindow.location.href = invoiceUrl;
          }
        } catch (e) {
          print('Cannot access iframe location: $e');
        }
        
        // Try to communicate with the iframe using postMessage
        final message = {
          'type': 'authentication',
          'userId': widget.userId,
          'authenticated': true,
          'mobileApp': true
        };
        
        contentWindow.postMessage(jsonEncode(message), '*');
      }
    } catch (e) {
      print('Error checking iframe content: $e');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Stack(
      children: [
        SizedBox(
          height: double.infinity,
          width: double.infinity,
          child: HtmlElementView(viewType: viewId),
        ),
        if (_isLoading)
          const Center(
            child: CircularProgressIndicator(),
          ),
      ],
    );
  }
} 