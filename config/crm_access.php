<?php
$entityPermissions = [];
foreach ((require __DIR__.'/crm.php')['entities'] as $slug => $definition) {
    $entityPermissions[$slug] = array_combine(['view','create','update','delete','import','export'],array_map(fn($a) => 'records.'.$slug.'.'.$a,['view','create','update','delete','import','export']));
}
$groups = [
    'Core CRM' => ['dashboard.view'=>'Dashboard','search.view'=>'Global search','reports.view'=>'Reports and analytics','assistant.use'=>'AI assistant'],
    'Sales operations'=>['pipeline.view'=>'Sales board','pipeline.move'=>'Move deals between stages','sales.view'=>'Quotations and invoices','sales.create'=>'Create sales documents','sales.update'=>'Edit draft documents','sales.transition'=>'Send and approve documents','sales.payment'=>'Record invoice payments','leads.convert'=>'Convert qualified leads'],
    'Marketing & service'=>['campaigns.launch'=>'Launch campaigns','tickets.reply'=>'Reply to service tickets','attachments.manage'=>'Upload attachments'],
    'Workspace administration'=>['settings.view'=>'View workspace settings','settings.manage'=>'Edit workspace settings','team.manage'=>'Invite members and manage team','roles.view'=>'View team roles','roles.manage'=>'Manage roles and permissions','currencies.view'=>'View currencies and rate history','currencies.manage'=>'Manage currencies and rates','integrations.manage'=>'Manage integration credentials','discovery.view'=>'Review prospects','discovery.search'=>'Search for prospects','discovery.import'=>'Approve and import prospects','billing.view'=>'View subscription','billing.manage'=>'Manage subscription','api.manage'=>'Manage API keys'],
];
foreach ($entityPermissions as $entity => $actions) $groups['CRM records: '.ucwords(str_replace('_',' ',$entity))] = array_combine(array_values($actions),array_map(fn($a)=>ucfirst($a).' '.str_replace('_',' ',$entity),array_keys($actions)));
$all=[];foreach($groups as $group=>$items)$all=array_merge($all,$items);
$viewer=array_keys(array_filter($all, fn($name,$key)=>str_ends_with($key,'.view') || str_ends_with($key,'.export') || in_array($key,['dashboard.view','search.view','reports.view'],true),ARRAY_FILTER_USE_BOTH));
$roleTemplates=[
    'owner'=>array_keys($all),
    'admin'=>array_values(array_diff(array_keys($all),['roles.manage','billing.manage'])),
    'manager'=>array_values(array_filter(array_keys($all),fn($p)=>!str_starts_with($p,'roles.') && !str_starts_with($p,'billing.') && !str_starts_with($p,'integrations.') && !str_starts_with($p,'api.'))),
    'sales'=>array_values(array_filter(array_keys($all),fn($p)=>preg_match('/^(records\.(contacts|companies|leads|deals|tasks|meetings|products|pipelines|stages)\.|pipeline\.|sales\.|leads\.|dashboard\.|search\.|reports\.|assistant\.|discovery\.|attachments\.)/', $p) && !str_ends_with($p,'.delete'))),
    'marketing'=>array_values(array_filter(array_keys($all),fn($p)=>preg_match('/^(records\.(contacts|companies|leads|campaigns|email_templates|segments|web_forms)\.|campaigns\.|dashboard\.|search\.|reports\.|assistant\.|discovery\.)/', $p)&&!str_ends_with($p,'.delete'))),
    'support'=>array_values(array_filter(array_keys($all),fn($p)=>preg_match('/^(records\.(contacts|companies|tickets|knowledge_articles|tasks)\.|tickets\.|dashboard\.|search\.|reports\.|attachments\.)/', $p)&&!str_ends_with($p,'.delete'))),
    'analyst'=>$viewer,
    'member'=>array_values(array_filter(array_keys($all),fn($p)=>preg_match('/^(records\.|dashboard\.|search\.|reports\.|sales\.view|pipeline\.view|tickets\.reply|attachments\.manage)/',$p)&&!str_ends_with($p,'.delete'))),
    'viewer'=>array_values(array_filter($viewer,fn($p)=>!str_ends_with($p,'.export'))),
];
return ['groups'=>$groups,'permissions'=>$all,'templates'=>$roleTemplates];
