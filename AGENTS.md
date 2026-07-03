# AGENTS.md — mss-project

## PENTING — ROOT PROJECT
Project utama dan SATU-SATUNYA target semua perubahan:
C:\Users\ASUS\mss-project

Folder C:\Users\ASUS\Herd\referenceeric HANYA referensi untuk dibaca,
JANGAN PERNAH ditulis/diedit. Folder C:\Users\ASUS\Herd\referenceeric-old-backup
adalah arsip lama, tidak dipakai.

Setiap kali menyebutkan kondisi suatu file ("file X sudah ada", "kolom
Y begini"), WAJIB sertakan path lengkap absolut yang benar-benar dibaca
saat itu juga. Jangan mengandalkan laporan dari sesi sebelumnya tanpa
verifikasi ulang.


## KEPUTUSAN ARSITEKTUR FINAL (JANGAN TANYA ULANG, SUDAH DIPUTUSKAN)

1. TIDAK memakai model Technician atau Report dari referenceeric.
   Pakai yang sudah ada di mss-project:
   - Teknisi/user bot -> App\Models\Employee (tabel employees),
     kolom telegram_id (bigint, nullable, unique)
   - Laporan -> App\Models\MaintenanceReport (tabel maintenance_reports)
   Model/tabel Technician dan Report LAMA di mss-project TIDAK dihapus
   (masih ada sisa file, jangan disentuh), tapi TIDAK dipakai untuk
   fitur baru apapun.

2. Tabel maintenance_reports sudah punya kolom (JANGAN dibuat ulang):
   report_code, work_duration_minutes, root_cause,
   photo_documentation (json), wizard_started_at, submitted_at,
   ai_suggestion_json (json), ai_analyzed, ai_confidence,
   shift (enum '1','2','3','reguler', NOT NULL, fallback ke 'reguler'
   jika tidak diisi wizard).

3. ai_aliases: pakai employee_id (bukan technician_id), TIDAK ada
   kolom area_id sama sekali (dihapus dari desain, mss-project tidak
   punya konsep Area/functional_loc).

4. Asset di mss-project TIDAK punya tech_ident_no, functional_loc,
   atau area_id. Kolom yang ada: tag_no, description, company_id.
   Pencarian asset pakai tag_no + description saja.

5. Tema visual WAJIB: warna aksen teal #0E9E8E (BUKAN biru/blue seperti
   referenceeric). Vanilla JS (BUKAN Alpine.js x-data). Layout memakai
   @section('page-title', ...) dan @section('page-sub', ...) --
   BUKAN struktur @yield('breadcrumb'). Referensi pola styling yang
   sudah benar: resources/views/cm/index.blade.php dan
   resources/views/ai-providers/index.blade.php.

6. layouts/app.blade.php WAJIB punya @stack('scripts') sebelum </body>
   dan <meta name="csrf-token" content="{{ csrf_token() }}"> di <head>
   (sudah ditambahkan, jangan dihapus).

7. Config Telegram HANYA disimpan di config/telegram.php, diakses via
   config('telegram.bot_token'), config('telegram.bot_username'), dst.
   config/services.php JUGA punya key 'telegram' sebagai peninggalan —
   TIDAK dihapus, tapi kode BARU harus konsisten pakai
   config('telegram.*') saja, BUKAN config('services.telegram.*').

## ATURAN WAJIB EDIT FILE (supaya tidak gagal apply / macet)

1. File BARU atau perubahan >30% isi file: overwrite penuh
   (create_file), JANGAN find_and_replace/diff parsial.
2. Perubahan KECIL (<30%): find_and_replace dengan target pencarian
   PENDEK (maksimal 5-10 baris).
3. Satu file, satu bagian besar per panggilan tool. Pecah jadi
   beberapa panggilan berurutan untuk file besar (>250-300 baris),
   laporkan hasil tiap panggilan sebelum lanjut.
4. Baca ulang file dari disk SEBELUM find_and_replace, jangan andalkan
   isi yang dibaca di awal sesi atau giliran sebelumnya.
5. Jika tool edit GAGAL, STOP -- jangan coba lagi dengan variasi teks
   berkali-kali, dan JANGAN eskalasi ke PowerShell. Laporkan: nama
   file, potongan teks yang dicari, dugaan penyebab gagal. Tunggu
   instruksi lanjutan — opsi teraman adalah manusia menulis manual
   langsung di editor.
6. File .blade.php: DEFAULT overwrite penuh, hindari find_and_replace
   parsial kecuali perubahan 1 baris tunggal yang unik.
7. JANGAN PERNAH gunakan terminal/PowerShell/python -c untuk menulis
   isi file .php atau .blade.php -- selalu pakai tool file bawaan.
8. Baca file lewat tool baca file (view/read_file) langsung, JANGAN
   verifikasi keberadaan/isi file lewat command PowerShell dengan
   php -r atau file_exists() di terminal. Untuk file kritis, verifikasi
   ukuran file (byte) sebagai pengecekan tambahan, jangan hanya percaya
   isi yang ditampilkan tool.
9. SELALU gunakan path ABSOLUT lengkap (C:\Users\ASUS\mss-project\...)
   untuk operasi tulis/edit, tidak terkecuali. JANGAN PERNAH path
   relatif -- workspace ini multi-root dengan referenceeric dan path
   relatif terbukti bisa salah folder.

## RIWAYAT INSIDEN (pelajaran, jangan diulang)

1. bootstrap/app.php sempat KEHILANGAN tag pembuka <?php akibat proses
   edit yang gagal separuh jalan -- menyebabkan seluruh situs down
   total (fatal error "handleRequest() on int"). Pemicunya: mencoba
   menulis file PHP lewat command PowerShell dengan escaping karakter
   {{ }} dan kutip yang rumit. Kejadian SERUPA terulang lagi saat
   restrukturisasi routes/web.php (tool edit menolak perubahan >30%,
   lalu dipaksa lewat PowerShell heredoc, sempat gagal berkali-kali
   karena tool cache masih menganggap file lama ada padahal sudah
   dihapus). Akhirnya diselesaikan dengan menulis file baru secara
   MANUAL langsung di VSCode (New File, paste, save) — bukan lewat
   tool maupun PowerShell.

2. AdminMiddleware.php didaftarkan sebagai alias di bootstrap/app.php
   TAPI file class-nya tidak pernah benar-benar dibuat (folder
   app/Http/Middleware/ bahkan sempat tidak ada). Menyebabkan error
   "Target class AdminMiddleware does not exist". Laporan "sudah
   selesai" dari sesi sebelumnya TERBUKTI SALAH.

3. AI sempat salah membaca kondisi mss-project dengan mengambil isi
   dari referenceeric (2 folder terbuka di 1 workspace membingungkan
   tool baca file). Contoh: melaporkan layouts/app.blade.php mss-project
   pakai @yield('breadcrumb') padahal itu isi file referenceeric.

4. File config/telegram.php sempat menjadi 0 byte (kosong total)
   akibat proses tool write yang gagal secara diam-diam, menyebabkan
   config('telegram') mengembalikan integer 1 alih-alih array (ini
   adalah perilaku default PHP: file kosong yang di-include tanpa
   statement `return` akan menghasilkan return value 1). Akibatnya
   config('telegram.bot_token') selalu null walau .env sudah benar
   berisi TELEGRAM_BOT_TOKEN. Ditemukan lewat php artisan tinker:
   mengetik config('telegram') menampilkan angka 1, bukan array.
   Tool read_file bahkan sempat menampilkan ISI PALSU (isi yang
   seharusnya ada) padahal file di disk benar-benar 0 byte — tool
   tidak bisa dipercaya penuh untuk verifikasi, HARUS dicek ukuran
   file juga (misal lewat dir/ls), bukan cuma isi yang ditampilkan.

5. Tool create_new_file dan edit_existing_file sempat SALAH
   MENARGETKAN folder C:\Users\ASUS\Herd\referenceeric padahal
   diminta menulis ke C:\Users\ASUS\mss-project, karena workspace
   multi-root (dua folder dibuka sekaligus di satu window VSCode)
   membuat resolusi path relatif menjadi ambigu bagi tool. Ini
   ditemukan tidak sengaja saat debug Insiden #4. Karena referenceeric
   seharusnya read-only dan tidak punya git history untuk verifikasi
   kerusakan, folder tersebut di-CLONE ULANG dari GitHub
   (rikchodam-glitch/oleochemicalReport) untuk memastikan keasliannya.
   Folder lama disimpan sebagai referenceeric-old-backup (tidak
   dipakai lagi, hanya arsip).

<!-- Tambahkan insiden baru di bawah ini, nomor urut lanjut -->

## ATURAN PENULISAN KODE

- Kode rapi, konsisten gaya yang sudah ada
- Tidak ada emoji di kode, komentar, atau string apapun
- Komentar dalam Bahasa Indonesia
- Setiap method baru wajib docblock (parameter + return)
- Early return untuk hindari nesting dalam
- Hapus kode tidak terpakai, jangan di-comment out tanpa alasan

## ALUR KERJA SETIAP SESI BARU

1. Baca ulang file AGENTS.md ini  secara penuh sebelum
   mulai kerja apapun
2. Sebutkan file apa saja yang akan disentuh sebelum mulai edit
3. Untuk fitur baru/kompleks ATAU untuk bug fix: baca dan laporkan dulu
   kondisi file terkait (dari mss-project DAN referenceeric jika
   relevan), TUNGGU konfirmasi manusia sebelum menulis kode
4. Setiap file yang diubah ditulis ulang lengkap (kecuali perubahan
   kecil, lihat Aturan Wajib Edit File poin 2)
5. Jika file dibutuhkan tapi belum jelas isinya, baca dulu, jangan
   mengarang