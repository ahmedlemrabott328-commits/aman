<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PricingRuleRequest;
use App\Http\Resources\PricingRuleResource;
use App\Models\PricingRule;
use App\Repositories\Contracts\PricingRuleRepositoryInterface;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

/** إدارة قواعد التسعير — القسم 8: "قابل للتعديل بالكامل من لوحة الإدارة" */
class PricingController extends Controller
{
    public function __construct(
        private PricingRuleRepositoryInterface $pricingRuleRepository,
        private AuditLogService $auditLog,
    ) {
    }

    public function index(Request $request)
    {
        $rules = $this->pricingRuleRepository->paginate(
            perPage: (int) $request->get('per_page', 20),
        );

        return $this->success(PricingRuleResource::collection($rules)->response()->getData(true));
    }

    public function store(PricingRuleRequest $request)
    {
        $rule = PricingRule::create($request->validated() + [
            'currency' => $request->currency ?? 'MRU',
            'created_by' => $request->user()->id,
            'effective_from' => $request->effective_from ?? now(),
        ]);

        $this->auditLog->log($request->user(), 'pricing_rule.created', $rule, [], $rule->toArray(), $request->ip());

        return $this->success(new PricingRuleResource($rule), 'تم إنشاء قاعدة التسعير', 201);
    }

    public function show(int $id)
    {
        return $this->success(new PricingRuleResource($this->pricingRuleRepository->findOrFail($id)));
    }

    public function update(PricingRuleRequest $request, int $id)
    {
        $rule = $this->pricingRuleRepository->findOrFail($id);
        $old = $rule->toArray();

        $rule->update($request->validated());

        $this->auditLog->log($request->user(), 'pricing_rule.updated', $rule, $old, $rule->fresh()->toArray(), $request->ip());

        return $this->success(new PricingRuleResource($rule->fresh()), 'تم تحديث قاعدة التسعير');
    }

    public function destroy(Request $request, int $id)
    {
        $rule = $this->pricingRuleRepository->findOrFail($id);
        $this->auditLog->log($request->user(), 'pricing_rule.deleted', $rule, $rule->toArray(), [], $request->ip());
        $rule->delete();

        return $this->success(null, 'تم حذف قاعدة التسعير');
    }
}
