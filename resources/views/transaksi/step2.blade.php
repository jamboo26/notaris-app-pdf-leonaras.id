<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Input Transaksi - Langkah 2 (Detail Kategori)') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <!-- x-data mendefinisikan state awal kategori untuk Alpine.js -->
                <form method="POST" action="{{ route('trx.store2') }}" x-data="{ kategori: '{{ old('kategori') }}' }">
                    @csrf

                    @if($errors->any())
                        <div class="bg-red-100 text-red-700 p-4 mb-4 rounded">
                            <ul class="list-disc pl-5">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Dropdown Kategori -->
                    <div class="mb-6">
                        <label class="block font-medium text-sm text-gray-700 mb-2">Kategori Pemohon</label>
                        <select name="kategori" x-model="kategori" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            <option value="">-- Pilih Kategori --</option>
                            <option value="Bank">Bank</option>
                            <option value="Perorangan">Perorangan</option>
                            <option value="Badan Hukum">Badan Hukum</option>
                        </select>
                    </div>

                    <hr class="mb-6">

                    <!-- SEKSI 1: BANK -->
                    <div x-show="kategori === 'Bank'" style="display: none;">
                        <h3 class="font-bold text-lg mb-4 text-blue-600 border-b pb-2">Detail Bank</h3>
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div>
                                <label class="block text-sm">Nama Bank</label>
                                <input type="text" name="nama_bank" class="w-full border-gray-300 rounded-md" placeholder="Contoh: BRI Cabang Sumedang">
                            </div>
                            <div>
                                <label class="block text-sm">Plafon (Rp)</label>
                                <input type="number" name="plafon" class="w-full border-gray-300 rounded-md" placeholder="Contoh: 500000000">
                            </div>
                        </div>

                        <label class="block text-sm font-bold mb-4">Pilih Jenis Permohonan Bank:</label>
                        @php $opsiBank = ['SKMHT', 'APHT', 'PERALIHAN HAK', 'AJB', 'HIBAH', 'APHB', 'PENINGKATAN HAK']; @endphp

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50 p-4 rounded-md border">
                            @foreach($opsiBank as $opsi)
                                <div class="flex items-center gap-4">
                                    <label class="w-1/2 flex items-center">
                                        <input type="checkbox" name="jenis_permohonan[]" value="{{ $opsi }}" class="mr-2 border-gray-300 rounded">
                                        {{ $opsi }}
                                    </label>
                                    <input type="number" name="biaya_{{ str_replace([' ', '-', '/'], '_', $opsi) }}" placeholder="Biaya (Rp)" class="w-1/2 border-gray-300 rounded-md text-sm">
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- SEKSI 2: PERORANGAN -->
                    <div x-show="kategori === 'Perorangan'" style="display: none;">
                        <h3 class="font-bold text-lg mb-4 text-green-600 border-b pb-2">Detail Perorangan</h3>
                        <label class="block text-sm font-bold mb-4">Pilih Jenis Permohonan Perorangan:</label>
                        @php $opsiPerorangan = ['PERALIHAN HAK', 'AJB', 'HIBAH', 'WARIS', 'APHB']; @endphp

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50 p-4 rounded-md border">
                            @foreach($opsiPerorangan as $opsi)
                                <div class="flex items-center gap-4">
                                    <label class="w-1/2 flex items-center">
                                        <input type="checkbox" name="jenis_permohonan[]" value="{{ $opsi }}" class="mr-2 border-gray-300 rounded">
                                        {{ $opsi }}
                                    </label>
                                    <input type="number" name="biaya_{{ str_replace([' ', '-', '/'], '_', $opsi) }}" placeholder="Biaya (Rp)" class="w-1/2 border-gray-300 rounded-md text-sm">
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- SEKSI 3: BADAN HUKUM (SUDAH DIKELOMPOKKAN) -->
                    <div x-show="kategori === 'Badan Hukum'" style="display: none;">
                        <h3 class="font-bold text-lg mb-4 text-purple-600 border-b pb-2">Detail Badan Hukum</h3>
                        <label class="block text-sm font-bold mb-4">Pilih Jenis Permohonan Badan Hukum:</label>

                        @php
                        // Mengelompokkan pilihan ke dalam array
                        $grupBadanHukum = [
                            'PT' => ['Pendirian', 'Perubahan', 'Laporan Tahunan'],
                            'CV' => ['Pendirian', 'Perubahan'],
                            'Yayasan' => ['Pendirian', 'Perubahan'],
                            'Perkumpulan' => ['Pendirian', 'Perubahan'],
                            'Koperasi' => ['Pendirian', 'Perubahan']
                        ];
                        @endphp

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            @foreach($grupBadanHukum as $grup => $opsiList)
                                <!-- Kotak per Grup (PT, CV, dst) -->
                                <div class="border border-gray-200 rounded-md bg-gray-50 p-4 shadow-sm">
                                    <h4 class="font-bold text-md mb-3 text-gray-800 border-b pb-2">{{ $grup }}</h4>

                                    @foreach($opsiList as $opsi)
                                        @php
                                            // Format nama untuk database: contoh "PT - Pendirian"
                                            $nilaiSimpan = $grup . ' - ' . $opsi;
                                            // Format nama untuk input tag
                                            $namaInputBiaya = 'biaya_' . str_replace([' ', '-', '/'], '_', $nilaiSimpan);
                                        @endphp
                                        <div class="flex items-center gap-2 mb-3">
                                            <label class="w-1/2 flex items-center text-sm font-medium text-gray-700">
                                                <input type="checkbox" name="jenis_permohonan[]" value="{{ $nilaiSimpan }}" class="mr-2 border-gray-300 rounded text-purple-600">
                                                {{ $opsi }}
                                            </label>
                                            <input type="number" name="{{ $namaInputBiaya }}" placeholder="Biaya (Rp)" class="w-1/2 border-gray-300 rounded-md text-sm">
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex justify-between mt-8">
                        <a href="{{ route('trx.step1') }}" class="text-gray-600 hover:underline">< Kembali</a>
                        <!-- Tombol submit -->
                        <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-md hover:bg-indigo-700 font-bold shadow-md transition duration-150">Simpan Transaksi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
