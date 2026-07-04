<?php
$content = '<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\CmFinding;
use App\Models\CmMeasurement;
use App\Models\Company;
use App\Models\Employee;
use App\Models\MaintenanceReport;
use App\Models\SparePart;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Tampilkan dashboard dengan filter PT (Semua / NPA / CES).
     */
    public function index(Request $request)
    {
        $companyId = (int) $request->input("company_id", 0);
        $companies = Company::orderBy("name")->get(["id", "name", "code"]);

        $assetQuery = Asset::query();
        if ($companyId > 0) $assetQuery->where("company_id", $companyId);
        $totalAssets = $assetQuery->count();

        $reportBase = MaintenanceReport::query();
        if ($companyId > 0) $reportBase->whereHas("asset", fn($q) => $q->where("company_id", $companyId));

        $laporanHariIni = (clone $reportBase)->whereDate("created_at", today())->count();

        $downQuery = (clone $reportBase)->whereMonth("created_at", now()->month)->whereNotNull("downtime_minutes");
        $totalDowntimeBulanIni = (int) (clone $downQuery)->sum("downtime_minutes");

        $lemQuery = (clone $reportBase)->whereMonth("created_at", now()->month)->where("is_overtime", true);
        $totalLemburBulanIni = (float) (clone $lemQuery)->sum("overtime_hours");
        $totalLaporanLemburBulanIni = (clone $lemQuery)->count();

        $durasi = $this->hitungDurasi($companyId);

        $spQuery = SparePart::query();
        if ($companyId > 0) $spQuery->where("company_id", $companyId);
        $stokKritis = (clone $spQuery)->whereColumn("stok_tersedia", "<", "stok_minimum")->count();
        $stokKritisList = (clone $spQuery)->whereColumn("stok_tersedia", "<", "stok_minimum")->take(5)->get();

        $chart7Hari   = $this->chart7Hari($companyId);
        $chart30Hari  = $this->chart30Hari($companyId);
        $chartTahunan = $this->chartTahunan($companyId);

        $topEquipment  = $this->top10($companyId);
        $topBulanIni   = $this->topBulanIni($companyId);

        $cmAlerts   = $this->cmAlerts($companyId);
        $severityCm = $this->severityCm($companyId);
        $cmMeas     = $this->cmMeasAlert($companyId);

        $distribusiJenis = $this->distribusi($companyId);
        $produktivitas   = $this->produktivitas($companyId);

        $dtStats = $this->dtPerCompany($companyId);
        $dtChart = $this->dtChartBulanan();
        $otStats = $this->otBulanIni($companyId);

        $rekomendasi = $this->rekomendasi($companyId);

        $kpiDt = $this->kpiDowntime($totalDowntimeBulanIni);
        $kpiOt = $this->kpiLembur($totalLemburBulanIni);

        return view("dashboard", compact(
            "companyId", "companies",
            "totalAssets", "laporanHariIni",
            "stokKritis", "stokKritisList",
            "totalDowntimeBulanIni",
            "totalLemburBulanIni", "totalLaporanLemburBulanIni",
            "durasi",
            "chart7Hari", "chart30Hari", "chartTahunan",
            "topEquipment", "topBulanIni",
            "cmAlerts", "severityCm", "cmMeas",
            "distribusiJenis", "produktivitas",
            "dtStats", "dtChart", "otStats",
            "rekomendasi", "kpiDt", "kpiOt",
        ));
    }

    private function hitungDurasi($cid) {
        $mulai = Carbon::now()->startOfWeek();
        $akhir = Carbon::now()->endOfWeek();
        $q = MaintenanceReport::whereBetween("created_at", [$mulai, $akhir]);
        if ($cid > 0) $q->whereHas("asset", fn($q2) => $q2->where("company_id", $cid));
        return [
            "total_menit"    => (int) (clone $q)->sum("work_duration_minutes"),
            "total_jam"      => 0,
            "total_karyawan" => Employee::when($cid > 0, fn($q) => $q->where("company_id", $cid))->where("is_active", true)->count(),
            "total_laporan"  => (clone $q)->count(),
        ];
    }

    private function chart7Hari($cid) {
        $labels = []; $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $h = Carbon::today()->subDays($i);
            $labels[] = $h->isoFormat("dddd");
            $q = MaintenanceReport::whereDate("created_at", $h);
            if ($cid > 0) $q->whereHas("asset", fn($q2) => $q2->where("company_id", $cid));
            $data[] = $q->count();
        }
        return compact("labels", "data");
    }

    private function chart30Hari($cid) {
        $labels = []; $data = [];
        for ($i = 29; $i >= 0; $i--) {
            $h = Carbon::today()->subDays($i);
            $labels[] = $h->isoFormat("DD MMM");
            $q = MaintenanceReport::whereDate("created_at", $h);
            if ($cid > 0) $q->whereHas("asset", fn($q2) => $q2->where("company_id", $cid));
            $data[] = $q->count();
        }
        return compact("labels", "data");
    }

    private function chartTahunan($cid) {
        $labels = []; $data = [];
        for ($b = 1; $b <= 12; $b++) {
            $labels[] = Carbon::create()->month($b)->isoFormat("MMM");
            $q = MaintenanceReport::whereYear("created_at", now()->year)->whereMonth("created_at", $b);
            if ($cid > 0) $q->whereHas("asset", fn($q2) => $q2->where("company_id", $cid));
            $data[] = $q->count();
        }
        return compact("labels", "data");
    }

    private function top10($cid) {
        $q = MaintenanceReport::select("asset_id", DB::raw("COUNT(*) as total"))
            ->where("created_at", ">=", Carbon::now()->subMonths(6))
            ->groupBy("asset_id")->orderByDesc("total")->take(10)
            ->with("asset:id,tag_no,description,status");
        if ($cid > 0) $q->whereHas("asset", fn($q2) => $q2->where("company_id", $cid));
        return $q->get();
    }

    private function topBulanIni($cid) {
        $q = MaintenanceReport::select("asset_id", DB::raw("COUNT(*) as total"))
            ->whereBetween("created_at", [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->groupBy("asset_id")->orderByDesc("total")->take(5)
            ->with("asset:id,tag_no,description,status");
        if ($cid > 0) $q->whereHas("asset", fn($q2) => $q2->where("company_id", $cid));
        return $q->get();
    }

    private function distribusi($cid) {
        $q = MaintenanceReport::select("jenis", DB::raw("COUNT(*) as total"))
            ->whereNotNull("jenis")->groupBy("jenis")->orderByDesc("total");
        if ($cid > 0) $q->whereHas("asset", fn($q2) => $q2->where("company_id", $cid));
        return $q->get();
    }

    private function produktivitas($cid) {
        $q = MaintenanceReport::select("reported_by", DB::raw("SUM(work_duration_minutes) as total_menit, COUNT(*) as total_laporan"))
            ->whereNotNull("work_duration_minutes")->where("work_duration_minutes", ">", 0)
            ->groupBy("reported_by")->orderByDesc("total_menit")->take(10)
            ->with("reporter:id,name");
        if ($cid > 0) $q->whereHas("asset", fn($q2) => $q2->where("company_id", $cid));
        return $q->get();
    }

    private function severityCm($cid) {
        $q = CmFinding::query();
        if ($cid > 0) $q->whereHas("asset", fn($q2) => $q2->where("company_id", $cid));
        $total = (clone $q)->count();
        $severities = [
            "critical"     => (clone $q)->where("severity", "critical")->count(),
            "high"         => (clone $q)->where("severity", "high")->count(),
            "medium"       => (clone $q)->where("severity", "medium")->count(),
            "low"          => (clone $q)->where("severity", "low")->count(),
            "unclassified" => (clone $q)->whereNull("severity")->orWhere("severity", "")->count(),
        ];
        $openCritical = (clone $q)->where("severity", "critical")->where("status", "!=", "closed")->count();
        $openHigh     = (clone $q)->where("severity", "high")->where("status", "!=", "closed")->count();
        $totalOpen    = (clone $q)->where("status", "!=", "closed")->count();
        return compact("total", "severities", "openCritical", "openHigh", "totalOpen");
    }

    private function cmAlerts($cid) {
        $q = CmFinding::whereIn("severity", ["high", "critical"])
            ->where("status", "!=", "closed")
            ->with("asset:id,tag_no,description")
            ->latest("tanggal")->take(5);
        if ($cid > 0) $q->whereHas("asset", fn($q2) => $q2->where("company_id", $cid));
        $r = $q->get();
        if ($r->isEmpty()) {
            $q2 = CmFinding::where("status", "!=", "closed")
                ->with("asset:id,tag_no,description")->latest("tanggal")->take(5);
            if ($cid > 0) $q2->whereHas("asset", fn($q3) => $q3->where("company_id", $cid));
            $r = $q2->get();
        }
        return $r;
    }

    private function cmMeasAlert($cid) {
        $vibFields = ["driver_de_vib_v","driver_de_vib_h","driver_de_vib_a",
            "driver_nde_vib_v","driver_nde_vib_h","driver_nde_vib_a",
            "driven_de_vib_v","driven_de_vib_h","driven_de_vib_a",
            "driven_nde_vib_v","driven_nde_vib_h","driven_nde_vib_a"];
        $tempFields = ["driver_de_temp","driver_nde_temp","driven_de_temp","driven_nde_temp"];

        $latestIds = CmMeasurement::select(DB::raw("MAX(id) as id"))->groupBy("asset_id")->pluck("id");
        $q = CmMeasurement::whereIn("id", $latestIds)->with("asset:id,tag_no,description,company_id");
        if ($cid > 0) $q->whereHas("asset", fn($q2) => $q2->where("company_id", $cid));
        $all = $q->get();

        $overV = collect(); $overT = collect(); $danger = 0;
        foreach ($all as $m) {
            $vm = 0; $tm = 0;
            foreach ($vibFields as $f) { $v = (float)($m->$f ?? 0); if ($v > $vm) $vm = $v; }
            foreach ($tempFields as $f) { $t = (float)($m->$f ?? 0); if ($t > $tm) $tm = $t; }
            if ($vm >= 7.0) { $overV->push((object)["tag_no"=>$m->asset->tag_no??"—", "nilai_vibrasi"=>$vm, "tanggal"=>$m->tanggal]); $danger++; }
            if ($tm >= 85) { $overT->push((object)["tag_no"=>$m->asset->tag_no??"—", "nilai_temp"=>$tm, "tanggal"=>$m->tanggal]); if ($vm < 7.0) $danger++; }
        }

        $totalMeas = CmMeasurement::query()
            ->when($cid > 0, fn($q) => $q->whereHas("asset", fn($q2) => $q2->where("company_id", $cid)))
            ->count();

        return [
            "over_vibrasi"       => $overV->sortByDesc("nilai_vibrasi")->values(),
            "over_temp"          => $overT->sortByDesc("nilai_temp")->values(),
            "total_measurements" => $totalMeas,
            "total_danger"       => $danger,
        ];
    }

    private function dtPerCompany($cid) {
        $q = MaintenanceReport::select("assets.company_id",
            DB::raw("COALESCE(SUM(maintenance_reports.downtime_minutes), 0) as total_downtime"),
            DB::raw("COUNT(maintenance_reports.id) as total_laporan"),
            DB::raw("SUM(CASE WHEN maintenance_reports.downtime_minutes > 0 THEN 1 ELSE 0 END) as laporan_dengan_downtime"))
            ->join("assets", "maintenance_reports.asset_id", "=", "assets.id")
            ->whereNotNull("maintenance_reports.downtime_minutes")
            ->where("maintenance_reports.downtime_minutes", ">", 0)
            ->whereBetween("maintenance_reports.created_at", [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->groupBy("assets.company_id");
        if ($cid > 0) $q->where("assets.company_id", $cid);
        return $q->get()->keyBy("company_id");
    }

    private function dtChartBulanan() {
        $labels = []; $npa = []; $ces = [];
        for ($i = 11; $i >= 0; $i--) {
            $t = Carbon::now()->subMonthsNoOverflow($i);
            $labels[] = $t->isoFormat("MMM YY");
            $npa[] = (int) MaintenanceReport::join("assets", "maintenance_reports.asset_id", "=", "assets.id")
                ->whereNotNull("maintenance_reports.downtime_minutes")
                ->whereYear("maintenance_reports.created_at", $t->year)
                ->whereMonth("maintenance_reports.created_at", $t->month)
                ->where("assets.company_id", 1)
                ->sum("maintenance_reports.downtime_minutes");
            $ces[] = (int) MaintenanceReport::join("assets", "maintenance_reports.asset_id", "=", "assets.id")
                ->whereNotNull("maintenance_reports.downtime_minutes")
                ->whereYear("maintenance_reports.created_at", $t->year)
                ->whereMonth("maintenance_reports.created_at", $t->month)
                ->where("assets.company_id", 2)
                ->sum("maintenance_reports.downtime_minutes");
        }
        return compact("labels", "npa", "ces");
    }

    private function otBulanIni($cid) {
        $q = MaintenanceReport::where("is_overtime", true)
            ->whereBetween("created_at", [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
        if ($cid > 0) $q->whereHas("asset", fn($q2) => $q2->where("company_id", $cid));
        $totalLaporan = (clone $q)->count();
        $totalJam     = (float) (clone $q)->sum("overtime_hours");
        $topTeknisi = MaintenanceReport::select("reported_by", DB::raw("COUNT(*) as total_laporan, COALESCE(SUM(overtime_hours), 0) as total_jam"))
            ->where("is_overtime", true)
            ->whereBetween("created_at", [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->groupBy("reported_by")->orderByDesc("total_jam")->take(5)
            ->with("reporter:id,name");
        if ($cid > 0) $topTeknisi->whereHas("asset", fn($q2) => $q2->where("company_id", $cid));
        return ["total_laporan"=>$totalLaporan, "total_jam"=>$totalJam, "top_teknisi"=>$topTeknisi->get()];
    }

    private function rekomendasi($cid) {
        $r = [];
        $cm = $this->cmMeasAlert($cid);
        if ($cm["total_danger"] > 0) $r[] = ["level"=>"high", "title"=>$cm["total_danger"]." equipment vibrasi/temperature over threshold", "desc"=>"Vibrasi >= 7.0 mm/s atau temp >= 85 C. Butuh corrective."];

        $cq = CmFinding::whereIn("severity", ["high","critical"])->where("status","!=","closed");
        if ($cid > 0) $cq->whereHas("asset", fn($q) => $q->where("company_id", $cid));
        $tc = (clone $cq)->count();
        if ($tc > 0) $r[] = ["level"=>$tc>3?"high":"med", "title"=>$tc." temuan CM (high/critical) masih open", "desc"=>"Prioritaskan action plan."];

        $sq = SparePart::whereColumn("stok_tersedia", "<", "stok_minimum");
        if ($cid > 0) $sq->where("company_id", $cid);
        $sk = (clone $sq)->count();
        if ($sk > 0) $r[] = ["level"=>"med", "title"=>$sk." sparepart stok di bawah minimum", "desc"=>"Segera reorder."];

        $eqQ = Asset::query(); if ($cid > 0) $eqQ->where("company_id", $cid);
        $eqs = (clone $eqQ)->with("maintenanceReports")->get();
        $mtbf = [];
        foreach ($eqs as $e) {
            $rpts = $e->maintenanceReports->sortBy("created_at")->pluck("created_at");
            if ($rpts->count() < 2) continue;
            $s = 0; $cnt = 0; $prev = null;
            foreach ($rpts as $t) { if ($prev !== null) { $s += $prev->diffInDays($t); $cnt++; } $prev = $t; }
            $m = $cnt > 0 ? round($s / $cnt, 1) : 0;
            if ($m > 0 && $m < 30) $mtbf[] = $e->tag_no;
        }
        if (count($mtbf) > 0) $r[] = ["level"=>"high", "title"=>count($mtbf)." equipment MTBF kritis", "desc"=>"Interval gagal pendek: ".collect($mtbf)->take(3)->implode(", ")];

        $l7 = MaintenanceReport::where("created_at", ">=", Carbon::now()->subDays(7))->count();
        if ($l7 === 0) $r[] = ["level"=>"med", "title"=>"Tidak ada laporan 7 hari", "desc"=>"Cek koneksi bot."];

        $dt = $this->dtPerCompany($cid);
        foreach ($dt as $cid2 => $s) {
            if ($s->total_downtime > 0) {
                $nama = $cid2 == 1 ? "NPA" : "CES";
                $jam = round($s->total_downtime / 60, 1);
                $lpp = $s->total_laporan;
                if ($s->total_downtime >= 600) $r[] = ["level"=>"high", "title"=>"Downtime {$nama} {$jam} jam", "desc"=>$lpp." laporan."];
                elseif ($s->total_downtime >= 120) $r[] = ["level"=>"med", "title"=>"Downtime {$nama} {$jam} jam", "desc"=>"Mulai signifikan."];
            }
        }

        $ot = $this->otBulanIni($cid);
        if ($ot["total_jam"] >= 40) $r[] = ["level"=>"med", "title"=>"Lembur ".$ot["total_jam"]." jam", "desc"=>$ot["total_laporan"]." laporan lembur."];

        return $r;
    }

    private function kpiDowntime($menit) {
        $kpi = 1200; $jam = round($menit / 60, 1); $kpijam = $kpi / 60;
        $sisa = round(max($kpijam - $jam, 0), 1); $pct = $kpijam > 0 ? round(($jam / $kpijam) * 100, 1) : 0;
        if ($jam >= $kpijam) return ["total_jam"=>$jam, "kpi_jam"=>$kpijam, "sisa_jam"=>$sisa, "persen"=>$pct, "status"=>"danger", "pesan"=>"Downtime {$jam} jam melebihi KPI {$kpijam} jam!"];
        if ($pct >= 80) return ["total_jam"=>$jam, "kpi_jam"=>$kpijam, "sisa_jam"=>$sisa, "persen"=>$pct, "status"=>"warning", "pesan"=>"Downtime {$jam} jam, sisa {$sisa} jam."];
        return ["total_jam"=>$jam, "kpi_jam"=>$kpijam, "sisa_jam"=>$sisa, "persen"=>$pct, "status"=>"safe", "pesan"=>"Downtime {$jam} dari {$kpijam} jam. Aman."];
    }

    private function kpiLembur($jam) {
        $kpi = 40; $sisa = round(max($kpi - $jam, 0), 1); $pct = $kpi > 0 ? round(($jam / $kpi) * 100, 1) : 0;
        if ($jam >= $kpi) return ["total_jam"=>$jam, "kpi_jam"=>$kpi, "sisa_jam"=>$sisa, "persen"=>$pct, "status"=>"danger", "pesan"=>"Lembur {$jam} jam melebihi KPI {$kpi} jam!"];
        if ($pct >= 80) return ["total_jam"=>$jam, "kpi_jam"=>$kpi, "sisa_jam"=>$sisa, "persen"=>$pct, "status"=>"warning", "pesan"=>"Lembur {$jam} jam, sisa {$sisa} jam."];
        return ["total_jam"=>$jam, "kpi_jam"=>$kpi, "sisa_jam"=>$sisa, "persen"=>$pct, "status"=>"safe", "pesan"=>"Lembur {$jam} dari {$kpi} jam. Normal."];
    }
}
';

file_put_contents('app/Http/Controllers/DashboardController.php', $content);
echo "OK: " . strlen($content) . " bytes written.\n";
