import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'dart:io';

/// A utility class to handle permissions for file uploads in WebView
class PermissionHandler {
  /// Request storage permissions for file uploads
  static Future<bool> requestStoragePermission(BuildContext context) async {
    if (Platform.isAndroid) {
      try {
        // Use platform channels to request permissions
        final MethodChannel permissionChannel = const MethodChannel('app/permissions');
        
        final bool hasPermission = await permissionChannel.invokeMethod('requestStoragePermission');
        
        if (!hasPermission) {
          // Show an explanation if permission was denied
          if (context.mounted) {
            _showPermissionDialog(
              context, 
              'Storage permission is needed to upload files. Please grant permission in app settings.'
            );
          }
        }
        
        return hasPermission;
      } on PlatformException catch (e) {
        debugPrint('Error requesting permission: ${e.message}');
        return false;
      }
    }
    
    // iOS and other platforms generally handle permissions differently
    return true;
  }
  
  /// Show dialog explaining why permission is needed
  static void _showPermissionDialog(BuildContext context, String message) {
    showDialog(
      context: context,
      builder: (BuildContext dialogContext) {
        return AlertDialog(
          title: const Text('Permission Required'),
          content: Text(message),
          actions: <Widget>[
            TextButton(
              child: const Text('OK'),
              onPressed: () {
                Navigator.of(dialogContext).pop();
              },
            ),
          ],
        );
      },
    );
  }
} 