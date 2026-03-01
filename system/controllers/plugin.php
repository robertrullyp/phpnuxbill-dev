<?php
/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

$plugin_action = isset($routes[1]) ? strtolower(trim((string) $routes[1])) : '';
$plugin_sub_route = isset($routes[2]) ? strtolower(trim((string) $routes[2])) : '';

// Allow public chatbot runtime endpoints while keeping all other plugin routes protected.
$ai_chatbot_public_routes = ['bootstrap', 'status', 'proxy', 'stream'];
$is_ai_chatbot_public_endpoint = $plugin_action === 'ai_chatbot_settings'
    && in_array($plugin_sub_route, $ai_chatbot_public_routes, true);

if (!$is_ai_chatbot_public_endpoint && !_admin(false) && !_auth(false)) {
    r2(getUrl('login'));
}

if(function_exists($routes[1])){
    call_user_func($routes[1]);
}else{
    r2(getUrl('dashboard'), 'e', 'Function not found');
}
