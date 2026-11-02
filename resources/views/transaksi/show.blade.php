<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Detail Transaksi: ') }} {{ $transaksi->id_transaksi }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Info Pemohon -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="font-bold text-lg mb-4 border-b pb-2">Informasi Pemohon</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div><span class="text-gray-500 text-sm">Nama Lengkap:</span> <br> <span class="font-semibold">{{ $transaksi->nama_lengkap }}</span></div>
                    <div><span class="text-gray-500 text-sm">Tanggal:</span> <br> <span class="font-semibold">{{ date('d M Y', strtotime($transaksi->tanggal_permohonan)) }}</span></div>
                    <div><span class="text-gray-500 text-sm">Kategori:</span> <br> <span class="font-semibold">{{ $transaksi->kategori }}</span></div>

                    @if($transaksi->kategori == 'Bank')
                        <div><span class="text-gray-500 text-sm">Nama Bank:</span> <br> <span class="font-semibold">{{ $transaksi->nama_bank }}</span></div>
                        <div><span class="text-gray-500 text-sm">Plafon:</span> <br> <span class="font-semibold">Rp {{ number_format($transaksi->plafon, 0, ',', '.') }}</span></div>
                    @endif
                </div>
            </div>

            <!-- Daftar Pekerjaan & Update Status -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-bold text-lg mb-4 border-b pb-2">Rincian Permohonan Pekerjaan</h3>

                <table class="min-w-full bg-white border border-gray-200 text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="py-2 px-4 border-b text-left">Jenis Permohonan</th>
                            <th class="py-2 px-4 border-b text-left">Biaya</th>
                            <th class="py-2 px-4 border-b text-center">Status Saat Ini</th>
                            <th class="py-2 px-4 border-b text-center">Ubah Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transaksi->details as $detail)
                            <tr>
                                <td class="py-2 px-4 border-b font-medium">{{ $detail->jenis_permohonan }}</td>
                                <td class="py-2 px-4 border-b text-gray-600">Rp {{ number_format($detail->biaya, 0, ',', '.') }}</td>
                                <td class="py-2 px-4 border-b text-center">
                                    @if($detail->status_dikerjakan)
                                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-bold">Sudah Dikerjakan</span>
                                    @else
                                        <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs font-bold">Belum Dikerjakan</span>
                                    @endif
                                </td>
                                <td class="py-2 px-4 border-b text-center">
                                    <!-- Form Update Status -->
                                    <form action="{{ route('trx.updateStatus', $detail->id) }}" method="POST" class="flex justify-center items-center gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status_dikerjakan" class="border-gray-300 rounded text-xs p-1" onchange="this.form.submit()">
                                            <option value="0" {{ $detail->status_dikerjakan == 0 ? 'selected' : '' }}>Belum</option>
                                            <option value="1" {{ $detail->status_dikerjakan == 1 ? 'selected' : '' }}>Sudah</option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="mt-6">
                    <a href="{{ route('trx.index') }}" class="text-gray-500 hover:underline">< Kembali ke Daftar</a>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
