<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Daftar Transaksi') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Notifikasi Sukses -->
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <!-- Header Actions & Search Form -->
                <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                    <h3 class="text-lg font-bold w-full md:w-auto">Data Permohonan</h3>

                    <form action="{{ route('trx.index') }}" method="GET" class="flex w-full md:w-auto gap-2">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Nama / ID Transaksi..." class="border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 w-full md:w-64 text-sm">
                        <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-700 text-sm font-medium">Cari</button>

                        @if(request('search'))
                            <a href="{{ route('trx.index') }}" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 text-sm font-medium flex items-center">Reset</a>
                        @endif
                    </form>

                    <a href="{{ route('trx.step1') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm font-bold whitespace-nowrap shadow-sm">+ Tambah Transaksi</a>
                </div>

                <!-- Tabel Data Transaksi -->
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border border-gray-200">
                        <thead class="bg-gray-100 border-b">
                            <tr>
                                <th class="py-3 px-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider whitespace-nowrap">ID Transaksi</th>
                                <th class="py-3 px-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider whitespace-nowrap">Tanggal</th>

                                @role('Admin')
                                    <th class="py-3 px-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider whitespace-nowrap">Diinput Oleh</th>
                                @endrole

                                <th class="py-3 px-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider whitespace-nowrap">Nama Lengkap</th>
                                <th class="py-3 px-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider whitespace-nowrap">Kategori</th>
                                <th class="py-3 px-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider whitespace-nowrap">Total Biaya</th>
                                <th class="py-3 px-4 text-center text-xs font-bold text-gray-700 uppercase tracking-wider whitespace-nowrap">Status</th>
                                <th class="py-3 px-4 text-center text-xs font-bold text-gray-700 uppercase tracking-wider whitespace-nowrap">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($transaksi as $trx)
                                <tr class="hover:bg-gray-50">
                                    <td class="py-3 px-4 font-medium text-gray-800 text-sm whitespace-nowrap align-middle">{{ $trx->id_transaksi }}</td>
                                    <td class="py-3 px-4 text-sm text-gray-600 whitespace-nowrap align-middle">{{ date('d M Y', strtotime($trx->tanggal_permohonan)) }}</td>

                                    @role('Admin')
                                        <td class="py-3 px-4 whitespace-nowrap align-middle">
                                            <span class="inline-flex items-center gap-1 bg-purple-100 text-purple-800 text-xs px-2.5 py-1 rounded-full font-semibold border border-purple-200 whitespace-nowrap">
                                                👤 {{ $trx->user->name ?? 'Sistem' }}
                                            </span>
                                        </td>
                                    @endrole

                                    <td class="py-3 px-4 text-sm font-semibold text-gray-900 whitespace-nowrap align-middle">{{ $trx->nama_lengkap }}</td>
                                    <td class="py-3 px-4 text-sm text-gray-600 whitespace-nowrap align-middle">{{ $trx->kategori }}</td>
                                    <td class="py-3 px-4 text-sm font-bold text-gray-800 whitespace-nowrap align-middle">Rp {{ number_format($trx->details->sum('biaya'), 0, ',', '.') }}</td>

                                    <!-- Status Badge -->
                                    <td class="py-3 px-4 text-center whitespace-nowrap align-middle">
                                        @if($trx->details->contains('status_dikerjakan', 0))
                                            <span class="inline-block bg-yellow-100 text-yellow-800 px-2.5 py-1 rounded-full text-xs font-bold whitespace-nowrap border border-yellow-200">In Progress</span>
                                        @else
                                            <span class="inline-block bg-green-100 text-green-800 px-2.5 py-1 rounded-full text-xs font-bold whitespace-nowrap border border-green-200">Selesai</span>
                                        @endif
                                    </td>

                                    <!-- Tombol Aksi -->
                                    <td class="py-3 px-4 text-center whitespace-nowrap align-middle">
                                        <div class="flex justify-center items-center gap-2">
                                            <a href="{{ route('trx.show', $trx->id) }}" class="bg-indigo-600 text-white px-3 py-1.5 rounded-md text-xs font-semibold hover:bg-indigo-700 shadow-sm whitespace-nowrap">Lihat & Update</a>

                                            @role('Admin')
                                            <form action="{{ route('trx.destroy', $trx->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?');" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="bg-red-600 text-white px-3 py-1.5 rounded-md text-xs font-semibold hover:bg-red-700 shadow-sm whitespace-nowrap">Hapus</button>
                                            </form>
                                            @endrole
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-4">
                    {{ $transaksi->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
