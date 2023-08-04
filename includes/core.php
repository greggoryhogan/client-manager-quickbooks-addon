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
        $access_token = get_option('cm_qb_refresh_token');
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
        'RedirectURI' => trailingslashit(get_bloginfo('url')).'wp-admin/?cm-authorize-qb=2',
        'scope' => 'com.intuit.quickbooks.accounting openid profile email phone address', //full scope: 'scope' => 'com.intuit.quickbooks.accounting com.intuit.quickbooks.payment openid',
        'baseUrl' => "https://quickbooks.api.intuit.com/"
    ));
}
/**
 * QB SDK Config
 */
add_action('admin_init','cma_authorize_qb');
function cma_authorize_qb() {
    if(isset($_GET['cma-action'])) {
        $action = $_GET['cma-action'];
        if($action == 'disconnect') {
            update_option('cm_qb_refresh_token','');
        }
    }
    $refresh_token = get_option('cm_qb_refresh_token');
    if(isset($_GET['cm-authorize-qb']) && $refresh_token == '') {
        $dataService = set_dataservice();
        $OAuth2LoginHelper = $dataService->getOAuth2LoginHelper();
        // Get the Authorization URL from the SDK
        $authUrl = $OAuth2LoginHelper->getAuthorizationCodeURL();
        if($_GET['cm-authorize-qb'] == 1) {
            wp_redirect($authUrl);
        } else {
            $response = $_SERVER['QUERY_STRING'];
            $parseUrl = parseAuthRedirectUrl($response);
            $access_token = $OAuth2LoginHelper->exchangeAuthorizationCodeForToken($parseUrl['code'], $parseUrl['realmId']);
            $dataService->updateOAuth2Token($access_token);
            $refreshTokenValue = $access_token->getRefreshToken();
            $refreshTokenExpiry = $access_token->getRefreshTokenExpiresAt();
            $access_tokenValue = $access_token->getAccessToken();
            set_transient( 'cm_qb_access_token',$access_token, HOUR_IN_SECONDS);
            update_option('cm_qb_refresh_token',$refreshTokenValue);    
            update_option('cm_qb_realmId',$parseUrl['realmId']);    
            wp_redirect( get_bloginfo('url').'/wp-admin/?cma-authorized=1' );
            exit;
        }
    } else if(isset($_GET['cm-authorize-qb']) && $refresh_token != '') {
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
 * Quickbooks Dashboard Widget
 */
add_action('wp_dashboard_setup', 'cm_qb_custom_dashboard_widgets');
function cm_qb_custom_dashboard_widgets() {
    global $wp_meta_boxes;
    wp_add_dashboard_widget('cm_qb_summary_widget', 'Client Manager Quickbooks Payments Summary', 'cm_qb_summary_callback');
}
 
function cm_qb_summary_callback() {
    $refresh_token = get_option('cm_qb_refresh_token');
    if($refresh_token != '') {
        $summary_transient = get_transient('cma_qb_db_widget');
        if($summary_transient === false) {
            ob_start();
            $dataService = set_dataservice();
            $access_token_transient = get_transient( 'cm_qb_access_token');
            //$access_token_transient = FALSE;
            if($access_token_transient === FALSE) {
                $OAuth2LoginHelper = $dataService->getOAuth2LoginHelper();
                $access_token = $OAuth2LoginHelper->refreshAccessTokenWithRefreshToken($refresh_token);
                $access_token->setRealmID(get_option('cm_qb_realmId'));
                $refreshTokenValue = $access_token->getRefreshToken();
                set_transient( 'cm_qb_access_token', maybe_serialize($access_token), HOUR_IN_SECONDS);
                update_option('cm_qb_refresh_token',$refreshTokenValue);   
            } else {
                $access_token = maybe_unserialize($access_token_transient);
            }
            $dataService->updateOAuth2Token($access_token);
            $year = date('Y-01-01');
            $query = "SELECT * FROM Payment WHERE TxnDate >= '$year' AND TxnDate <= CURRENT_DATE";
            $payments = $dataService->Query($query);
            $annual_total = 0;
            if(is_array($payments)) {
                if(!empty($payments)) {
                    foreach($payments as $payment) {
                        $total = $payment->TotalAmt;
                        $annual_total += $total;
                    }
                }
                echo '<div class="client-summary-widget">';
                    echo '<div>Payments YTD ('.count($payments).')</div><div style="font-weight:bold;">$'.number_format($annual_total,2).'</div>';
                echo '</div>';
            } else {
                echo '<p>No payments in '.date('Y').'</p>';
            }
            
            //Previous Year
            $previous_year = (int)date('Y') - 1;
            $year_end = date("$previous_year-12-31");
            $year = date("$previous_year-01-01");
            $query = "SELECT * FROM Payment WHERE TxnDate >= '$year' AND TxnDate <= '$year_end'";
            $payments = $dataService->Query($query);
            $annual_total = 0;
            if(is_array($payments)) {
                if(!empty($payments)) {
                    foreach($payments as $payment) {
                        $total = $payment->TotalAmt;
                        $annual_total += $total;
                    }
                }
                echo '<div class="client-summary-widget">';
                    echo '<div>'.$previous_year.' ('.count($payments).')</div><div>$'.number_format($annual_total,2).'</div>';
                echo '</div>';
            } else {
                echo '<p>No payments in '.$previous_year.'</p>';
            }

            //Previous Year
            $previous_year = $previous_year - 1;
            $year_end = date("$previous_year-12-31");
            $year = date("$previous_year-01-01");
            $query = "SELECT * FROM Payment WHERE TxnDate >= '$year' AND TxnDate <= '$year_end'";
            $payments = $dataService->Query($query);
            $annual_total = 0;
            if(is_array($payments)) {
                if(!empty($payments)) {
                    foreach($payments as $payment) {
                        $total = $payment->TotalAmt;
                        $annual_total += $total;
                    }
                }
                echo '<div class="client-summary-widget">';
                    echo '<div>'.$previous_year.' ('.count($payments).')</div><div>$'.number_format($annual_total,2).'</div>';
                echo '</div>';
            } else {
                echo '<p>No payments in '.$previous_year.'</p>';
            }
            $summary_transient = ob_get_clean();
            set_transient( 'cma_qb_db_widget', $summary_transient, HOUR_IN_SECONDS );
        }
        echo $summary_transient;
    } else {
        echo '<p>Please connect your Quickbooks account.</p>';
    }
}