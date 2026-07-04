# Judul Skripsi

Perancangan Sistem Pendukung Keputusan Maintenance dan Pengadaan Spare Part
Terintegrasi Berbasis Predictive dan Prescriptive Analytics pada Industri
Manufaktur

---

## A. Konteks Sistem

| Item | Keterangan |
|------|-----------|
| Nama sistem | Managerial Support System (MSS) |
| Stack | Laravel 13, PHP 8.3, SQLite (default), Tailwind, Alpine.js |
| Entitas dalam scope | PT Citra Enggal Sejahtera (CES) dan PT Nutripro Prima Asia (NPA) |
| Entitas di luar scope | PT EPE — tidak dibahas di skripsi ini |
| Level skripsi | **PERANCANGAN / DESAIN (mockup/prototipe)** — bukan pembuktian hasil yang valid secara statistik/operasional. Fitur boleh dirancang meski data belum sepenuhnya matang, asalkan keterbatasannya dinyatakan jujur, bukan diklaim sebagai hasil yang sudah valid. |
| Status implementasi | Sistem masih tahap development, belum dipakai operasional |

### Data Valid

| Dataset | Jumlah | Rentang | Per Asset | Jenis Data | Digunakan Untuk |
|---------|--------|---------|-----------|------------|-----------------|
| CM Measurements | **1.691** | **2024-01-01 s.d 2026-02-01** (25 bln) | 2-26 record (rata2 21,4) | Periodik rutin (vibrasi & temperatur) | Risk scoring (tren) |
| CM Findings | **84** | 2026-02-23 s.d 2026-06-26 (~4 bln) | 1-4 record (rata2 1,7) | Temuan visual | Kriteria AHP (severity, frekuensi) |
| Maintenance Reports | **8 (dummy)** | 1 hari | - | Data uji/placeholder | Weibull, Forecasting, Kriteria AHP |
| Assets | **79** | - | - | Master data | Subjek analisis |
| Spare Parts | (ada) | - | - | Master data | BOK Logistik |

**Catatan:** Maintenance Reports saat ini berisi data dummy (8 record, 1 hari). Semua fitur yang menggunakan data ini berjalan sebagai demonstrasi mekanisme, bukan hasil operasional riil.

### Detail CM Measurements

- 1.691 record tersebar di 79 asset. Rata-rata 21,4 per asset. Min 2, Max 26.
- ~57 asset punya 26 record = 1x pengukuran per bulan selama ~2 tahun.
- Data vibrasi: 12 titik per pengukuran (Driver DE/NDE, Driven DE/NDE, V/H/A).
- Data temperatur: 4 titik per pengukuran (Driver DE/NDE, Driven DE/NDE).

---

## B. Tiga Bidang Kompetensi (BOK)

1. **BOK Maintenance** — prioritas equipment, predictive risk, rekomendasi maintenance
2. **BOK Logistik** — manajemen spare part, pengadaan
3. **BOK Cost** — estimasi biaya downtime, lembur, cost saving

---

## C. Tiga Lapisan Analitik per BOK

---

### 1. Descriptive (Existing)

**Status: ✅ [SUDAH IMPLEMENTASI PENUH]**

Dashboard KPI, chart, tren — berjalan di `DashboardController` dan `DssController`.

Data yang sudah ditampilkan:
- KPI cards (total asset, equipment danger, laporan, downtime, lembur, jam kerja)
- Tren laporan 7 hari (bar chart), Top 5 equipment rusak
- CM Alert (findings high/critical masih open)
- Equipment danger list, sparepart kritis
- Downtime vs KPI, Lembur vs KPI
- Distribusi jenis pekerjaan, Top 10 equipment (6 bln)
- Produktivitas karyawan, Severity CM
- Timeline CM per equipment, Downtime per company
- Chart downtime bulanan NPA vs CES

---

### 2. Predictive

#### 2a. Trend-Based Risk Scoring

**Status: 🔧 [BELUM IMPLEMENTASI — Akan Dibangun]**

**Metode:** Risk scoring berbasis tren data CM periodik (vibrasi & temperatur).

**Input Data:**
- `cm_measurements`: 12 titik vibrasi + 4 titik temperatur
- 26 titik per asset (~2 tahun, periodik bulanan) — cukup untuk regresi linier sederhana

**Komponen Skor Risiko:**
1. **Trend slope** — koefisien regresi linier dari N pengukuran terakhir per parameter
2. **Rate of change** — nilai terakhir / rata-rata beberapa pengukuran sebelumnya
3. **Weighted composite score** — gabungan bobot parameter → skor 0-100
4. **Kategori risiko** — rendah / sedang / tinggi

**Output (satu modul, bukan terpisah):**
1. Skor risiko equipment (kategori rendah/sedang/tinggi)
2. Proyeksi arah tren sederhana — misal: "Vibrasi DE naik 12% dalam 6 bulan terakhir" (sebagai informasi pendukung, **bukan model forecasting terpisah**)

**Catatan desain:**
- Data 26 titik per asset cukup untuk linear regression / moving average sederhana
- Tidak dirancang untuk model musiman/seasonal
- Bobot dan formula final masih perlu ditentukan berdasarkan literatur
- Perlu literatur pendukung: ISO 10816, paper vibration trend analysis

#### 2b. Weibull Reliability Fitting

**Status: 🏗️ [ARSITEKTUR SIAP — LOGIKA MENYUSUL (Future Development)]**

Keputusan final:
- **Arsitektur disiapkan:** migration tabel `weibull_results`, model Eloquent, service class skeleton.
- **Logika fitting matematis (least square, beta/eta/R(t)) TIDAK diimplementasikan penuh sekarang.**

**Alasan:**
- CM Findings (84 record, 50 asset, rata-rata 1,7/asset dalam 4 bulan) terlalu sedikit untuk hasil fitting yang bermakna secara statistik.
- Waktu pengerjaan fitting yang benar butuh effort signifikan untuk hasil yang belum tentu reliable.
- Data kegagalan eksplisit masih sangat terbatas.

**Dokumentasi wajib di skripsi:**
- Fitur ini ditandai sebagai **"future development"** — arsitektur siap, logika menyusul setelah data kegagalan operasional terkumpul cukup (di luar timeline skripsi ini).
- Disebut sebagai bukti kesiapan sistem dikembangkan ke arah reliability engineering, bukan sebagai fitur yang sudah berfungsi.
- Tidak boleh disajikan seolah-olah sudah menghasilkan output Weibull yang valid.

#### 2c. Forecasting Downtime (Exponential Smoothing / Moving Average)

**Status: 🏗️ [ARSITEKTUR SIAP + DEMONSTRASI DENGAN DATA DUMMY]**

Keputusan berubah dari sebelumnya: **tidak di-drop**. Sama seperti Weibull, arsitektur disiapkan (migration, model, service class), DAN logika forecasting (exponential smoothing / moving average — metode time-series yang mapan dan terverifikasi) **tetap dijalankan sebagai demonstrasi**, memakai data yang ada saat ini walaupun `maintenance_reports` masih dummy.

**Alasan tetap dijalankan (sebagai demonstrasi):**
- Metode exponential smoothing / moving average adalah metode time-series yang sudah mapan dan terverifikasi di literatur, bukan istilah yang dikarang.
- Mekanisme forecasting-nya valid secara teknis — yang kurang hanyalah data input yang representatif.
- Begitu `maintenance_reports` mulai terisi data riil, forecasting akan otomatis menjadi lebih bermakna tanpa perlu mengubah logika.

**Dokumentasi wajib di skripsi:**
- **WAJIB dinyatakan eksplisit sebagai "demonstrasi mekanisme dengan data dummy/placeholder, karena data operasional riil belum tersedia".**
- Jangan disajikan seolah-olah merupakan hasil dari data operasional nyata.
- Disebut sebagai bukti bahwa mekanisme forecasting sudah siap secara teknis.

---

### 3. Prescriptive

#### 3a. BOK Maintenance: AHP + TOPSIS

**Status: 🔧 [BELUM IMPLEMENTASI — Akan Dibangun]**

**AHP (Analytic Hierarchy Process)**
- Tujuan: pembobotan kriteria prioritas equipment
- Input: pairwise comparison dari user/pakar (skala Saaty 1-9)
- Output: bobot akhir per kriteria + Consistency Ratio (CR)
- **Tidak tergantung data historis** — input dari pakar

**Kriteria AHP:**

| Kriteria | Sumber Data | Catatan untuk Skripsi |
|----------|-------------|----------------------|
| Severity | `cm_findings.severity` | ✅ Data tersedia (84 record, valid untuk mockup) |
| Frekuensi kerusakan | `cm_findings` per asset | ✅ Data tersedia |
| Downtime | `maintenance_reports.downtime_minutes` | ⚠️ **Keterbatasan: data saat ini dummy.** Tetap muncul di desain sebagai kriteria yang relevan secara konseptual, dicatat sebagai keterbatasan. |
| MTBF | Selisih tanggal laporan maintenance | ⚠️ **Keterbatasan: data saat ini tipis.** Sama seperti Weibull — akan makin valid seiring waktu recording berjalan. Tetap muncul di desain (level perancangan), dicatat sebagai keterbatasan. |
| Status stok sparepart terkait | `spare_parts.stok_tersedia` | ✅ Data tersedia |

**TOPSIS (Technique for Order Preference by Similarity to Ideal Solution)**
- Tujuan: perankingan equipment berdasarkan bobot AHP
- Input: matriks keputusan (nilai kriteria per asset) x bobot AHP
- Output: ranking equipment prioritas maintenance

**Rekomendasi KPI (tambahan)**
- Rekomendasi membantu manajemen mencapai target KPI downtime & lembur
- Berbasis threshold/proyeksi sederhana — logika masih perlu didesain

#### 3b. BOK Logistik: Pengadaan Spare Part

**Status: 🔧 [SEBAGIAN — Alert Akan Dibangun, EOQ/ROP TENTATIF]**

**Sudah dirancang (masuk implementasi):**
- Alert rekomendasi pengadaan spare part berdasarkan:
  - Hasil ranking AHP-TOPSIS (equipment prioritas → sparepart terkait)
  - Status stok saat ini (kritis/aman/habis)

**Tentatif (menunggu konfirmasi):**
- **EOQ (Economic Order Quantity) dan ROP (Reorder Point + Safety Stock)**: ❓ TENTATIF
  - Akan dikerjakan jika tim logistik mengonfirmasi data biaya pesan (ordering cost) dan biaya simpan (holding cost).
  - Data biaya belum ada di skema `spare_parts` — perlu field baru: `harga_satuan`, `biaya_pesan`, `biaya_simpan`.
  - Jika tidak jadi, cukup alert berbasis status stok + prioritas AHP-TOPSIS.

#### 3c. BOK Cost: Estimasi Biaya Maintenance

**Status: 🔧 [BELUM IMPLEMENTASI — Akan Dibangun]**

- **Biaya downtime**: `downtime_minutes` x rate per menit (rate input manual)
- **Biaya lembur**: `overtime_hours` x rate per jam (rate manual)
- **Cost saving**: konsep perbandingan biaya prediktif vs reaktif (literatur emergency procurement)
- **Tidak ada harga per spare part** — keputusan desain sadar

---

## D. Ringkasan Status Implementasi Per Fitur

| Fitur | BOK | Status |
|-------|-----|--------|
| Dashboard KPI (Descriptive) | Maintenance | ✅ SUDAH IMPLEMENTASI PENUH |
| Trend-based risk scoring (Predictive) | Maintenance | 🔧 BELUM — akan dibangun |
| Weibull reliability | Maintenance | 🏗️ ARSITEKTUR SIAP — logika menyusul (future dev) |
| Forecasting downtime (ES/MA) | Maintenance | 🏗️ ARSITEKTUR SIAP + demonstrasi dg data dummy |
| AHP + TOPSIS | Maintenance | 🔧 BELUM — akan dibangun |
| Alert spare part (stok based) | Logistik | 🔧 BELUM — akan dibangun |
| EOQ / ROP | Logistik | ❓ TENTATIF — tunggu konfirmasi tim |
| Cost breakdown (downtime + lembur) | Cost | 🔧 BELUM — akan dibangun |

**Keterangan status:**
- ✅ **SUDAH IMPLEMENTASI PENUH** — fitur berjalan dengan data riil.
- 🔧 **BELUM — akan dibangun** — fitur baru yang direncanakan untuk skripsi.
- 🏗️ **ARSITEKTUR SIAP** — struktur kode (migration, model, service skeleton) dibuat, logika inti belum/baru sebagian.
- ❓ **TENTATIF** — tergantung ketersediaan data/konfirmasi pihak terkait.

---

## E. Ruang Lingkup — Tidak Termasuk

- Fungsi Work Order (sudah ditangani sistem lain)
- Integrasi real-time dengan ERP/sistem keuangan
- Implementasi/deployment ke lingkungan pabrik (skripsi sampai tahap desain)
- Pembuktian statistik hasil prediksi (level skripsi: perancangan)

---

## F. Prioritas Pengembangan

| Prioritas | Modul | BOK | Status Data |
|-----------|-------|-----|-------------|
| **1** | AHP + TOPSIS untuk prioritas equipment | Maintenance | Data kriteria tersedia (dengan catatan keterbatasan MTBF & downtime) |
| **2** | Trend-based risk scoring (predictive) | Maintenance | 1.691 record CM, 25 bulan ✅ |
| **3** | Cost breakdown (downtime + lembur) | Cost | Butuh input rate manual |
| **4** | Forecasting downtime (ES/MA) — demonstrasi | Maintenance | Data dummy ⚠️ |
| **5** | Prescriptive KPI downtime/lembur | Maintenance | Logika perlu didesain |
| **6** | Alert spare part (stok + prioritas) | Logistik | Data stok tersedia ✅ |
| **7** | Weibull (arsitektur saja) | Maintenance | Skeleton class + migration |
| **8** | EOQ/ROP | Logistik | Tentatif ❓ |
