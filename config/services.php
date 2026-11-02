<?php
return ['apollo'=>['max_results'=>(int)env('APOLLO_DISCOVERY_PAGE_SIZE',20)],'stripe'=>['secret'=>env('STRIPE_SECRET'),'webhook_secret'=>env('STRIPE_WEBHOOK_SECRET')],'crm_ai'=>['key'=>env('CRM_AI_API_KEY'),'model'=>env('CRM_AI_MODEL','gpt-4.1-mini')]];
