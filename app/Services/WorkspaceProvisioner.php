<?php

namespace App\Services;

use App\Models\Record;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class WorkspaceProvisioner
{
    public function create(string $name, int $owner): Tenant
    {
        return DB::transaction(function () use ($name, $owner) {
            $tenant = Tenant::create(['name' => $name, 'slug' => \Illuminate\Support\Str::slug($name) . '-' . strtolower(\Illuminate\Support\Str::random(6)), 'trial_ends_at' => now()->addDays((int)env('CRM_TRIAL_DAYS', 14))]);
            DB::table('memberships')->insert(['tenant_id' => $tenant->id, 'user_id' => $owner, 'role' => 'owner', 'created_at' => now(), 'updated_at' => now()]);
            RoleService::seedForTenant($tenant->id);
            CurrencyService::seedForTenant($tenant->id, $tenant->currency);
            DB::table('subscriptions')->insert(['tenant_id' => $tenant->id, 'status' => 'trial', 'created_at' => now(), 'updated_at' => now()]);
            $pipeline = (new Record())->useEntity('pipelines');
            $pipeline->fill(['tenant_id' => $tenant->id, 'name' => 'Default sales pipeline', 'entity' => 'deals', 'is_default' => 1, 'created_by' => $owner]);
            $pipeline->save();
            foreach ([['Prospecting', 10, 0, 0], ['Qualification', 25, 0, 0], ['Discovery', 40, 0, 0], ['Proposal sent', 60, 0, 0], ['Negotiation', 80, 0, 0], ['Closed won', 100, 1, 0], ['Closed lost', 0, 0, 1]] as $index => $stage) {
                $row = (new Record())->useEntity('stages');
                $row->fill(['tenant_id' => $tenant->id, 'pipeline_id' => $pipeline->id, 'name' => $stage[0], 'probability' => $stage[1], 'is_won' => $stage[2], 'is_lost' => $stage[3], 'sort_order' => $index * 10, 'created_by' => $owner]);
                $row->save();
            }
            return $tenant;
        });
    }
}
