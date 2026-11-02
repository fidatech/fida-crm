<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\{User,Record};
use App\Services\WorkspaceProvisioner;
use App\Services\CrmService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
class CrmWorkspaceTest extends TestCase {
 use RefreshDatabase;
 private function tenant(User $user):int{return app(WorkspaceProvisioner::class)->create('Acme Test Co',$user->id)->id;}
 public function test_registration_creates_working_pipeline_and_dashboard():void{
  $response=$this->post('/register',['name'=>'Test Owner','organization'=>'Demo Enterprise','email'=>'owner@example.test','password'=>'SecurePassword2026','password_confirmation'=>'SecurePassword2026']);
  $response->assertRedirect(route('dashboard'));
  $this->assertDatabaseCount('tenants',1);$this->assertDatabaseCount('stages',7);$this->get('/dashboard')->assertOk();$this->get('/pipeline')->assertOk();
 }
 public function test_tenant_cannot_read_another_tenants_contacts():void{
  $owner=User::create(['name'=>'Owner','email'=>'owner@a.test','password'=>'Password12345']);$other=User::create(['name'=>'Other','email'=>'owner@b.test','password'=>'Password12345']);$a=$this->tenant($owner);$b=$this->tenant($other);
  $record=app(CrmService::class)->save('contacts',$a,['first_name'=>'Private','last_name'=>'Customer','email'=>'private@example.test'],null,$owner->id);
  $this->actingAs($other)->withSession(['tenant_id'=>$b])->get('/records/contacts/'.$record->id)->assertNotFound();
  $this->actingAs($other)->withSession(['tenant_id'=>$b])->get('/records/contacts')->assertDontSee('private@example.test');
 }
 public function test_cross_tenant_contact_reference_is_rejected():void{
  $aUser=User::create(['name'=>'Alice','email'=>'alice@test.test','password'=>'Password12345']);$bUser=User::create(['name'=>'Bob','email'=>'bob@test.test','password'=>'Password12345']);$a=$this->tenant($aUser);$b=$this->tenant($bUser);
  $company=app(CrmService::class)->save('companies',$a,['name'=>'Secret Ltd'],null,$aUser->id);
  $this->actingAs($bUser)->withSession(['tenant_id'=>$b])->post('/records/contacts',['first_name'=>'Wrong','last_name'=>'Reference','company_id'=>$company->id])->assertStatus(422);
  $this->assertDatabaseMissing('contacts',['tenant_id'=>$b,'first_name'=>'Wrong']);
 }
 public function test_viewer_cannot_create_records_or_sales_documents():void{
  $owner=User::create(['name'=>'Owner','email'=>'owner@role.test','password'=>'Password12345']);$viewer=User::create(['name'=>'Viewer','email'=>'viewer@role.test','password'=>'Password12345']);$id=$this->tenant($owner);
  DB::table('memberships')->insert(['tenant_id'=>$id,'user_id'=>$viewer->id,'role'=>'viewer','created_at'=>now(),'updated_at'=>now()]);
  $this->actingAs($viewer)->withSession(['tenant_id'=>$id])->post('/records/companies',['name'=>'Unallowed'])->assertForbidden();
  $this->actingAs($viewer)->withSession(['tenant_id'=>$id])->post('/sales/quote',['title'=>'Unallowed'])->assertForbidden();
 }
 public function test_quote_to_invoice_and_partial_payment_are_tenant_scoped():void{
  $user=User::create(['name'=>'Owner','email'=>'owner@sales.test','password'=>'Password12345']);$tenant=$this->tenant($user);$sales=app(\App\Services\SalesService::class);
  $quote=$sales->save($tenant,['kind'=>'quote','title'=>'Annual services','currency'=>'USD','issued_on'=>'2026-10-14','lines'=>[['description'=>'Service','quantity'=>2,'unit_price'=>100,'discount_percent'=>10,'tax_percent'=>5]]],null,$user->id);
  $q=DB::table('sales_documents')->where('id',$quote)->first();$this->assertEquals('189.00',$q->total);
  $sales->status($tenant,$quote,'sent',$user->id);$sales->status($tenant,$quote,'accepted',$user->id);$invoice=$sales->convert($tenant,$quote,$user->id);$sales->status($tenant,$invoice,'sent',$user->id);
  $sales->payment($tenant,$invoice,['amount'=>50,'method'=>'bank','paid_on'=>'2026-10-14'],$user->id);
  $this->assertDatabaseHas('sales_documents',['id'=>$invoice,'tenant_id'=>$tenant,'amount_paid'=>50,'status'=>'partial']);
 }
 public function test_blade_views_render_without_parse_errors():void{
  $user=User::create(['name'=>'Tester','email'=>'owner@render.test','password'=>'Password12345']);$id=$this->tenant($user);
  $this->actingAs($user)->withSession(['tenant_id'=>$id]);
  foreach(['/dashboard','/pipeline','/reports','/records/contacts','/records/contacts/create','/records/deals/create','/records/custom_fields/create','/sales/quote','/sales/quote/create','/workspace/settings','/billing/plans','/assistant'] as $url)$this->get($url)->assertOk();
 }
}
