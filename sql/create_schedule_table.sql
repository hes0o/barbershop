-- Drop existing table if it exists
DROP TABLE IF EXISTS `barber_schedule`;

-- Create barber_schedule table
CREATE TABLE `barber_schedule` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `barber_id` int(11) NOT NULL,
    `day_of_week` varchar(10) NOT NULL,
    `start_time` time NOT NULL,
    `end_time` time NOT NULL,
    `status` enum('available','unavailable') NOT NULL DEFAULT 'available',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `barber_day` (`barber_id`, `day_of_week`),
    KEY `barber_id` (`barber_id`),
    CONSTRAINT `barber_schedule_ibfk_1` FOREIGN KEY (`barber_id`) REFERENCES `barbers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 