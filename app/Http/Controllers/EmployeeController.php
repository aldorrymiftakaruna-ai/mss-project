<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Company;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = Employee::with('company')->latest()->get();
        $companies = Company::all();
        return view('employees.index', compact('employees', 'companies'));
    }

    public function show(Employee $employee)
    {
        $employee->load(['company', 'manpowerLogs.maintenanceReport.asset']);

        return view('employees.show', compact('employee'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'role'  => 'required|in:foreman,teknisi',
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
            'role'              => 'required|in:foreman,teknisi',
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