<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class TransactionController extends Controller
{
    // =======================================================
    // 1. BAGIAN TAMPILAN DAFTAR & DETAIL (Manager & Admin)
    // =======================================================

    // Menampilkan daftar semua transaksi (beserta fitur pencarian)
    public function index(Request $request)
    {
        $search = $request->search;
        $user = auth()->user();

        // Mulai query transaksi
        $query = Transaction::with(['details', 'user'])->latest();

        // JIKA USER ADALAH MANAGER (dan bukan Admin), FILTER HANYA DATA MILIKNYA
        if ($user->hasRole('Manager') && !$user->hasRole('Admin')) {
            $query->where('user_id', $user->id);
        }

        // Pencarian Nama / ID Transaksi
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nama_lengkap', 'like', '%' . $search . '%')
                  ->orWhere('id_transaksi', 'like', '%' . $search . '%');
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

        // Jika Manager, hanya bisa lihat detail kinerjanya sendiri
        if ($user->hasRole('Manager') && !$user->hasRole('Admin')) {
            $query->where('user_id', $user->id);
        }

        // Jika transaksi tidak ditemukan / milik Manager lain, akan muncul 404
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
            'tanggal_permohonan' => 'required|date'
        ]);

        $request->session()->put('step1', $validated);
        return redirect()->route('trx.step2');
    }

    // Menampilkan Halaman 2 (Kategori & Biaya)
    public function createStep2(Request $request)
    {
        // Jika langsung tembak URL Halaman 2 tanpa isi Halaman 1, kembalikan
        if (!$request->session()->has('step1')) {
            return redirect()->route('trx.step1');
        }
        return view('transaksi.step2');
    }

    // Memproses Halaman 2 dan menyimpannya secara permanen ke Database
    public function storeStep2(Request $request)
    {
        $request->validate([
            'kategori' => 'required|in:Bank,Perorangan,Badan Hukum',
            'jenis_permohonan' => 'required|array', // Minimal pilih 1 jenis
        ]);

        $step1 = $request->session()->get('step1');

        DB::beginTransaction();
        try {
            // 1. Simpan tabel utama
            $transaction = Transaction::create([
                'user_id' => auth()->id(),
                'nama_lengkap' => $step1['nama_lengkap'],
                'tanggal_permohonan' => $step1['tanggal_permohonan'],
                'kategori' => $request->kategori,
                'nama_bank' => $request->nama_bank,
                'plafon' => $request->plafon ? str_replace(',', '', $request->plafon) : null,
            ]);

            // 2. Simpan tabel detail (Bisa lebih dari 1 permohonan)
            foreach ($request->jenis_permohonan as $jenis) {

                // Ambil biaya dari input teks berdasarkan nama jenis yang di-format
                // (Mengubah spasi, tanda hubung, dan garis miring menjadi underscore)
                $inputKey = 'biaya_' . str_replace([' ', '-', '/'], '_', $jenis);
                $biaya = $request->input($inputKey, 0);

                TransactionDetail::create([
                    'transaction_id' => $transaction->id,
                    'jenis_permohonan' => $jenis,
                    'biaya' => $biaya,
                    'status_dikerjakan' => false
                ]);
            }

            DB::commit();
            $request->session()->forget('step1'); // Bersihkan session

            // Redirect langsung ke halaman daftar transaksi
            return redirect()->route('trx.index')->with('success', 'Transaksi berhasil disimpan!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Gagal menyimpan data: ' . $e->getMessage()]);
        }
    }


    // =======================================================
    // 3. BAGIAN UPDATE STATUS PEKERJAAN
    // =======================================================

    // Mengubah status dikerjakan (Belum / Sudah)
    public function updateStatus(Request $request, $id)
    {
        $detail = TransactionDetail::findOrFail($id);

        // Update status sesuai nilai yang dikirim dari form (0 atau 1)
        $detail->update([
            'status_dikerjakan' => $request->status_dikerjakan
        ]);

        return back()->with('success', 'Status pekerjaan berhasil diperbarui!');
    }

    // =======================================================
    // 4. BAGIAN LAPORAN KEUANGAN (Notaris & Admin)
    // =======================================================

    // Menghapus data transaksi (beserta detailnya)
    public function destroy($id)
    {
        $transaksi = Transaction::findOrFail($id);
        $transaksi->delete(); // Detail transaksi otomatis terhapus karena relasi cascade

        return back()->with('success', 'Data transaksi berhasil dihapus!');
    }
    public function keuangan(Request $request)
    {
        // Ambil filter dari URL (jika kosong, gunakan bulan dan tahun saat ini)
        $bulan =$request->bulan ?? date('m');
        $tahun =$request->tahun ?? date('Y');
        $kategori =$request->kategori; // Bisa kosong (semua kategori)

        // Mulai Query
        $query = Transaction::with('details')
            ->whereMonth('tanggal_permohonan', $bulan)
            ->whereYear('tanggal_permohonan', $tahun);

        // Jika filter kategori dipilih
        if ($kategori) {
            $query->where('kategori',$kategori);
        }

        // Ambil data (urutkan berdasarkan tanggal terbaru)
        $transaksi =$query->latest('tanggal_permohonan')->get();

        // Hitung total uang masuk dari relasi tabel detail
        $totalUangMasuk = $transaksi->map(function ($trx) {
            return $trx->details->sum('biaya');
        })->sum();

        return view('laporan.keuangan', compact('transaksi', 'totalUangMasuk', 'bulan', 'tahun', 'kategori'));
    }
// Mencetak PDF Laporan Keuangan
    public function cetakPdf(Request $request)
    {
        // Ambil filter yang sama dari URL
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

        // Mengubah view HTML menjadi file PDF
        $pdf = Pdf::loadView('laporan.pdf', compact('transaksi', 'totalUangMasuk', 'bulan', 'tahun', 'kategori'));

        // return $pdf->download('laporan.pdf'); // Gunakan ini jika ingin langsung terunduh
        return $pdf->stream('Laporan-Keuangan-'.$bulan.'-'.$tahun.'.pdf'); // Gunakan ini jika ingin tampil di tab baru (Preview)
    }
}
