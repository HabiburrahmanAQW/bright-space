-- Tahap 4. Jalankan pada database bright_space setelah Tahap 1-3.
-- Membuat satu tabel baru, tidak mengubah tabel users/soal_bank legacy.
CREATE TABLE IF NOT EXISTS bs_mission_questions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    mission_id BIGINT UNSIGNED NOT NULL,
    mode_type ENUM('kuis','story','puzzle','matching','latihan') NOT NULL,
    prompt TEXT NOT NULL,
    payload_json JSON NOT NULL,
    explanation TEXT NOT NULL,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_bsmq_mission_sort (mission_id, mode_type, sort_order, id),
    CONSTRAINT fk_bsmq_mission FOREIGN KEY (mission_id)
      REFERENCES bs_missions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
