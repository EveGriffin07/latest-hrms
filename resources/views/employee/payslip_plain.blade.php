<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Payslip {{ $period_label }}</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <style>
    body { font-family: sans-serif; max-width: 600px; margin: 2rem auto; padding: 1rem; }
    table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
    th, td { border: 1px solid #ddd; padding: 8px 12px; text-align: left; }
    th { background: #f1f5f9; }
    .right { text-align: right; }
    h1 { font-size: 1.25rem; margin-bottom: 0.5rem; }
    .muted { color: #64748b; font-size: 0.9rem; }
  </style>
</head>
<body>
  <h1>Payslip — {{ $period_label }}</h1>
  <p class="muted">Generated for your records.</p>
  <table>
    <tr><th>Item</th><th class="right">Amount (RM)</th></tr>
    <tr><td>Basic + Allowances (Gross)</td><td class="right">{{ number_format($gross, 2) }}</td></tr>
    <tr><td>Total Deductions</td><td class="right">({{ number_format((float) $payslip->total_deductions, 2) }})</td></tr>
    <tr><td><strong>Net Pay</strong></td><td class="right"><strong>{{ number_format((float) $payslip->net_salary, 2) }}</strong></td></tr>
  </table>
</body>
</html>
