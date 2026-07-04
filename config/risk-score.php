<?php

/*
|--------------------------------------------------------------------------
| Konfigurasi Risk Score
|--------------------------------------------------------------------------
|
| File ini berisi parameter untuk perhitungan risk score berbasis tren
| data Condition Monitoring (vibrasi & temperatur).
|
| Bobot: seberapa besar kontribusi masing-masing parameter ke skor akhir.
| Threshold: batas skor untuk kategori risiko.
| N: jumlah titik data terakhir yang dianalisis untuk regresi.
|
*/

return [

    /*
    | Bobot kontribusi ke composite score (total = 1.0)
    */
    'weights' => [
        'vibration_slope'  => 0.30, // kemiringan tren vibrasi
        'temperature_slope'=> 0.20, // kemiringan tren temperatur
        'vibration_level'  => 0.25, // level vibrasi terkini vs threshold
        'temperature_level'=> 0.15, // level temperatur terkini vs threshold
        'rate_of_change'   => 0.10, // laju perubahan (acceleration)
    ],

    /*
    | Threshold skor untuk kategori risiko
    | skor < rendah     → rendah
    | skor < sedang     → sedang
    | selebihnya        → tinggi
    */
    'thresholds' => [
        'rendah' => 0.33,
        'sedang' => 0.66,
    ],

    /*
    | N: jumlah titik data terakhir yang digunakan untuk regresi linier
    | Makin besar N, makin smooth tren tapi kurang sensitif terhadap perubahan terbaru.
    */
    'n_points' => 8,

    /*
    | Alpha untuk exponential smoothing (jika data kurang dari N)
    | Nilai 0-1, makin tinggi makin responsif terhadap perubahan terbaru.
    */
    'smoothing_alpha' => 0.3,

    /*
    | Bobot tambahan jika equipment sudah pernah memiliki temuan CM
    | severity tinggi/critical (scalar multiplier)
    */
    'finding_penalty' => 1.2,

];
