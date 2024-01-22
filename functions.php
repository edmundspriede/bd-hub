
add_action('rest_api_init', 'register_custom_endpoint');

function register_custom_endpoint() {
    register_rest_route('bd/v1', '/update-listing-meta/', array(
        'methods' => 'POST',
        'callback' => 'update_listing_meta',
        'permission_callback' => function () {
            return current_user_can('edit_posts');
        },
    ));
}

function update_listing_meta(WP_REST_Request $request) {

	
	$data = $request->get_json_params(); // Assuming the data for update is sent as JSON
	
	if ($data['id'] && $data['serialized'] ) {
		
		    if ( $data['categories']  )  {
				
				$categs = explode(',',  $data['categories'] );
				foreach ($categs as $k => $v ){
					
					$term = get_term_by('name', $v , 'listing-category', OBJECT);
					
					$terms[]  =$term->term_id;
				}
			}
		    
		
			update_post_meta( $data['id'],  'lp_listingpro_options', $data['serialized']);
		    //$terms = wp_set_post_categories( $data['id'], $terms);
		    wp_set_object_terms($data['id'], $terms, 'listing-category');
		
		
	}  else return new WP_REST_Response(['success' => false, 400]);
	
		
	$listing_serialized = get_post_meta( 1983, 'lp_listingpro_options', true);
	return new WP_REST_Response(['success' => true, "id" => $data['id'],  "serialized" => $listing_serialized, 'cats' => $terms], 200);
	
}

function initiate_emails_webook($form, $args) {
    
    global $wpdb;
    
    $sSQL = "SELECT DISTINCT u.user_email
            FROM wp0y_term_relationships as tr
            JOIN wp0y_posts as p ON tr.object_id = p.ID
            JOIN wp0y_users as u ON p.post_author = u.ID
            WHERE tr.term_taxonomy_id IN ( %d )
        ";
    
	$emails = $wpdb->get_col(
            $wpdb->prepare(
               $sSQL ,   implode("," , $args['categs'])
            )
        );

    if (!empty($emails)) {
        
        $args['emails'] = $emails;
        $args['sql'] = $sSQL;
    }    
     
  
    $json_data = json_encode($args);

    // Your custom webhook URL
    $webhook_url = 'https://n8n.m50.lv:5678/webhook/7e0ba00e-795f-417a-90bf-1aa41d1fe976';
    
   	// Send the data to the webhook endpoint using wp_remote_post
   	$response = wp_remote_post(
       		 $webhook_url,
       		 array(
           		 'headers' => array(
           		     'Content-Type' => 'application/json',
           		 ),
           		 'body' => $json_data,
					'sslverify' => false
        		)
   		 );
		
	
	return true;
}

add_filter( 'jet-form-builder/custom-filter/bd-form-webhook', 'initiate_emails_webook' , 2, 10);
