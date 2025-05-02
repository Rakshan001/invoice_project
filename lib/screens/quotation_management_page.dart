import 'package:flutter/material.dart';

class QuotationManagementPage extends StatelessWidget {
  const QuotationManagementPage({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Quotations'),
        backgroundColor: const Color(0xFF4E73DF),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Logo
            Padding(
              padding: const EdgeInsets.only(bottom: 24),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Image.asset(
                    'assets/images/logo.png',
                    height: 50,
                    fit: BoxFit.contain,
                  ),
                ],
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
                ExpandedNavigationButton(
                  icon: Icons.add,
                  label: 'Create Quotation',
                  onPressed: () {
                    // Placeholder for create quotation route
                  },
                ),
                ExpandedNavigationButton(
                  icon: Icons.list,
                  label: 'All Quotations',
                  onPressed: () {
                    Navigator.pushNamed(context, '/quotations');
                  },
                ),
              ],
            ),
            const SizedBox(height: 16),

            // OTHER Section
            const Text(
              'OTHER',
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
                  icon: Icons.settings,
                  label: 'Quotation Settings',
                  onPressed: () {
                    // Placeholder for quotation settings route
                  },
                ),
                ExpandedNavigationButton(
                  icon: Icons.dashboard,
                  label: 'Main Dashboard',
                  onPressed: () {
                    Navigator.pushNamed(context, '/landing');
                  },
                ),
                ExpandedNavigationButton(
                  icon: Icons.logout,
                  label: 'Logout',
                  onPressed: () {
                    Navigator.pushNamed(context, '/login');
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