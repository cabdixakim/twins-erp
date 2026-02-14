

<?php echo $__env->make('depot-stock.partials.batches-metric', [
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
], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/depot-stock/partials/batches-metric-modal.blade.php ENDPATH**/ ?>