import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../auth_provider.dart';

class InvoiceManagementPage extends StatelessWidget {
  const InvoiceManagementPage({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    // Access auth provider to get user info
    final authProvider = Provider.of<AuthProvider>(context);
    final userInfo = authProvider.getCurrentUserInfo();
    final fullName = userInfo['fullName'] ?? 'User';

    return Scaffold(
      appBar: AppBar(
        title: const Text('Invoice Management'),
        backgroundColor: const Color(0xFF4E73DF),
        actions: [
          Padding(
            padding: const EdgeInsets.all(8.0),
            child: CircleAvatar(
              backgroundColor: Colors.white,
              child: Text(
                fullName.isNotEmpty ? fullName[0].toUpperCase() : 'U',
                style: const TextStyle(
                  color: Color(0xFF4E73DF),
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
          ),
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // User greeting
            Padding(
              padding: const EdgeInsets.only(bottom: 24),
              child: Card(
                color: Colors.white,
                elevation: 2,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Padding(
                  padding: const EdgeInsets.all(16.0),
                  child: Row(
                    children: [
                      CircleAvatar(
                        backgroundColor: const Color(0xFF4E73DF).withOpacity(0.1),
                        child: const Icon(
                          Icons.person,
                          color: Color(0xFF4E73DF),
                        ),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Welcome, $fullName',
                              style: const TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                            Text(
                              'User ID: ${userInfo['userId'] ?? 'N/A'}',
                              style: TextStyle(
                                color: Colors.grey[600],
                                fontSize: 12,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
            // MAIN Section
            const Text(
              'MAIN',
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w600,
                color: Color(0xFFB7B9CC),
                letterSpacing: 0.5,
              ),
            ),
            const SizedBox(height: 8),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                ExpandedNavigationButton(
                  icon: Icons.dashboard,
                  label: 'Dashboard',
                  onPressed: () {
                    Navigator.pushNamed(context, '/landing');
                  },
                  isFullWidth: true,
                ),
              ],
            ),
            const SizedBox(height: 16),

            // COMPANY Section
            const Text(
              'COMPANY',
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w600,
                color: Color(0xFFB7B9CC),
                letterSpacing: 0.5,
              ),
            ),
            const SizedBox(height: 8),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                ExpandedNavigationButton(
                  icon: Icons.apartment,
                  label: 'Company',
                  onPressed: () {
                    // Placeholder for company route
                  },
                ),
                ExpandedNavigationButton(
                  icon: Icons.account_balance,
                  label: 'Bank Details',
                  onPressed: () {
                    // Placeholder for bank details route
                  },
                ),
                ExpandedNavigationButton(
                  icon: Icons.percent,
                  label: 'Tax Rates',
                  onPressed: () {
                    // Placeholder for tax rates route
                  },
                ),
                ExpandedNavigationButton(
                  icon: Icons.settings,
                  label: 'Invoice Settings',
                  onPressed: () {
                    // Placeholder for invoice settings route
                  },
                ),
              ],
            ),
            const SizedBox(height: 16),

            // BUSINESS Section
            const Text(
              'BUSINESS',
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w600,
                color: Color(0xFFB7B9CC),
                letterSpacing: 0.5,
              ),
            ),
            const SizedBox(height: 8),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                ExpandedNavigationButton(
                  icon: Icons.group,
                  label: 'Clients',
                  onPressed: () {
                    // Placeholder for clients route
                  },
                ),
                ExpandedNavigationButton(
                  icon: Icons.file_copy,
                  label: 'Invoices',
                  onPressed: () {
                    Navigator.pushNamed(context, '/invoices');
                  },
                ),
                ExpandedNavigationButton(
                  icon: Icons.add,
                  label: 'Create Invoice',
                  onPressed: () {
                    // Placeholder for create invoice route
                  },
                ),
                ExpandedNavigationButton(
                  icon: Icons.analytics,
                  label: 'Analytics',
                  onPressed: () {
                    // Placeholder for analytics route
                  },
                ),
              ],
            ),
            const SizedBox(height: 16),

            // COMMUNICATIONS Section
            const Text(
              'COMMUNICATIONS',
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w600,
                color: Color(0xFFB7B9CC),
                letterSpacing: 0.5,
              ),
            ),
            const SizedBox(height: 8),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                ExpandedNavigationButton(
                  icon: Icons.email,
                  label: 'Email Templates',
                  onPressed: () {
                    // Placeholder for email templates route
                  },
                  isFullWidth: true,
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

// Expanded Navigation Button Widget
class ExpandedNavigationButton extends StatelessWidget {
  final IconData icon;
  final String label;
  final VoidCallback onPressed;
  final bool isFullWidth;

  const ExpandedNavigationButton({
    Key? key,
    required this.icon,
    required this.label,
    required this.onPressed,
    this.isFullWidth = false,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width:
          isFullWidth
              ? MediaQuery.of(context).size.width -
                  32 // Full width minus padding
              : (MediaQuery.of(context).size.width - 40) /
                  2, // Half width for two buttons
      child: ElevatedButton.icon(
        onPressed: onPressed,
        icon: Icon(icon, size: 16),
        label: Text(label),
        style: ElevatedButton.styleFrom(
          backgroundColor: const Color(0xFF4E73DF),
          foregroundColor: Colors.white,
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
          textStyle: const TextStyle(fontSize: 14, fontWeight: FontWeight.w500),
        ),
      ),
    );
  }
}