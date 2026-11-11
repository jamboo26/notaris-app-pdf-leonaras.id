<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Laporan Keuangan & Kinerja') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Kotak Filter -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <form action="{{ route('laporan.keuangan') }}" method="GET" class="flex flex-wrap items-end gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bulan</label>
                        <select name="bulan" class="border-gray-300 rounded-md shadow-sm">
                            @for($i = 1; $i <= 12; $i++)
                                @php $val = str_pad($i, 2, '0', STR_PAD_LEFT); @endphp
                                <option value="{{ $val }}" {{ $bulan == $val ? 'selected' : '' }}>
                                    {{ date('F', mktime(0, 0, 0, $i, 1)) }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tahun</label>
                        <select name="tahun" class="border-gray-300 rounded-md shadow-sm">
                            @for($i = date('Y') - 5; $i <= date('Y'); $i++)
                                <option value="{{ $i }}" {{ $tahun == $i ? 'selected' : '' }}>{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kategori (Opsional)</label>
                        <select name="kategori" class="border-gray-300 rounded-md shadow-sm">
                            <option value="">Semua Kategori</option>
                            <option value="Bank" {{ $kategori == 'Bank' ? 'selected' : '' }}>Bank</option>
                            <option value="Perorangan" {{ $kategori == 'Perorangan' ? 'selected' : '' }}>Perorangan</option>
                            <option value="Badan Hukum" {{ $kategori == 'Badan Hukum' ? 'selected' : '' }}>Badan Hukum</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-transparent mb-1">Aksi</label>
                        <div class="flex items-center gap-3">
                            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 shadow-sm font-medium">Terapkan Filter</button>
                            <a href="{{ route('laporan.keuangan') }}" class="text-gray-500 hover:text-gray-900 hover:underline font-medium">Reset</a>
                            <a href="{{ route('laporan.pdf', request()->query()) }}" target="_blank" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 shadow-sm ml-2 border border-red-700">🖨️ Cetak PDF</a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Rekapan Keseluruhan (All Time) -->
            <div class="mb-4 text-sm text-gray-600 font-medium px-2">
                ⚡ Total Keseluruhan (All Time): <span class="font-bold text-gray-800">{{ $allTimeTransaksi }} Transaksi</span> dengan <span class="font-bold text-gray-800">{{ $allTimePekerjaan }} Detail Pekerjaan</span>.
            </div>

            <!-- Kotak Ringkasan Pekerjaan (Berdasarkan Filter) -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-blue-600 text-white rounded-lg p-5 shadow-sm border border-blue-700">
                    <h4 class="text-sm font-medium opacity-90">Transaksi / Klien</h4>
                    <h2 class="text-3xl font-extrabold mt-1">{{ $totalTransaksi }}</h2>
                    <p class="text-xs mt-1 opacity-75">Pada periode terpilih</p>
                </div>
                <div class="bg-indigo-600 text-white rounded-lg p-5 shadow-sm border border-indigo-700">
                    <h4 class="text-sm font-medium opacity-90">Total Rincian Pekerjaan</h4>
                    <h2 class="text-3xl font-extrabold mt-1">{{ $semuaPekerjaan }}</h2>
                    <p class="text-xs mt-1 opacity-75">Pada periode terpilih</p>
                </div>
                <div class="bg-green-600 text-white rounded-lg p-5 shadow-sm border border-green-700">
                    <h4 class="text-sm font-medium opacity-90">Pekerjaan Selesai</h4>
                    <h2 class="text-3xl font-extrabold mt-1">{{ $pekerjaanSelesai }}</h2>
                    <p class="text-xs mt-1 opacity-75">Sudah dikerjakan</p>
                </div>
                <div class="bg-yellow-500 text-white rounded-lg p-5 shadow-sm border border-yellow-600">
                    <h4 class="text-sm font-medium opacity-90">Pekerjaan Tertunda</h4>
                    <h2 class="text-3xl font-extrabold mt-1">{{ $pekerjaanBelum }}</h2>
                    <p class="text-xs mt-1 opacity-75">In Progress / Belum selesai</p>
                </div>
            </div>

            <!-- Diagram & Total Keuangan -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <!-- Diagram Chart.js -->
                <div class="md:col-span-2 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border border-gray-100">
                    <h3 class="font-bold text-gray-700 mb-4 border-b pb-2">Status Penyelesaian Pekerjaan</h3>
                    <div class="relative w-full" style="height: 250px;">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>

                <!-- Kotak Total Pendapatan (Dengan Rincian) -->
                <div class="bg-gradient-to-br from-green-500 to-green-700 shadow-sm sm:rounded-lg p-6 text-white flex flex-col justify-between border border-green-600">
                    <div class="text-center mb-4">
                        <div class="bg-white bg-opacity-20 rounded-full w-12 h-12 flex items-center justify-center mx-auto mb-2">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <h3 class="text-md font-medium opacity-90 mb-1">Total Pendapatan Keseluruhan</h3>
                        <h1 class="text-3xl md:text-4xl font-extrabold break-words">Rp {{ number_format($totalUangMasuk, 0, ',', '.') }}</h1>
                    </div>

                    <!-- Garis pemisah dan rincian -->
                    <div class="grid grid-cols-2 gap-2 pt-4 border-t border-green-400 border-opacity-50 mt-auto">
                        <div class="text-center pr-2">
                            <p class="text-[11px] uppercase tracking-wider opacity-80 mb-1">Selesai</p>
                            <p class="text-lg font-bold text-green-100">Rp {{ number_format($uangSelesai, 0, ',', '.') }}</p>
                        </div>
                        <div class="text-center border-l border-green-400 border-opacity-50 pl-2">
                            <p class="text-[11px] uppercase tracking-wider opacity-80 mb-1">Tertunda</p>
                            <p class="text-lg font-bold text-yellow-200">Rp {{ number_format($uangBelum, 0, ',', '.') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabel Data Laporan (Tetap Sama) -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-bold text-gray-700 mb-4 border-b pb-2">Rincian Transaksi</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border border-gray-200">
                        <thead class="bg-gray-100 border-b">
                            <tr>
                                <th class="py-3 px-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Tanggal</th>
                                <th class="py-3 px-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">ID Transaksi</th>
                                <th class="py-3 px-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Pemohon / Klien</th>
                                <th class="py-3 px-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Rincian Pekerjaan & Status</th>
                                <th class="py-3 px-4 text-right text-xs font-bold text-gray-700 uppercase tracking-wider">Total Rp</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($transaksi as $trx)
                                <tr class="hover:bg-gray-50">
                                    <td class="py-3 px-4 text-sm text-gray-600 whitespace-nowrap">{{ date('d M Y', strtotime($trx->tanggal_permohonan)) }}</td>
                                    <td class="py-3 px-4 text-sm font-medium text-gray-800 whitespace-nowrap">{{ $trx->id_transaksi }}</td>
                                    <td class="py-3 px-4">
                                        <div class="font-bold text-sm text-gray-900">{{ $trx->nama_lengkap }}</div>
                                        <div class="text-xs text-gray-500">{{ $trx->kategori }} {{ $trx->nama_bank ? ' - '.$trx->nama_bank : '' }}</div>
                                    </td>
                                    <td class="py-3 px-4 text-sm text-gray-600">
                                        <ul class="list-disc pl-4 space-y-1">
                                            @foreach($trx->details as $detail)
                                                <li>
                                                    {{ $detail->jenis_permohonan }}
                                                    <span class="text-gray-400">({{ number_format($detail->biaya, 0, ',', '.') }})</span>
                                                    @if($detail->status_dikerjakan)
                                                        <span class="ml-2 text-[10px] bg-green-100 text-green-700 px-1.5 py-0.5 rounded-full font-bold">Selesai</span>
                                                    @else
                                                        <span class="ml-2 text-[10px] bg-yellow-100 text-yellow-700 px-1.5 py-0.5 rounded-full font-bold">Pending</span>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    </td>
                                    <td class="py-3 px-4 text-right font-bold text-gray-800 whitespace-nowrap">
                                        {{ number_format($trx->details->sum('biaya'), 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-gray-500 font-medium">Tidak ada transaksi pada periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <!-- Script Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- TAMBAHAN: Plugin DataLabels untuk memunculkan teks di diagram -->
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Daftarkan plugin DataLabels ke Chart.js
            Chart.register(ChartDataLabels);

            const ctx = document.getElementById('statusChart').getContext('2d');

            const dataSelesai = {{ $pekerjaanSelesai }};
            const dataBelum = {{ $pekerjaanBelum }};

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Selesai', 'Belum Selesai'],
                    datasets: [{
                        data: [dataSelesai, dataBelum],
                        backgroundColor: [
                            'rgba(22, 163, 74, 0.9)', // Green
                            'rgba(234, 179, 8, 0.9)'  // Yellow
                        ],
                        borderColor: ['#ffffff', '#ffffff'],
                        borderWidth: 2,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: { font: { family: "'Nunito', sans-serif", size: 13 } }
                        },
                        // Konfigurasi teks persentase di dalam diagram
                        datalabels: {
                            color: '#ffffff', // Warna teks putih
                            font: {
                                weight: 'bold',
                                size: 14
                            },
                            formatter: (value, context) => {
                                // Hitung total semua data
                                let total = context.chart._metasets[context.datasetIndex].total;
                                // Jika tidak ada data (0), jangan tampilkan apapun
                                if (total === 0 || value === 0) return null;

                                // Hitung persentase (1 angka di belakang koma)
                                let percentage = (value * 100 / total).toFixed(1) + "%";
                                return percentage;
                            }
                        }
                    },
                    cutout: '55%' // Sedikit dipertebal agar teks muat di dalam cincin
                }
            });
        });
    </script>
</x-app-layout>
