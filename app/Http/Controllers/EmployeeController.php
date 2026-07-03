<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Company;
use Illuminate\Http\Request;
use Carbon\Carbon;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = Employee::with('company')->latest()->get();
        $companies = Company::all();
        return view('employees.index', compact('employees', 'companies'));
    }

    public function show(Request $request, Employee $employee)
    {
        $filter = $request->get('filter', 'all');
        $bulan  = $request->get('bulan');
        $tahun  = $request->get('tahun');

        // Query laporan yang dilaporkan oleh karyawan ini
        $reportQuery = $employee->maintenanceReports()->getQuery();

        if ($bulan && $tahun) {
            $reportQuery->whereYear('created_at', $tahun)
                        ->whereMonth('created_at', $bulan);
        } elseif ($tahun) {
            $reportQuery->whereYear('created_at', $tahun);
        }

        $employee->setRelation('maintenanceReports', $reportQuery->with('asset')->latest()->get());
        $employee->load('company');

        return view('employees.show', compact('employee', 'filter', 'bulan', 'tahun'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'role'  => 'required|in:foreman,teknisi,supervisor',
            'shift' => 'nullable|in:shift,reguler',
        ]);

        Employee::create($request->only([
            'company_id', 'name', 'telegram_id',
            'telegram_username', 'role', 'shift', 'is_active'
        ]));

        return redirect()->route('employees.index')
                         ->with('success', 'Karyawan berhasil ditambahkan.');
    }

    public function update(Request $request, Employee $employee)
    {
        $request->validate([
            'name'              => 'required|string|max:255',
            'role'              => 'required|in:foreman,teknisi,supervisor',
            'shift'             => 'nullable|in:shift,reguler',
            'telegram_id'       => 'nullable|string|max:255',
            'telegram_username' => 'nullable|string|max:255',
            'is_active'         => 'nullable|boolean',
        ]);

        $employee->update($request->only([
            'name', 'telegram_id', 'telegram_username', 'role', 'shift', 'is_active'
        ]));

        return redirect()->route('employees.show', $employee)
                         ->with('success', 'Data karyawan berhasil diperbarui.');
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();
        return redirect()->route('employees.index')->with('success', 'Karyawan berhasil dihapus.');
    }
}