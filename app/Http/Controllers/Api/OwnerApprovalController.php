<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OwnerApprovalController extends Controller
{
    /**
     * Mengambil daftar pengajuan EVENT berdasarkan status (default: pending)
     */
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');

        // Validasi input status agar sesuai dengan tipe ENUM pada tabel events Anda
        $allowedStatuses = ['pending', 'approved', 'rejected', 'cancelled', 'pending_cancel', 'pending_reschedule'];
        if (!in_array($status, $allowedStatuses)) {
            return response()->json([
                'success' => false,
                'message' => 'Status pencarian tidak valid'
            ], 400);
        }

        try {
            // Melakukan join tabel events -> eo -> users dengan relasi foreign key yang tepat
            $eventSubmissions = DB::table('events')
                ->join('eo', 'events.eo_id', '=', 'eo.id')
                ->join('users', 'eo.user_id', '=', 'users.id')
                ->select(
                    'events.id as id',                 // ID Event untuk acuan eksekusi proses approval
                    'events.title as event_title',     // Judul Event
                    'events.status',                   // Status Enum Event
                    'events.created_at',               // Tanggal dibuatnya pengajuan berkas
                    'events.rejected_reason',          // Alasan penolakan jika status 'rejected'
                    'eo.nama_badan_usaha',             // Info Administrasi EO
                    'eo.alamat_badan_usaha',
                    'eo.penanggung_jawab',
                    'eo.logo',
                    'eo.dokumen_badan_usaha',
                    'eo.ktp_penanggung_jawab',
                    'eo.bank_name',
                    'eo.account_number',
                    'eo.account_name',
                    'users.name as user_name',          // Data User pengaju berkas
                    'users.email as user_email'
                )
                ->where('events.status', $status)
                ->orderBy('events.created_at', 'desc') // Menampilkan pengajuan terbaru di posisi atas
                ->get();

            return response()->json([
                'success' => true,
                'message' => "Berhasil memuat data pengajuan dengan status $status",
                'data' => $eventSubmissions
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server saat memuat data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Memproses aksi Approval atau Rejection (Menyetujui atau Menolak Data Berkas)
     */
    public function processApproval(Request $request, $id)
    {
        // Validasi payload data yang masuk dari aplikasi Flutter
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:approved,rejected',
            // Kolom reason wajib diisi jika status penolakan bernilai 'rejected'
            'reason' => 'required_if:status,rejected|string|nullable|max:5000',
        ], [
            'reason.required_if' => 'Alasan penolakan (rejected reason) wajib dicantumkan jika menolak data.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi data gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Memastikan baris data event tersebut benar-benar eksis di database
            $event = DB::table('events')->where('id', $id)->first();

            if (!$event) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data pengajuan tidak ditemukan di database'
                ], 404);
            }

            // Memastikan data event masih dalam status 'pending' sebelum diubah nilainya
            if ($event->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pengajuan ini sudah dieksekusi atau diproses sebelumnya'
                ], 400);
            }

            // Menyiapkan array pasangan kolom dan nilai baru untuk memperbarui isi database
            $updateData = [
                'status' => $request->status,
                'updated_at' => now() // Sinkronisasi dengan timestamp updated_at
            ];

            // Kondisional pengisian kolom text 'rejected_reason'
            if ($request->status === 'rejected') {
                $updateData['rejected_reason'] = $request->reason;
            } else {
                // Jika berkas disetujui, pastikan kolom alasan dibersihkan (set NULL)
                $updateData['rejected_reason'] = null;
            }

            // Eksekusi pembaruan data secara langsung ke tabel 'events'
            DB::table('events')
                ->where('id', $id)
                ->update($updateData);

            return response()->json([
                'success' => true,
                'message' => "Data pengajuan berhasil di-" . ($request->status === 'approved' ? 'setujui' : 'tolak')
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui status data ke database',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}