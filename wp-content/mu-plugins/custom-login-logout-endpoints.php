<?php
/*
Plugin Name: WP REST API Custom Endpoints - Modal Login / Logout
Plugin URI: https://www.linkedin.com/in/yavisht/
Description: Bootstrap 3 based Modal Login / Logout using custom WP REST API Endpoints
Version: 1.0
Author: Yavisht Katgara
Author URI: https://www.linkedin.com/in/yavisht/
License: GPLv2 or later
*/

/*
*
* The whole idea is to handle the following 
* custom endpoints and inejct a
* Bootstrap Login Modal form.
* 
* POST https://<your-website>/wp-json/custom-api/login
* GET https://<your-website>/wp-json/custom-api/logout
*
*/

add_action( 'rest_api_init', 'yk_register_api_hooks' );

function yk_register_api_hooks() {
    
    // POST https://<your-website>/wp-json/custom-api/login

    register_rest_route( 'custom-api', '/login/', array(
            'methods'  => 'POST',
            'callback' => 'yk_login',
        )
    );
    
    // GET https://<your-website>/wp-json/custom-api/logout

    register_rest_route( 'custom-api', '/logout/', array(
            'methods'  => 'GET',
            'callback' => 'yk_logout',
        )
    );

} // End of yk_register_api_hooks();


// 
// 
// Login Function
// 
//

function yk_login() {
    
    // Don't forget to handle sanitazation and escaping!!  

    if($_POST["user_login"] && $_POST["user_password"]){
        
        $creds = array(
            'user_login'    => $_POST["user_login"],
            'user_password' => $_POST["user_password"],
            'remember' => true,
        );
        
        // Perform Login with the ^^ Creds

        $user = wp_signon( $creds, is_ssl() );
    
        // Catch Errors

        if ( is_wp_error( $user ) ) {

            $login_error = $user->get_error_code();
            
            // Beautify error
            $login_error = str_replace('_', ' ', $login_error);
            
            $login_response = array(
                'status'    => 'failed',
                'message' => ucwords($login_error),
            );
            
            return $login_response;

        } else {
            
            $login_response = array(
                'status'    => 'success',
                'message' => 'Welcome '. $user->data->user_nicename,
            );

            return $login_response;

        } // End of Error check
        
    } else {
        
        // For Extra security if someone removes 'required' from form input fields.

        $login_response = array(
            'status'    => 'failed',
            'message' => 'Please make sure you have entered a Username and Password',
        );
        
        return $login_response;
    
    } // End of if check

} // End of yk_login();

// 
// 
// Logout Function
// 
//
function yk_logout() {
    
    // Perform quick logout
    
    wp_logout();

    $logout_response = array(
        'status'    => 'success',
        'message' => 'Successfully Logged Out',
    );

    return $logout_response;

} // End of yk_logout

// 
// 
// Make the URL's globally available
// 
//

function yk_the_api_login_endpoint(){
    $yk_rest_login_endpoint = get_bloginfo('url') . '/wp-json/custom-api/login/';
    echo $yk_rest_login_endpoint;
}

function yk_the_api_logout_endpoint(){
    $yk_rest_logout_endpoint = get_bloginfo('url') . '/wp-json/custom-api/logout/';
    echo $yk_rest_logout_endpoint;
}

// 
// 
// Inject login / logout links in the menu
// 
//

add_filter( 'wp_nav_menu_items', 'yk_add_loginout_link', 10, 2 );

function yk_add_loginout_link( $items, $args ) {

    if (is_user_logged_in() && $args->theme_location == 'primary') {
        
        $get_the_user = wp_get_current_user();
        
        //
        // Add Dashboard on login.
        //

        $items .= '<li class="menu-item dropdown">

                        <a title="' . $get_the_user->user_nicename . '" href="#" data-toggle="dropdown" class="dropdown-toggle" aria-haspopup="true" aria-expanded="false">
                            <i class="fa fa-user" aria-hidden="true"></i> ' . $get_the_user->user_nicename . ' <span class="caret"></span>
                        </a>
                        
                        <ul role="menu" class=" dropdown-menu">
                            <li><a id="yk_api_logout_link" href="#">Logout</a></li>
                        </ul>
                    
                    </li>';

    } elseif (!is_user_logged_in() && $args->theme_location == 'primary') {
        
        $items .= '<li><a href="#" data-toggle="modal" data-target="#ykLoginModal">Login</a></li>';
    
    }

    return $items;
}

// 
// 
// Inject Boostrap modal code in the footer.
// 
//

add_action('wp_footer', 'yk_inject_modal_markup');

function yk_inject_modal_markup() { ?>

    <!-- Modal HTML -->

    <div class="modal" id="ykLoginModal" tabindex="-1" role="dialog" aria-labelledby="ykLoginModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                
                <!-- Modal Header -->
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" id="ykLoginModalLabel">Login</h4>
                </div>
                <!-- END Modal Header -->
                
                <!-- Modal Body -->
                <div class="modal-body">
                    <form id="loginform" method="post" name="loginform">
                        
                        <div class="form-field">
                            <label>Username:</label>
                            <input type="text" class="login-username" name="user_login" required />
                        </div>
                        
                        <div class="form-field">
                            <label>Password:</label>
                            <input type="password" class="login-password" name="user_pass" required />
                        </div>
                        
                        <div>
                            <button type="submit" name="login-submit" class="btn btn-primary btn-block">Login</button>
                        </div>

                    </form>
                </div>
                <!-- END Modal Body -->

                <!-- Modal Footer -->
                <div class="modal-footer">
                    <div class="yk_login_errors"></div>
                </div>
                <!-- END Modal Footer -->

            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->

    <script>
        jQuery(document).ready(function($) {
        
            //
            // JS Login Function
            //

            $('#loginform').submit(function(event) {
                
                //console.log("submitting form");
            
                // get the form data
                // there are many ways to get this data using jQuery (you can use the class or id also)
                
                var loginCredentials = {
                    'user_login'        : $('input[name=user_login]').val(),
                    'user_password'     : $('input[name=user_pass]').val(),
                    'remember'          : true,
                };

                //console.log(loginCredentials);
                // Post the form
                
                $.ajax({
                    type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
                    url         : '<?php yk_the_api_login_endpoint(); ?>', // the url where we want to POST
                    dataType    : 'JSON', // what type of data do we expect back from the server
                    data        : loginCredentials // our data object
                })

                // using the done promise callback
                .done(function(data) {
                    
                    if(data.status === 'success'){
                         $( ".yk_login_errors" ).html( data.message );
                         location.reload();
                    } else {
                        $( ".yk_login_errors" ).html( data.message );
                    }
                    
                }); // end of .done
                
                // stop the form from submitting the normal way and refreshing the page
                event.preventDefault();

            }); // End of Login click function
            
            //
            // JS Logout Function
            //

            $('#yk_api_logout_link').click(function() {
                
                $.ajax({
                    type    : 'GET', // define the type of HTTP verb we want to use (GET for our form)
                    url     : '<?php yk_the_api_logout_endpoint(); ?>', // the url where we want to GET
                })

                // using the done promise callback
                .done(function(data) {
                    
                    // If check not really needed but adding it since status is returned
                    if(data.status === 'success'){
                        location.reload();
                    }

                });

            }); // end of Logout click function

        }); // end of jQuery
    </script>
<?php
}

// 
// 
// End of Inject Boostrap modal code in the footer.
// 
//
