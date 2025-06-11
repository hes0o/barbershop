-- Table for barber's default schedule
CREATE TABLE IF NOT EXISTS barber_schedule (
    id INT PRIMARY KEY AUTO_INCREMENT,
    barber_id INT NOT NULL,
    day_of_week VARCHAR(10) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status ENUM('available', 'unavailable') NOT NULL DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (barber_id) REFERENCES barbers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_barber_day (barber_id, day_of_week)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for barber's schedule overrides
CREATE TABLE IF NOT EXISTS barber_schedule_override (
    id INT PRIMARY KEY AUTO_INCREMENT,
    barber_id INT NOT NULL,
    date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status ENUM('available', 'unavailable') NOT NULL DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (barber_id) REFERENCES barbers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_barber_date (barber_id, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create barber_schedule table if it doesn't exist
CREATE TABLE IF NOT EXISTS `barber_schedule` (
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