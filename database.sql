-- Homelab Manager Database Schema
-- Tabellen: devices, storage, cables, racks

CREATE TABLE IF NOT EXISTS `racks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `height_units` int(11) DEFAULT NULL,
  `barcode_number` varchar(10) UNIQUE NOT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `devices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `mac_address` varchar(17) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `barcode_number` varchar(10) UNIQUE NOT NULL,
  `rack_id` int(11) DEFAULT NULL,
  `rack_unit_start` int(11) DEFAULT NULL,
  `rack_unit_end` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`rack_id`) REFERENCES `racks`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `storage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `device_id` int(11) NOT NULL,
  `type` enum('HDD','SSD','NVMe') NOT NULL,
  `capacity` varchar(50) NOT NULL,
  `model` varchar(255) DEFAULT NULL,
  `serial_number` varchar(255) UNIQUE DEFAULT NULL,
  `barcode_id` varchar(10) UNIQUE NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`device_id`) REFERENCES `devices`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `barcode_number` varchar(10) UNIQUE NOT NULL,
  `from_device_id` int(11) DEFAULT NULL,
  `to_device_id` int(11) DEFAULT NULL,
  `from_port` varchar(100) DEFAULT NULL,
  `to_port` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
