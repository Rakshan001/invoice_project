-- Create quotations table
CREATE TABLE IF NOT EXISTS `quotations` (
    `quotation_id` int(11) NOT NULL AUTO_INCREMENT,
    `quotation_number` varchar(50) NOT NULL,
    `company_id` int(11) NOT NULL,
    `client_id` int(11) NOT NULL,
    `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
    `tax_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
    `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
    `discount_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
    `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
    `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
    `status` enum('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
    `notes` text DEFAULT NULL,
    `terms_conditions` text DEFAULT NULL,
    `valid_until` date DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`quotation_id`),
    UNIQUE KEY `quotation_number` (`quotation_number`),
    KEY `company_id` (`company_id`),
    KEY `client_id` (`client_id`),
    CONSTRAINT `quotations_company_fk` FOREIGN KEY (`company_id`) REFERENCES `company_master` (`company_id`) ON DELETE CASCADE,
    CONSTRAINT `quotations_client_fk` FOREIGN KEY (`client_id`) REFERENCES `client_master` (`client_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create quotation_items table
CREATE TABLE IF NOT EXISTS `quotation_items` (
    `item_id` int(11) NOT NULL AUTO_INCREMENT,
    `quotation_id` int(11) NOT NULL,
    `description` text NOT NULL,
    `quantity` decimal(10,2) NOT NULL DEFAULT 1.00,
    `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
    `tax_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
    `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
    `discount_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
    `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
    `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
    PRIMARY KEY (`item_id`),
    KEY `quotation_id` (`quotation_id`),
    CONSTRAINT `quotation_items_fk` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`quotation_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create quotation_history table for tracking status changes
CREATE TABLE IF NOT EXISTS `quotation_history` (
    `history_id` int(11) NOT NULL AUTO_INCREMENT,
    `quotation_id` int(11) NOT NULL,
    `status` enum('pending','accepted','rejected') NOT NULL,
    `notes` text DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`history_id`),
    KEY `quotation_id` (`quotation_id`),
    CONSTRAINT `quotation_history_fk` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`quotation_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create quotation_settings table for company-specific settings
CREATE TABLE IF NOT EXISTS `quotation_settings` (
    `setting_id` int(11) NOT NULL AUTO_INCREMENT,
    `company_id` int(11) NOT NULL,
    `default_tax_rate` decimal(5,2) DEFAULT 0.00,
    `default_discount_rate` decimal(5,2) DEFAULT 0.00,
    `default_terms` text DEFAULT NULL,
    `quotation_prefix` varchar(10) DEFAULT 'QT',
    `next_number` int(11) DEFAULT 1,
    `validity_days` int(11) DEFAULT 30,
    PRIMARY KEY (`setting_id`),
    UNIQUE KEY `company_id` (`company_id`),
    CONSTRAINT `quotation_settings_fk` FOREIGN KEY (`company_id`) REFERENCES `company_master` (`company_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 