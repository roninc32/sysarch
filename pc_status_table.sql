-- Create pc_status table if it doesn't exist
CREATE TABLE IF NOT EXISTS `pc_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lab_number` varchar(50) NOT NULL,
  `pc_id` varchar(20) NOT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `lab_pc_unique` (`lab_number`, `pc_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add admin_comment and processed_date columns to sitin_reservation if they don't exist
ALTER TABLE `sitin_reservation`
ADD COLUMN IF NOT EXISTS `admin_comment` text DEFAULT NULL AFTER `status`,
ADD COLUMN IF NOT EXISTS `processed_date` timestamp NULL DEFAULT NULL AFTER `admin_comment`;

-- Add is_admin column to users table if it doesn't exist
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `is_admin` tinyint(1) NOT NULL DEFAULT 0 AFTER `last_name`;
