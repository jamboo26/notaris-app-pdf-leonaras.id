<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Banner Ucapan Selamat Datang -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6 border-l-4 border-indigo-600 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h3 class="text-xl font-bold text-gray-800">
                        Halo, {{ auth()->user()->name }}! 👋
                    </h3>
                    <p class="text-gray-600 mt-1">
                        Selamat datang di aplikasi <span class="font-bold text-indigo-600">Notaris App</span> by <span class="font-bold text-gray-800">Leonaras.ID</span>.
                    </p>
                </div>
                <div>
                    @foreach(auth()->user()->getRoleNames() as $role)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800 border border-indigo-200">
                            Role: {{ $role }}
                        </span>
                    @endforeach
                </div>
            </div>

            <!-- Rekapan Keseluruhan (All Time) -->
            <div class="mb-4 text-sm text-gray-600 font-medium px-1">
                ⚡ Total Keseluruhan (All Time): 
                <span class="font-bold text-gray-800">{{ $allTimeTransaksi }} Transaksi</span> dengan 
                <span class="font-bold text-gray-800">{{ $allTimePekerjaan }} Detail Pekerjaan</span>.
            </div>

            <!-- 4 Kartu Ringkasan Pekerjaan (Bulan Berjalan) -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-blue-600 text-white rounded-lg p-5 shadow-sm border border-blue-700">
                    <h4 class="text-sm font-medium opacity-90">Transaksi / Klien</h4>
                    <h2 class="text-3xl font-extrabold mt-1">{{ $totalTransaksi }}</h2>
                    <p class="text-xs mt-1 opacity-75">Bulan {{ date('F Y') }}</p>
                </div>
                
                <div class="bg-indigo-600 text-white rounded-lg p-5 shadow-sm border border-indigo-700">
                    <h4 class="text-sm font-medium opacity-90">Total Rincian Pekerjaan</h4>
                    <h2 class="text-3xl font-extrabold mt-1">{{ $semuaPekerjaan }}</h2>
                    <p class="text-xs mt-1 opacity-75">Bulan {{ date('F Y') }}</p>
                </div>
                
                <div class="bg-green-600 text-white rounded-lg p-5 shadow-sm border border-green-700">
                    <h4 class="text-sm font-medium opacity-90">Pekerjaan Selesai</h4>
                    <h2 class="text-3xl font-extrabold mt-1">{{ $pekerjaanSelesai }}</h2>
                    <p class="text-xs mt-1 opacity-75">Sudah dikerjakan</p>
                </div>
                
                <div class="bg-red-600 text-white rounded-lg p-5 shadow-sm border border-red-700">
                    <h4 class="text-sm font-medium opacity-90">Pekerjaan Tertunda</h4>
                    <h2 class="text-3xl font-extrabold mt-1">{{ $pekerjaanBelum }}</h2>
                    <p class="text-xs mt-1 opacity-75">In Progress / Belum selesai</p>
                </div>
            </div>

            <!-- Grafik Status Penyelesaian & Akses Cepat Menu -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Grafik Chart.js -->
                <div class="md:col-span-2 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border border-gray-100">
                    <h3 class="font-bold text-gray-700 mb-4 border-b pb-2">
                        Status Penyelesaian Pekerjaan ({{ date('F Y') }})
                    </h3>
                    <div class="relative w-full" style="height: 250px;">
                        <canvas id="dashboardStatusChart"></canvas>
                    </div>
                </div>

                <!-- Tombol Pintasan Cepat Sesuai Role -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border border-gray-100 flex flex-col justify-between">
                    <div>
                        <h4 class="font-bold text-gray-700 mb-4 border-b pb-2">Akses Cepat Menu</h4>
                        <div class="flex flex-col gap-3">
                            @hasanyrole('Manager|Admin')
                                <a href="{{ route('trx.step1') }}" class="w-full text-center bg-blue-600 text-white px-4 py-2.5 rounded-md text-sm font-semibold hover:bg-blue-700 shadow-sm">
                                    + Input Transaksi Baru
                                </a>
                                <a href="{{ route('trx.index') }}" class="w-full text-center bg-gray-800 text-white px-4 py-2.5 rounded-md text-sm font-semibold hover:bg-gray-700 shadow-sm">
                                    Lihat Daftar Transaksi
                                </a>
                            @endhasanyrole

                            @hasanyrole('Notaris|Admin')
                                <a href="{{ route('laporan.keuangan') }}" class="w-full text-center bg-green-600 text-white px-4 py-2.5 rounded-md text-sm font-semibold hover:bg-green-700 shadow-sm">
                                    Buka Laporan Keuangan & Kinerja
                                </a>
                            @endhasanyrole

                            @role('Admin')
                                <a href="{{ route('users.index') }}" class="w-full text-center bg-purple-600 text-white px-4 py-2.5 rounded-md text-sm font-semibold hover:bg-purple-700 shadow-sm">
                                    Kelola Akun User
                                </a>
                            @endrole
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-6 pt-3 border-t text-center">
                        Notaris App &bull; Leonaras.ID
                    </p>
                </div>
            </div>

        </div>
    </div>

    <!-- Script Chart.js & DataLabels -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Chart.register(ChartDataLabels);

            const ctx = document.getElementById('dashboardStatusChart').getContext('2d');
            const dataSelesai = {{ $pekerjaanSelesai }};
            const dataBelum = {{ $pekerjaanBelum }};

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Selesai', 'Belum Selesai (Pending)'],
                    datasets: [{
                        data: [dataSelesai, dataBelum],
                        backgroundColor: [
                        'rgba(22, 163, 74, 0.9)',  // Hijau (Selesai)
                        'rgba(220, 38, 38, 0.9)'   // Merah (Belum Selesai / Tertunda)
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
                        datalabels: {
                            color: '#ffffff',
                            font: {
                                weight: 'bold',
                                size: 14
                            },
                            formatter: (value, context) => {
                                let total = context.chart._metasets[context.datasetIndex].total;
                                if (total === 0 || value === 0) return null;
                                return (value * 100 / total).toFixed(1) + "%";
                            }
                        }
                    },
                    cutout: '55%'
                }
            });
        });
    </script>
</x-app-layout>