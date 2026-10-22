<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The farm no longer reads from billing.klokads.com: its own books are the only source. The
 * copied rows, the mappings and the saved connection go; the entries themselves stay.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('imported_records');
        Schema::dropIfExists('import_mappings');
        DB::table('settings')->where('key', 'like', 'crater.%')->delete();

        foreach (DB::table('roles')->get(['id', 'abilities']) as $role) {
            $abilities = array_values(array_diff(json_decode($role->abilities, true) ?: [], ['manage-import']));
            DB::table('roles')->where('id', $role->id)->update(['abilities' => json_encode($abilities)]);
        }
    }

    public function down(): void
    {
        // The import is gone for good; nothing to bring back.
    }
};
