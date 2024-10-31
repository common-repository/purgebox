<?php
/**
 * Uninstall plugin.
 */
if( !defined( 'ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') ) {
    exit();
}
delete_option('purgebox_version');
delete_option('purgebox_api_key');
delete_option('purgebox_group');

function purgebox_remove_custom_capability() {
    global $wp_roles;
    if (!isset($wp_roles)) {
        $wp_roles = new WP_Roles();
    }
    foreach ($wp_roles->role_objects as $role) {
        if ($role->has_cap('purge_all')) {
            $role->remove_cap('purge_all');
        }
    }
}

purgebox_remove_custom_capability();