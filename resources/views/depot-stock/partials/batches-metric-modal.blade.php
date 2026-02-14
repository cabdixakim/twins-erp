{{-- resources/views/depot-stock/partials/batches-metric-modal.blade.php --}}

@include('depot-stock.partials.batches-metric', [
  'metrics' => $metrics,
  'currentDepot' => $currentDepot,
  'stocks' => $stocks,
  'border' => $border,
  'surface' => $surface,
  'surface2' => $surface2,
  'fg' => $fg,
  'muted' => $muted,
  'pillGreen' => $pillGreen,
  'modalSize' => 'max-w-5xl', // 1/4 reduction from 7xl
  'modalHeight' => 'max-h-[127.5vh]',
  'modalAlign' => 'items-start justify-center pt-12', // push up, center horizontally
])
