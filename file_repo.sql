-- Create the database
CREATE DATABASE IF NOT EXISTS `file_repo`;
USE `file_repo`;

-- Users table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Folders table with hierarchical support
CREATE TABLE IF NOT EXISTS `folders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `folder_name` VARCHAR(255) NOT NULL,
  `parent_id` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`parent_id`) REFERENCES `folders`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Files table with metadata (added deleted_at for soft-delete)
CREATE TABLE IF NOT EXISTS `files` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `folder_id` INT DEFAULT NULL,
  `filename` VARCHAR(255) NOT NULL,
  `filepath` VARCHAR(255) NOT NULL,
  `file_size` BIGINT NOT NULL,
  `file_type` VARCHAR(100) NOT NULL,
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`folder_id`) REFERENCES `folders`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Favorites table for favorite files (unique per user+file)
CREATE TABLE IF NOT EXISTS `favorites` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `file_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`file_id`) REFERENCES `files`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `ux_favorites_user_file` (`user_id`, `file_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_folders_user ON `folders` (`user_id`);
CREATE INDEX IF NOT EXISTS idx_files_user ON `files` (`user_id`);
CREATE INDEX IF NOT EXISTS idx_files_folder ON `files` (`folder_id`);
CREATE INDEX IF NOT EXISTS idx_favorites_user ON `favorites` (`user_id`);
CREATE INDEX IF NOT EXISTS idx_favorites_file ON `favorites` (`file_id`);

-- Create a default admin user (password: admin123)
INSERT INTO `users` (`email`, `password`) VALUES ('admin@example.com', MD5('admin123'));

-- Create some sample folders
INSERT INTO `folders` (`user_id`, `folder_name`) VALUES (1, 'Documents');
INSERT INTO `folders` (`user_id`, `folder_name`, `parent_id`) VALUES (1, 'Projects', 1);
INSERT INTO `folders` (`user_id`, `folder_name`) VALUES (1, 'Images');

-- Create a sample file record
INSERT INTO `files` (
  `user_id`, 
  `folder_id`, 
  `filename`, 
  `filepath`, 
  `file_size`, 
  `file_type`,
  `deleted_at`
) VALUES (
  1, 
  1, 
  'Example Document', 
  'uploads/user_1/folder_1/example.txt', 
  1024, 
  'text/plain',
  NULL
);






-- ALTER TABLE `files`
--   ADD COLUMN `deleted_at` DATETIME DEFAULT NULL;