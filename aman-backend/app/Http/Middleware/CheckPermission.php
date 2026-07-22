<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** تطبيق RBAC: يتحقق أن الأدمن الحالي يملك الصلاحية المطلوبة قبل تنفيذ أي إجراء إداري */
class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        $admin = $request->user();

        if (! $admin || ! $admin->hasPermission($permission)) {
            return response()->json([
                'success' => false,
                'message' => 'ليست لديك صلاحية للقيام بهذا الإجراء',
            ], 403);
        }

        return $next($request);
    }
}
