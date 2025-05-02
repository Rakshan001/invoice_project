// import 'package:flutter/foundation.dart';
// import 'package:http/http.dart' as http;
// import 'dart:convert';

// class AuthProvider with ChangeNotifier {
//   bool _isLoggedIn = false;
//   String? _userId;
//   String? _email;
//   String? _fullName;
//   int _regStep =
//       1; // Tracks registration step (1: input email/name, 2: verify OTP, 3: set password)
//   Map<String, String> _cookies = {};

//   bool get isLoggedIn => _isLoggedIn;
//   String? get userId => _userId;
//   String? get email => _email;
//   String? get fullName => _fullName;
//   int get regStep => _regStep;

//   // Helper to extract cookies from response headers
//   void _extractCookies(http.Response response) {
//     String? rawCookie = response.headers['set-cookie'];
//     if (rawCookie != null) {
//       rawCookie.split(';').forEach((cookie) {
//         var parts = cookie.trim().split('=');
//         if (parts.length >= 2) {
//           _cookies[parts[0]] = parts[1];
//         }
//       });
//     }
//   }

//   // Helper to include cookies in requests
//   Map<String, String> _getHeaders() {
//     String cookieHeader = _cookies.entries
//         .map((e) => '${e.key}=${e.value}')
//         .join('; ');
//     return {'Content-Type': 'application/json', 'Cookie': cookieHeader};
//   }

//   // Login method for login.php
//   Future<Map<String, dynamic>> login(
//     String email,
//     String password,
//     bool rememberMe,
//   ) async {
//     try {
//       final response = await http.post(
//         Uri.parse('https://yourserver.com/login.php'),
//         headers: {'Content-Type': 'application/json'},
//         body: jsonEncode({
//           'email': email,
//           'password': password,
//           'remember': rememberMe,
//         }),
//       );

//       _extractCookies(response);

//       final data = jsonDecode(response.body);
//       if (data['status'] == 'success') {
//         _isLoggedIn = true;
//         _userId = data['user_id']?.toString();
//         _email = data['email'];
//         _fullName = data['full_name'];
//         notifyListeners();
//         return {'status': 'success', 'message': 'Login successful'};
//       } else {
//         return {'status': 'error', 'message': data['message']};
//       }
//     } catch (e) {
//       return {'status': 'error', 'message': 'Failed to connect to server: $e'};
//     }
//   }

//   // Step 1: Register email and name (calls register.php with step1)
//   Future<Map<String, dynamic>> registerStep1(
//     String fullName,
//     String email,
//   ) async {
//     try {
//       final response = await http.post(
//         Uri.parse('https://yourserver.com/register.php'),
//         headers: {'Content-Type': 'application/json'},
//         body: jsonEncode({
//           'step': 'step1',
//           'full_name': fullName,
//           'email': email,
//         }),
//       );

//       _extractCookies(response);

//       final data = jsonDecode(response.body);
//       if (data['status'] == 'success') {
//         _regStep = 2;
//         _email = email;
//         _fullName = fullName;
//         notifyListeners();
//       }
//       return data;
//     } catch (e) {
//       return {'status': 'error', 'message': 'Failed to connect to server: $e'};
//     }
//   }

//   // Resend OTP (calls register.php with resend_otp)
//   Future<Map<String, dynamic>> resendOTP() async {
//     try {
//       final response = await http.post(
//         Uri.parse('https://yourserver.com/register.php'),
//         headers: _getHeaders(),
//         body: jsonEncode({'step': 'resend_otp'}),
//       );

//       _extractCookies(response);
//       return jsonDecode(response.body);
//     } catch (e) {
//       return {'status': 'error', 'message': 'Failed to connect to server: $e'};
//     }
//   }

//   // Verify OTP (calls register.php with step2)
//   Future<Map<String, dynamic>> verifyOTP(String otp) async {
//     try {
//       final response = await http.post(
//         Uri.parse('https://yourserver.com/register.php'),
//         headers: _getHeaders(),
//         body: jsonEncode({'step': 'step2', 'otp': otp}),
//       );

//       _extractCookies(response);

//       final data = jsonDecode(response.body);
//       if (data['status'] == 'success') {
//         _regStep = 3;
//         notifyListeners();
//       }
//       return data;
//     } catch (e) {
//       return {'status': 'error', 'message': 'Failed to connect to server: $e'};
//     }
//   }

//   // Complete registration (calls register.php with step3)
//   Future<Map<String, dynamic>> completeRegistration(
//     String password,
//     String confirmPassword,
//     bool agreeTerms,
//   ) async {
//     try {
//       final response = await http.post(
//         Uri.parse('https://yourserver.com/register.php'),
//         headers: _getHeaders(),
//         body: jsonEncode({
//           'step': 'step3',
//           'password': password,
//           'confirm_password': confirmPassword,
//           'agree_terms': agreeTerms,
//         }),
//       );

//       _extractCookies(response);

//       final data = jsonDecode(response.body);
//       if (data['status'] == 'success') {
//         _isLoggedIn = true;
//         _userId = data['user_id']?.toString();
//         _regStep = 1;
//         notifyListeners();
//       }
//       return data;
//     } catch (e) {
//       return {'status': 'error', 'message': 'Failed to connect to server: $e'};
//     }
//   }

//   // Forgot password (initiates password reset flow)
//   Future<Map<String, dynamic>> forgotPassword(String email) async {
//     try {
//       final response = await http.post(
//         Uri.parse(
//           'https://yourserver.com/forgot_password.php',
//         ), // Adjust if endpoint differs
//         headers: {'Content-Type': 'application/json'},
//         body: jsonEncode({'email': email}),
//       );

//       _extractCookies(response);
//       return jsonDecode(response.body);
//     } catch (e) {
//       return {'status': 'error', 'message': 'Failed to connect to server: $e'};
//     }
//   }

//   // Reset password (calls reset_password.php)
//   Future<Map<String, dynamic>> resetPassword(
//     String token,
//     String password,
//     String confirmPassword,
//   ) async {
//     try {
//       final response = await http.post(
//         Uri.parse('https://yourserver.com/reset_password.php'),
//         headers: {'Content-Type': 'application/json'},
//         body: jsonEncode({
//           'token': token,
//           'password': password,
//           'confirm_password': confirmPassword,
//         }),
//       );

//       _extractCookies(response);
//       return jsonDecode(response.body);
//     } catch (e) {
//       return {'status': 'error', 'message': 'Failed to connect to server: $e'};
//     }
//   }

//   // Fetch landing page data (calls landing.php)
//   Future<Map<String, dynamic>> fetchLandingData() async {
//     try {
//       final response = await http.get(
//         Uri.parse('https://yourserver.com/landing.php'),
//         headers: _getHeaders(),
//       );

//       if (response.statusCode == 200) {
//         return jsonDecode(response.body);
//       } else if (response.statusCode == 401) {
//         _isLoggedIn = false;
//         notifyListeners();
//         return jsonDecode(response.body);
//       } else {
//         return {'status': 'error', 'message': 'Failed to load data'};
//       }
//     } catch (e) {
//       return {'status': 'error', 'message': 'Error connecting to server: $e'};
//     }
//   }

//   void logout() {
//     _isLoggedIn = false;
//     _userId = null;
//     _email = null;
//     _fullName = null;
//     _regStep = 1;
//     _cookies.clear();
//     notifyListeners();
//   }
// }
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../auth_provider.dart';

class ResetPasswordScreen extends StatefulWidget {
  final String token;

  const ResetPasswordScreen({Key? key, required this.token}) : super(key: key);

  @override
  _ResetPasswordScreenState createState() => _ResetPasswordScreenState();
}

class _ResetPasswordScreenState extends State<ResetPasswordScreen> {
  final _passwordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();
  bool _isLoading = false;
  String? _errorMessage;
  String? _successMessage;

  void _resetPassword() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
      _successMessage = null;
    });

    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final result = await authProvider.resetPassword(
      widget.token,
      _passwordController.text,
      _confirmPasswordController.text,
    );

    setState(() {
      _isLoading = false;
      if (result['status'] == 'error') {
        _errorMessage = result['message'];
      } else {
        _successMessage = result['message'];
        Future.delayed(const Duration(seconds: 2), () {
          Navigator.pushReplacementNamed(context, '/login');
        });
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [Color(0xFF5E5CE6), Color(0xFF4B48BF)],
          ),
        ),
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(16.0),
            child: Card(
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(15),
              ),
              elevation: 10,
              child: Padding(
                padding: const EdgeInsets.all(20.0),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const CircleAvatar(
                      radius: 35,
                      backgroundColor: Colors.white,
                      child: Icon(
                        Icons.lock_reset,
                        size: 30,
                        color: Color(0xFF5E5CE6),
                      ),
                    ),
                    const SizedBox(height: 16),
                    const Text(
                      'Reset Password',
                      style: TextStyle(
                        fontSize: 24,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    Text(
                      'Enter your new password',
                      style: TextStyle(color: Colors.grey[600]),
                    ),
                    const SizedBox(height: 20),
                    if (_errorMessage != null)
                      Padding(
                        padding: const EdgeInsets.only(bottom: 16),
                        child: Text(
                          _errorMessage!,
                          style: const TextStyle(color: Colors.red),
                        ),
                      ),
                    if (_successMessage != null)
                      Padding(
                        padding: const EdgeInsets.only(bottom: 16),
                        child: Text(
                          _successMessage!,
                          style: const TextStyle(color: Colors.green),
                        ),
                      ),
                    TextField(
                      controller: _passwordController,
                      decoration: InputDecoration(
                        labelText: 'New Password',
                        prefixIcon: Icon(Icons.lock),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(8),
                        ),
                      ),
                      obscureText: true,
                    ),
                    const SizedBox(height: 16),
                    TextField(
                      controller: _confirmPasswordController,
                      decoration: InputDecoration(
                        labelText: 'Confirm Password',
                        prefixIcon: Icon(Icons.lock),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(8),
                        ),
                      ),
                      obscureText: true,
                    ),
                    const SizedBox(height: 16),
                    _isLoading
                        ? const CircularProgressIndicator()
                        : ElevatedButton(
                          onPressed: _resetPassword,
                          child: const Text('RESET PASSWORD'),
                          style: ElevatedButton.styleFrom(
                            minimumSize: const Size(double.infinity, 50),
                          ),
                        ),
                    const SizedBox(height: 16),
                    TextButton(
                      onPressed:
                          () =>
                              Navigator.pushReplacementNamed(context, '/login'),
                      child: const Text('Back to Login'),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
