-- BRIGHT SPACE Tahap 6B. Jalankan sekali di database bright_space.
-- Backup dahulu; tidak menghapus tabel data atau mengubah data siswa yang sudah ada.
CREATE TABLE IF NOT EXISTS bs_student_items (
    student_id BIGINT UNSIGNED NOT NULL,
    item_id VARCHAR(80) NOT NULL,
    is_equipped TINYINT(1) NOT NULL DEFAULT 0,
    purchased_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (student_id,item_id),
    CONSTRAINT fk_bs_item_student FOREIGN KEY (student_id) REFERENCES bs_students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Snapshot publikasi. Soal baru/edit/hapus tidak mengubah versi siswa sebelum Update Publikasi.
CREATE TABLE IF NOT EXISTS bs_published_questions (
    mission_id BIGINT UNSIGNED NOT NULL,
    source_question_id BIGINT UNSIGNED NOT NULL,
    mode_type ENUM('kuis','story','puzzle','matching','latihan') NOT NULL,
    prompt TEXT NOT NULL,
    payload_json JSON NOT NULL,
    explanation TEXT NOT NULL,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    published_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (mission_id,source_question_id),
    KEY idx_bspq_order (mission_id,mode_type,sort_order,source_question_id),
    CONSTRAINT fk_bspq_mission FOREIGN KEY (mission_id) REFERENCES bs_missions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Lanjutkan dengan: php tools/init_stage6b.php
-- Script bootstrap hanya mengisi snapshot untuk misi terpublikasi yang belum punya snapshot.
