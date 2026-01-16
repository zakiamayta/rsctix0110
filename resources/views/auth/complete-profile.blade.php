<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Lengkapi Profil | RSCtix</title>

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/light-theme.css') }}">

    <style>
        :root {
            --bs-body-font-family: 'Poppins', system-ui, sans-serif;
        }
    </style>
</head>

<body class="bg-light">

<div class="container min-vh-100 d-flex align-items-center justify-content-center">
    <div class="card shadow-lg rounded-4 p-4" style="max-width:460px; width:100%;">

        <h3 class="fw-bold mb-2 text-center">Lengkapi Profil</h3>
        <p class="text-muted text-center mb-4">
            Langkah terakhir sebelum menggunakan RSCtix
        </p>

        <form method="POST" action="{{ route('profile.complete.store') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" value="{{ auth()->user()->email }}" readonly>
            </div>

            <div class="mb-3">
                <label class="form-label">Nama Depan</label>
                <input type="text" name="first_name" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Nama Belakang</label>
                <input type="text" name="last_name" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Nomor Telepon</label>
                <input type="text" name="phone" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Tanggal Lahir</label>
                <input type="date" name="birth_date" class="form-control" required>
            </div>

            <div class="mb-4">
                <label class="form-label">Jenis Kelamin</label>
                <select name="gender" class="form-select">
                    <option value="">Pilih</option>
                    <option value="male">Laki-laki</option>
                    <option value="female">Perempuan</option>
                </select>
            </div>

            <button class="w-100 btn-gradient-orange">
                Simpan & Lanjutkan
            </button>
        </form>

    </div>
</div>

</body>
</html>
