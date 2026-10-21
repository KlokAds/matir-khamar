<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Writes an audit row for every create, update and delete, so the owner can
 * always see who changed what and from where.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (Model $model) {
            AuditLog::record('created', $model, null, $model->auditValues($model->getAttributes()));
        });

        static::updated(function (Model $model) {
            $changed = $model->getChanges();
            unset($changed['updated_at']);

            if ($changed === []) {
                return;
            }

            $old = array_intersect_key($model->getOriginal(), $changed);
            AuditLog::record('updated', $model, $model->auditValues($old), $model->auditValues($changed));
        });

        static::deleted(function (Model $model) {
            AuditLog::record('deleted', $model, $model->auditValues($model->getAttributes()), null);
        });
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function auditValues(array $values): array
    {
        $hidden = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];

        return array_diff_key($values, array_flip($hidden));
    }
}
