import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:http/http.dart' as http;
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'dart:io' show Platform;

class AuthProvider with ChangeNotifier {
  final _storage = const FlutterSecureStorage();
  String? _token;
  int? _userId;
  String? _email;
  String? _fullName;
  int? _companyId;
  String? _companyName;
  bool _isInitialized = false; // Track if initialization is complete
  
  // Base API URL - Different for web and mobile
  String get baseUrl {
    // For web (Chrome) testing, use the standard URL
    if (kIsWeb) {
      return 'http://192.168.1.4/invoice_settings';
    }
    
    // For Android emulator, use 10.0.2.2 which maps to host's localhost
    if (Platform.isAndroid) {
      // When testing with USB debugging on a physical device,
      // try to use the local network IP address of your computer
      // This needs to be an IP address your phone can reach on the same network
      const useLocalNetwork = true; // Set to true to use local network
      
      if (useLocalNetwork) {
        // Use the IP address your phone can access
        return 'http://192.168.1.4/invoice_settings'; 
      } else {
        // For emulator use 10.0.2.2
        return 'http://10.0.2.2/invoice_settings';
      }
    }
    
    // Default fallback
    return 'http://192.168.1.4/invoice_settings';
  }
  
  bool get isAuthenticated => _token != null;
  bool get isInitialized => _isInitialized;
  String? get token => _token;
  int? get userId => _userId;
  String? get email => _email;
  String? get fullName => _fullName;
  int? get companyId => _companyId;
  String? get companyName => _companyName;

  AuthProvider() {
    _loadUserData();
  }

  Future<void> _loadUserData() async {
    try {
      _token = await _storage.read(key: 'remember_token');
      final userIdStr = await _storage.read(key: 'user_id');
      _userId = userIdStr != null ? int.tryParse(userIdStr) : null;
      _email = await _storage.read(key: 'email');
      _fullName = await _storage.read(key: 'full_name');
      
      final companyIdStr = await _storage.read(key: 'company_id');
      _companyId = companyIdStr != null ? int.tryParse(companyIdStr) : null;
      _companyName = await _storage.read(key: 'company_name');
      
      if (_token != null) {
        debugPrint('User is already logged in: $_email');
      }
      
      // Mark initialization as complete
      _isInitialized = true;
      notifyListeners();
    } catch (e) {
      debugPrint('Error loading user data: $e');
      _isInitialized = true; // Still mark as initialized even if there's an error
      notifyListeners();
    }
  }

  Future<Map<String, dynamic>> login(
    String email,
    String password,
    bool remember,
  ) async {
    try {
      // Check if email and password are valid
      if (email.isEmpty) {
        return {'status': 'error', 'message': 'Email is required'};
      }
      
      if (password.isEmpty) {
        return {'status': 'error', 'message': 'Password is required'};
      }
      
      final loginUrl = '$baseUrl/login.php';
      debugPrint('Attempting to login with email: $email');
      debugPrint('Making POST request to: $loginUrl');
      
      try {
        final response = await http.post(
          Uri.parse(loginUrl),
          headers: {'Content-Type': 'application/json'},
          body: jsonEncode({
            'email': email,
            'password': password,
            'remember': remember,
          }),
        ).timeout(const Duration(seconds: 15)); // Add timeout to avoid hanging

        debugPrint('Login response status: ${response.statusCode}');
        
        // Handle HTTP error status codes
        if (response.statusCode != 200) {
          return {
            'status': 'error', 
            'message': 'Server error: HTTP ${response.statusCode}'
          };
        }
        
        // Try to decode JSON response
        Map<String, dynamic> data;
        try {
          data = jsonDecode(response.body);
          debugPrint('Login response parsed successfully');
        } catch (e) {
          debugPrint('Failed to parse response: ${response.body}');
          return {
            'status': 'error',
            'message': 'Invalid response from server'
          };
        }
        
        if (data['status'] == 'success') {
          final userData = data['data'];
          _userId = userData['user_id'];
          _email = userData['email'];
          _fullName = userData['full_name'];
          _companyId = userData['company_id'];
          _companyName = userData['company_name'];
          
          if (remember && userData['remember_token'] != null) {
            _token = userData['remember_token'];
            await _storage.write(key: 'remember_token', value: _token);
          }
          
          // Save user data to secure storage
          await _storage.write(key: 'user_id', value: _userId.toString());
          await _storage.write(key: 'email', value: _email);
          await _storage.write(key: 'full_name', value: _fullName);
          
          if (_companyId != null) {
            await _storage.write(key: 'company_id', value: _companyId.toString());
          }
          
          if (_companyName != null) {
            await _storage.write(key: 'company_name', value: _companyName);
          }
          
          notifyListeners();
          return {'status': 'success'};
        } else {
          return {'status': 'error', 'message': data['message'] ?? 'Login failed'};
        }
      } catch (e) {
        debugPrint('Network error: $e');
        return {
          'status': 'error', 
          'message': 'Connection error: Unable to reach the server. Please check your network connection and server status.'
        };
      }
    } catch (e) {
      debugPrint('Login error: $e');
      return {'status': 'error', 'message': 'Connection error: $e'};
    }
  }

  // For demo purposes - allows logging in even if the server is not available
  Future<Map<String, dynamic>> loginOffline(
    String email,
    String password,
  ) async {
    // Only use for testing - in production, always validate with the server
    if (email == 'srisha2373@gmail.com' && password == 'password123') {
      _userId = 1;
      _email = email;
      _fullName = 'Demo User';
      _token = 'demo_token';
      
      // Save to secure storage
      await _storage.write(key: 'user_id', value: _userId.toString());
      await _storage.write(key: 'email', value: _email);
      await _storage.write(key: 'full_name', value: _fullName);
      await _storage.write(key: 'remember_token', value: _token);
      
      notifyListeners();
      return {'status': 'success'};
    } else {
      return {'status': 'error', 'message': 'Invalid credentials'};
    }
  }

  // Add a method to get current user info that can be used by other screens
  Map<String, dynamic> getCurrentUserInfo() {
    return {
      'userId': _userId,
      'email': _email,
      'fullName': _fullName,
      'companyId': _companyId,
      'companyName': _companyName,
      'isAuthenticated': isAuthenticated,
    };
  }

  Future<void> logout() async {
    _token = null;
    _userId = null;
    _email = null;
    _fullName = null;
    _companyId = null;
    _companyName = null;
    
    await _storage.deleteAll();
    notifyListeners();
  }
} 