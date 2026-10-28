-- BRIGHT SPACE Tahap 5; impor ke database bright_space setelah Tahap 4.
-- Aman untuk dijalankan ulang (CREATE TABLE IF NOT EXISTS). Tidak mengubah tabel legacy.
-- Penting: kredensial siswa baru berada pada bs_students, bukan tabel users legacy.
CREATE TABLE IF NOT EXISTS bs_students (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(40) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    star_coins INT UNSIGNED NOT NULL DEFAULT 120,
    total_xp INT UNSIGNED NOT NULL DEFAULT 0,
    level SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_bs_students_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bs_student_mission_progress (
    student_id BIGINT UNSIGNED NOT NULL,
    mission_id BIGINT UNSIGNED NOT NULL,
    attempts_count INT UNSIGNED NOT NULL DEFAULT 1,
    best_correct SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    best_total SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    first_completed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_completed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    first_reward_coins INT UNSIGNED NOT NULL DEFAULT 0,
    first_reward_xp INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (student_id, mission_id),
    CONSTRAINT fk_bsp_student FOREIGN KEY (student_id) REFERENCES bs_students(id) ON DELETE CASCADE,
    CONSTRAINT fk_bsp_mission FOREIGN KEY (mission_id) REFERENCES bs_missions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bs_mission_attempts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    mission_id BIGINT UNSIGNED NOT NULL,
    correct_count SMALLINT UNSIGNED NOT NULL,
    question_count SMALLINT UNSIGNED NOT NULL,
    finished_reason ENUM('completed','timeout') NOT NULL,
    awarded_coins INT UNSIGNED NOT NULL DEFAULT 0,
    awarded_xp INT UNSIGNED NOT NULL DEFAULT 0,
    completed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_bsa_student_time (student_id,completed_at),
    KEY idx_bsa_mission (mission_id),
    CONSTRAINT fk_bsa_student FOREIGN KEY (student_id) REFERENCES bs_students(id) ON DELETE CASCADE,
    CONSTRAINT fk_bsa_mission FOREIGN KEY (mission_id) REFERENCES bs_missions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
