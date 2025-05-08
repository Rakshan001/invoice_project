import 'dart:html' as html;
import 'package:flutter/material.dart';
import 'dart:ui_web' as ui;

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

  @override
  void initState() {
    super.initState();
    _initializeWebView();
  }

  void _initializeWebView() {
    // Register iframe view
    viewId = 'invoice-iframe-${DateTime.now().millisecondsSinceEpoch}';
    
    // Store authentication data in local storage
    html.window.localStorage['user_id'] = widget.userId;
    html.window.localStorage['mobile_app'] = 'true';
    
    // Set up iframe
    ui.platformViewRegistry.registerViewFactory(viewId, (int viewId) {
      final iframe = html.IFrameElement()
        ..src = widget.url
        ..style.border = 'none'
        ..style.height = '100%'
        ..style.width = '100%'
        ..setAttribute('frameborder', '0')
        ..setAttribute('allow', 'fullscreen')
        ..id = 'invoice-iframe';
      
      // Add load event listener
      iframe.onLoad.listen((event) {
        setState(() {
          _isLoading = false;
        });
      });
      
      return iframe;
    });
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