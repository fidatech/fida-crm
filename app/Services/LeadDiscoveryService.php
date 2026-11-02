<?php
namespace App\Services;
use Illuminate\Support\Facades\{Crypt,DB,Http};
use Illuminate\Validation\ValidationException;
class LeadDiscoveryService {
 public function key(int $tenant):?string {
  $value=DB::table('tenant_integrations')->where('tenant_id',$tenant)->where('provider','apollo')->value('encrypted_key');
  return $value?Crypt::decryptString($value):null;
 }
 public function search(int $tenant,int $searchId,?int $actor):int {
  $search=DB::table('discovery_searches')->where('tenant_id',$tenant)->where('id',$searchId)->first();abort_unless($search,404);
  $key=$this->key($tenant);if(!$key)throw ValidationException::withMessages(['provider'=>'Connect your Apollo API key under Prospect discovery settings.']);
  $run=DB::table('discovery_runs')->insertGetId(['tenant_id'=>$tenant,'search_id'=>$searchId,'provider'=>'apollo','status'=>'running','created_by'=>$actor,'created_at'=>now(),'updated_at'=>now()]);
  try {
   // Apollo's documented Organization Search API; limit one small page per execution to control credit spend.
   $params=['page'=>1,'per_page'=>min(25,(int)config('services.apollo.max_results',20))];
   if($search->query)$params['q_organization_keyword_tags']=[$search->query];
   if($search->industry)$params['organization_industries']=[strtolower($search->industry)];
   if($search->location)$params['organization_locations']=[$search->location];
   if($search->headcount)$params['organization_num_employees_ranges']=[$search->headcount];
   $response=Http::withHeaders(['x-api-key'=>$key,'Accept'=>'application/json'])->acceptJson()->withQueryParameters($params)->timeout(22)->post('https://api.apollo.io/api/v1/mixed_companies/search');
   if(!$response->successful())throw new \RuntimeException('Apollo search returned HTTP '.$response->status().'. Check API plan, permissions or rate limit.');
   $companies=$response->json('organizations',[]);if(!is_array($companies))throw new \RuntimeException('Unexpected Apollo response format.');
   $count=0;
   foreach(array_slice($companies,0,25) as $c){
    if(!is_array($c)||empty($c['id'])||empty($c['name']))continue;
    $domain=strtolower(trim((string)($c['primary_domain']??$c['website_url']??'')));if($domain && str_contains($domain,'://'))$domain=(string)parse_url($domain,PHP_URL_HOST);$domain=preg_replace('/^www\./','',$domain);
    $data=['tenant_id'=>$tenant,'run_id'=>$run,'provider'=>'apollo','external_id'=>substr((string)$c['id'],0,150),'name'=>mb_substr((string)$c['name'],0,255),'domain'=>$domain?mb_substr($domain,0,255):null,'industry'=>mb_substr((string)($c['industry']??''),0,255)?:null,'city'=>mb_substr((string)($c['city']??''),0,120)?:null,'country'=>mb_substr((string)($c['country']??''),0,120)?:null,'employees'=>is_numeric($c['estimated_num_employees']??null)?max(0,(int)$c['estimated_num_employees']):null,'status'=>'pending','fit_score'=>$this->score($c,$search),'source_data'=>json_encode(['apollo_id'=>$c['id'],'website_url'=>$c['website_url']??null,'linkedin_url'=>$c['linkedin_url']??null],JSON_INVALID_UTF8_SUBSTITUTE),'created_at'=>now(),'updated_at'=>now()];
    // Rescanning the same company must never reopen a dismissed or approved candidate.
    $exists=DB::table('discovery_candidates')->where('tenant_id',$tenant)->where('provider','apollo')->where('external_id',$data['external_id'])->exists();
    if(!$exists){try{DB::table('discovery_candidates')->insert($data);$count++;}catch(\Illuminate\Database\UniqueConstraintViolationException $e){ /* concurrent run won */ }}
   }
   DB::table('discovery_runs')->where('id',$run)->where('tenant_id',$tenant)->update(['status'=>'completed','result_count'=>$count,'updated_at'=>now()]);
   DB::table('discovery_searches')->where('id',$searchId)->where('tenant_id',$tenant)->update(['last_run_at'=>now(),'updated_at'=>now()]);return $count;
  }catch(\Throwable $e){DB::table('discovery_runs')->where('id',$run)->where('tenant_id',$tenant)->update(['status'=>'failed','error'=>mb_substr($e->getMessage(),0,400),'updated_at'=>now()]);throw $e;}
 }
 private function score(array $company,object $search):int {
  // Explainable fit estimate, not an AI-generated conversion prediction.
  $score=25;
  if($search->industry && strcasecmp((string)($company['industry']??''),$search->industry)===0)$score+=30;
  if($search->location && stripos(json_encode([$company['city']??'', $company['state']??'', $company['country']??'']),$search->location)!==false)$score+=20;
  if(!empty($company['primary_domain']))$score+=10;
  if(!empty($company['estimated_num_employees']))$score+=10;
  return min(95,$score);
 }
}
