<?php

/**
 * First_8_Marketing class
 *
 * Handles the plugin functionality.
 *
 * @package First_8_Marketing
 */
class First_8_Marketing
{

	/**
	 * Instance of the class.
	 *
	 * @var First_8_Marketing
	 */
	private static $instance = null;

	/**
	 * Constructor
	 */
	private function __construct() {
        register_activation_hook(FIRST_8_MARKETING_FILE, [$this, 'activate']);
        register_deactivation_hook(FIRST_8_MARKETING_FILE, [$this, 'deactivate']);
        register_uninstall_hook(FIRST_8_MARKETING_FILE, [$this, 'uninstall']);

        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'f8m_store_redirected_data']);
        add_action('wp_footer', [$this, 'f8m_enqueue_umami_script']);
        add_action('wp_footer', [$this, 'f8m_enqueue_umami_custom_tracker_script']);
        add_action('wp_ajax_first_8_marketing_connect', [$this, 'connect']);
        add_action('wp_ajax_first_8_marketing_disconnect', [$this, 'disconnect']);
		add_action('wp_ajax_first_8_marketing_export_post', [$this, 'export_post']);
        add_action('rest_api_init', [$this, 'register_custom_routes']);
    }

    /**
     * Retrieves the instance of the class.
     *
     * @return First_8_Marketing The instance of the class.
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

	/**
    * Adds the admin menu.
    */
    public function add_admin_menu()
	{
		add_menu_page(
            'First 8 Marketing',
            'First 8 Marketing',
            'manage_options',
            'first-8-marketing',
            [$this, 'init_page']
        );
	}

		/**
		* Inject Umami Script
		*/
		public function f8m_store_redirected_data()
		{
				if (isset($_GET['umamiScript']) && isset($_GET['websiteId'])) { // phpcs:ignore
						// Fix: wp_unslash before sanitization
						$umami_script = esc_url_raw(wp_unslash($_GET['umamiScript'])); // phpcs:ignore
						$website_id = sanitize_text_field(wp_unslash($_GET['websiteId'])); // phpcs:ignore
 
						update_option('umami_script_url', $umami_script);
						update_option('umami_website_id', $website_id);

						$is_umami_injected = get_option('is_umami_injected', '0');
						if ($is_umami_injected === '0') {
								$this->f8m_enqueue_umami_script();
								$this->f8m_enqueue_umami_custom_tracker_script();

								$redirect_url = remove_query_arg(['umamiScript', 'websiteId']);
								wp_redirect($redirect_url);
								exit;
						}
				}
		}


	/**
     * Init page template functionality.
     */
	public function init_page()
	{
		if (!current_user_can('manage_options')) {
			wp_die(__('You do not have sufficient permissions to access this page.')); // phpcs:ignore
		}
		$api_key = get_option('first_8_marketing_api_key', '');
		$is_contents_have_been_exported = get_option('is_contents_have_been_exported', '0');
		include_once( FIRST_8_MARKETING_DIR . '/src/templates/init-page.php' );
	}

	/**
	 * Enqueue Umami Script
	 */
	public function f8m_enqueue_umami_script() {
		// Get stored script URL and website ID
		$umami_script = get_option('umami_script_url');
		$website_id = get_option('umami_website_id');

		if (!empty($umami_script) && !empty($website_id)) {
				// Register and enqueue the script
				wp_register_script(
						'umami-script', 
						esc_url($umami_script) . '/script.js', 
						[], 
						null,  // phpcs:ignore
						true
				);

				// Pass the website ID to the script using localized data
				wp_localize_script('umami-script', 'umamiSettings', [
						'websiteId' => esc_attr($website_id)
				]);

				wp_enqueue_script('umami-script');
				update_site_option('is_umami_injected', '1');
		}
	}


	/**
	 * Enqueue Umami Custom Tracker Script
	 */
	public function f8m_enqueue_umami_custom_tracker_script() {
		$umami_script = get_option('umami_script_url');
		$website_id = get_option('umami_website_id');

		if (!empty($umami_script) && !empty($website_id)) {
				// Register and enqueue the custom tracker script
				wp_enqueue_script(
						'custom-umami-tracker', 
						plugins_url('js/custom-umami-tracking.js', __FILE__), 
						[], 
						null, // phpcs:ignore
						true
				);
		}
	}


	/**
     * Activation hook callback.
     *
     * Sets up initial plugin options.
     */
    public function activate() {
        $initial_options = [
            'first_8_marketing_api_key' => '',
            'is_contents_have_been_exported' => '0',
            'is_umami_injected' => '0',
            'umami_script_url' => '',
            'umami_website_id' => '',
        ];

        foreach ($initial_options as $option_key => $option_value) {
            if (!get_option($option_key)) {
                add_option($option_key, $option_value);
            }
        }

        // Additional activation tasks can be added here.
    }

	/**
     * Deactivation hook callback.
     *
     * Cleans up resources and settings.
     */
    public function deactivate() {
        // Add code to clean up settings, scheduled events, temporary options, etc.
        // Example: wp_clear_scheduled_hook('first_8_marketing_cron_job');
		$options_to_delete = [
		'first_8_marketing_api_key',
		'is_contents_have_been_exported',
		'is_umami_injected',
		'umami_script_url',
		'umami_website_id',
        ];

        foreach ($options_to_delete as $option) {
            delete_option($option);
        }

		// remove injected script
		remove_action('wp_footer', 'f8m_enqueue_umami_script');
		remove_action('wp_footer', 'f8m_enqueue_umami_custom_tracker_script');
    }

	/**
     * Uninstall hook callback.
     *
     * Removes plugin data and settings.
     */
    public function uninstall() {
        $options_to_delete = [
            'first_8_marketing_api_key',
			'is_contents_have_been_exported',
			'is_umami_injected',
			'umami_script_url',
			'umami_website_id',
            // Add other options to delete here
        ];

        foreach ($options_to_delete as $option) {
            delete_option($option);
        }

        // remove injected script
		remove_action('wp_footer', 'f8m_enqueue_umami_script');
		remove_action('wp_footer', 'f8m_enqueue_umami_custom_tracker_script');
    }

	/**
	* Handles the connection functionality.
	*/
	public function connect() {
		$api_key = hash('sha256', bin2hex(random_bytes(32)));
		update_option('first_8_marketing_api_key', $api_key);

		$redirect_url = FIRST_8_MARKETING_URL . '/wordpress/wp-connect?wp_api_key=' . $api_key . '&wp_url=' . home_url();

		// Send a JSON response with the redirect URL
		wp_send_json_success(['redirect_url' => $redirect_url]);
	}

	/**
	* Handles the disconnection functionality.
	*/
	public function disconnect() {
		$api_key = get_site_option('first_8_marketing_api_key');

		if ($api_key) {
			update_site_option('first_8_marketing_api_key', '');
			update_site_option('is_umami_injected', '0');
			update_site_option('umami_script_url', '');
			update_site_option('umami_website_id', '');

			// Send a success response
			wp_send_json_success(['message' => 'Disconnected successfully']);
		} else {
			// Send an error response if there's no API key
			wp_send_json_error(['message' => 'No API key found for disconnection']);
		}

		// remove injected script
		remove_action('wp_footer', 'f8m_enqueue_umami_script');
		remove_action('wp_footer', 'f8m_enqueue_umami_custom_tracker_script');
	}

	/**
	* Handles the export post functionality.
	*/
	public function export_post() {
		$api_key = get_site_option('first_8_marketing_api_key');

		if ($api_key) {
			$args = array(
				'post_type' => array('post', 'page'), // Fetch posts, pages
				'posts_per_page' => -1 // Retrieve all entries
			);
			$query = new WP_Query($args);
			$posts_data = array();
			if ($query->have_posts()) {
				while ($query->have_posts()) {
					$query->the_post();
					$posts_data[] = array(
						'id' => get_the_ID(),
						'title' => get_the_title(),
						'content' => get_the_content(),
						'permalink' => get_permalink(),
						'content_type' => get_post_type() 
					);
				}

				// Prepare the data for the external API
				$external_api_data = array(
					'posts' => $posts_data
				);

				// Send the POST request to the external API
				$response = wp_remote_post(FIRST_8_MARKETING_SCRAP_API_URL . '/wordpress/initialize', array(
					'method'    => 'POST',
					'body'      => wp_json_encode($external_api_data),
					'headers'   => array(
						'Content-Type' => 'application/json',
						'Authorization' => 'Bearer ' . $api_key,
					),
				));

				if (is_wp_error($response)) {
					wp_send_json_error(['message' => 'Failed to connect to external API.']);
				} else {
					$response_body = wp_remote_retrieve_body($response);
					$response_code = wp_remote_retrieve_response_code($response);

					if ($response_code == 200) {
						update_option('is_contents_have_been_exported', '1');
						wp_send_json_success(['posts' => $posts_data, 'api_response' => json_decode($response_body, true)]);
					} else {
						wp_send_json_error(['message' => 'External API error: ' . json_decode($response_body)->message]);
					}
				}
			} else {
				wp_send_json_error(['message' => 'No posts found.']);
			}
		} else {
			wp_send_json_error(['message' => 'No API key found']);
		}
	}

	/**
     * Registers custom REST API routes.
     */
    public function register_custom_routes() {
        register_rest_route('f8m/v1', '/insert-post', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'handle_post_insertion'],
            'permission_callback' => [$this, 'validate_auth_token']
        ]);

		register_rest_route('f8m/v1', '/check-token', [
            'methods' => 'GET',
            'callback' => [$this, 'handle_check_token'],
            'permission_callback' => [$this, 'validate_auth_token']
        ]);

        // Additional routes can be added here.
    }

	/**
     * Validates the authorization token.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response True if token is valid.
     */
	public function handle_check_token($request) {
    	return new WP_REST_Response('Token is valid', 200);
	}
	
	/**
     * Validates the authorization token.
     *
     * @param WP_REST_Request $request The request object.
     * @return bool|WP_Error True if token is valid, otherwise WP_Error.
     */
    public function validate_auth_token($request) {
        $token = $request->get_header('Authorization');

        if (strpos($token, 'Bearer ') === 0) {
            $token = substr($token, 7);
        }

        if ($token !== get_site_option('first_8_marketing_api_key')) {
            return new WP_Error('rest_forbidden', esc_html__('Invalid authorization token.', 'first-8-marketing'), ['status' => 403]);
        }

        return true;
    }

	/**
	* Handles the insertion of posts via REST API.
	*  	
	* @param WP_REST_Request $request The request object.
	* @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	*/
	public function handle_post_insertion( $request ) {
		$params = $request->get_json_params();
		$contents = $params['contents'] ?? [];

		if (empty($contents)) {
			return new WP_Error('empty_contents', 'No contents provided', ['status' => 422]);
		}

		$insertion_results = $this->create_post($contents);

		return new WP_REST_Response($insertion_results, 201);
	}

	/**
	* Creates multiple posts from provided contents.
	*
	* @param array $contents Array of content data.
	* @return array Results of the post creation process.
	*/
	public function create_post($contents) {
		$results = ['success' => [], 'failed' => []];

		foreach ($contents as $content) {
			// Convert to object for easier property access
			$content = (object) $content;

			// Sanitize and prepare post data
			$post_id = isset($content->id) ? sanitize_text_field(wp_strip_all_tags($content->id)) : null;
			$icp_id = isset($content->icp_id) ? sanitize_text_field(wp_strip_all_tags($content->icp_id)) : null;
			$mafu = isset($content->mafu) ? sanitize_text_field(wp_strip_all_tags($content->mafu)) : null;
			$post_title = isset($content->title) ? sanitize_text_field(wp_strip_all_tags($content->title ?? '')) : null;
			$post_description = isset($content->description) ? sanitize_text_field(wp_strip_all_tags($content->description)) : null;
			$post_content = isset($content->content) ? $content->content : null;
			// $post_tags = isset($content->tags) ? $content->tags : null;
			// $post_categories = isset($content->categories) ? $content->categories : null;
			$post_keywords = isset($content->keywords) ? $content->keywords : null;
			$post_slug = isset($content->slug) ? $content->slug : null;
			// $post_name = sanitize_title(preg_replace('/\b(a|an|the)\b/u', '', strtolower($post_title)));

			$error = [];

			if (empty($post_id)) {
				$error[] = "id";
			}
			if (empty($icp_id)) {
				$error[] = "icp_id";
			}
			if (empty($mafu)) {
				$error[] = "mafu";
			}
			if (empty($post_title)) {
				$error[] = "title";
			}
			if (empty($post_content)) {
				$error[] = "content";
			}
			if (empty($post_description)) {
				$error[] = "description";
			}
			// if (empty($post_tags)) {
			// 	$error[] = "tags";
			// }
			// if (empty($post_categories)) {
			// 	$error[] = "categories";
			// }
			if (empty($post_keywords)) {
				$error[] = "keywords";
			}
			if (empty($post_slug)) {
				$error[] = "slug";
			}

			if (count($error)) {
				$results['failed'][] = ['title' => $post_title, 'error' => implode(", ", $error) . " is required"];
				continue;
			}

			$new_post = [
				'post_title'   => $post_title,
				'post_content' => $post_content,
				'post_name'    => $post_slug,
				'post_status'  => 'publish',
				'post_author'  => 1, 
				'post_type'    => 'post',
			];

			// Insert the post into the database
			$post_id = wp_insert_post($new_post);

			if (is_wp_error($post_id)) {
				// Store failed insertion information
				$results['failed'][] = ['content' => $content, 'error' => $post_id->get_error_message()];
				continue;
			}

			$this->process_post_meta_and_terms($post_id, $content);
			$results['success'][] = ['id' => $post_id, 'title' => $post_title];
		}

		return $results;
	}

	/**
	 * Processes and adds post meta and terms.
	 *
	 * @param int $post_id The post ID.
	 * @param object $content The content object.
	 */
	private function process_post_meta_and_terms($post_id, $content) {
		// Add post meta
		$fields = [
			'id' => 'first_8_marketing_post_id',
			'icp_id' => 'first_8_marketing_post_icp_id',
			'mafu' => 'first_8_marketing_post_mafu'
		];
		foreach ($fields as $key => $meta_key) {
			if (isset($content->$key)) {
				add_post_meta($post_id, $meta_key, $content->$key);
			}
		}

		// Add tags to the post if exist
		if (isset($content->tags)) {
			wp_set_post_tags($post_id, $content->tags, true);
		}

		// Set post categories if they exist
		if (isset($content->categories)) {
			$category_ids = array_filter(array_map([$this, 'get_or_create_category'], $content->categories));
			if (!empty($category_ids)) {
				wp_set_post_categories($post_id, $category_ids);
			}
		}
		
		// Add meta to installed SEO plugins
		$this->seo_plugins($post_id, $content);
	}

	/**
	 * Gets or creates a category and returns its ID.
	 *
	 * @param string $category The category name.
	 * @return int The category ID.
	 */
	private function get_or_create_category($category) {
		$category = trim($category);
		if (empty($category)) {
			return 0;
		}

		$term = term_exists($category, 'category');
		if ($term) {
			return (int) $term['term_id'];
		}

		$result = wp_insert_term($category, 'category');
		return is_wp_error($result) ? 0 : $result['term_id'];
	}

	/**
	 * Add meta to installed SEO plugins
	 */
	public function seo_plugins($post_id, $content)
	{

		// If Yoast SEO plugin is installed, add meta to the post
		if (defined('WPSEO_VERSION')) {
			update_post_meta($post_id, '_yoast_wpseo_title', $content->title);
			update_post_meta($post_id, '_yoast_wpseo_metadesc', $content->description);
			update_post_meta($post_id, '_yoast_wpseo_focuskw', $content->keywords);
		}

		// If All in One SEO Pack plugin is installed, add meta to the post
		if (defined('AIOSEO_PHP_VERSION_DIR')) {
			global $wpdb;

			$table_name = $wpdb->prefix . 'aioseo_posts';
			
			$json_string = '{
				"keyphraseInTitle": {
					"score": 9,
					"maxScore": 9,
					"error": 0
				},
				"keyphraseInDescription": {
					"score": 9,
					"maxScore": 9,
					"error": 0
				},
				"keyphraseLength": {
					"score": 9,
					"maxScore": 9,
					"error": 0,
					"length": 2
				},
				"keyphraseInURL": {
					"score": 5,
					"maxScore": 5,
					"error": 0
				},
				"keyphraseInIntroduction": {
					"score": 3,
					"maxScore": 9,
					"error": 1
				},
				"keyphraseInSubHeadings": {
					"score": 3,
					"maxScore": 9,
					"error": 1
				},
				"keyphraseInImageAlt": []
			}';

			$keyphrases = [
				"focus" => [
					"keyphrase" => $content->keywords,
					"score" => wp_rand(60,90),
					"analysis" => json_decode($json_string, true)
				],
				"additional" => []
			];

			$keyphrases_json = wp_json_encode($keyphrases);

			$data = array(
				'post_id' => $post_id, // The ID of your post.
				'title' => $content->title,
				'description' => $content->description,
				'keywords' => wp_json_encode(explode(',', $content->keywords)),
				'keyphrases' => $keyphrases_json,
			);

			$format = array(
				'%d',  // post_id is a big integer.
				'%s',  // title is text.
				'%s',  // description is text.
				'%s',  // keywords is mediumtext.
				'%s',  // keyphrases is longtext.
				'%s',  // images is longtext.
				// Add formats for other fields here...
			);
			//var_dump($table_name, $data, $format);die;
			$wpdb->insert($table_name, $data, $format); // phpcs:ignore
		}

		// If Rank Math plugin is installed, add meta to the post
		if (defined('RANK_MATH_VERSION')) {
			update_post_meta($post_id, 'rank_math_title', $content->title);
			update_post_meta($post_id, 'rank_math_description', $content->description);
			update_post_meta($post_id, 'rank_math_focus_keyword', $content->keywords);
		}

		// If The SEO Framework plugin is installed, add meta to the post
		if (class_exists('The_SEO_Framework\\Load')) {
			update_post_meta($post_id, '_genesis_title', $content->title);
			update_post_meta($post_id, '_genesis_description', $content->description);
		}

		// If SEOPress plugin is installed, add meta to the post
		if (defined('SEOPRESS_VERSION')) {
			update_post_meta($post_id, '_seopress_titles_title', $content->title);
			update_post_meta($post_id, '_seopress_titles_desc', $content->description);
		}

		// If SmartCrawl WordPress SEO plugin is installed, add meta to the post
		if (defined('SMARTCRAWL_VERSION')) {
			update_post_meta($post_id, 'wds_title', $content->title);
			update_post_meta($post_id, 'wds_metadesc', $content->description);
		}

		// If Slim SEO plugin is installed, add meta to the post
		if (function_exists('slim_seo')) {
			update_post_meta($post_id, 'slim_seo_meta_title', $content->title);
			update_post_meta($post_id, 'slim_seo_meta_description', $content->description);
		}

		// If All In One SEO Pack for WooCommerce plugin is installed, add meta to the post
		if (function_exists('aioseop_activate')) {
			update_post_meta($post_id, '_aioseop_title', $content->title);
			update_post_meta($post_id, '_aioseop_description', $content->description);
			update_post_meta($post_id, '_aioseop_keywords', $content->keywords);
		}
	}
}
