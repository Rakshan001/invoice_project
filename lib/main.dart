import 'package:flutter/material.dart';
import 'package:invoice_project/landing.dart';
import 'package:invoice_project/screens/invoice_management_page.dart';
import 'package:invoice_project/screens/quotation_management_page.dart';
import 'package:invoice_project/screens/create_invoice_webview.dart';
import 'package:provider/provider.dart';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'dart:io' show Platform;
import 'package:webview_flutter/webview_flutter.dart';
import 'auth_provider.dart';
import 'login_screen.dart';
import 'signup_screen.dart';
import 'forgot_password_screen.dart';

void main() {
  // Initialize WebView only on mobile platforms
  if (!kIsWeb) {
    WebViewPlatform.instance;
  }
  
  runApp(
    ChangeNotifierProvider(
      create: (context) => AuthProvider(),
      child: const MyApp(),
    ),
  );
}

class MyApp extends StatelessWidget {
  const MyApp({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Business Management App',
      theme: ThemeData(
        primaryColor: const Color(0xFF4E73DF), // Updated to match landing.dart
        scaffoldBackgroundColor: Colors.white,
        fontFamily: 'Inter',
        textTheme: const TextTheme(
          bodyMedium: TextStyle(color: Color(0xFF1E293B)),
        ),
        visualDensity: VisualDensity.adaptivePlatformDensity,
      ),
      home: const AuthenticationWrapper(),
      routes: {
        '/login': (context) => const LoginScreen(),
        '/landing': (context) => const LandingScreen(),
        '/signup': (context) => const SignupScreen(),
        '/forgot_password': (context) => const ForgotPasswordScreen(),
        '/profile':
            (context) =>
                const Scaffold(body: Center(child: Text('Profile Screen'))),
        '/settings':
            (context) =>
                const Scaffold(body: Center(child: Text('Settings Screen'))),
        '/invoices': (context) => const InvoiceManagementPage(),
        '/quotations': (context) => const QuotationManagementPage(),
        '/create_invoice': (context) => const CreateInvoiceWebView(),
      },
      onUnknownRoute: (settings) {
        return MaterialPageRoute(
          builder:
              (context) => Scaffold(
                body: Center(child: Text('Route ${settings.name} not found')),
              ),
        );
      },
    );
  }
}

// This widget checks if the user is authenticated and redirects accordingly
class AuthenticationWrapper extends StatelessWidget {
  const AuthenticationWrapper({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    final authProvider = Provider.of<AuthProvider>(context);
    
    // Show splash screen while checking authentication status
    if (!authProvider.isInitialized) {
      return const Scaffold(
        body: Center(
          child: CircularProgressIndicator(),
        ),
      );
    }
    
    // If authenticated, go to landing screen, otherwise go to login
    return authProvider.isAuthenticated
        ? const LandingScreen()
        : const LoginScreen();
  }
}
