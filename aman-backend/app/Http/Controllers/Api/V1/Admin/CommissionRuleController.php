<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CommissionRuleRequest;
use App\Http\Resources\CommissionRuleResource;
use App\Models\CommissionRule;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class CommissionRuleController extends Controller
{
    public function __construct(private AuditLogService $auditLog)
    {
    }

    public function index(Request $request)
    {
        $rules = CommissionRule::query()->paginate((int) $request->get('per_page', 20));

        return $this->success(CommissionRuleResource::collection($rules)->response()->getData(true));
    }

    public function store(CommissionRuleRequest $request)
    {
        $rule = CommissionRule::create($request->validated() + ['effective_from' => $request->effective_from ?? now()]);
        $this->auditLog->log($request->user(), 'commission_rule.created', $rule, [], $rule->toArray(), $request->ip());

        return $this->success(new CommissionRuleResource($rule), 'تم إنشاء قاعدة العمولة', 201);
    }

    public function show(int $id)
    {
        return $this->success(new CommissionRuleResource(CommissionRule::findOrFail($id)));
    }

    public function update(CommissionRuleRequest $request, int $id)
    {
        $rule = CommissionRule::findOrFail($id);
        $old = $rule->toArray();
        $rule->update($request->validated());

        $this->auditLog->log($request->user(), 'commission_rule.updated', $rule, $old, $rule->fresh()->toArray(), $request->ip());

        return $this->success(new CommissionRuleResource($rule->fresh()), 'تم تحديث قاعدة العمولة');
    }

    public function destroy(Request $request, int $id)
    {
        $rule = CommissionRule::findOrFail($id);
        $this->auditLog->log($request->user(), 'commission_rule.deleted', $rule, $rule->toArray(), [], $request->ip());
        $rule->delete();

        return $this->success(null, 'تم حذف قاعدة العمولة');
    }
}
