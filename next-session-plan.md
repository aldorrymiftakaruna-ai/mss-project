# Rencana Pengembangan — Step by Step

## Path Repository
`C:\Users\ASUS\mss-project`

---

## Struktur Sidebar Target (Final)

Berikut adalah sidebar setelah semua fitur baru selesai. Menu baru ditandai ➕.

```
MENU UTAMA
├── Dashboard              ✅ existing
├── Equipment              ✅ existing
├── Laporan Maintenance    ✅ existing
├── Condition Monitoring   ✅ existing
├── Spare Part             ✅ existing
├── Karyawan               ✅ existing

ANALITIK
├── Decision Support System   ✅ existing (descriptive)
├── ─────────────────────────────────────────────
├── DSS Terintegrasi          ➕ BARU — waterfall predictive→prescriptive→cost→rekomendasi
├── AHP Prioritas             ➕ BARU — AHP pairwise + TOPSIS ranking
├── Predictive Risk           ➕ BARU — trend-based risk scoring per asset
├── Forecasting               ➕ BARU — demonstrasi ES/MA downtime
├── Analisis Biaya            ➕ BARU — cost breakdown + settings rate
├── Weibull                   ➕ BARU — skeleton (future development)

SISTEM
├── AI Providers           ✅ existing
├── Bot Telegram           ✅ existing
├── Pengaturan             ✅ existing
```

### Detail Menu Baru

| Menu | Route Name | Controller | View Folder | Status |
|------|-----------|------------|-------------|--------|
| DSS Terintegrasi | `dss.integrated` | `IntegratedDssController` | `integrated/` | 🔧 akan dibangun |
| AHP Prioritas | `ahp.index` | `AhpController` | `prescriptive/ahp/` | 🔧 akan dibangun |
| Predictive Risk | `predictive.index` | `PredictiveController` | `predictive/` | 🔧 akan dibangun |
| Forecasting | `forecast.index` | `ForecastController` | `predictive/` | 🔧 akan dibangun |
| Analisis Biaya | `cost.index` | `CostController` | `cost/` | 🔧 akan dibangun |
| Weibull | `weibull.index` | `WeibullController` | `predictive/` | 🏗️ arsitektur siap |

**File yang dimodifikasi:** `resources/views/layouts/app.blade.php` — tambah 6 menu baru di group `Analitik`.

---

## Fase 0: Migration & Model (1 hari)

Buat 10 migration + 10 model sekaligus sebagai fondasi data.

| # | Migration | Tabel | Fields |
|---|-----------|-------|--------|
| 0.1 | `create_cost_rates_table` | `cost_rates` | id, company_id, downtime_rate_per_min, overtime_rate_per_hour, effective_date, timestamps |
| 0.2 | `create_cost_analyses_table` | `cost_analyses` | id, maintenance_report_id, downtime_cost, overtime_cost, labor_cost, sparepart_cost, analyzed_at, timestamps |
| 0.3 | `create_risk_scores_table` | `risk_scores` | id, asset_id, score, category (enum: rendah/sedang/tinggi), parameters_json, calculated_at, timestamps |
| 0.4 | `create_ahp_sessions_table` | `ahp_sessions` | id, name, ahli_id, consistency_ratio, is_final, timestamps |
| 0.5 | `create_ahp_criteria_table` | `ahp_criteria` | id, ahp_session_id, name, label, weight, priority_vector, timestamps |
| 0.6 | `create_ahp_pairwise_table` | `ahp_pairwise` | id, ahp_session_id, criterion_a_id, criterion_b_id, value (decimal 1-9), timestamps |
| 0.7 | `create_topsis_results_table` | `topsis_results` | id, ahp_session_id, asset_id, score, ranking, calculated_at, timestamps |
| 0.8 | `create_weibull_results_table` | `weibull_results` | id, asset_id, beta, eta, mttf, reliability_at_period, parameters_json, calculated_at, timestamps (nullable semua) |
| 0.9 | `create_forecast_logs_table` | `forecast_logs` | id, model_type (ES/MA), period, actual_value, forecast_value, absolute_error, calculated_at, timestamps |
| 0.10 | `create_decision_recommendations_table` | `decision_recommendations` | id, asset_id, recommendation_type, priority_score, description, created_at, timestamps |

**Output:** 10 migration + 10 model (`CostRate`, `CostAnalysis`, `RiskScore`, `AhpSession`, `AhpCriterion`, `AhpPairwise`, `TopsisResult`, `WeibullResult`, `ForecastLog`, `DecisionRecommendation`).

---

## Fase 1: AHP + TOPSIS (3-4 hari)

### 1.1 AHP Service
| Item | Detail |
|------|--------|
| File | `app/Services/Prescriptive/AhpService.php` |
| Method | `createSession()`, `addPairwise()`, `calculateWeights()`, `calculateCR()`, `getResult()` |
| Logic | Normalisasi matriks pairwise, eigenvector, Consistency Ratio = CI / RI |

### 1.2 AHP Controller
| Item | Detail |
|------|--------|
| File | `app/Http/Controllers/AhpController.php` |
| Method | `index()`, `create()`, `storePairwise()`, `result()`, `history()` |

### 1.3 View: Input Kriteria
| Item | Detail |
|------|--------|
| File | `resources/views/prescriptive/ahp/criteria.blade.php` |
| Isi | Form tambah kriteria (nama, label) |

### 1.4 View: Pairwise Matrix
| Item | Detail |
|------|--------|
| File | `resources/views/prescriptive/ahp/pairwise.blade.php` |
| Isi | Tabel perbandingan berpasangan skala Saaty 1-9 |

### 1.5 View: Hasil AHP
| Item | Detail |
|------|--------|
| File | `resources/views/prescriptive/ahp/result.blade.php` |
| Isi | Tabel bobot per kriteria + CR + status konsisten/tidak |

### 1.6 TOPSIS Service
| Item | Detail |
|------|--------|
| File | `app/Services/Prescriptive/TopsisService.php` |
| Method | `setDecisionMatrix()`, `normalize()`, `applyWeights()`, `idealSolution()`, `calculateRanking()` |
| Input | Data real dari database (cm_findings, maintenance_reports, spare_parts) |

### 1.7 TOPSIS Controller
| Item | Detail |
|------|--------|
| Route | Tambahan di `AhpController` atau controller baru |
| Method | `calculateRanking()` ambil semua asset + nilai kriteria + proses TOPSIS |

### 1.8 View: Ranking TOPSIS
| Item | Detail |
|------|--------|
| File | `resources/views/prescriptive/topsis/ranking.blade.php` |
| Isi | Tabel ranking equipment + score + link detail |

---

## Fase 2: Trend-Based Risk Scoring (2-3 hari)

### 2.1 Risk Score Service
| Item | Detail |
|------|--------|
| File | `app/Services/Predictive/RiskScoreService.php` |
| Method | `calculateTrendSlope()` (linear regression N titik), `calculateRateOfChange()`, `compositeScore()`, `classifyRisk()` |

### 2.2 Config
| Item | Detail |
|------|--------|
| File | `config/risk-score.php` |
| Isi | Bobot vibrasi vs temperatur, jumlah N, threshold skor |

### 2.3 Controller
| Item | Detail |
|------|--------|
| File | `app/Http/Controllers/PredictiveController.php` |
| Method | `index()`, `detail($asset)`, `recalculate()` (AJAX) |

### 2.4 View: Dashboard Risiko
| Item | Detail |
|------|--------|
| File | `resources/views/predictive/index.blade.php` |
| Isi | Kartu risiko per asset (hijau/kuning/merah) |

### 2.5 View: Detail Equipment
| Item | Detail |
|------|--------|
| File | `resources/views/predictive/detail.blade.php` |
| Isi | Grafik tren vibrasi per titik ukur + proyeksi arah tren |

---

## Fase 3: Cost Breakdown (1-2 hari)

### 3.1 Cost Service
| Item | Detail |
|------|--------|
| File | `app/Services/Cost/MaintenanceCostService.php` |
| Method | `calculateDowntimeCost()`, `calculateOvertimeCost()`, `analyzeReport($reportId)` |

### 3.2 Controller
| Item | Detail |
|------|--------|
| File | `app/Http/Controllers/CostController.php` |
| Method | `index()`, `settings()`, `updateRates()` |

### 3.3 View: Settings Rate
| Item | Detail |
|------|--------|
| File | `resources/views/cost/settings.blade.php` |
| Isi | Form input rate per menit & per jam per company |

### 3.4 View: Dashboard Biaya
| Item | Detail |
|------|--------|
| File | `resources/views/cost/index.blade.php` |
| Isi | Chart breakdown biaya per bulan, per company |

---

## Fase 4: Forecasting Downtime — Demonstrasi (1 hari)

### 4.1 Forecast Service
| Item | Detail |
|------|--------|
| File | `app/Services/Predictive/ForecastService.php` |
| Method | `exponentialSmoothing($data, $alpha)`, `movingAverage($data, $period)`, `generateReport()` |

### 4.2 Controller
| Item | Detail |
|------|--------|
| Route | Di `PredictiveController` atau controller baru (`ForecastController`) |
| Method | `forecastIndex()`, `calculate()` |

### 4.3 View: Forecasting
| Item | Detail |
|------|--------|
| File | `resources/views/predictive/forecast.blade.php` |
| Isi | Tabel actual vs forecast + grafik + error metrics (MAE/MSE) |

---

## Fase 5: Prescriptive Engine & Dashboard (2 hari)

### 5.1 Prescriptive Engine
| Item | Detail |
|------|--------|
| File | `app/Services/Prescriptive/PrescriptiveEngine.php` |
| Logic | Gabung ranking TOPSIS + risk score + cost urgency → rekomendasi prioritas |

### 5.2 Controller
| Item | Detail |
|------|--------|
| File | `app/Http/Controllers/IntegratedDssController.php` |
| Logic | Ambil data dari semua service |

### 5.3 View: Dashboard Terintegrasi
| Item | Detail |
|------|--------|
| File | `resources/views/integrated/index.blade.php` |
| Isi | Waterfall: Predictive → Prescriptive → Cost → Rekomendasi Final |

---

## Fase 6: Weibull — Arsitektur Saja (0,5 hari)

### 6.1 Model
| Item | Detail |
|------|--------|
| File | `app/Models/WeibullResult.php` |
| Isi | Model kosong dengan fillable & relasi ke asset |

### 6.2 Service Skeleton
| Item | Detail |
|------|--------|
| File | `app/Services/Predictive/WeibullService.php` |
| Isi | Class kosong dengan docblock: `fitDistribution($assetId)`, `calculateReliability($t)`, `getMTTF()` |

**Status:** Future development — arsitektur siap, logika menyusul.

---

## Fase 7: EOQ/ROP — Tentatif (1-2 hari, jika jadi)

### 7.1 Migration
| Item | Detail |
|------|--------|
| File | Migration tambahan |
| Isi | Kolom `harga_satuan`, `biaya_pesan`, `biaya_simpan` di tabel `spare_parts` |

### 7.2 EOQ Service
| Item | Detail |
|------|--------|
| File | `app/Services/Sparepart/EoqService.php` |
| Logic | EOQ = sqrt((2 x D x S) / H) |

### 7.3 ROP Service
| Item | Detail |
|------|--------|
| File | `app/Services/Sparepart/RopService.php` |
| Logic | ROP = d x L + SS, SS = Z x sigma x sqrt(L) |

### 7.4 View
| Item | Detail |
|------|--------|
| File | `resources/views/spareparts/optimasi.blade.php` |
| Isi | Tabel EOQ + ROP per sparepart |

---

## Fase Final: Route & Sidebar (0,5 hari)

| # | Task | File | Detail |
|---|------|------|--------|
| F.1 | Update routes | `routes/web.php` | Tambah semua route baru di dalam middleware `admin` |
| F.2 | Update sidebar | `resources/views/layouts/app.blade.php` | Tambah 6 menu baru di group "Analitik" (lihat struktur sidebar di atas) |
| F.3 | Seed demo | `database/seeders/` | Seeder untuk data dummy AHP + pairwise + cost rates |

---

## Timeline

| Fase | Modul | Hari | Prioritas |
|------|-------|------|-----------|
| 0 | Migration + Model | 1 | **WAJIB** — fondasi data |
| 1 | AHP + TOPSIS | 3-4 | **WAJIB** — inti BOK Maintenance |
| 2 | Risk Scoring | 2-3 | **WAJIB** — inti Predictive |
| 3 | Cost Breakdown | 1-2 | **WAJIB** — BOK Cost |
| 4 | Forecasting (Demo) | 1 | **WAJIB** — demonstrasi ES/MA |
| 5 | Prescriptive Engine + Dashboard | 2 | **WAJIB** — integrasi final |
| 6 | Weibull (arsitektur) | 0,5 | **WAJIB** — future dev |
| 7 | EOQ/ROP | 1-2 | **TENTATIF** — tunggu konfirmasi |
| Final | Route + Sidebar + Seed | 0,5 | **WAJIB** — finishing |
| | **Total** | **~12-17 hari** | |

---

## Rekap File yang Akan Dibuat

| Kategori | Jumlah | Keterangan |
|----------|--------|------------|
| Migration | 10 | 0.1 s.d 0.10 |
| Model | 10 | CostRate, CostAnalysis, RiskScore, AhpSession, AhpCriterion, AhpPairwise, TopsisResult, WeibullResult, ForecastLog, DecisionRecommendation |
| Service | 7 | AhpService, TopsisService, RiskScoreService, ForecastService, MaintenanceCostService, PrescriptiveEngine, WeibullService (skeleton) |
| Controller | 5 | AhpController, PredictiveController, ForecastController, CostController, IntegratedDssController |
| View | ~12 | criteria, pairwise, result, ranking, risk dashboard, detail, forecast, cost dashboard, rate settings, integrated, weibull (kosong) |
| Config | 1 | `config/risk-score.php` |
| Modifikasi | 2 | `routes/web.php`, `resources/views/layouts/app.blade.php` |
| **Total** | **~47 file** | |

---

## Legend Status

| Simbol | Arti |
|--------|------|
| ✅ SUDAH IMPLEMENTASI PENUH | Fitur berjalan dengan data riil |
| 🔧 BELUM — akan dibangun | Fitur baru yang direncanakan |
| 🏗️ ARSITEKTUR SIAP | Struktur kode dibuat, logika inti belum/baru sebagian |
| ❓ TENTATIF | Tergantung ketersediaan data/konfirmasi pihak terkait |
