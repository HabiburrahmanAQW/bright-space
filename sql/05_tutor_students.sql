-- BRIGHT SPACE Tahap 7C Revisi: satu siswa dibuat/dikelola oleh satu tutor.
-- Impor sekali ke database bright_space setelah sql/03_progress_misi.sql.
-- Aman untuk database lama: tidak menghapus siswa/tutor yang sudah ada.
CREATE TABLE IF NOT EXISTS bs_tutor_students (
    student_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    tutor_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_bs_tutor_students_tutor (tutor_id),
    CONSTRAINT fk_bsts_student FOREIGN KEY (student_id) REFERENCES bs_students(id) ON DELETE CASCADE,
    CONSTRAINT fk_bsts_tutor FOREIGN KEY (tutor_id) REFERENCES bs_tutors(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
