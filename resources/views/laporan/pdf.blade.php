<!DOCTYPE html>
<html>
<head>
    <title>Laporan Keuangan</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 0; padding: 0; }
        .header p { margin: 5px 0; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #333; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-box { margin-top: 20px; padding: 10px; border: 1px solid #333; background-color: #e6ffe6; font-size: 14px; font-weight: bold; text-align: right; }
    </style>
</head>
<body>

    <div class="header">
        <h2>Laporan Keuangan Notaris & PPAT</h2>
        <p>Periode: Bulan {{ $bulan }} Tahun {{ $tahun }}</p>
        @if($kategori)
            <p>Kategori: {{ $kategori }}</p>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%" class="text-center">No</th>
                <th width="12%">Tanggal</th>
                <th width="15%">ID Transaksi</th>
                <th width="25%">Pemohon</th>
                <th width="28%">Rincian Pekerjaan</th>
                <th width="15%" class="text-right">Biaya (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transaksi as $index => $trx)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ date('d-m-Y', strtotime($trx->tanggal_permohonan)) }}</td>
                    <td>{{ $trx->id_transaksi }}</td>
                    <td>
                        <strong>{{ $trx->nama_lengkap }}</strong><br>
                        <small>{{ $trx->kategori }} {{ $trx->nama_bank ? '- '.$trx->nama_bank : '' }}</small>
                    </td>
                    <td>
                        <ul style="margin:0; padding-left:15px;">
                            @foreach($trx->details as $detail)
                                <li>{{ $detail->jenis_permohonan }}</li>
                            @endforeach
                        </ul>
                    </td>
                    <td class="text-right">
                        {{ number_format($trx->details->sum('biaya'), 0, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">Tidak ada transaksi pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="total-box" style="text-align: right;">
        <table style="width: 100%; border: none; margin-top: 0;">
            <tr>
                <td style="border: none; text-align: right; padding: 2px;">Pendapatan dari Pekerjaan Selesai :</td>
                <td style="border: none; text-align: right; padding: 2px; width: 150px;">Rp {{ number_format($uangSelesai, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td style="border: none; text-align: right; padding: 2px;">Pendapatan dari Pekerjaan Tertunda :</td>
                <td style="border: none; text-align: right; padding: 2px;">Rp {{ number_format($uangBelum, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td colspan="2" style="border: none; padding: 5px 0;">
                    <hr style="border: 0; border-top: 1px solid #333; margin: 0;">
                </td>
            </tr>
            <tr>
                <td style="border: none; text-align: right; padding: 5px 2px; font-size: 14px;"><strong>Total Pendapatan Keseluruhan :</strong></td>
                <td style="border: none; text-align: right; padding: 5px 2px; font-size: 14px;"><strong>Rp {{ number_format($totalUangMasuk, 0, ',', '.') }}</strong></td>
            </tr>
        </table>
    </div>

</body>
</html>
