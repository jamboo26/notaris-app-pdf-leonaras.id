<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionDetail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

class TransactionController extends Controller
{
    private const JENIS_PERMOHONAN = [
        'Bank' => ['SKMHT', 'APHT', 'PERALIHAN HAK', 'AJB', 'HIBAH', 'APHB', 'PENINGKATAN HAK'],
        'Perorangan' => ['PERALIHAN HAK', 'AJB', 'HIBAH', 'WARIS', 'APHB'],
        'Badan Hukum' => [
            'PT - Pendirian',
            'PT - Perubahan',
            'PT - Laporan Tahunan',
            'CV - Pendirian',
            'CV - Perubahan',
            'Yayasan - Pendirian',
            'Yayasan - Perubahan',
            'Perkumpulan - Pendirian',
            'Perkumpulan - Perubahan',
            'Koperasi - Pendirian',
            'Koperasi - Perubahan',
        ],
    ];

    // =======================================================
    // 0. BAGIAN DASHBOARD UTAMA (Admin, Manager, Notaris)
    // =======================================================
    public function dashboard()
    {
        $user = auth()->user();
        $bulan = date('m');
        $tahun = date('Y');

        // 1. Hitung Total Keseluruhan (All Time)
        $allTimeQuery = Transaction::query();
        if ($user->hasRole('Manager') && !$user->hasRole('Admin')) {
            $allTimeQuery->where('user_id', $user->id);
        }
        $allTimeTransaksi = (clone $allTimeQuery)->count();
        $allTimePekerjaan = TransactionDetail::whereIn('transaction_id', (clone $allTimeQuery)->pluck('id'))->count();

        // 2. Hitung Statistik Bulan Berjalan
        $bulanIniQuery = Transaction::with('details')
            ->whereMonth('tanggal_permohonan', $bulan)
            ->whereYear('tanggal_permohonan', $tahun);

        if ($user->hasRole('Manager') && !$user->hasRole('Admin')) {
            $bulanIniQuery->where('user_id', $user->id);
        }

        $transaksiBulanIni = $bulanIniQuery->get();
        $totalTransaksi = $transaksiBulanIni->count();
        $pekerjaanSelesai = 0;
        $pekerjaanBelum = 0;

        foreach ($transaksiBulanIni as $trx) {
            $pekerjaanSelesai += $trx->details->where('status_dikerjakan', 1)->count();
            $pekerjaanBelum += $trx->details->where('status_dikerjakan', 0)->count();
        }
        $semuaPekerjaan = $pekerjaanSelesai + $pekerjaanBelum;

        return view('dashboard', compact(
            'allTimeTransaksi', 'allTimePekerjaan',
            'totalTransaksi', 'semuaPekerjaan',
            'pekerjaanSelesai', 'pekerjaanBelum'
        ));
    }
    
    // =======================================================
    // 1. BAGIAN TAMPILAN DAFTAR & DETAIL (Manager & Admin)
    // =======================================================

    // Menampilkan daftar transaksi (Filter per Manager & Pencarian)
    public function index(Request $request)
    {
        $search = $request->search;
        $user = auth()->user();

        // Mulai query transaksi beserta relasi detail dan user pembuatnya
        $query = Transaction::with(['details', 'user'])->latest();

        // Jika user adalah Manager (dan bukan Admin), tampilkan hanya data miliknya
        if ($user->hasRole('Manager') && ! $user->hasRole('Admin')) {
            $query->where('user_id', $user->id);
        }

        // Pencarian Nama Pemohon atau ID Transaksi
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', '%'.$search.'%')
                    ->orWhere('id_transaksi', 'like', '%'.$search.'%');
            });
        }

        $transaksi = $query->paginate(10);

        return view('transaksi.index', compact('transaksi'));
    }

    // Menampilkan detail spesifik satu transaksi
    public function show($id)
    {
        $user = auth()->user();
        $query = Transaction::with('details');

        // Jika Manager, hanya bisa lihat detail transaksinya sendiri
        if ($user->hasRole('Manager') && ! $user->hasRole('Admin')) {
            $query->where('user_id', $user->id);
        }

        $transaksi = $query->findOrFail($id);

        return view('transaksi.show', compact('transaksi'));
    }

    // =======================================================
    // 2. BAGIAN INPUT TRANSAKSI (MULTI-STEP)
    // =======================================================

    // Menampilkan Halaman 1 (Pemohon)
    public function createStep1()
    {
        return view('transaksi.step1');
    }

    // Memproses Halaman 1 dan menyimpannya sementara ke Session
    public function storeStep1(Request $request)
    {
        $validated = $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'tanggal_permohonan' => 'required|date',
        ]);

        $request->session()->put('step1', $validated);

        return redirect()->route('trx.step2');
    }

    // Menampilkan Halaman 2 (Kategori & Biaya)
    public function createStep2(Request $request)
    {
        if (! $request->session()->has('step1')) {
            return redirect()->route('trx.step1');
        }

        return view('transaksi.step2');
    }

    // Memproses Halaman 2 dan menyimpannya ke Database
    public function storeStep2(Request $request)
    {
        if (! $request->session()->has('step1')) {
            return redirect()->route('trx.step1');
        }

        $this->normalizeCurrencyInputs($request);
        $validated = $request->validate($this->transactionRules($request));
        $step1 = $request->session()->get('step1');

        DB::transaction(function () use ($validated, $step1) {
            $transaction = Transaction::create([
                'user_id' => auth()->id(),
                'nama_lengkap' => $step1['nama_lengkap'],
                'tanggal_permohonan' => $step1['tanggal_permohonan'],
                'kategori' => $validated['kategori'],
                'nama_bank' => $validated['kategori'] === 'Bank' ? ($validated['nama_bank'] ?? null) : null,
                'plafon' => $validated['kategori'] === 'Bank' ? ($validated['plafon'] ?? null) : null,
            ]);

            foreach ($validated['jenis_permohonan'] as $jenis) {
                $inputKey = 'biaya_'.str_replace([' ', '-', '/'], '_', $jenis);

                TransactionDetail::create([
                    'transaction_id' => $transaction->id,
                    'jenis_permohonan' => $jenis,
                    'biaya' => $validated[$inputKey] ?? 0,
                    'status_dikerjakan' => false,
                ]);
            }

        });

        $request->session()->forget('step1');

        return redirect()->route('trx.index')->with('success', 'Transaksi berhasil disimpan!');
    }

    // =======================================================
    // 3. BAGIAN EDIT & UPDATE TRANSAKSI LENGKAP
    // =======================================================

    // Menampilkan Form Edit Lengkap
    public function edit($id)
    {
        $user = auth()->user();
        $query = Transaction::with('details');

        if ($user->hasRole('Manager') && ! $user->hasRole('Admin')) {
            $query->where('user_id', $user->id);
        }

        $transaksi = $query->findOrFail($id);

        return view('transaksi.edit', compact('transaksi'));
    }

    // Menyimpan Perubahan Edit Lengkap
    public function update(Request $request, $id)
    {
        $this->normalizeCurrencyInputs($request);
        $validated = $request->validate(array_merge([
            'nama_lengkap' => 'required|string|max:255',
            'tanggal_permohonan' => 'required|date',
        ], $this->transactionRules($request)));

        $user = auth()->user();
        $query = Transaction::with('details');

        if ($user->hasRole('Manager') && ! $user->hasRole('Admin')) {
            $query->where('user_id', $user->id);
        }

        $transaction = $query->findOrFail($id);

        DB::transaction(function () use ($transaction, $validated) {
            $transaction->update([
                'nama_lengkap' => $validated['nama_lengkap'],
                'tanggal_permohonan' => $validated['tanggal_permohonan'],
                'kategori' => $validated['kategori'],
                'nama_bank' => $validated['kategori'] === 'Bank' ? ($validated['nama_bank'] ?? null) : null,
                'plafon' => $validated['kategori'] === 'Bank' ? ($validated['plafon'] ?? null) : null,
            ]);

            $statusLama = $transaction->details->pluck('status_dikerjakan', 'jenis_permohonan')->toArray();
            $transaction->details()->delete();

            foreach ($validated['jenis_permohonan'] as $jenis) {
                $inputKey = 'biaya_'.str_replace([' ', '-', '/'], '_', $jenis);

                TransactionDetail::create([
                    'transaction_id' => $transaction->id,
                    'jenis_permohonan' => $jenis,
                    'biaya' => $validated[$inputKey] ?? 0,
                    'status_dikerjakan' => $statusLama[$jenis] ?? false,
                ]);
            }
        });

        return redirect()->route('trx.index')->with('success', 'Data transaksi '.$transaction->id_transaksi.' berhasil diperbarui!');
    }

    // =======================================================
    // 4. BAGIAN UPDATE STATUS & HAPUS TRANSAKSI
    // =======================================================

    // Mengubah status dikerjakan (Belum / Sudah)
    public function updateStatus(Request $request, $id)
    {
        $user = auth()->user();
        $query = TransactionDetail::query();

        if ($user->hasRole('Manager') && ! $user->hasRole('Admin')) {
            $query->whereHas('transaction', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            });
        }

        $detail = $query->findOrFail($id);
        $validated = $request->validate([
            'status_dikerjakan' => ['required', 'boolean'],
        ]);

        $detail->update($validated);

        return back()->with('success', 'Status pekerjaan berhasil diperbarui!');
    }

    // Menghapus data transaksi (Khusus Admin)
    public function destroy($id)
    {
        $transaksi = Transaction::findOrFail($id);
        $transaksi->delete();

        return back()->with('success', 'Data transaksi berhasil dihapus!');
    }

    // =======================================================
    // 5. BAGIAN LAPORAN KEUANGAN & CETAK PDF (Notaris & Admin)
    // =======================================================

    public function keuangan(Request $request)
    {
        $bulan = $request->bulan ?? date('m');
        $tahun = $request->tahun ?? date('Y');
        $kategori = $request->kategori;

        $query = Transaction::with('details')
            ->whereMonth('tanggal_permohonan', $bulan)
            ->whereYear('tanggal_permohonan', $tahun);

        if ($kategori) {
            $query->where('kategori', $kategori);
        }

        $transaksi = $query->latest('tanggal_permohonan')->get();

        $totalUangMasuk = $transaksi->map(function ($trx) {
            return $trx->details->sum('biaya');
        })->sum();

        // Rekapan pekerjaan & rincian nominal berdasarkan filter
        $totalTransaksi = $transaksi->count();
        $pekerjaanSelesai = 0;
        $pekerjaanBelum = 0;
        $uangSelesai = 0;
        $uangBelum = 0;

        foreach ($transaksi as $trx) {
            $pekerjaanSelesai += $trx->details->where('status_dikerjakan', 1)->count();
            $pekerjaanBelum += $trx->details->where('status_dikerjakan', 0)->count();
            $uangSelesai += $trx->details->where('status_dikerjakan', 1)->sum('biaya');
            $uangBelum += $trx->details->where('status_dikerjakan', 0)->sum('biaya');
        }
        $semuaPekerjaan = $pekerjaanSelesai + $pekerjaanBelum;

        // Rekapan keseluruhan (All Time)
        $allTimeTransaksi = Transaction::count();
        $allTimePekerjaan = TransactionDetail::count();

        return view('laporan.keuangan', compact(
            'transaksi', 'totalUangMasuk', 'bulan', 'tahun', 'kategori',
            'totalTransaksi', 'semuaPekerjaan', 'pekerjaanSelesai', 'pekerjaanBelum',
            'allTimeTransaksi', 'allTimePekerjaan',
            'uangSelesai', 'uangBelum'
        ));
    }

    // Mencetak PDF Laporan Keuangan
    public function cetakPdf(Request $request)
    {
        $bulan = $request->bulan ?? date('m');
        $tahun = $request->tahun ?? date('Y');
        $kategori = $request->kategori;

        $query = Transaction::with('details')
            ->whereMonth('tanggal_permohonan', $bulan)
            ->whereYear('tanggal_permohonan', $tahun);

        if ($kategori) {
            $query->where('kategori', $kategori);
        }

        $transaksi = $query->latest('tanggal_permohonan')->get();

        $totalUangMasuk = $transaksi->map(function ($trx) {
            return $trx->details->sum('biaya');
        })->sum();

        $uangSelesai = 0;
        $uangBelum = 0;

        foreach ($transaksi as $trx) {
            $uangSelesai += $trx->details->where('status_dikerjakan', 1)->sum('biaya');
            $uangBelum += $trx->details->where('status_dikerjakan', 0)->sum('biaya');
        }

        $pdf = Pdf::loadView('laporan.pdf', compact(
            'transaksi', 'totalUangMasuk', 'bulan', 'tahun', 'kategori', 'uangSelesai', 'uangBelum'
        ));

        return $pdf->stream('Laporan-Keuangan-'.$bulan.'-'.$tahun.'.pdf');
    }

    /**
     * @return array<string, array<int, string|In>>
     */
    private function transactionRules(Request $request): array
    {
        $kategori = $request->input('kategori');
        $jenisPermohonan = is_string($kategori) ? (self::JENIS_PERMOHONAN[$kategori] ?? []) : [];
        $rules = [
            'kategori' => ['required', Rule::in(array_keys(self::JENIS_PERMOHONAN))],
            'jenis_permohonan' => ['required', 'array', 'min:1'],
            'jenis_permohonan.*' => ['required', 'string', 'distinct', Rule::in($jenisPermohonan)],
            'nama_bank' => ['nullable', 'string', 'max:255'],
            'plafon' => ['nullable', 'integer', 'min:0', 'max:9999999999999'],
        ];

        foreach (array_merge(...array_values(self::JENIS_PERMOHONAN)) as $jenis) {
            $inputKey = 'biaya_'.str_replace([' ', '-', '/'], '_', $jenis);
            $rules[$inputKey] = ['nullable', 'integer', 'min:0', 'max:9999999999999'];
        }

        return $rules;
    }

    private function normalizeCurrencyInputs(Request $request): void
    {
        foreach ($request->all() as $key => $value) {
            if (($key === 'plafon' || str_starts_with($key, 'biaya_')) && is_string($value)) {
                $request->merge([$key => str_replace('.', '', $value)]);
            }
        }
    }
}
