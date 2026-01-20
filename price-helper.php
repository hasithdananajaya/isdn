<?php
function price_label($usdAmount, $currency = 'USD') {
  $usd = (float)$usdAmount;
  if ($currency === 'LKR') {
    $lkr = $usd * 320.0;
    return 'LKR ' . number_format($lkr, 2);
  }
  return '$' . number_format($usd, 2);
}
