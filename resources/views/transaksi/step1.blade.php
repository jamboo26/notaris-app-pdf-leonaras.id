<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Input Transaksi - Langkah 1 (Pemohon)') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('trx.store1') }}">
                    @csrf

                    <!-- Nama Lengkap -->
                    <div class="mb-4">
                        <label class="block font-medium text-sm text-gray-700">Nama Lengkap Pemohon</label>
                        <input type="text" name="nama_lengkap" value="{{ old('nama_lengkap') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('nama_lengkap') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- Tanggal Permohonan -->
                    <div class="mb-6">
                        <label class="block font-medium text-sm text-gray-700">Tanggal Permohonan</label>
                        <input type="date" name="tanggal_permohonan" value="{{ old('tanggal_permohonan', date('Y-m-d')) }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('tanggal_permohonan') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">Lanjut ke Langkah 2</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
