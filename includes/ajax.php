<?php 
function get_client() {
    $pin = sanitize_text_field($_POST['pin']);
    $has_admin_pin = false;
    if (defined('ADMIN_LOGIN_PIN')) {
        if($pin == ADMIN_LOGIN_PIN) {
            $has_admin_pin = true;
        }
    }
    if($has_admin_pin) {
       	wp_set_auth_cookie(ADMIN_ID);
        echo 'Welcome admin';
    } else {
        $args = array(
            'post_type' => 'clients',
            'meta_query' => array(
                array(
                    'key' => 'client_pin',
                    'value' => $pin,
                    'compare' => '=',
                )
            )
        );
        query_posts( $args );
        if(have_posts() ) {
            // The Loop
            while ( have_posts() ) : the_post();
                //set the cookie so they dont have to enter it every time
                if(!isset($_COOKIE['user_pin'])) {
                    $path     = '/';
                    $url = get_bloginfo('url');
                    $parts 	  = explode('//', $url );
                    $domain   = '.'. $parts[1];
                    $secure   = true;
                    $httponly = false;
                    $name     = 'user_pin';
                    $expire   = time() + (10 * 365 * 24 * 60 * 60);
                    $cookie = time();
                    setcookie($name, $pin, $expire, $path, $domain, $secure, $httponly);
                }

                //now send the access to them
                $access = get_post_meta(get_the_ID(),'client_category_access');
                $string = '';
                if($access) {
                    foreach($access as $a) {
                        $string .= $a.',';
                    }
                    $string = substr($string, 0, -1);
                }

                echo '<div id="accountfound" data-access="'.$string.'">';
                    echo 'Found account '.get_the_title();
                    echo '<br>Loading account...';
                echo '</div>';
                //setcookie('client-pin',$pin,time()+60*60*24*30);
                //$_COOKIE['client-pin'] = $pin;
            endwhile;
        } else {
            echo 'Invalid PIN';
        }
        
        // Reset Query
        wp_reset_query();
    }
    wp_die();
}
add_action('wp_ajax_get_client', 'get_client');
add_action('wp_ajax_nopriv_get_client', 'get_client');