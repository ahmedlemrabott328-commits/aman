<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectCaptainRequest;
use App\Http\Resources\AdminCaptainResource;
use App\Models\Captain;
use App\Repositories\Contracts\CaptainRepositoryInterface;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class CaptainController extends Controller
{
    public function __construct(
        private CaptainRepositoryInterface $captainRepository,
        private AuditLogService $auditLog,
        private NotificationService $notifications,
    ) {
    }

    public function index(Request $request)
    {
        $captains = $this->captainRepository->paginate(
            perPage: (int) $request->get('per_page', 20),
            filters: $request->only(['approval_status', 'city_id', 'is_online', 'search']),
        );

        return $this->success(AdminCaptainResource::collection($captains)->response()->getData(true));
    }

    /** طلبات الاعتماد المعلّقة — القسم 5: "اعتماد الحساب من قبل الإدارة قبل التفعيل" */
    public function pending(Request $request)
    {
        $captains = $this->captainRepository->pendingApproval((int) $request->get('per_page', 20));

        return $this->success(AdminCaptainResource::collection($captains)->response()->getData(true));
    }

    public function show(int $id)
    {
        $captain = $this->captainRepository->findOrFail($id);
        $captain->load(['city', 'documents', 'vehicles.vehicleType', 'services']);

        return $this->success(new AdminCaptainResource($captain));
    }

    public function approve(Request $request, int $id)
    {
        $captain = $this->captainRepository->findOrFail($id);

        if ($captain->documents()->where('status', '!=', 'approved')->exists()) {
            return $this->error('all_documents_must_be_approved_first', 422);
        }

        $old = $captain->only('approval_status');

        $captain->update([
            'approval_status' => Captain::STATUS_APPROVED,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        $this->auditLog->log($request->user(), 'captain.approved', $captain, $old, $captain->only('approval_status'), $request->ip());

        $this->notifications->notifyCaptain(
            $captain, 'تم اعتماد حسابك', 'تهانينا! تم اعتماد حسابك، يمكنك الآن استقبال الرحلات.',
            'captain_approved',
        );

        return $this->success(new AdminCaptainResource($captain->fresh()), 'تم اعتماد الكابتن');
    }

    public function reject(RejectCaptainRequest $request, int $id)
    {
        $captain = $this->captainRepository->findOrFail($id);
        $old = $captain->only('approval_status');

        $captain->update([
            'approval_status' => Captain::STATUS_REJECTED,
            'rejection_reason' => $request->reason,
        ]);

        $this->auditLog->log($request->user(), 'captain.rejected', $captain, $old, $captain->only('approval_status', 'rejection_reason'), $request->ip());

        $this->notifications->notifyCaptain(
            $captain, 'تعذّر اعتماد حسابك', $request->reason, 'captain_rejected',
        );

        return $this->success(new AdminCaptainResource($captain->fresh()), 'تم رفض طلب الكابتن');
    }

    public function suspend(RejectCaptainRequest $request, int $id)
    {
        $captain = $this->captainRepository->findOrFail($id);
        $old = $captain->only('approval_status', 'is_online');

        $captain->update([
            'approval_status' => Captain::STATUS_SUSPENDED,
            'is_online' => false,
            'rejection_reason' => $request->reason,
        ]);

        $this->auditLog->log($request->user(), 'captain.suspended', $captain, $old, $captain->only('approval_status'), $request->ip());

        return $this->success(new AdminCaptainResource($captain->fresh()), 'تم إيقاف حساب الكابتن');
    }

    /** مراجعة وثيقة كابتن (موافقة/رفض) — يُستخدم من شاشة تفاصيل الكابتن */
    public function reviewDocument(Request $request, int $captainId, int $documentId)
    {
        $data = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'rejection_reason' => ['required_if:status,rejected', 'nullable', 'string', 'max:255'],
        ]);

        $captain = $this->captainRepository->findOrFail($captainId);
        $document = $captain->documents()->findOrFail($documentId);

        $document->update([
            'status' => $data['status'],
            'rejection_reason' => $data['rejection_reason'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $this->auditLog->log($request->user(), 'captain_document.reviewed', $document, [], $data, $request->ip());

        return $this->success(new \App\Http\Resources\CaptainDocumentResource($document));
    }
}
