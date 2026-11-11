<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    protected function sanitizeMoney($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $cleaned = preg_replace('/[^0-9,.-]/', '', (string) $value);

        if ($cleaned === '' || $cleaned === '-' || $cleaned === '.') {
            return 0.0;
        }

        $cleaned = str_replace('.', '', $cleaned);
        $cleaned = str_replace(',', '.', $cleaned);

        return (float) $cleaned;
    }

    protected function canManageTransaction($transaction): bool
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return false;
        }

        if ($user->hasRole('Admin')) {
            return true;
        }

        return $user->hasRole('Manager') && $transaction->user_id === $user->id;
    }

    public function index(Request $request)
    {
        $search = $request->search;
        $user = Auth::user();

        if (! $user instanceof User) {
            abort(403);
        }

        $query = Transaction::with(['details', 'user'])->latest();

        if ($user->hasRole('Manager') && ! $user->hasRole('Admin')) {
            $query->where('user_id', $user->id);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', '%'.$search.'%')
                    ->orWhere('id_transaksi', 'like', '%'.$search.'%');
            });
        }

        $transaksi = $query->paginate(10);

        return view('transaksi.index', compact('transaksi'));
    }

    public function show($id)
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            abort(403);
        }

        $query = Transaction::with('details');

        if ($user->hasRole('Manager') && ! $user->hasRole('Admin')) {
            $query->where('user_id', $user->id);
        }

        $transaksi = $query->findOrFail($id);

        if (! $this->canManageTransaction($transaksi)) {
            abort(403);
        }

        return view('transaksi.show', compact('transaksi'));
    }

    public function createStep1()
    {
        return view('transaksi.step1');
    }

    public function storeStep1(Request $request)
    {
        $validated = $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'tanggal_permohonan' => 'required|date',
        ]);

        $request->session()->put('step1', $validated);

        return redirect()->route('trx.step2');
    }

    public function createStep2(Request $request)
    {
        if (! $request->session()->has('step1')) {
            return redirect()->route('trx.step1');
        }

        return view('transaksi.step2');
    }

    public function storeStep2(Request $request)
    {
        $request->validate([
            'kategori' => ['required', 'in:Bank,Perorangan,Badan Hukum'],
            'jenis_permohonan' => ['required', 'array', 'min:1'],
        ]);

        $step1 = $request->session()->get('step1');

        if (! $step1) {
            return redirect()->route('trx.step1');
        }

        $jenisPermohonan = array_values(array_unique(array_filter(
            $request->input('jenis_permohonan', []),
            fn ($jenis) => is_string($jenis) && trim($jenis) !== ''
        )));

        if (empty($jenisPermohonan)) {
            return back()->withErrors([
                'jenis_permohonan' => 'Pilih minimal 1 jenis permohonan.',
            ]);
        }

        DB::beginTransaction();

        try {
            $transaction = Transaction::create([
                'user_id' => Auth::id(),
                'nama_lengkap' => $step1['nama_lengkap'],
                'tanggal_permohonan' => $step1['tanggal_permohonan'],
                'kategori' => $request->kategori,
                'nama_bank' => $request->nama_bank,
                'plafon' => $request->filled('plafon') ? $this->sanitizeMoney($request->plafon) : null,
            ]);

            foreach ($jenisPermohonan as $jenis) {
                $inputKey = 'biaya_'.str_replace([' ', '-', '/'], '_', trim((string) $jenis));
                $biaya = $this->sanitizeMoney($request->input($inputKey, 0));

                TransactionDetail::create([
                    'transaction_id' => $transaction->id,
                    'jenis_permohonan' => $jenis,
                    'biaya' => $biaya,
                    'status_dikerjakan' => false,
                ]);
            }

            DB::commit();
            $request->session()->forget('step1');

            return redirect()->route('trx.index')->with('success', 'Transaksi berhasil disimpan!');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Gagal menyimpan data: '.$e->getMessage()]);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status_dikerjakan' => ['required', 'boolean'],
        ]);

        $detail = TransactionDetail::with('transaction')->findOrFail($id);

        if (! $this->canManageTransaction($detail->transaction)) {
            abort(403);
        }

        $detail->update([
            'status_dikerjakan' => $request->boolean('status_dikerjakan'),
        ]);

        return back()->with('success', 'Status pekerjaan berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $transaksi = Transaction::findOrFail($id);

        $user = Auth::user();

        if (! $user instanceof User || ! $user->hasRole('Admin')) {
            abort(403);
        }

        $transaksi->delete();

        return back()->with('success', 'Data transaksi berhasil dihapus!');
    }

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

        $allTimeTransaksi = Transaction::count();
        $allTimePekerjaan = TransactionDetail::count();

        return view('laporan.keuangan', compact(
            'transaksi',
            'totalUangMasuk',
            'bulan',
            'tahun',
            'kategori',
            'totalTransaksi',
            'semuaPekerjaan',
            'pekerjaanSelesai',
            'pekerjaanBelum',
            'allTimeTransaksi',
            'allTimePekerjaan',
            'uangSelesai',
            'uangBelum'
        ));
    }

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

        $pdf = Pdf::loadView('laporan.pdf', compact('transaksi', 'totalUangMasuk', 'bulan', 'tahun', 'kategori', 'uangSelesai', 'uangBelum'));

        return $pdf->stream('Laporan-Keuangan-'.$bulan.'-'.$tahun.'.pdf');
    }
}
