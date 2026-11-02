<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Laporan Keuangan') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Kotak Filter -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <form action="{{ route('laporan.keuangan') }}" method="GET" class="flex items-end gap-4">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bulan</label>
                        <select name="bulan" class="border-gray-300 rounded-md">
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
                        <select name="tahun" class="border-gray-300 rounded-md">
                            @for($i = date('Y') - 5; $i <= date('Y'); $i++)
                                <option value="{{ $i }}" {{ $tahun == $i ? 'selected' : '' }}>{{ $i }}</option>
                            @endfor
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kategori (Opsional)</label>
                        <select name="kategori" class="border-gray-300 rounded-md">
                            <option value="">Semua Kategori</option>
                            <option value="Bank" {{ $kategori == 'Bank' ? 'selected' : '' }}>Bank</option>
                            <option value="Perorangan" {{ $kategori == 'Perorangan' ? 'selected' : '' }}>Perorangan</option>
                            <option value="Badan Hukum" {{ $kategori == 'Badan Hukum' ? 'selected' : '' }}>Badan Hukum</option>
                        </select>
                    </div>

                    <!-- ... kode dropdown Bulan, Tahun, Kategori di atasnya biarkan sama ... -->

                    <div>
                        <!-- Label transparan penyeimbang -->
                        <label class="block text-sm font-medium text-transparent mb-1">Aksi</label>
                        <div class="flex items-center gap-3">
                            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 shadow-sm">
                                Terapkan Filter
                            </button>
                            <a href="{{ route('laporan.keuangan') }}" class="text-gray-500 hover:text-gray-900 hover:underline font-medium">
                                Reset
                            </a>
                            <!-- TOMBOL CETAK PDF (request()->query() akan membawa filter aktif ke PDF) -->
                            <a href="{{ route('laporan.pdf', request()->query()) }}" target="_blank" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 shadow-sm ml-4 border border-red-700">
                                🖨️ Cetak PDF
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Kotak Ringkasan Total -->
            <div class="bg-gradient-to-r from-green-400 to-green-600 shadow-sm sm:rounded-lg p-6 mb-6 text-blue text-center">
                <h3 class="text-lg opacity-90 mb-1">Total Pemasukan pada Periode Terpilih</h3>
                <h1 class="text-4xl font-extrabold">Rp {{ number_format($totalUangMasuk, 0, ',', '.') }}</h1>
            </div>

            <!-- Tabel Data Laporan -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border border-gray-200">
                        <thead class="bg-gray-100 border-b">
                            <tr>
                                <th class="py-3 px-4 text-left font-semibold text-gray-700">Tanggal</th>
                                <th class="py-3 px-4 text-left font-semibold text-gray-700">ID Transaksi</th>
                                <th class="py-3 px-4 text-left font-semibold text-gray-700">Pemohon / Klien</th>
                                <th class="py-3 px-4 text-left font-semibold text-gray-700">Rincian Permohonan</th>
                                <th class="py-3 px-4 text-right font-semibold text-gray-700">Total Rp</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transaksi as $trx)
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-3 px-4 text-sm">{{ date('d M Y', strtotime($trx->tanggal_permohonan)) }}</td>
                                    <td class="py-3 px-4 text-sm font-medium text-gray-600">{{ $trx->id_transaksi }}</td>
                                    <td class="py-3 px-4">
                                        <div class="font-bold">{{ $trx->nama_lengkap }}</div>
                                        <div class="text-xs text-gray-500">{{ $trx->kategori }} {{ $trx->nama_bank ? ' - '.$trx->nama_bank : '' }}</div>
                                    </td>
                                    <td class="py-3 px-4 text-sm text-gray-600">
                                        <ul class="list-disc pl-4">
                                            @foreach($trx->details as $detail)
                                                <li>{{ $detail->jenis_permohonan }} ({{ number_format($detail->biaya, 0, ',', '.') }})</li>
                                            @endforeach
                                        </ul>
                                    </td>
                                    <td class="py-3 px-4 text-right font-bold text-gray-800">
                                        {{ number_format($trx->details->sum('biaya'), 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-6 text-center text-gray-500 font-medium">Tidak ada transaksi pada periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
