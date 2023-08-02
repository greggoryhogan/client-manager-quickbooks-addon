<?php
use QuickBooksOnline\API\DataService\DataService;
/**
 * Add notice if our plugin isn't active
 * Adapted from https://theaveragedev.com/generating-a-wordpress-plugin-activation-link-url/
 */
function qbtac_core_plugin_check() {
    if(!in_array('client-manager/client-manager.php', apply_filters('active_plugins', get_option('active_plugins')))){ 
        echo '<div class="notice notice-warning">';
            echo '<p>Client Manager is required for the Quickbooks add-on to function. Please install or activate the plugin in order to continue.</p>';
        echo '</div>'; 
    }
    if(in_array('client-manager/client-manager.php', apply_filters('active_plugins', get_option('active_plugins')))){ 
        $access_token = get_option('cm_qb_access_token');
        if($access_token == '') {
            $auth_link = admin_url(sprintf('?%s', http_build_query($_GET)) );
            if(substr($auth_link, -1) == '?') {
                $auth_link .= 'cm-authorize-qb=1';
            } else {
                $auth_link .= '&cm-authorize-qb=1';
            }
            echo '<div class="notice notice-warning">';
                echo '<p>Client Manager needs authorization from Quickbooks to continue. <a href="'.$auth_link.'">Authorize with Quickbooks</a>.</p>';
            echo '</div>'; 
        }
    }
    if(isset($_GET['cma-authorized'])) {
        echo '<div class="notice">';
            echo '<p>Successfully authorized with Quickbooks.</p>';
        echo '</div>'; 
    }

}
add_action( 'admin_notices', 'qbtac_core_plugin_check' );

function set_dataservice() {
    return DataService::Configure(array(
        'auth_mode' => 'oauth2',
        'ClientID' => QB_CLIENT_ID,
        'ClientSecret' =>  QB_CLIENT_SECRET,
        'RedirectURI' => get_bloginfo('url').'/wp-admin/?cm-authorize-qb=2',
        'scope' => 'com.intuit.quickbooks.accounting openid profile email phone address',
        'baseUrl' => "development"
    ));
}
/**
 * QB SDK Config
 */
add_action('admin_init','cma_authorize_qb');
function cma_authorize_qb() {
    $access_token = get_option('cm_qb_access_token');
    if(isset($_GET['cm-authorize-qb']) && $access_token == '') {
        $dataService = set_dataservice();
        $OAuth2LoginHelper = $dataService->getOAuth2LoginHelper();
        // Get the Authorization URL from the SDK
        $authUrl = $OAuth2LoginHelper->getAuthorizationCodeURL();
        if($_GET['cm-authorize-qb'] == 1) {
            wp_redirect($authUrl);
        } else {
            $response = $_SERVER['QUERY_STRING'];

            /* Will result in $api_response being an array of data,
            parsed from the JSON response of the API listed above */
            $parseUrl = parseAuthRedirectUrl($response);
            //Update the OAuth2Token
            $accessToken = $OAuth2LoginHelper->exchangeAuthorizationCodeForToken($parseUrl['code'], $parseUrl['realmId']);
            //$dataService->updateOAuth2Token($accessToken);
            //Store the token
            //$_SESSION['sessionAccessToken'] = $accessToken;
            update_option('cm_qb_access_token',$accessToken);    
            wp_redirect( get_bloginfo('url').'/wp-admin/?cma-authorized=1' );
            exit;
        }
    } else if(isset($_GET['cm-authorize-qb']) && $access_token != '') {
        wp_redirect( get_bloginfo('url').'/wp-admin/?cma-authorized=1' );
    }
}

/**
 * Parse response from QB
 */
function parseAuthRedirectUrl($url) {
    parse_str($url,$qsArray);
    return array(
        'code' => $qsArray['code'],
        'realmId' => $qsArray['realmId']
    );
}

/**
 * Query for invoices paid year to date
 */
function cmaqb_get_invoices_paid_to_date() {
    //SELECT * FROM Invoice WHERE TxnDate > '2011-01-01' AND TxnDate <= CURRENT_DATE
   // https://quickbooks.api.intuit.com/v3/company/<realmId>/query?query=<select_statement>
}

/**
 * Quickbooks Dashboard Widget
 */
add_action('wp_dashboard_setup', 'cm_qb_custom_dashboard_widgets');
function cm_qb_custom_dashboard_widgets() {
    global $wp_meta_boxes;
    wp_add_dashboard_widget('cm_qb_summary_widget', 'Client Manager Quickbooks Summary', 'cm_qb_summary_callback');
}
 
function cm_qb_summary_callback() {
    // Create SDK instance
    $dataService = $dataService = set_dataservice();
    $accessToken = get_option('cm_qb_access_token');
    $dataService->updateOAuth2Token($accessToken);
    $companyInfo = $dataService->getCompanyInfo();
    print_r($companyInfo);
}