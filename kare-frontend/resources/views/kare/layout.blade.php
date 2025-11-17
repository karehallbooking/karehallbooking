<!doctype html>
<html>
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Kare User</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    :root{
      --brand-primary:#0B6FA4; /* university blue */
      --brand-accent:#0AA3A0;  /* teal banner */
      --brand-dark:#0C2F3E;    /* sidebar/dark headings */
      --brand-warning:#F0B400; /* amber */
      --brand-danger:#D9534F;  /* red */
    }
    .bg-primary, .btn-primary{background-color:var(--brand-primary)!important;border-color:var(--brand-primary)!important}
    .text-primary{color:var(--brand-primary)!important}
    .btn-outline-secondary{border-color:var(--brand-dark)!important;color:var(--brand-dark)!important}
    .btn-outline-secondary:hover{background:var(--brand-dark)!important;color:#fff!important}
    .card-header, .info-card{background:linear-gradient(135deg,var(--brand-primary),var(--brand-accent));color:#fff}
    .badge-pending{background:var(--brand-warning)!important;color:#000!important}
    .badge-approved{background:var(--brand-primary)!important}
    .badge-rejected{background:var(--brand-danger)!important}
    a{color:var(--brand-primary)}
  </style>
</head>
<body class="bg-light">
<div class="container py-4">
  @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
  @yield('content')
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
