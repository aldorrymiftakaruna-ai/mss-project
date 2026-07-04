<?php

namespace App\Http\Controllers;

use App\Services\Predictive\ForecastService;
use Illuminate\Http\Request;

class ForecastController extends Controller
{
    protected ForecastService $forecastService;

    public function __construct(ForecastService $forecastService)
    {
        $this->forecastService = $forecastService;
    }

    /**
     * Menampilkan halaman forecasting.
     */
    public function index()
    {
        $dataType = request('data_type', 'downtime');
        $modelType = request('model_type', 'ES');
        $alpha = (float) request('alpha', 0.3);
        $maPeriod = (int) request('ma_period', 3);
        $months = (int) request('months', 12);
        $forecastCount = (int) request('forecast_count', 3);

        // Ambil data historis
        if ($dataType === 'work_duration') {
            $historicalData = $this->forecastService->getHistoricalWorkDurationData($months);
        } else {
            $historicalData = $this->forecastService->getHistoricalDowntimeData($months);
        }

        $results = collect();
        $metrics = ['mae' => 0, 'mse' => 0, 'mape' => 0];

        if ($historicalData->isNotEmpty()) {
            if ($modelType === 'ES') {
                $results = $this->forecastService->exponentialSmoothing($historicalData, $alpha, $forecastCount);
            } else {
                $results = $this->forecastService->movingAverage($historicalData, $maPeriod, $forecastCount);
            }

            $metrics = $this->forecastService->errorMetrics($results);
        }

        return view('predictive.forecast', compact(
            'dataType',
            'modelType',
            'alpha',
            'maPeriod',
            'months',
            'forecastCount',
            'historicalData',
            'results',
            'metrics'
        ));
    }

    /**
     * Menghitung forecast berdasarkan parameter dari form.
     */
    public function calculate(Request $request)
    {
        $validated = $request->validate([
            'data_type' => 'required|in:downtime,work_duration',
            'model_type' => 'required|in:ES,MA',
            'alpha' => 'required_if:model_type,ES|numeric|min:0.01|max:0.99',
            'ma_period' => 'required_if:model_type,MA|integer|min:2|max:12',
            'months' => 'required|integer|min:3|max:60',
            'forecast_count' => 'required|integer|min:1|max:12',
        ]);

        return redirect()->route('forecast.index', $validated);
    }
}
