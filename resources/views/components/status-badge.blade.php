@php
$statusMap = [
    'healthy'      => ['label' => 'Sehat',        'class' => 'bg-green-100 text-green-700'],
    'exhausted'    => ['label' => 'Quota Habis',  'class' => 'bg-amber-100 text-amber-700'],
    'error'        => ['label' => 'Error',        'class' => 'bg-red-100 text-red-700'],
    'disabled'     => ['label' => 'Nonaktif',     'class' => 'bg-gray-100 text-gray-500'],
    'success'      => ['label' => 'Sukses',       'class' => 'bg-green-100 text-green-700'],
    'pending'      => ['label' => 'Pending',      'class' => 'bg-amber-100 text-amber-700'],
    'confirmed'    => ['label' => 'Dikonfirmasi', 'class' => 'bg-green-100 text-green-700'],
    'rejected'     => ['label' => 'Ditolak',      'class' => 'bg-red-100 text-red-700'],
    'open'         => ['label' => 'Open',         'class' => 'bg-red-100 text-red-700'],
    'on_progress'  => ['label' => 'On Progress',  'class' => 'bg-amber-100 text-amber-700'],
    'done'         => ['label' => 'Done',         'class' => 'bg-green-100 text-green-700'],
];

$info = $statusMap[$status] ?? ['label' => ucfirst(str_replace('_', ' ', $status)), 'class' => 'bg-gray-100 text-gray-500'];
@endphp

<span {{ $attributes->merge(['class' => 'px-2 py-0.5 rounded-full text-xs font-medium inline-block ' . $info['class']]) }}>
    {{ $info['label'] }}
</span>