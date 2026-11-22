<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Kartu Kode Peserta - Dento</title>
    <style>
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: #f8fafc;
            margin: 0;
            padding: 24px;
            color: #25435C;
        }
        .header-action {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            background: #fff;
            padding: 16px 24px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
        }
        .grid-cards {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        .card {
            background: #ffffff;
            border: 2px dashed #4AA8D8;
            border-radius: 12px;
            padding: 20px;
            box-sizing: border-box;
            page-break-inside: avoid;
        }
        .card-title {
            font-size: 18px;
            font-weight: 700;
            color: #1F6E9C;
            margin-bottom: 4px;
            display: flex;
            justify-content: space-between;
        }
        .code-box {
            background: #E8F6FB;
            border-radius: 8px;
            padding: 12px;
            margin: 12px 0;
            display: flex;
            justify-content: space-around;
            align-items: center;
        }
        .code-item {
            text-align: center;
        }
        .code-label {
            font-size: 11px;
            color: #64748B;
            text-transform: uppercase;
            font-weight: 600;
        }
        .code-value {
            font-size: 24px;
            font-weight: 800;
            color: #25435C;
            letter-spacing: 2px;
        }
        .instructions {
            font-size: 12px;
            color: #64748B;
            line-height: 1.4;
        }
        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .header-action {
                display: none;
            }
            .grid-cards {
                grid-template-columns: repeat(2, 1fr);
                gap: 16px;
            }
            .card {
                border: 1.5px dashed #7F97AA;
            }
        }
    </style>
</head>
<body>
    <div class="header-action">
        <div>
            <h2 style="margin: 0 0 4px 0; color: #1F6E9C;">Kartu Login Peserta Dento</h2>
            <p style="margin: 0; font-size: 14px; color: #b45309; font-weight: 600;">
                ⚠️ PENTING: PIN hanya ditampilkan sekarang dan tidak disimpan dalam bentuk teks biasa. Harap cetak atau simpan sekarang!
            </p>
        </div>
        <div>
            <button onclick="window.print()" style="padding: 10px 20px; background: #059669; color: #fff; border: none; border-radius: 6px; font-weight: 600; font-size: 15px; cursor: pointer;">
                🖨️ Cetak Kartu
            </button>
            <a href="{{ route('admin.students.index') }}" style="margin-left: 12px; font-size: 14px; color: #64748B; text-decoration: none;">
                Kembali ke Daftar
            </a>
        </div>
    </div>

    <div class="grid-cards">
        @foreach($cards as $card)
            <div class="card">
                <div class="card-title">
                    <span>Dento - Sahabat Gigi Sehat</span>
                    <span style="font-size: 13px; color: #64748B;">{{ $card['kelas'] }}</span>
                </div>
                <div style="font-size: 12px; color: #64748B;">{{ $card['sekolah'] }}</div>

                <div class="code-box">
                    <div class="code-item">
                        <div class="code-label">Kode Peserta</div>
                        <div class="code-value">{{ $card['kode'] }}</div>
                    </div>
                    <div style="border-left: 2px solid #cbd5e1; height: 36px;"></div>
                    <div class="code-item">
                        <div class="code-label">PIN Masuk</div>
                        <div class="code-value" style="color: #059669;">{{ $card['pin'] }}</div>
                    </div>
                </div>

                <div class="instructions">
                    Petunjuk untuk Siswa:<br>
                    1. Buka aplikasi Dento di laptop.<br>
                    2. Masukkan Kode Peserta dan 4 angka PIN di atas.<br>
                    3. Jaga kerahasiaan kartu dan PIN ini.
                </div>
            </div>
        @endforeach
    </div>
</body>
</html>
