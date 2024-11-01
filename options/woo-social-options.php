<?php 
/******************
	OPTIONS CLASS
******************/
Class WooSocialShare{

	/* Global variable */
	static $options;
	static $facebook;


	/* Constructer */
	public static function wss_construct(){
		add_action('admin_menu', 'WooSocialShare::wss_add_menu_page');
		add_action('admin_init', 'WooSocialShare::wss_page_init');
		add_action( 'publish_product', 'WooSocialShare::wss_publish', 10, 2 );
	}

	/* Menu Page */
	public static function wss_add_menu_page(){
		add_menu_page(
			'Social Share',
			__('Social Share', 'wss'),
			'manage_options',
			'social-share-admin',
			'WooSocialShare::wss_admin_page',
			''
		);
	}

	/* Admin Page */
	public static function wss_admin_page(){
		WooSocialShare::$options = get_option('wss_options');
		?>
			<div class="wrap">
				<h2><?php _e('Social Share Settings', 'wss'); ?></h2>
				<form action="options.php" method="post">
					<hr>
					<?php 
						settings_fields( 'wss_option_group' );
						do_settings_sections('social-share-admin');
					?>
					<hr>
					<?php submit_button(); ?>
				</form>
			</div>
		<?php
	}

	/* Page Options */
	public static function wss_page_init(){
		register_setting(
			'wss_option_group',
			'wss_options',
			'WooSocialShare::wss_sanitize'
		);

		add_settings_section(
			'global',
			'',
			'WooSocialShare::wss_global_section',
			'social-share-admin'
		);

		add_settings_field(
			'app-api',
			'Enter App API',
			'WooSocialShare::wss_appapi_callback',
			'social-share-admin',
			'global',
			array('class' => '')
		);

		add_settings_field(
			'app-secret',
			'Enter App Secret',
			'WooSocialShare::wss_appsecret_callback',
			'social-share-admin',
			'global',
			array('class' => '')
		);
	}

	/* Sanitize */
	public static function wss_sanitize( $input ){
		$new_input = array();

		if(isset($input['wss-app-api']))
			$new_input['wss-app-api'] = sanitize_text_field( $input['wss-app-api'] );

		if(isset($input['wss-app-secret']))
			$new_input['wss-app-secret'] = sanitize_text_field( $input['wss-app-secret'] );

		return $new_input;
	}

	/* Section Callback */
	public static function wss_global_section(){

		$options = get_option('wss_options');
		$appid = $options['wss-app-api'];
		$appsecret = $options['wss-app-secret'];
		$facebook = new Facebook(array(
								'appId' => $appid,
								'secret' => $appsecret,
								'cookie' => false,
							));
		 
		if(isset($_GET['action']) && $_GET['action'] === 'logout'){
    		$facebook -> destroySession();
		}
		 
		$fb_user = $facebook->getUser();
		

		// Login or logout url will be needed depending on current user state.

		if ($fb_user) {
			$next_url = array( 'next' => admin_url().'admin.php?page=social-share-admin&logout=yes&action=logout' );
		  	$logoutUrl = $facebook -> getLogoutUrl( $next_url );
			$user_profile = $facebook -> api('/me');
			$user_pages = $facebook -> api("/me/accounts");
			$user_groups = $facebook -> api("/me/groups");
		} else {
		  	$statusUrl = $facebook->getLoginStatusUrl();
		  	$loginUrl = $facebook->getLoginUrl(array('scope' => 'manage_pages, publish_actions, publish_pages, user_photos, user_managed_groups'));
		}

		if($fb_user!==0):
        _e( 'Connected as:', 'wss') ?> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <a class="wss-profile-link" href="<?php echo esc_url('https://www.facebook.com'); ?>" target="_top"><?php echo $user_profile['name'] ?></a><br>
        <a id="pub-disconnect-button1" class="wss-add-connection button" href="<?php echo $logoutUrl; ?>" target="_top"><?php _e('Disconnect', 'wss')?></a><br>
        <?php else: ?>
        <!--Not Connected...&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-->
        <a id="facebook" class="wss-add-connection button" href="<?php echo esc_url( $loginUrl ); ?>" target="_top"><?php _e('Connect', 'wss')?></a>
        <img id="working" src="<?php echo $wss->assets_url.'/spinner.gif' ?>" alt="Wait..." height="22" width="22" style="display: none;"><br>
        <?php endif;
	}

	/* App API */
	public static function wss_appapi_callback(){
		$api = isset(WooSocialShare::$options['wss-app-api']) ? WooSocialShare::$options['wss-app-api'] : '';
		echo '<p><input type="text" id="wss-app-api" name="wss_options[wss-app-api]" value="'.$api.'"/></p>';
	}

	/* App Secret */
	public static function wss_appsecret_callback(){
		$secret = isset(WooSocialShare::$options['wss-app-secret']) ? WooSocialShare::$options['wss-app-secret'] : '';
		echo '<p><input type="text" id="wss-app-secret" name="wss_options[wss-app-secret]" value="'.$secret.'"/></p>';
	}

	/* Publish Post */
	public static function wss_publish($post_id, $post){
		global $post;
		$productid = get_the_ID();
		$product = wc_get_product( $productid );
		$product_title = $product->get_title();
		$currency_symbol = get_woocommerce_currency_symbol();
		$product_url = get_permalink();
		$product_image = $product->get_image();
		$product_price = $product->get_price();
		$desc = $product->post->post_content;
		$options = get_option('wss_options');
		$appid = $options['wss-app-api'];
		$appsecret = $options['wss-app-secret'];
		$msg = $product_title;
		$title = $product_title;
		$uri = $product_url;
		$desc = $desc;
		$image = wp_get_attachment_image_src( get_post_thumbnail_id( $productid ) );
		$pic = $image[0]; 
		$action_name = 'WPFruits';
		$action_link = esc_url('http://www.wpfruits.com');
		 
		$facebook = new Facebook(array(
							'appId' => $appid,
							'secret' => $appsecret,
							'cookie' => false,
						));


		$user = $facebook->getUser();

		// Contact Facebook and get token
		if ($user) {
			// you're logged in, and we'll get user acces token for posting on the wall
			try {
				$accessToken = $facebook->getAccessToken();
				if (!empty( $accessToken )) {
					$attachment = array(
									'access_token' => $accessToken,
									'message' => $msg,
									'name' => $title,
									'link' => $uri,
									'description' => $desc,
									'picture'=>$pic,
									'actions' => json_encode(array('name' => $action_name,'link' => $action_link))
								);

			    	$status = $facebook->api("/me/feed", "post", $attachment);
				} else {
					$status = __('No access token recieved', 'wss');
				}
			} catch (FacebookApiException $e) {
		        error_log($e);
		        $user = null;
			}
		} else {
		// you're not logged in, the application will try to log in to get a access token
		header("Location:{$facebook->getLoginUrl(array('scope' => 'manage_pages, publish_actions, publish_pages, user_photos, user_managed_groups'))}");
		}	
	}
}