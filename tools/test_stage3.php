<?php
/** Test murni adapter tanpa membutuhkan MySQL. */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
define('BRIGHT_SPACE', true);
require_once __DIR__ . '/../config/student_mission_bridge.php';
function check_stage3(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException('TEST GAGAL: ' . $message);
}
$legacy = [
    ['id'=>'matematika','nama'=>'Planet Matematika','level'=>0,'status'=>'Terbuka','deskripsi'=>'','ikon'=>'<svg/>','gradien'=>'','border'=>'','tombol'=>''],
    ['id'=>'seni-coding','nama'=>'Planet Seni & Coding','level'=>5,'status'=>'Terkunci','deskripsi'=>'','ikon'=>'<svg/>','gradien'=>'','border'=>'','tombol'=>''],
];
$rows = [
    ['id'=>'sejarah','name'=>'Planet Sejarah','description'=>'Tes','required_level'=>3,'theme_key'=>'rose','icon_key'=>'umum','publication_status'=>'published'],
    ['id'=>'draft-1','name'=>'Draft','description'=>'','required_level'=>0,'theme_key'=>'sky','icon_key'=>'umum','publication_status'=>'draft'],
    ['id'=>'matematika','name'=>'Pengganti Legacy','description'=>'','required_level'=>0,'theme_key'=>'amber','icon_key'=>'umum','publication_status'=>'draft'],
];
$level2 = bs_student_merge_planets($legacy, $rows, 2);
check_stage3(count($level2) === 2, 'draft tidak boleh tampil, termasuk jika ID menimpa planet lama');
check_stage3($level2[0]['id'] === 'seni-coding' && $level2[0]['status'] === 'Terkunci', 'status legacy mengikuti level');
check_stage3($level2[1]['id'] === 'sejarah' && $level2[1]['status'] === 'Terkunci', 'published terkunci untuk level 2');
$level5 = bs_student_merge_planets($legacy, $rows, 5);
check_stage3($level5[0]['status'] === 'Terbuka' && $level5[1]['status'] === 'Terbuka', 'planet terbuka setelah mencapai level');
check_stage3(str_contains($level5[1]['ikon'], 'class="w-full h-full"'), 'SVG preview mengikuti ukuran wrapper');
$modes = bs_student_modes_from_rows([
    ['id'=>19,'publication_status'=>'published','mode_type'=>'kuis','category'=>'wajib','title'=>'Sesi pertama','description'=>'','reward_coins'=>20,'timer_seconds'=>300,'question_target'=>5],
    ['id'=>20,'publication_status'=>'published','mode_type'=>'kuis','category'=>'side','title'=>'Sesi kedua','description'=>'','reward_coins'=>10,'timer_seconds'=>0,'question_target'=>4],
    ['id'=>21,'publication_status'=>'draft','mode_type'=>'matching','category'=>'side','title'=>'Rahasia','description'=>'','reward_coins'=>10,'timer_seconds'=>0,'question_target'=>4],
], ['kuis'=>['warna'=>['bg'=>'from-blue-100 to-indigo-200','border'=>'border-blue-300','tombol'=>'bg-blue-600']]]);
check_stage3(count($modes) === 2 && isset($modes['mission-19'], $modes['mission-20']), 'ID tiap misi unik; draft tersembunyi');
check_stage3($modes['mission-20']['kategori'] === 'side' && $modes['mission-19']['kategori'] === 'wajib', 'kategori independen dari gamemode');
echo "OK: seluruh tes adapter Tahap 3 lulus.\n";
