<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OwnerApprovalController extends Controller
{
    /**
     * Mengambil daftar pengajuan akun EO berdasarkan status (pending, approved, rejected)
     * Endpoint: GET /api/owner/approval/eo?status=...
     */
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');

        $allowedStatuses = ['pending', 'approved', 'rejected'];
        if (!in_array($status, $allowedStatuses)) {
            return response()->json([
                'success' => false,
                'message' => 'Status pencarian tidak valid'
            ], 400);
        }

        try {
            $eoSubmissions = DB::table('eo')
                ->join('users', 'eo.user_id', '=', 'users.id')
                ->select(
                    'eo.id as id',
                    'eo.user_id',
                    'eo.status',
                    'eo.nama_badan_usaha',
                    'eo.alamat_badan_usaha',
                    'eo.dokumen_badan_usaha',
                    'eo.penanggung_jawab',
                    'eo.ktp_penanggung_jawab',
                    'eo.bank_name',
                    'eo.account_name',
                    'eo.account_number',
                    'eo.logo',
                    'eo.rejected_reason',
                    'eo.created_at',
                    'users.name as user_name',
                    'users.email as user_email'
                )
                ->where('eo.status', $status)
                ->orderBy('eo.created_at', 'desc')
                ->get();

            // Transformasi logo EO ke bentuk Full URL agar frontend bisa merender gambar
            $eoSubmissions = $eoSubmissions->map(function ($eo) {
                if ($eo->logo) {
                    $eo->logo = url($eo->logo);
                }
                if ($eo->dokumen_badan_usaha) {
                    $eo->dokumen_badan_usaha = url($eo->dokumen_badan_usaha);
                }
                if ($eo->ktp_penanggung_jawab) {
                    $eo->ktp_penanggung_jawab = url($eo->ktp_penanggung_jawab);
                }
                return $eo;
            });

            return response()->json([
                'success' => true,
                'message' => "Berhasil memuat data pengajuan EO dengan status $status",
                'data' => $eoSubmissions
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
     * Memproses aksi Approval atau Rejection pendaftaran akun EO
     * Endpoint: POST /api/owner/approval/eo/{id}
     */
    public function processApproval(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:approved,rejected',
            'reason' => 'required_if:status,rejected|string|nullable|max:5000',
        ], [
            'reason.required_if' => 'Alasan penolakan wajib dicantumkan jika menolak data berkas.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi data gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $eo = DB::table('eo')->where('id', $id)->first();

            if (!$eo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data pengajuan EO tidak ditemukan di database'
                ], 404);
            }

            if ($eo->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pengajuan EO ini sudah dieksekusi atau diproses sebelumnya'
                ], 400);
            }

            $updateData = [
                'status' => $request->status,
                'updated_at' => now()
            ];

            if ($request->status === 'rejected') {
                $updateData['rejected_reason'] = $request->reason;
            } else {
                $updateData['rejected_reason'] = null;
            }

            DB::table('eo')->where('id', $id)->update($updateData);

            return response()->json([
                'success' => true,
                'message' => "Pendaftaran akun EO berhasil di-" . ($request->status === 'approved' ? 'setujui' : 'tolak')
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui status data ke database',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mengambil daftar pengajuan Event berdasarkan parameter status enum
     * Endpoint: GET /api/owner/approval/events?status=...
     */
    public function indexEvents(Request $request)
    {
        // 💡 Ambil query parameter status. Jika tidak diset lewat url, default menjadi null (Bukan 'pending')
        $status = $request->query('status');

        // 💡 Validasi hanya dilakukan JIKA parameter status dikirim oleh frontend (tidak kosong)
        if (!empty($status)) {
            $allowedStatuses = ['pending', 'approved', 'rejected', 'cancelled', 'pending_cancel', 'pending_reschedule'];
            if (!in_array($status, $allowedStatuses)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Status pencarian event tidak valid'
                ], 400);
            }
        }

        try {
            $events = DB::table('events')
                ->join('eo', 'events.eo_id', '=', 'eo.id')
                ->select(
                    'events.id',
                    'events.eo_id',
                    'events.title',
                    'events.event_url',
                    'events.description',
                    'events.lineup',
                    'events.organizer',
                    'events.instagram',
                    'events.date',
                    'events.proposed_date',
                    'events.ticket_sale_start',
                    'events.ticket_redeem_start',
                    'events.min_age',
                    'events.location',
                    'events.poster',
                    'events.max_tickets_per_email',
                    'events.rejected_reason',
                    'events.status',
                    'events.reschedule_reason',
                    'events.reschedule_rejected_reason',
                    'events.can_adjust_schedule',
                    'events.created_at',
                    'eo.nama_badan_usaha as eo_name',
                    'eo.logo as eo_logo'
                )
                // 💡 PENTING: Gunakan ->when() agar query WHERE status bersiat kondisional.
                // Jika $status kosong / null, data akan ditarik keseluruhan (Semua Pengajuan).
                ->when(!empty($status), function ($query) use ($status) {
                    return $query->where('events.status', $status);
                })
                ->orderBy('events.created_at', 'desc')
                ->get();

            // Mengubah relative path menjadi Full URL secara dinamis
            $events = $events->map(function ($event) {
                if ($event->poster) {
                    $event->poster = url($event->poster);
                }
                if ($event->eo_logo) {
                    $event->eo_logo = url($event->eo_logo);
                }
                return $event;
            });

            $msgLabel = !empty($status) ? "dengan status $status" : "keseluruhan (Semua)";
            return response()->json([
                'success' => true,
                'message' => "Berhasil memuat data pengajuan event $msgLabel",
                'data' => $events
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server saat memuat data event',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Memproses keputusan Approval, Rejection, Reschedule, atau Pembatalan Event oleh Owner
     * Endpoint: POST /api/owner/approval/events/{id}
     */
    public function processEventApproval(Request $request, $id)
    {
        $event = DB::table('events')->where('id', $id)->first();

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Data pengajuan event tidak ditemukan'
            ], 404);
        }

        $rules = [
            'status' => 'required|in:approved,rejected,cancelled',
        ];

        if ($request->status === 'rejected') {
            $rules['reason'] = 'required|string|max:5000';
        }

        $messages = [
            'status.in' => 'Status keputusan tidak valid.',
            'reason.required' => 'Alasan keputusan/penolakan wajib dicantumkan.'
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi data keputusan event gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $updateData = [
                'updated_at' => now()
            ];

            if ($event->status === 'pending') {
                if ($request->status === 'approved') {
                    $updateData['status'] = 'approved';
                    $updateData['rejected_reason'] = null;
                    $updateData['can_adjust_schedule'] = 1; 
                } else if ($request->status === 'rejected') {
                    $updateData['status'] = 'rejected';
                    $updateData['rejected_reason'] = $request->reason;
                    $updateData['can_adjust_schedule'] = 0; 
                }
            } 
            
            else if ($event->status === 'pending_reschedule') {
                if ($request->status === 'approved') {
                    $updateData['status'] = 'approved';
                    $updateData['date'] = $event->proposed_date; 
                    $updateData['proposed_date'] = null;
                    $updateData['reschedule_reason'] = null;
                    $updateData['reschedule_rejected_reason'] = null; 
                } else if ($request->status === 'rejected') {
                    $updateData['status'] = 'approved'; 
                    $updateData['proposed_date'] = null; 
                    $updateData['reschedule_rejected_reason'] = $request->reason; 
                }
            } 
            
            else if ($event->status === 'pending_cancel') {
                if ($request->status === 'approved' || $request->status === 'cancelled') {
                    $updateData['status'] = 'cancelled';
                    $updateData['can_adjust_schedule'] = 0; 
                    $updateData['rejected_reason'] = $request->reason; 
                } else if ($request->status === 'rejected') {
                    $updateData['status'] = 'approved';
                    $updateData['rejected_reason'] = $request->reason; 
                }
            } 
            
            else {
                return response()->json([
                    'success' => false,
                    'message' => 'Event berada pada status yang tidak memerlukan aksi approval saat ini'
                ], 400);
            }

            DB::table('events')->where('id', $id)->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Keputusan approval event berhasil disimpan',
                'current_status' => $updateData['status']
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses keputusan event ke database',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}