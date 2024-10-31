<?php
/*
Plugin Name: PurgeBox
Plugin URI: https://ja.wordpress.org/plugins/purgebox/
Description: REDBOX CDN Purge Plugin.
Author: REDBOX
Version: 1.8
Author URI: https://www.redbox.ne.jp
License: GPL3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

*/
define( 'PURGEBOX_PLUGIN_FILE', __FILE__ );

// Import classes
require_once dirname( __FILE__ ). '/classes/class-purgebox-api.php';
require_once dirname( __FILE__ ). '/classes/class-purgebox-purge.php';
require_once dirname( __FILE__ ). '/classes/class-purgebox-admin.php';
// Setup Purging
new PurgeBox_Purge();

// Setup admin
if( is_admin() ) {
	new PurgeBox_Admin();
}


// プラグインがロードされるたびにアップデートをチェックする関数
function purgebox_check_and_update_version() {
    if (!function_exists('get_plugin_data')) {
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
    }
    $plugin_data = get_plugin_data(PURGEBOX_PLUGIN_FILE);
    $current_version = $plugin_data['Version'];
    $saved_version = get_option('purgebox_plugin_version');

    // バージョンが古い場合はアップデート処理を実行
    if (version_compare($saved_version, $current_version, '<')) {
        // アップデート処理をここに実装
        purgebox_run_update_procedures();

        // アップデート処理の後、新しいバージョンを保存
        update_option('purgebox_plugin_version', $current_version);
    }
}

add_action('plugins_loaded', 'purgebox_check_and_update_version');


// アップデート時に実行する具体的な処理
function purgebox_run_update_procedures() {
    // ここにアップデートに必要なコードを書く
    add_purgebox_admin_role();
    error_log('plugin update detected. added to administrator.');
}



// プラグイン有効化時に実行される関数
function add_purgebox_admin_role() {

    // Administratorロールに 'purge_all' ケーパビリティを追加
    $role = get_role('administrator');
    if ($role) {
        $role->add_cap('purge_all');
        error_log('purge_all capability added to administrator.');
    }else{
        error_log('Failed to get the administrator role.');

    }
}

// プラグイン有効化時に実行される関数を登録
register_activation_hook(__FILE__, 'add_purgebox_admin_role');
