<?php
namespace Tests\Feature;
use App\Models\User;
use App\Services\{CrmService,CurrencyService,RoleService,WorkspaceProvisioner};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB,Http};
use Tests\TestCase;
class CrmEnrichmentTest extends TestCase {
 use RefreshDatabase;
 private function workspace(string $prefix='Owner'):array{$user=User::create(['name'=>$prefix,'email'=>strtolower($prefix).'@example.test','password'=>'Strong-Pass-123!']);$tenant=app(WorkspaceProvisioner::class)->create($prefix.' LLC',$user->id);return [$user,$tenant->id];}
 public function test_new_workspaces_have_tenant_roles_and_base_currency():void {
  [$user,$tenant]=$this->workspace();$this->assertDatabaseHas('tenant_roles',['tenant_id'=>$tenant,'slug'=>'owner']);$this->assertDatabaseHas('tenant_currencies',['tenant_id'=>$tenant,'code'=>'USD','is_base'=>true]);
  $this->assertDatabaseHas('tenants',['id'=>$tenant,'currency'=>'USD','timezone'=>'UTC','status'=>'trial']);
  $this->assertSame('USD',CurrencyService::base($tenant));
  $this->actingAs($user)->withSession(['tenant_id'=>$tenant])->get('/workspace/roles')->assertOk();
  $this->get('/workspace/currencies')->assertOk();$this->get('/prospecting')->assertOk();
 }
 public function test_currency_seeding_handles_missing_initial_currency():void {
  $tenant=\App\Models\Tenant::create(['name'=>'Null-currency regression','slug'=>'null-currency-regression']);
  CurrencyService::seedForTenant($tenant->id,null);
  $this->assertDatabaseHas('tenant_currencies',['tenant_id'=>$tenant->id,'code'=>'USD','is_base'=>true,'rate_to_base'=>1]);
  $this->assertDatabaseHas('currency_rate_history',['tenant_id'=>$tenant->id,'code'=>'USD','base_code'=>'USD','source'=>'initial']);
 }
 public function test_viewer_cannot_modify_records_even_through_api():void {
  [$owner,$tenant]=$this->workspace();$viewer=User::create(['name'=>'Viewer','email'=>'viewer@example.test','password'=>'Strong-Pass-123!']);$id=DB::table('tenant_roles')->where('tenant_id',$tenant)->where('slug','viewer')->value('id');DB::table('memberships')->insert(['tenant_id'=>$tenant,'user_id'=>$viewer->id,'role'=>'viewer','role_id'=>$id,'created_at'=>now(),'updated_at'=>now()]);
  $this->actingAs($viewer)->withSession(['tenant_id'=>$tenant])->get('/records/contacts')->assertOk();
  $this->post('/records/contacts',['first_name'=>'Unauthorized'])->assertForbidden();
  $this->post('/workspace/roles',['name'=>'Escalated','permissions'=>['roles.manage']])->assertForbidden();
  $token='fida_'.str_repeat('a',64);DB::table('api_keys')->insert(['tenant_id'=>$tenant,'user_id'=>$viewer->id,'name'=>'Viewer integration','token_hash'=>hash('sha256',$token),'abilities'=>'["read","write"]','created_at'=>now(),'updated_at'=>now()]);
  $this->withToken($token)->postJson('/api/v1/contacts',['first_name'=>'API forbidden'])->assertForbidden();
 }
 public function test_custom_permissions_are_enforced_at_create_and_delete():void {
  [$owner,$tenant]=$this->workspace();$agent=User::create(['name'=>'Agent','email'=>'agent@example.test','password'=>'Strong-Pass-123!']);
  $this->actingAs($owner)->withSession(['tenant_id'=>$tenant])->post('/workspace/roles',['name'=>'Research reader','permissions'=>['records.companies.view','records.leads.view']])->assertRedirect();
  $role=DB::table('tenant_roles')->where('tenant_id',$tenant)->where('slug','research-reader')->first();$this->assertNotNull($role);
  DB::table('memberships')->insert(['tenant_id'=>$tenant,'user_id'=>$agent->id,'role'=>'research-reader','role_id'=>$role->id,'created_at'=>now(),'updated_at'=>now()]);
  $this->actingAs($agent)->withSession(['tenant_id'=>$tenant])->get('/records/companies')->assertOk();
  $this->post('/records/companies',['name'=>'Denied'])->assertForbidden();
  $this->get('/records/deals')->assertForbidden();
 }
 public function test_manual_rates_are_snapshotted_and_other_tenants_cannot_edit_them():void {
  [$owner,$tenant]=$this->workspace();
  $this->actingAs($owner)->withSession(['tenant_id'=>$tenant])->post('/workspace/currencies',['code'=>'EUR','rate_to_base'=>1.10000000])->assertRedirect();
  $deal=app(CrmService::class)->save('deals',$tenant,['title'=>'European contract','amount'=>120,'currency'=>'EUR','status'=>'open'],null,$owner->id);
  $this->assertSame('132.00',(string)$deal->amount_base);
  $this->put('/workspace/currencies/EUR',['rate_to_base'=>1.20000000,'active'=>1])->assertRedirect();
  $this->assertDatabaseHas('deals',['tenant_id'=>$tenant,'id'=>$deal->id,'amount_base'=>132]);
  $this->assertDatabaseCount('currency_rate_history',3);
  [$other,$otherTenant]=$this->workspace('Other');
  $this->actingAs($other)->withSession(['tenant_id'=>$otherTenant])->put('/workspace/currencies/EUR',['rate_to_base'=>99,'active'=>1])->assertNotFound();
 }
 public function test_provider_search_stores_real_candidates_and_requires_approval():void {
  [$owner,$tenant]=$this->workspace();$this->actingAs($owner)->withSession(['tenant_id'=>$tenant]);
  $this->post('/prospecting/credentials',['api_key'=>'test-personal-apollo-key-123456'])->assertRedirect();
  $this->post('/prospecting/searches',['name'=>'Austin tech companies','query'=>'software','location'=>'Texas','frequency'=>'manual'])->assertRedirect();
  $search=(int)DB::table('discovery_searches')->where('tenant_id',$tenant)->value('id');
  Http::fake(['api.apollo.io/*'=>Http::response(['organizations'=>[['id'=>'apollo-company-123','name'=>'Verifiable Example Inc','primary_domain'=>'verifiable.example','industry'=>'information technology & services','city'=>'Austin','country'=>'United States','estimated_num_employees'=>100]]],200)]);
  $this->post('/prospecting/searches/'.$search.'/run')->assertRedirect();
  $candidate=DB::table('discovery_candidates')->where('tenant_id',$tenant)->first();$this->assertNotNull($candidate);$this->assertSame('pending',$candidate->status);
  $this->post('/prospecting/candidates/'.$candidate->id.'/review',['decision'=>'approve'])->assertRedirect();
  $this->assertDatabaseHas('companies',['tenant_id'=>$tenant,'domain'=>'verifiable.example']);
  $this->assertDatabaseHas('leads',['tenant_id'=>$tenant,'source'=>'Apollo organization search']);
  $this->post('/prospecting/candidates/'.$candidate->id.'/review',['decision'=>'approve'])->assertStatus(422);
 }
}
