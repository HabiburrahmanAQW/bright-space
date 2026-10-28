-- BRIGHT SPACE - TAHAP 1
-- Jalankan pada database `bright_space` melalui phpMyAdmin.
-- Hanya membuat tabel baru dengan prefiks bs_. Tidak DROP / ALTER tabel lama.
-- Pastikan memilih database yang tepat sebelum impor.

CREATE TABLE IF NOT EXISTS bs_tutors (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(40) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('tutor', 'admin') NOT NULL DEFAULT 'tutor',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_bs_tutors_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bs_planets (
    id VARCHAR(64) NOT NULL PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    description VARCHAR(500) NOT NULL DEFAULT '',
    required_level SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    theme_key ENUM('indigo', 'emerald', 'amber', 'violet', 'rose', 'sky') NOT NULL DEFAULT 'indigo',
    icon_key ENUM('matematika', 'sains', 'bahasa', 'seni-coding', 'umum') NOT NULL DEFAULT 'umum',
    publication_status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_bs_planets_status_level (publication_status, required_level),
    KEY idx_bs_planets_creator (created_by),
    CONSTRAINT fk_bs_planets_tutor FOREIGN KEY (created_by)
      REFERENCES bs_tutors (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bs_missions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    planet_id VARCHAR(64) NOT NULL,
    category ENUM('wajib', 'side') NOT NULL,
    mode_type ENUM('kuis', 'story', 'puzzle', 'matching', 'latihan') NOT NULL,
    title VARCHAR(160) NOT NULL,
    description VARCHAR(500) NOT NULL DEFAULT '',
    reward_coins INT UNSIGNED NOT NULL DEFAULT 0,
    timer_seconds INT UNSIGNED NOT NULL DEFAULT 300,
    question_target SMALLINT UNSIGNED NOT NULL DEFAULT 10,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    publication_status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_bs_missions_planet_category (planet_id, category, publication_status, sort_order),
    CONSTRAINT fk_bs_missions_planet FOREIGN KEY (planet_id)
      REFERENCES bs_planets (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
