<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk | RSCtix</title>

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons (MONOKROM) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Custom Theme -->
    <link rel="stylesheet" href="{{ asset('css/light-theme.css') }}">
</head>

<body class="bg-light">

<div class="container-fluid min-vh-100 d-flex align-items-center">
    <div class="row w-100 justify-content-center">

        <div class="col-xl-10">
            <div class="row shadow-lg rounded-4 overflow-hidden bg-white">

                <!-- LEFT HERO -->
                <div class="col-lg-6 d-none d-lg-flex flex-column justify-content-center p-5"
                     style="background: linear-gradient(135deg,#f97316,#facc15); color:white;">

                    <h1 class="fw-bold mb-3" style="font-size:2.4rem;">
                        Platform Tiket Event<br>
                        <span style="opacity:.95;">Modern & Terintegrasi</span>
                    </h1>

                    <p class="mb-4" style="font-size:1rem; opacity:.95;">
                        Kelola event, penjualan tiket, merchandise, refund,
                        dan laporan keuangan dalam satu sistem terpadu.
                    </p>

                    <div class="mb-2 d-flex align-items-center gap-2">
                        <i class="bi bi-ticket-perforated"></i>
                        Penjualan tiket cepat & aman
                    </div>

                    <div class="mb-2 d-flex align-items-center gap-2">
                        <i class="bi bi-bar-chart-line"></i>
                        Monitoring penjualan real-time
                    </div>

                    <div class="mb-2 d-flex align-items-center gap-2">
                        <i class="bi bi-wallet2"></i>
                        Penarikan dana transparan
                    </div>

                    <div class="mb-4 d-flex align-items-center gap-2">
                        <i class="bi bi-arrow-repeat"></i>
                        Manajemen refund terkontrol
                    </div>

                    <div class="fw-semibold">
                        Dipercaya oleh Event Organizer & Manajemen Event
                    </div>
                </div>

                <!-- RIGHT LOGIN -->
                <div class="col-lg-6 p-5 d-flex align-items-center">
                    <div class="w-100" style="max-width:420px; margin:auto;">

                        <div class="text-center">

                            <h2 class="fw-bold mb-2">
                                Masuk ke RSCtix
                            </h2>

                            <p class="text-muted mb-4">
                                Login menggunakan akun Google
                            </p>

                            <a
                                href="{{ route('google.login') }}"
                                class="btn btn-light border w-100 py-3 d-flex align-items-center justify-content-center gap-3 rounded-4"
                            >

                                <img
                                    src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg"
                                    width="22"
                                >

                                <span class="fw-semibold">
                                    Lanjut dengan Google
                                </span>

                            </a>

                        </div>

                        <div class="text-center mt-4 text-muted" style="font-size:.85rem;">
                            © {{ date('Y') }} RSCtix · Platform Tiket Event
                        </div>

                    </div>
                </div>

            </div>
        </div>

    </div>
</div>

<!-- FONT OVERRIDE BOOTSTRAP -->
<style>
  :root {
    --bs-body-font-family: 'Poppins', system-ui, -apple-system,
      BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
  }

  body {
    font-family: 'Poppins', system-ui, -apple-system,
      BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
  }
</style>

</body>
</html>
