\# AGENTS.md — mss-project



\## PENTING — ROOT PROJECT

Project utama dan SATU-SATUNYA target semua perubahan adalah folder ini:

C:\\Users\\ASUS\\mss-project



Folder C:\\Users\\ASUS\\Herd\\referenceeric HANYA referensi untuk dibaca,

JANGAN PERNAH ditulis/diedit. Jika ada file dengan nama sama di kedua

folder, yang dimaksud selalu C:\\Users\\ASUS\\mss-project kecuali

disebutkan eksplisit "dari referenceeric".



Setiap kali menyebutkan kondisi suatu file ("file X sudah ada",

"kolom Y begini"), WAJIB sertakan path lengkap absolut yang benar-benar

dibaca. Jika tidak yakin file itu dari project mana, katakan

"saya tidak yakin, perlu cek ulang" dan baca ulang dengan path lengkap

C:\\Users\\ASUS\\mss-project\\... secara eksplisit.



\## ATURAN WAJIB UNTUK EDIT FILE (supaya tidak gagal apply)



1\. Untuk file BARU atau file yang perubahannya lebih dari 30% isinya:

&#x20;  GUNAKAN overwrite/tulis ulang seluruh file (create\_file atau tool

&#x20;  sejenis), JANGAN gunakan find\_and\_replace/diff parsial.



2\. Untuk perubahan KECIL di file yang sudah ada (di bawah 30% isi file):

&#x20;  Gunakan find\_and\_replace, TAPI potongan "cari" (search string) WAJIB

&#x20;  pendek — maksimal 5-10 baris. Jangan sertakan blok kode panjang

&#x20;  (lebih dari 15 baris) sebagai target pencarian.



3\. JANGAN mengubah lebih dari satu bagian besar dalam satu file dalam

&#x20;  satu kali panggilan tool. Pecah jadi beberapa panggilan berurutan,

&#x20;  laporkan hasil tiap panggilan sebelum lanjut.



4\. SEBELUM find\_and\_replace, baca ulang file tersebut langsung dari

&#x20;  disk di panggilan tool sebelumnya. Jangan andalkan isi file yang

&#x20;  dibaca di awal sesi.



5\. Jika tool edit GAGAL diterapkan, JANGAN mencoba lagi dengan variasi

&#x20;  teks mirip berkali-kali. STOP, laporkan: nama file, potongan teks

&#x20;  yang dicari, alasan kemungkinan gagal. Tunggu instruksi lanjutan.



6\. Untuk file .blade.php: DEFAULT ke overwrite penuh file. Jangan coba

&#x20;  find\_and\_replace parsial kecuali perubahan 1 baris tunggal yang unik.



7\. Batasi satu panggilan tool untuk maksimal sekitar 200-300 baris kode.

&#x20;  Jika file lebih panjang, informasikan dulu akan ditulis bertahap.



\## ATURAN PENULISAN KODE



\- Kode rapi, terstruktur, konsisten dengan gaya kode yang sudah ada

\- Tidak ada emoji di kode, komentar, string konstanta, maupun variabel

\- Komentar ditulis dalam Bahasa Indonesia yang jelas

\- Setiap method baru wajib docblock singkat (parameter + return)

\- Hapus kode yang tidak dipakai, jangan di-comment out tanpa alasan

\- Gunakan early return untuk menghindari nesting yang dalam



\## TAMPILAN (VIEW / BLADE)



\- Warna aksen utama: teal #0E9E8E (bukan biru)

\- Sesuaikan gaya Tailwind yang sudah dipakai di file lain mss-project,

&#x20; contoh referensi: resources/views/cm/index.blade.php

\- layouts/app.blade.php mss-project memakai @yield('page-title') dan

&#x20; @yield('page-sub') — BUKAN @yield('breadcrumb'). Jangan pernah ganti

&#x20; ke struktur breadcrumb.

\- Tidak ada inline style kecuali untuk nilai dinamis



\## ALUR KERJA



\- Sebutkan daftar file yang akan disentuh di awal sesi

\- Setiap file yang diubah ditulis ulang secara lengkap

\- Di akhir sesi berikan ringkasan perubahan dan instruksi deploy

\- Jika ada file yang dibutuhkan tapi belum dilampirkan, minta dulu

\- Jangan mengarang isi file yang belum dilihat langsung dari disk

