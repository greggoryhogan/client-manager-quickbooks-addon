<?php
// Remove subscribe to calendar dropdown from main calendar page
add_filter( 'tribe_template_html:events/v2/components/subscribe-links/list', '__return_false' );

add_action( 'wp_enqueue_scripts', 'my_theme_enqueue_styles' );
function my_theme_enqueue_styles() {
    $parenthandle = 'parent-style'; // This is 'twentyfifteen-style' for the Twenty Fifteen theme.
    $theme = wp_get_theme();
    wp_enqueue_style( $parenthandle, get_template_directory_uri() . '/style.css', 
        array()
    );
    wp_enqueue_style( 'child-style', get_stylesheet_uri(),
        array( $parenthandle )
    );

    wp_enqueue_script( 'theme-js', get_stylesheet_directory_uri() . '/js.js', array('jquery'),'1.2',true );
    wp_localize_script( 'theme-js', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
}

add_action( 'tribe_template_after_include:events/v2/month/calendar-body', function( $file, $name, $template ) {
    showHours();
}, 10, 3 );

add_action( 'tribe_template_before_include:events/v2/components/events-bar/views', function( $file, $name, $template ) {
    if(get_current_user_ID() > 0) {
        echo '<div class="plus"><a href="'.get_bloginfo('url').'/wp-admin/post-new.php?post_type=tribe_events" class="btn button" target="_blank">Log Time</a></div>';
    }
    
}, 100, 3 );

add_action( 'tribe_template_after_include:events/v2/month/calendar-body/day/calendar-events/calendar-event/title', function( $file, $name, $template ) {
    $event_id = get_the_ID();
    $event = tribe_get_event($event_id);
    if($event) {

        $term_list = wp_get_post_terms( $event_id, Tribe__Events__Main::TAXONOMY );
        
        foreach( $term_list as $term_single ) {
            $event_cat = $term_single->name;
        }
        
        $hours = $event->duration / 60 / 60;
        $details = get_the_content($event_id);
        $time = 'hours';
        if($hours == 1) {
            $time = 'hour';
        } 

        $timestamp = strtotime($event->start_date);
        
        $event_month = date('Y-m', $timestamp);
        $rate = get_client_rate($event_cat);

        echo '<h4 data-category="'.$event_cat.'" class="calculate-hours tribe-events-calendar-month__calendar-event-title tribe-common-h8 tribe-common-h--alt" data-month="'.$event_month.'"> -&nbsp;&nbsp;<span class="hours">'.$hours.'</span> '.$time.'<span class="details">'.$details.'</span><span class="rate">'.$rate.'</span></h4>';
    }
}, 10, 3 );

function get_client_rate($client) {
    global $wpdb;

    $post = $wpdb->get_results("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'client_category_access' AND  meta_value = '$client' LIMIT 1");


    //$post = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type= %s", $client, 'clients'));
    if ( $post ) {
       // print_r($post);
        return get_post_meta($post[0]->post_id, 'client_rate',true);
    } else {
        return 0;
    }

}
function get_the_displayed_month() {
    //return date( 'F', strtotime( tribe_get_month_view_date() ) );
    return tribe_get_current_month_text();
}

function showHours() {
    echo '<div id="allhours"></div>'; ?>
    <script>
        // Create our number formatter.
        var formatter = new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',

        // These options are needed to round to whole numbers if that's what you want.
        //minimumFractionDigits: 0, // (this suffices for whole numbers, but will print 2500.10 as $2,500.1)
        //maximumFractionDigits: 0, // (causes 2500.99 to be printed as $2,501)
        });
        var categories = [];
        var totalTime = 0;
        var month = jQuery('.tribe-events-c-top-bar__datepicker-time').attr('datetime');

        jQuery('.calculate-hours').each(function() {
            var this_month = jQuery(this).attr('data-month');
                
                if(this_month != month) {
                   jQuery(this).parent().remove();
                   //alert(this_month + ' '+ month);
                } else {        
                    var cat = jQuery(this).attr('data-category');
                    if(jQuery.inArray(cat,categories) == -1) {
                        categories.push(cat);
                    }
                }
        });
        var totalcost = 0;
        jQuery.each(categories, function(key,val) {
            if(val != '') {
                var total = 0;
                var notes = '';
                var rate = 0;
                jQuery('[data-category='+val+']').each(function() {
                    var time = parseFloat(jQuery(this).find('span.hours').text());
                    total += time;
                    totalTime += time;
                    notes += jQuery(this).find('.details').text()+'<br>';
                    rate = jQuery(this).find('.rate').text();
                });
                var time = 'hours';
                if(total == 1) {
                    time = 'hour';
                } 
                var lineitem = parseInt(rate) * total;
                totalcost += lineitem;
                jQuery('#allhours').append('<div class="grid"><div class="title" data-client="'+val+'" data-rate="'+rate+'">'+val+'<div class="details">'+notes+'</div></div><div><span class="hours">'+total+'</span> '+time+', '+formatter.format(lineitem)+'<div></div>');
            }
        }); 
        jQuery('#allhours').append('<div class="grid total"><div>Total Time</div><div>'+totalTime+' hours, '+formatter.format(totalcost)+'<div></div>');

        if(jQuery('#accountfound').length) {
            updateTimeLog('existing');
        }

    </script><?php
}

add_filter( 'tribe_events_views_v2_should_cache_html', '__return_false' );

add_filter( 'auth_cookie_expiration', 'keep_me_logged_in_for_1_year' );
function keep_me_logged_in_for_1_year( $expirein ) {
    return 31556926; // 1 year in seconds
}


function my_plugin_body_class($classes) {
    if(get_current_user_ID() > 0) {
        $classes[] = 'logged-in user-'.get_current_user_ID();
    } else {
        $classes[] = 'logged-out';
    }
    return $classes;
}

add_filter('body_class', 'my_plugin_body_class');

add_action('init','save_options');
function save_options() {
    if(get_option('hide_my_site_ihmsa')){
        update_option('hide_my_site_ihmsa', 'hmsia');
    }
    else {
        add_option('hide_my_site_ihmsa', 'hmsia');
    }
}

add_action('admin_head', 'remove_some_stuff_with_css');

function remove_some_stuff_with_css() {
  echo '<style>
  #event_tribe_venue,
  #event_tribe_organizer,
  #event_url,
  #event_cost,
  .tribe-allday {
      display:none;
  }
  </style>';
}


add_filter( 'tribe_event_label_singular', function() { return 'Hours'; } );
add_filter( 'tribe_event_label_singular_lowercase', function() { return 'hours'; } );
add_filter( 'tribe_event_label_plural', function() { return 'Hours'; } );
add_filter( 'tribe_event_label_plural_lowercase', function() { return 'hours'; } );

// ************* Remove default Posts type since no blog *************

// Remove side menu
add_action( 'admin_menu', 'remove_default_post_type' );

function remove_default_post_type() {
    remove_menu_page( 'edit.php' );
}

// Remove +New post in top Admin Menu Bar
add_action( 'admin_bar_menu', 'remove_default_post_type_menu_bar', 999 );

function remove_default_post_type_menu_bar( $wp_admin_bar ) {
    $wp_admin_bar->remove_node( 'new-post' );
}

// Remove Quick Draft Dashboard Widget
add_action( 'wp_dashboard_setup', 'remove_draft_widget', 999 );

function remove_draft_widget(){
    remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
}
// End remove post type

add_action( 'wp_body_open', 'wpdoc_add_custom_body_open_code' );
 
function wpdoc_add_custom_body_open_code() {
    clientLogin();
}

function clientLogin() {
    if(get_current_user_ID() > 0) {
        return;
    }
    $pinval = '';
    if(isset($_COOKIE['user_pin'])) {
        $pinval = $_COOKIE['user_pin'];
    }
    echo '<div id="client-login">';
        echo '<form id="pinrequest">';
        echo '<p>Enter your PIN to Continue</p>';
        echo '<input type=password name="client-pin" id="client-pin" placeholder="PIN" class="enter_pin" value="'.$pinval.'">';
        echo '<div id="pinresponse">';
        if(isset($_COOKIE['user_pin'])) {
            echo 'Loading account info';
        } else {
            echo 'Loading...';
        }
        echo '</div>';
        echo '</form>';
    echo '</div>';
}

// Our custom post type function
function create_posttype() {
 
    register_post_type( 'clients',
    // CPT Options
        array(
            'labels' => array(
                'name' => __( 'Clients' ),
                'singular_name' => __( 'Client' )
            ),
            'public' => true,
            'has_archive' => false,
            'rewrite' => array('slug' => 'clients'),
            'show_in_rest' => true,
            'supports' => array( 'title', 'custom-fields' ),
 
        )
    );
}
// Hooking up our function to theme setup
add_action( 'init', 'create_posttype' );

add_action( 'template_redirect', 'redirect_single_client' );
function redirect_single_client(){
    if ( ! is_singular( 'clients' ) )
        return;
    wp_redirect( get_bloginfo('url'), 301 );
    exit;
}

add_filter( 'sanitize_text_field',  function ( $filtered, $str ) {
    return _sanitize_text_fields( $str, true );
}
, 10, 2 );

/*
 *
 * Register meta boxes for clients cpt
 * 
 */
function time_tracker_register_meta_boxes() {
    add_meta_box( 'time-tracker-clients', __( 'Client Access', 'time_tracker' ), 'time_tracker_display_client_options', 'clients' );
    add_meta_box( 'time-tracker-clients-summary', __( 'Client Earnings Summary', 'time_tracker' ), 'time_tracker_display_client_summary', 'clients' );
    add_meta_box( 'time-tracker-monthly-summary', __( 'Current Month', 'time_tracker' ), 'time_tracker_display_month_summary', 'clients');
    add_meta_box( 'time-tracker-client_notes', __( 'Client Notes', 'time_tracker' ), 'time_tracker_display_client_notes', 'clients');
}
add_action( 'add_meta_boxes', 'time_tracker_register_meta_boxes' );

function time_tracker_display_client_notes( $post ) {
    $post_id = $post->ID;
    $client_notes = get_post_meta($post_id,'client_notes',true);
    echo '<textarea style="width: 100%;border:none;" rows="10" name="client_notes">'.$client_notes.'</textarea>';
}

function time_tracker_display_month_summary( $post ) {
    global $post;
    $tmp_post = $post;
    $post_id = $post->ID;
    $access = get_post_meta($post_id,'client_category_access');
    $rate = get_post_meta($post_id,'client_rate', true);
    if(!empty($access)) {
        $args = array(
            'post_type' => 'tribe_events',
            'posts_per_page' => -1,
            'tax_query' => array(
                array (
                    'taxonomy' => 'tribe_events_cat',
                    'field' => 'name',
                    'terms' => $access,
                )
            ),
            'meta_query' => array(
                array(
                    'key' => '_EventStartDate',
                    'value' =>  date('Y-m-d 00:00:00',strtotime(date('Y-m-01'))), //this month date('Y-m-01'); //this yeat date('Y-m-d 00:00:00',date("Y"))
                    'compare' => '>',
                )
            )
        );
        $the_query = new WP_Query($args);
        if($the_query->have_posts()) {
            $count = $the_query->found_posts;
            $total = 0;
            while($the_query->have_posts()) {
                $the_query->the_post();
                $the_id = get_the_ID();
                //echo get_the_title().'<br>';
                $start = get_post_meta($the_id,'_EventStartDate',true);
                $end = get_post_meta($the_id,'_EventEndDate',true);
                $hours = ( strtotime($end) - strtotime($start) ) / 60 / 60;
                $total += $hours;
                //print_r(get_post_meta(get_the_ID()));
            }
            $accrued = number_format($total * $rate,2);
            echo '<p style="margin-bottom: 0;">$'.$accrued.'<br>'.$total .' hours, '.$count.' days.</p>';
        } else {
            echo '<p style="margin-bottom: 0;">Nothing earned this month.</p>';
        }
        wp_reset_postdata();
        $post = $tmp_post;
            
    } else {
        echo '<p>Nothing selected for this client.</p>';
    }
    
}

function time_tracker_display_client_summary( $post ) {
    global $post;
    $tmp_post = $post;
    $post_id = $post->ID;
    $access = get_post_meta($post_id,'client_category_access');
    $rate = get_post_meta($post_id,'client_rate', true);
    if(!empty($access)) {
        foreach($access as $client) {
            echo '<h4 style="border-bottom: 1px solid #c3c4c7;">'.$client.'</h4>';
            $args = array(
                'post_type' => 'tribe_events',
                'posts_per_page' => -1,
                'tax_query' => array(
                    array (
                        'taxonomy' => 'tribe_events_cat',
                        'field' => 'name',
                        'terms' => $client,
                    )
                ),
                'meta_query' => array(
                    array(
                        'key' => '_EventStartDate',
                        'value' =>  date('Y-m-d 00:00:00',strtotime(date('Y-m-01'))), //this month date('Y-m-01'); //this yeat date('Y-m-d 00:00:00',date("Y"))
                        'compare' => '>',
                    )
                )
            );
            $the_query = new WP_Query($args);
            if($the_query->have_posts()) {
                $count = $the_query->found_posts;
                $total = 0;
                while($the_query->have_posts()) {
                    $the_query->the_post();
                    $the_id = get_the_ID();
                    //echo get_the_title().'<br>';
                    $start = get_post_meta($the_id,'_EventStartDate',true);
                    $end = get_post_meta($the_id,'_EventEndDate',true);
                    $hours = ( strtotime($end) - strtotime($start) ) / 60 / 60;
                    $total += $hours;
                    //print_r(get_post_meta(get_the_ID()));
                }
                $accrued = number_format($total * $rate,2);
                echo '<p style="margin-bottom: 0;">Month to date: '.$total .' hours, $'.$accrued.' over '.$count.' days.</p>';
            } else {
                echo '<p style="margin-bottom: 0;">Month to date: 0.</p>';
            }
            wp_reset_query();
            //echo '<h3>This year</h3>';
            
            $args = array(
                'post_type' => 'tribe_events',
                'posts_per_page' => -1,
                'tax_query' => array(
                    array (
                        'taxonomy' => 'tribe_events_cat',
                        'field' => 'name',
                        'terms' => $client,
                    )
                ),
                'meta_query' => array(
                    array(
                        'key' => '_EventStartDate',
                        'value' =>  date('Y-m-d 00:00:00',strtotime(date('Y-01-01'))), //this month date('Y-m-01'); //this yeat date('Y-m-d 00:00:00',date("Y"))
                        'compare' => '>',
                    )
                )
            );
            $the_query = new WP_Query($args);
            if($the_query->have_posts()) {
                $count = $the_query->found_posts;
                $total = 0;
                while($the_query->have_posts()) {
                    $the_query->the_post();
                    $the_id = get_the_ID();
                    //echo get_the_title().'<br>';
                    $start = get_post_meta($the_id,'_EventStartDate',true);
                    $end = get_post_meta($the_id,'_EventEndDate',true);
                    $hours = ( strtotime($end) - strtotime($start) ) / 60 / 60;
                    $total += $hours;
                    //print_r(get_post_meta(get_the_ID()));
                }
                $accrued = number_format($total * $rate,2);
                echo '<p style="margin-top: 0;">Year to date: '.$total .' hours, $'.$accrued.' over '.$count.' days.</p>';
            } else {
                echo '<p style="margin-top: 0;">Year to date: 0.</p>';
            }
            wp_reset_query();
        }
    } else {
        echo '<p>Nothing selected for this client.</p>';
    }
    $post = $tmp_post;
}
/*
 *
 * Meta box display callback.
 * 
 */
function time_tracker_display_client_options( $post ) {
    $post_id = $post->ID;
    ?>
    <style type="text/css">.clientoption,.clientpin {margin: 5px 0 4px 0;}</style>
    Client PIN:<br>
    <input type="text" name="client_pin" class="clientpin" value="<?php echo get_post_meta($post_id,'client_pin', true); ?>" style="display: block; margin: 5px 0;" />
    Client Rate:<br>
    <input type="text" name="client_rate" class="clientrate" value="<?php echo get_post_meta($post_id,'client_rate', true); ?>" style="display: block; margin: 5px 0;" />
    Client Can Access:
    <div class="time_tracker_box">
        <?php 
        $terms = get_terms('tribe_events_cat', array('hide_empty' => 0));
        $access = get_post_meta($post_id,'client_category_access');
        if($terms) {
            foreach($terms as $term) {
                echo '<div class="clientoption"><input type="checkbox" name="client_category_access[]" value="'.$term->name.'"';
                if(in_array($term->name,$access)) {
                    echo ' checked';
                }
                echo '/> '.$term->name.'</div>';
            }
        }
        ?>

    </div><?php 
}

/*
 *
 * Save meta box content
 *
 */
function save_time_tracker_client_meta( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( $parent_id = wp_is_post_revision( $post_id ) ) {
        $post_id = $parent_id;
    }
    
    if(isset( $_POST['client_pin'] )) {
        update_post_meta( $post_id, 'client_pin', sanitize_text_field($_POST['client_pin'] ));
    }

    if(isset( $_POST['client_rate'] )) {
        update_post_meta( $post_id, 'client_rate', sanitize_text_field($_POST['client_rate'] ));
    }

    if(isset( $_POST['client_category_access'] ))  {
        delete_post_meta($post_id,'client_category_access');
        foreach($_POST['client_category_access'] as $value) {
            add_post_meta($post_id, 'client_category_access', sanitize_text_field($value));
        }
    }

    if(isset( $_POST['client_notes'] )) {
        update_post_meta( $post_id, 'client_notes', sanitize_text_field($_POST['client_notes'] ));
    } 

     
}
add_action( 'save_post_clients', 'save_time_tracker_client_meta' );

function get_client() {
    $pin = sanitize_text_field($_POST['pin']);
    if($pin == ADMIN_LOGIN_PIN) {
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

add_action( 'save_post', 'timetracker_isnt_dead', 10, 3 );
function timetracker_isnt_dead( $post_ID, $post, $update ) {
    date_default_timezone_set('America/New_York');
    update_option('deadman_update',date('m/d/Y h:i a', time()));
    update_option('timetracker_is_dead',0);
}

/*
 *
 * Use my name is timetracker credentials to send accounts mail 
 * 
 */
function pmp_smtp_filters( $phpmailer ) {
	$phpmailer->isSMTP();    
	$phpmailer->Host = SMTP_HOST;
	$phpmailer->SMTPAuth = true; // Ask it to use authenticate using the Username and Password properties
	$phpmailer->Port = SMTP_PORT;
    $phpmailer->Username = SMTP_EMAIL;
	$phpmailer->Password = SMTP_PASSWORD;
	$phpmailer->setFrom(SMTP_EMAIL, SMTP_FROM);
	$phpmailer->SMTPDebug = 0; //1 outputs debug info on screen
}
add_action( 'phpmailer_init', 'pmp_smtp_filters' ); 



/*
 *
 * Change no hours text
 * 
 */
function change_default_strings( $translated_text, $text, $domain ) {
	switch ( $translated_text ) {
		case 'There are no upcoming %s at this time.' :
			$translated_text = __( 'No %s logged.', 'woocommerce' );
			break;
		case 'There are no upcoming %1$s.' :
			$translated_text = __( 'No %1$s logged.', 'woocommerce' );
			break;
	}
	return $translated_text;
}
add_filter( 'gettext', 'change_default_strings', 20, 3 ); 
//add_filter( 'ngettext', 'change_default_strings', 20, 3 ); 

add_filter('tribe_events_views_v2_messages_map','change_ec_strings');
function change_ec_strings($map) {
	$map = [
			'no_results_found'                 => __(
				'There were no results found.',
				'the-events-calendar'
			),
			'no_upcoming_events'               => sprintf(
			/* Translators: %1$s is the lowercase plural virtual event term. */
				_x(
					'No time has been logged.',
					'A message to indicate there are no upcoming events.',
					'the-events-calendar'
				),
				tribe_get_event_label_plural_lowercase()
			),
			'month_no_results_found'           => __(
				'There were no results found for this view.',
				'the-events-calendar'
			),
			// translators: the placeholder is the keyword(s), as the user entered it in the bar.
			'no_results_found_w_keyword'       => __(
				'There were no results found for <strong>"%1$s"</strong>.',
				'the-events-calendar'
			),
			// translators: the placeholder is the keyword(s), as the user entered it in the bar.
			'month_no_results_found_w_keyword' => __(
				'There were no results found for <strong>"%1$s"</strong> this month.',
				'the-events-calendar'
			),
			// translators: %1$s: events (plural), %2$s: the formatted date string, e.g. "February 22, 2020".
			'day_no_results_found'             => __(
				'No %1$s scheduled for %2$s.',
				'the-events-calendar'
			),
			// translators: the placeholder is an html link to the next month with available events.
			'month_no_results_found_w_ff_link' => __(
				'There were no results found for this view. %1$s',
				'the-events-calendar'
			),
			// translators: %1$s: events (plural), %2$s: the formatted date string, e.g. "February 22, 2020". %3$s html link to next day with available events.
			'day_no_results_found_w_ff_link'   => __(
				'No %1$s scheduled for %2$s. %3$s',
				'the-events-calendar'
			),
		];
	return $map;
}
//Allow 15 minute intervals for events calender timess
add_filter( 'tribe_events_meta_box_timepicker_step', function() {
    return 15;
});

add_action( 'admin_bar_menu', 'timetracker_client_list_admin_menu', 50 );
function timetracker_client_list_admin_menu( $wp_admin_bar ) {
    $args = array(
        'id'    => 'my-clients',
        'title' => 'Client List',
        'href'  => admin_url() . 'edit.php?post_type=clients'
    );
    $wp_admin_bar->add_node( $args );
    $posts = get_posts([
        'post_type' => 'clients',
        'post_status' => 'publish',
        'numberposts' => -1,
        'orderby' => 'menu_order',
        'order' => 'ASC'
        // 'order'    => 'ASC'
    ]);
    if(is_array($posts)) {
        if(!empty($posts)) {
            foreach($posts as $post) {
                //https://accounts.mynameistimetracker.com/wp-admin/post.php?post=105&action=edit
                $id = $post->ID;
                $args = array(
                    'id'    => 'client-'.$id,
                    'title' => get_the_title($id),
                    'href'  => admin_url() . 'post.php?post='.$id.'&action=edit',
                    'parent' => 'my-clients'
                    );
                $wp_admin_bar->add_node( $args );
            }
        }
    }
    
}

add_action('admin_head', 'timetracker_admin_css');

function timetracker_admin_css() {
  echo '<style>
  #event_tribe_venue,
  #event_tribe_organizer,
  #event_url,
  #event_cost {
      display:none;
  }
  </style>';
}