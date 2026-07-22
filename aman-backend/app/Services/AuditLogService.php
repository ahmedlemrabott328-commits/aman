<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Admin;
use Illuminate\Database\Eloquent\Model;

/** خدمة مركزية لتسجيل كل عملية إدارية (يطلبها القسم 15: سجل العمليات) */
class AuditLogService
{
    public function log(Admin $admin, string $action, Model $entity, array $old = [], array $new = [], ?string $ip = null): void
    {
        AuditLog::create([
            'actor_type' => 'admin',
            'actor_id' => $admin->id,
            'action' => $action,
            'entity_type' => class_basename($entity),
            'entity_id' => $entity->getKey(),
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => $ip,
        ]);
    }
}
