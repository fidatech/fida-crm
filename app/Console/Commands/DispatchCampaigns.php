<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Http\Controllers\MarketingController;
class DispatchCampaigns extends Command {
 protected $signature='crm:send-campaigns';protected $description='Process tenant-scoped campaign deliveries';
 public function handle(MarketingController $marketing):int{$count=$marketing->processQueue();$this->info('Processed '.$count.' outbound deliveries');return self::SUCCESS;}
}
