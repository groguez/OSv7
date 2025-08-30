<?php

require_once ("interfaces/Ecom.php");
require_once (APPPATH."libraries/shopify-api/vendor/autoload.php");

use Shopify\Clients\Graphql;
use Shopify\Context;
use Shopify\Auth\FileSessionStorage;
use Shopify\Exception\HttpRequestException;

class Shopify extends Ecom
{
	private $shopify_store_username;
	private $base_url_api;
	private $api_code_token;
	private $shopify_graphql_client;
	private $inventory_step_count_for_non_integer;
	
	function __construct()
	{
		ini_set('memory_limit', -1);
		parent::__construct();
		$this->api_code_token = $this->config->item('shopify_oauth_token');
		$this->shopify_store_username = $this->config->item('shopify_shop');
		$this->base_url_api = 'https://'.$this->shopify_store_username.'.myshopify.com';
		$this->inventory_step_count_for_non_integer = ($this->config->item('shopify_step_size') === null || $this->config->item('shopify_step_size') === '') ? 1 : $this->config->item('shopify_step_size');
		$this->inventory_step_count_for_non_integer = (float)$this->inventory_step_count_for_non_integer;
		if ($this->api_code_token)
		{
			$this->shopify_graphql_client = new Graphql($this->shopify_store_username.'.myshopify.com', $this->api_code_token);
			$this->load->helper('config');
			
			if (is_cli())
			{
				$_SERVER['CI_PHPPOS_HOST'] = 1;
			}
			
			
			if (get_config_key_shared('shopify_public') && get_config_key_shared('shopify_private'))
			{
				Context::initialize(
					get_config_key_shared('shopify_public'),
					get_config_key_shared('shopify_private'),
					'read_products,write_products,read_customers,write_customers,read_orders,write_orders,read_inventory,write_inventory,read_locations',
					'phppointofsale.com',
					new FileSessionStorage(sys_get_temp_dir()),
					'2025-01',false,false
				);
			}
		}
	}
	
	function needs_more_permissions()
	{
		try
		{
			$return = $this->get_all_publication_channels();
			return count($return) == 0;
		}
		catch(Exception $e)
		{
		}
		return false;
		
	}
	
	function convert_shopify_to_pos_time($shopifyTime, $posTimezone = 'America/New_York') {
		$dt = new DateTime($shopifyTime, new DateTimeZone('UTC'));
		$dt->setTimezone(new DateTimeZone($posTimezone));
		return $dt->format('Y-m-d H:i:s');
	}

	function run_graphql_query(array $payload)
	{
	    $maxRetries = 6;
	    $attempt = 0;
		
		if (!$this->shopify_graphql_client)
		{
			return false;
		}
	    while ($attempt < $maxRetries) {
	        try {
	            $rawResponse = $this->shopify_graphql_client->query($payload);
	            $response    = json_decode($rawResponse->getBody(), true);
	            if (isset($response['extensions']['cost']['throttleStatus'])) {
	                $ts = $response['extensions']['cost']['throttleStatus'];
	                $currentlyAvailable = $ts['currentlyAvailable'] ?? 1000;
	                $restoreRate        = $ts['restoreRate'] ?? 50;
	                // If we're dangerously close to 0, sleep enough to partially replenish
	                if ($currentlyAvailable < 50) {
	                    // e.g. sleep(1) or compute how many seconds you need
	                    // Sleep enough so that restoreRate * seconds >= needed buffer
	                    $sleepSeconds = ceil((50 - $currentlyAvailable) / $restoreRate);
	                    sleep($sleepSeconds);
	                }
	            }

	            // Also check for userErrors or top-level errors if needed
	            if (!empty($response['errors'])) {
	                // Possibly handle them or throw
	                return false;
	            }

	            return $response; // success
	        } catch (HttpRequestException $e) {
	            $statusCode = $e->getCode();
	            if ($statusCode == 429) {
	                // If there's a known `Retry-After` or if we see too many requests
	                sleep(2); // or parse a header, if available
	                $attempt++;
	            } elseif ($statusCode >= 500 && $statusCode < 600) {
	                // Exponential backoff for server errors
	                sleep(pow(2, $attempt));
	                $attempt++;
	            } else {
	                return false;
	            }
	        }
	    }

	    return false;
	}
	
	public function set_access_token($access_token)
	{
	   $this->api_code_token = $access_token;
	}

	public function get_location(){
		$query = <<<GQL
		{
			locations(first: 1) {
				edges {
					node {
						id
						name
						address {
							formatted
						}
					}
				}
			}
		}
GQL;
		$response = $this->run_graphql_query([
			"query" => $query
		]);

		// Check if response is valid and contains at least one location
		if (
			!isset($response['data']['locations']['edges']) ||
			!is_array($response['data']['locations']['edges']) ||
			count($response['data']['locations']['edges']) === 0
		) {
			return null;
		}

		// Extract GID and convert to numeric ID
		$location_id = $response['data']['locations']['edges'][0]['node']['id'];
		$gidParts = explode('/', $location_id); // e.g. "gid://shopify/Location/123456"
		$num_location_id = end($gidParts);

		return $num_location_id;
	}

	private function kill_if_needed()
	{
		if ($this->Appconfig->get_raw_kill_ecommerce_cron())
		{
			if (is_cli())
			{
				echo date(get_date_format().' h:i:s ').': KILLING CRON'."\n";
			}
			
			$this->Appconfig->save('kill_ecommerce_cron',0);
			echo json_encode(array('success' => TRUE, 'cancelled' => TRUE, 'sync_date' => date('Y-m-d H:i:s')));
			$this->save_log();
			die();
		}
	}
	
	
	function check_shopify_paid()
	{
		return $this->config->item('shopify_charge_id');
		
	}

	public function cancel_subscription()
	{
		$charge_id = $this->config->item('shopify_charge_id');

		$mutation = <<<QUERY
			mutation cancelSubscription(\$id: ID!) {
				appSubscriptionCancel(id: \$id) {
					userErrors {
						field
						message
					}
				}
			}
QUERY;

		$variables = ['id' => "gid://shopify/AppSubscription/$charge_id"];
		$this->run_graphql_query(['query' => $mutation, 'variables' => $variables]);
		return TRUE;
	}

	function create_subscription()
	{
		$is_test = (!defined("ENVIRONMENT") or ENVIRONMENT == 'development') ? TRUE: FALSE;

		$mutation = <<<QUERY
			mutation createSubscription(\$name: String!, \$returnUrl: URL!, \$trialDays: Int!, \$test: Boolean!, \$lineItems: [AppSubscriptionLineItemInput!]!) {
				appSubscriptionCreate(name: \$name, returnUrl: \$returnUrl, test: \$test, trialDays: \$trialDays, lineItems: \$lineItems) {
					confirmationUrl
					userErrors {
						field
						message
					}
				}
			}
QUERY;

		$variables = [
			'name' => $this->config->item('branding_short_name')." Shopify",
			'returnUrl' => site_url('ecommerce/shopify_return_url_subscription'),
			'trialDays' => 14,
			'test' => $is_test,
			'lineItems' => [
				[
					'plan' => [
						'appRecurringPricingDetails' => [
							'price' => [
								'amount' => (float)to_currency_no_money(SHOPIFY_PRICE),
								'currencyCode' => 'USD'
							]
						]
					]
				]
			]
		];

		$charge_response = $this->run_graphql_query(['query' => $mutation, 'variables' => $variables]);
		if (isset($charge_response['data']['appSubscriptionCreate']['confirmationUrl']))
		{
			$this->Appconfig->save('shopify_was_cancelled',0);
			redirect($charge_response['data']['appSubscriptionCreate']['confirmationUrl']);
			return TRUE;
		}

		return FALSE;
	}
	
	
		
	//This is a weird function. This is called when updating inventory for an item with variations
	public function save_item_variations($item_id)
	{
		if (!$this->check_shopify_paid())
		{
			$this->log(lang('shopify_not_paid'));
			return;
		}
		$item = $this->get_items_for_ecommerce($item_id)->row();
		$this->save_item($item);
	}

	//TODO This is a weird function.
	//This function will save bits of data directly to e-commerce platform for managing stock or not. It is called in 2 places in application/controllers/Items.php. It uses a woo commerce format; but we can translate it so it works with shopify
	public function update_item_from_phppos_to_ecommerce($item_id, $data = array())
	{
		if (!$this->check_shopify_paid())
		{
			$this->log(lang('shopify_not_paid'));
			return;
		}
		
		if ($data['manage_stock'])
		{
			$quantity = $data['stock_quantity'];
			$item_info = $this->Item->get_info($item_id);
			$tags = implode(',',$this->Tag->get_tags_for_item($item_id));
			$quantity_step = stripos($tags, 'decimal') !== false ? $this->inventory_step_count_for_non_integer : 1;
			
			$inv_data = array();
			
			//This is a variation when we pass in ecommerce_inventory_item_id
			if (isset($data['ecommerce_inventory_item_id']))
			{
				//Update stock level
				$this->db->where('ecommerce_inventory_item_id', $data['ecommerce_inventory_item_id']);
				$this->db->update('item_variations',array('ecommerce_variation_quantity' => (int)$quantity));
				
				$inv_data['inventory_item_id'] = $data['ecommerce_inventory_item_id'];
			}
			else//regular item
			{
				//Update stock level
				$this->db->where('ecommerce_inventory_item_id', $item_info->ecommerce_inventory_item_id);
				$this->db->update('items',array('ecommerce_product_quantity' => (int)$quantity));
				
				$inv_data['inventory_item_id'] = $item_info->ecommerce_inventory_item_id;
			}
			$inv_data['available'] = (int)$quantity/$quantity_step;
			
			$inv_data['location_id'] = $this->config->item('shopify_location_id');

			$mutation = <<<GQL
				mutation setInventory(\$input: InventorySetOnHandQuantitiesInput!) {
					inventorySetOnHandQuantities(input: \$input) {
						userErrors { field message }
					}
				}
GQL;

			$variables = [
				'input' => [
					'reason' => 'correction',
					'setQuantities' => [
						[
							'inventoryItemId' => "gid://shopify/InventoryItem/{$inv_data['inventory_item_id']}",
							'locationId' => "gid://shopify/Location/{$inv_data['location_id']}",
							'quantity' => $inv_data['available'],
						]
					]
				]
			];

			$this->run_graphql_query(['query' => $mutation, 'variables' => $variables]);
			
			$this->update_inventory_policy($item_id);
		}
	}
	private function get_inventory_policy($item_id)
	{
		$item_info = $this->Item->get_info($item_id, FALSE);
		if ($item_info->shopify_item_level_inventory_policy)
		{
			return strtoupper($item_info->shopify_item_level_inventory_policy);				
		}
		
		return 'DENY';
		
	}

	public function update_inventory_policy($item_id)
	{
		$existingProductId = $this->get_ecommerce_product_id_for_item_id($item_id);
		$inventoryPolicy = $this->get_inventory_policy($item_id);
		
		$variables = [
		  "productId" => "gid://shopify/Product/$existingProductId",
		];
		$variations_to_update = array();
		
		$item_variations = $this->get_item_variations_for_ecommerce($item_id, true);
		
		foreach ($item_variations as $item_variation) 
		{
		    $variations_to_update[] = $item_variation['ecommerce_variation_id'];
		}
		
		
		if (empty($item_variations) || count($item_variations) == 0)
		{
			$variations_to_update[] = $this->Item->get_info($item_id)->ecommerce_first_variation_id;
		}
		$variables['variants'] = array(); 
		
		foreach($variations_to_update as $variantId)
		{
			$variables['variants'][] = array(
				"id"=>"gid://shopify/ProductVariant/$variantId",
				"inventoryPolicy" => $inventoryPolicy,
			);
		}
		
		$query = <<<QUERY
		  mutation productVariantsBulkUpdate(\$productId: ID!, \$variants: [ProductVariantsBulkInput!]!) {
		    productVariantsBulkUpdate(productId: \$productId, variants: \$variants) {
		      product {
		        id
		      }
		      productVariants {
		        id
				inventoryPolicy
		        metafields(first: 2) {
		          edges {
		            node {
		              namespace
		              key
		              value
		            }
		          }
		        }
		      }
		      userErrors {
		        field
		        message
		      }
		    }
		  }
QUERY;

		$this->run_graphql_query(['query' => $query, 'variables' => $variables]);	
	}
	
	
	
	private function get_all_publication_channels()
	{
		$pub_channel_ids = array();
		
		$after = '';
		
		$query = <<<QUERY
		    query(\$first: Int!) {
		      publications(first: \$first) {
		        edges {
		          node {
		            id
		          }
		        }
		      }
		    }
QUERY;

		$response = $this->run_graphql_query(["query" => $query,'variables' => array('first' => 100)]);
		if(!$response) return array();

		$this->covertIdsToNumber($response);
		$return = array();
		
		foreach($response['data']['publications']['edges'] as $pub)
		{
			$return[] = $pub['node']['id'];
		}
		
		return $return;
	}
	
	function publish_item_to_channel($shopify_product_id, $shopify_pub_channel_id)
	{
		$query = <<<QUERY
		  mutation publishablePublish(\$id: ID!, \$input: [PublicationInput!]!) {
		    publishablePublish(id: \$id, input: \$input) {
		      publishable {
		        availablePublicationsCount {
		          count
		        }
		        resourcePublicationsCount {
		          count
		        }
		      }
		      shop {
		        publicationCount
		      }
		      userErrors {
		        field
		        message
		      }
		    }
		  }
QUERY;

		$variables = [
		  "id" => "gid://shopify/Product/$shopify_product_id",
		  "input" => [
		    "publicationId" => "gid://shopify/Publication/$shopify_pub_channel_id",
		  ],
		];

		$response = $this->run_graphql_query(["query" => $query, "variables" => $variables]);
	}
		
	public function save_item($item)
	{
	    // 1) Check subscription/payment
	    if (!$this->check_shopify_paid())
	    {
	        $this->log(lang('shopify_not_paid'));
	        return;
	    }

	    // 2) Honor kill flag
	    $this->kill_if_needed();

	    $this->log(lang('common_save') . ': ' . $item->name);

	    $item_id = $item->item_id;
		$inventory_policy = $this->get_inventory_policy($item_id);
		$possible_attributes_and_values_for_item = $this->get_possible_attributes_and_values_for_item($item_id);
	    $data = $this->make_product_data($item);  // but remove the “variants” portion from the “product” index
	    $item_variations = $this->get_item_variations_for_ecommerce($item_id, true);

		// check can convert pos item variation to shopify item variation
		// Rule: in Shopify, you cannot use duplicate option names for a single product.
		$item_variation_id_attribute_name_and_value = array();
		foreach ($item_variations as $item_variation) {
			$attribute_name_list = array();
			$str_attribute_name_and_value = "";
		    foreach ($item_variation['attributes'] as $attribute) {
				if(in_array($attribute['attribute_name'], $attribute_name_list))
				{
					$this->log(lang('common_save') . ': ' . $item->name . ' ' . 'variation duplicate attribute error :'. $attribute['attribute_name']);
					return false;
				}
				else
				{
					$attribute_name_list[] = $attribute['attribute_name'];
					if($str_attribute_name_and_value == ""){
						$str_attribute_name_and_value = $attribute['attribute_name'].':'.$attribute['attribute_value_name'];
					} else {
						$str_attribute_name_and_value .= ', ' . $attribute['attribute_name'].':'.$attribute['attribute_value_name'];
					}
				}
			}
			$item_variation_id_attribute_name_and_value[$item_variation['id']] = $str_attribute_name_and_value;
		}

		// Let’s parse out essential fields:
	    $productArray = $data['product'];
		$product_status = 'ACTIVE';
			
		if ($productArray['status'])
		{
			$product_status = strtoupper($productArray['status']);
		}
		else
		{
			if ($item->item_inactive)
			{
				$product_status = 'ACTIVE';
			}
			else
			{
				$product_status = 'ARCHIVED';
			}
		}
		
		
	    // Clean up from old approach: in modern GraphQL, the correct field for description is “descriptionHtml”
	    // and we do NOT pass “variants” in the ProductInput. So we rename or remove them.
	    $productInput = [
	        'productSet' => [
				'title'           => $productArray['title'] ?? $item->name,
	        	'descriptionHtml' => $productArray['body_html'] ?? '',
	        	'status'          => $product_status,
	        	'tags'            => $productArray['tags'],
			],
			'synchronous'     => TRUE,
	    ];
		
		$quantity_step = stripos($productArray['tags'], 'decimal') !== false ? $this->inventory_step_count_for_non_integer : 1;
		
		$options = array();
		
		foreach($possible_attributes_and_values_for_item as $attr)
		{
			$attr_entry = array();
			
			$attr_entry['name'] = $attr['name'];
			$attr_entry['values'] = array();
			
			foreach($attr['values'] as $val)
			{
				$attr_entry['values'][] = array('name' => $val);
			}
			
			
			$options[] = $attr_entry;
		}
		
		
	    // If you have “vendor”, “product_type” or other recognized fields:
	    if (!empty($productArray['vendor'])) {
	        $productInput['productSet']['vendor'] = $productArray['vendor'];
	    }
	    if (!empty($productArray['product_type'])) {
	        $productInput['productSet']['productType'] = $productArray['product_type'];
	    }
	    // DO NOT set 'variants' in productInput—Shopify GraphQL no longer supports that approach.

	    // See if this item is NEW or an UPDATE
	    $existingProductId = $this->get_ecommerce_product_id_for_item_id($item_id);
	    $is_new_product    = !$existingProductId;

        // productCreate or update
        $mutationSet = <<<GQL
		mutation createProductAndVariants(\$productSet: ProductSetInput!, \$synchronous: Boolean!) {
		    productSet(synchronous: \$synchronous, input: \$productSet) {
		      product {
		        id
		        title
				media(first: 20) {
			        nodes {
			          id
			          alt
			          mediaContentType
			          status
			        }
			      }
				updatedAt
		        options(first: 5) {
		          name
		          position
		          optionValues {
		            name
		          }
		        }
		        variants(first: 100) {
		          nodes {
	  		        id
	  		        title
		            price
					media(first: 20) {
			            nodes {
			              id
			              alt
			              mediaContentType
			              status
			            }
			          }
				    inventoryQuantity
					inventoryPolicy
		            inventoryItem {
					   id
					   tracked
					}
		            selectedOptions {
		              name
		              optionValue {
		                id
		                name
		              }
		            }
		          }
		        }
		      }
		      userErrors {
		        field
		        message
		      }
		    }
		  }
GQL;

		$variants = []; // Initialize an empty array for variants
		$item_variation_images = array();
		$k = 0;
		foreach ($item_variations as $item_variation) {
		    $optionValues = [];

		    // Loop through the attributes to build optionValues
		    foreach ($item_variation['attributes'] as $attribute) {
		        $optionValues[] = [
		            "optionName" => $attribute['attribute_name'],
		            "name" => $attribute['attribute_value_name'],
		        ];
		    }

		    // Create the variant
		    $variant = [
		        "optionValues" => $optionValues,
		        "price" => (float)$data['product']['variants'][$k]['price'],
		    ];
			
			
			if (isset($data['product']['variants'][$k]['compare_at_price']) && $data['product']['variants'][$k]['compare_at_price'])
			{
				$variant['compareAtPrice'] = (float)$data['product']['variants'][$k]['compare_at_price'];
			}
			else
			{
				$variant['compareAtPrice'] = NULL;
			}
			
			
			if(isset($data['product']['inventory_management']) && $data['product']['inventory_management'])
			{
				$variant['inventoryItem']['tracked'] = TRUE;
			}
		
			if (isset($data['product']['inventory_policy']))
			{
				$variant['inventoryPolicy'] = $inventory_policy;
			}
			
			if (isset($data['product']['variants'][0]['weight_unit']))
			{
				$variant['inventoryItem']['measurement']['weight']['unit'] = $data['product']['variants'][0]['weight_unit'];
				$variant['inventoryItem']['measurement']['weight']['value'] = (float)$data['product']['variants'][0]['weight'];
			}
			
			if (isset($data['product']['variants'][$k]['cost']) || isset($data['product']['variants'][0]['cost']))
			{
				$variant['inventoryItem']['cost'] = (float)$data['product']['variants'][$k]['cost'] ?? $data['product']['variants'][0]['cost'];
			}
			
			$variant["inventoryQuantities"] = [["locationId"=>"gid://shopify/Location/".$this->config->item('shopify_location_id'), "name"=>"available", "quantity"=>(int)$this->get_item_variation_quantity($item_variation['id'])/$quantity_step]];
			
			
		    $item_variation_images = $this->get_item_variation_images_for_ecommerce($item_variation['id']);
			
			if (count($item_variation_images))
			{
				$item_variation_image = $item_variation_images[0];
		        $url = shopify_app_file_url($item_variation_image['image_id']); // a function you’d implement
				
		        $variant['file'] = [
		            'filename' => clean_filename($url),
		            'originalSource'   => $url,
		            'contentType' => 'IMAGE',
		            'alt' => 'LocalID_' . $item_variation_image['image_id'] . ' ' . ($item_variation_image['alt_text'] ?? ''),
					'duplicateResolutionMode' => 'APPEND_UUID',
					
		        ];
				
			}
			
			
		    // Append to the variants array
		    $variants[] = $variant;
			$k++;
		}
		
		
		if (empty($variants) || count($variants) == 0)
		{
			$productInput['productSet']['variants'] = array();
			$productInput['productSet']['variants'][0]['price'] = (float)$data['product']['variants'][0]['price'];
			if (isset($data['product']['variants'][0]['compare_at_price']) && $data['product']['variants'][0]['compare_at_price'])
			{
				$productInput['productSet']['variants'][0]['compareAtPrice'] = (float)$data['product']['variants'][0]['compare_at_price'];
			}
			else
			{
				$productInput['productSet']['variants'][0]['compareAtPrice'] = NULL;
			}
			
			if (isset($data['product']['variants'][0]['sku']) && $data['product']['variants'][0]['sku'])
			{
				$productInput['productSet']['variants'][0]['sku'] = $data['product']['variants'][0]['sku'];
			}
			
			if (isset($data['product']['variants'][0]['barcode']) && $data['product']['variants'][0]['barcode'])
			{
				$productInput['productSet']['variants'][0]['barcode'] = $data['product']['variants'][0]['barcode'];
			}
			
			
			if (isset($data['product']['variants'][0]['weight_unit']))
			{
				$productInput['productSet']['variants'][0]['inventoryItem']['measurement']['weight']['unit'] = $data['product']['variants'][0]['weight_unit'];
				$productInput['productSet']['variants'][0]['inventoryItem']['measurement']['weight']['value'] = (float)$data['product']['variants'][0]['weight'];
			}
			$productInput['productSet']['productOptions'] = array(['name' => 'Title', 'values' => ['name' => 'Default Title']]);
			$productInput['productSet']['variants'][0]['optionValues'] = array(['name' => 'Default Title', 'optionName' => 'Title']);

			if(isset($data['product']['inventory_management']) && $data['product']['inventory_management'])
			{
				$productInput['productSet']['variants'][0]['inventoryItem']['tracked'] = TRUE;
			}

			if (isset($data['product']['inventory_policy']))
			{
				$productInput['productSet']['variants'][0]['inventoryPolicy'] = $data['product']['inventory_policy'];
			}
			$productInput['productSet']['variants'][0]["inventoryQuantities"] = [["locationId"=>"gid://shopify/Location/".$this->config->item('shopify_location_id'), "name"=>"available", "quantity"=>(int)$this->get_item_quantity($item_id)/$quantity_step]];
			if (isset($data['product']['variants'][0]['cost']))
			{
				$productInput['productSet']['variants'][0]['inventoryItem']['cost'] = (float)$data['product']['variants'][0]['cost'];
			}
				
			
		}
		
	    $item_images = $this->get_all_item_images_for_ecommerce_with_main_image_1st($item_id, TRUE);

	    // Build "media" array
	    $files_array = [];
	    foreach ($item_images as $img) 
		{
		        $url = shopify_app_file_url($img['image_id']); // a function you’d implement
		        $files_array[] = [
		            'filename' => clean_filename($url),
		            'originalSource'   => $url,
		            'contentType' => 'IMAGE',
		            'alt' => 'LocalID_' . $img['image_id'] . ' ' . ($img['alt_text'] ?? ''),
					'duplicateResolutionMode' => 'APPEND_UUID',
		        ];
	    }
		
		
		if (count($files_array))
		{
			$productInput['productSet']['files'] = $files_array;
		}
		if(!empty($options))
		{
			$productInput['productSet']['productOptions'] = $options;
			$productInput['productSet']['variants'] = $variants;
		}
		
		if ($existingProductId)
		{
			$productInput['productSet']['id'] = "gid://shopify/Product/{$existingProductId}";
		}
		
        $response  = $this->run_graphql_query(['query' => $mutationSet, 'variables' => $productInput]);
		$productData = $response['data']['productSet']['product'];
        // Parse out numeric product ID
        $gidParts         = explode('/', $productData['id']); // e.g. "gid://shopify/Product/1234567"
        $shopifyNumericId = end($gidParts);
		
        // Link the product in your DB (no variant ID or inventory item ID yet)
        $quantity_for_main_variant = (int)($productArray['ecommerce_product_quantity'] ?? 0);
		
        // We'll store $existingProductId for the variant step
        $existingProductId = $shopifyNumericId;
		
		$inventory_item_id_gid = $response['data']['productSet']['product']['variants']['nodes'][0]['inventoryItem']['id'];
        $gidParts         = explode('/', $inventory_item_id_gid); // e.g. "gid://shopify/Product/1234567"
        $inventory_item_id = end($gidParts);
		
		$first_variant_id_gid = $response['data']['productSet']['product']['variants']['nodes'][0]['id'];
        $gidParts         = explode('/', $first_variant_id_gid); // e.g. "gid://shopify/Product/1234567"
        $first_variant_id = end($gidParts);
		
		$this->link_item($item_id, $existingProductId, $quantity_for_main_variant, date('Y-m-d H:i:s',strtotime($response['data']['productSet']['product']['updatedAt'])),$inventory_item_id, $first_variant_id);	
		
		if (count($response['data']['productSet']['product']['variants']['nodes']) > 1 || $response['data']['productSet']['product']['variants']['nodes'][0]['title'] != 'Default Title')
		{
			foreach($response['data']['productSet']['product']['variants']['nodes'] as $variation)
			{
				$gidVarParts         = explode('/', $variation['id']);
		        $shopifyVariationNumericId = end($gidVarParts);
				$variation_id = $this->get_variation_id_for_ecommerce_product_variation($shopifyVariationNumericId);
				if(!$variation_id)
				{
					$str_attribute_name_and_value  = "";
					foreach ($variation['selectedOptions'] as $selectedOption)
					{
						if($str_attribute_name_and_value  == ""){
							$str_attribute_name_and_value = $selectedOption['name'] . ':' . $selectedOption['optionValue']['name'];
						} else {
							$str_attribute_name_and_value .= ', ' . $selectedOption['name'] . ':' . $selectedOption['optionValue']['name'];
						}
					}

					foreach($item_variation_id_attribute_name_and_value as $f_variation_id => $e_attribute_name_and_value){
						if($e_attribute_name_and_value == $str_attribute_name_and_value)
						{
							$variation_id = $f_variation_id;
							break;
						}
					}

					if(!$variation_id)//not found
					{
						foreach ($variation['selectedOptions'] as $attribute)
						{
							$attribute_id = $this->get_attribute_id_from_ecommerce_attribute_id(NULL, $item_id,  $attribute['name']);
							
							if (!$attribute_id)
							{
								$attribute_id = $this->get_attribute_id_from_ecommerce_attribute_id(NULL, false,  $attribute['name']);
							}
							
							$attribute_value_ids[] = $this->lookup_attribute_value_id_from_attribute_id_and_option($attribute_id, $attribute['optionValue']['name']);
						}
						//attempt to match with existing variations
						$variation_id = $this->Item_variations->lookup($item_id, $attribute_value_ids);
					}
				}

				if($variation_id){
					$this->link_item_variation($variation_id, $shopifyVariationNumericId, isset($variation_id) ? $item_variations[$variation_id]['quantity'] : 0, strtotime($response['data']['productSet']['product']['updatedAt']),$variation['inventoryItem']['id']);
				} else {
					$this->log( lang('common_save') . ': variation not found :'. $variation['id'] );
				}
			}
		}
		
		foreach($this->get_all_publication_channels() as $channel)
		{
			$this->publish_item_to_channel($shopifyNumericId, $channel);
		}
		
		
		if (count($files_array) || count($item_variation_images))
		{
			sleep(5);
			
			$image_prod_response = $this->get_graphql_from_product_id($shopifyNumericId);
			
			$productData = $image_prod_response['data']['product'];
			$mediaEdges  = $productData['images']['edges'] ?? [];
			$variantData = $productData['variants']['nodes'] ?? [];
			// Now parse product-level media
			foreach ($mediaEdges as $edge) {
			    $imageObject   = $edge['node']?? null;
			    $imageId       = $imageObject['id'] ?? null;   // "gid://shopify/Image/9999"
			    $imageUrl      = $imageObject['url'] ?? '';
			    $imageAlt      = $imageObject['altText'] ?? '';
				$matches = array();
			    preg_match('/LocalID_(\d+)/', $imageAlt, $matches);
		        if (!empty($matches[1])) 
			    {
		           $localImageId = $matches[1];
				   $imageIdParts         = explode('/', $imageId);
		           $imageIdNumeric = end($imageIdParts);
				   
				   
	   			   $this->Item->link_image_to_ecommerce($localImageId, $imageIdNumeric);
		        }
			}

			// Then parse each variant’s media
			foreach ($variantData as $variant) 
			{
			    $variantId   = $variant['id']; // "gid://shopify/ProductVariant/2222"
			    $mediaEdgesV = $variant['media']['edges'] ?? [];

			    foreach ($mediaEdgesV as $vEdge) 
				{
			        $vImageObj    = $vEdge['node'] ?? null;
			        $vImageId     = $vImageObj['id'] ?? null;  // "gid://shopify/Image/7777"
			        $vImageUrl    = $vImageObj['url'] ?? '';
			        $vAlt         = $vImageObj['altText'] ?? '';
					$matches = array();
				    preg_match('/LocalID_(\d+)/', $vAlt, $matches);
			        if (!empty($matches[1])) 
				    {
			           $localImageId = $matches[1];
					   $imageIdParts         = explode('/', $vImageId);
			           $vImageIdNumeric = end($imageIdParts);
					   
		   			   $this->Item->link_image_to_ecommerce($imageIdNumeric, $vImageIdNumeric);
			        }
			    }
			}
		}

	    return $existingProductId;
	}
		
	private function get_possible_attributes_and_values_for_item($item_id)
	{
		$possible_attributes_and_values_for_item = $this->Item_attribute->get_attributes_for_item_with_attribute_values($item_id);
		
		$options_for_item = array();
		
		foreach(array_values($possible_attributes_and_values_for_item) as $pos_attribute)
		{
			$values = array_values(array_map(function($val) {
			    return $val['name'];
			}, $pos_attribute['attr_values']));
			
			$options_for_item[] = array('name' => $pos_attribute['name'], 'values' => $values);
		}
		
		return $options_for_item;
		
	}

	public function save_item_from_phppos_to_ecommerce($item_id)
	{	
		if (!$this->check_shopify_paid())
		{
			$this->log(lang('shopify_not_paid'));
			return;
		}
				
		$this->log(lang("save_item_from_phppos_to_ecommerce").' '. $item_id);

		try
		{	
			$item = $this->get_items_for_ecommerce($item_id)->row();
			if($item)
				$this->save_item($item);

		}
		catch (Exception $e)
		{
			$this->log($e->getMessage());
		}
		
	}

	function get_tags($use_cache = TRUE)
	{
		if (!$this->check_shopify_paid())
		{
			$this->log(lang('shopify_not_paid'));
			return;
		}
		
		//No need to sync tags in shopify as it is just CSV for an item
	}

	function get_categories($use_cache = TRUE)
	{
		if (!$this->check_shopify_paid())
		{
			$this->log(lang('shopify_not_paid'));
			return;
		}
		
		//No need to sync categories in shopify as it is just a string
	}
	
	//TODO TEST
	function sync_inventory_changes()
	{
	    if (!$this->check_shopify_paid())
	    {
	        $this->log(lang('shopify_not_paid'));
	        return;
	    }

	    $this->log(lang("sync_inventory_changes"));

	    $this->process_shopify_products('process_sync_inventory_changes');
	}
	
	//TODO TEST
	function process_sync_inventory_changes($response)
	{
		error_reporting(E_ALL);
		ini_set('display_errors', 'On');
		if (!$this->check_shopify_paid())
		{
			$this->log(lang('shopify_not_paid'));
			return;
		}
		
		if ($response === FALSE)
		{
			return;
		}
		
		$result_products = $response['data']['products']['edges'];
		
		$shopify_product_ids = array(-1);
		$shopify_variation_ids = array(-1);
		
		foreach($result_products as $shopify_product)
		{
			
			//Regular non variant products
			if (count($shopify_product['node']['variants']['edges']) > 1 || $shopify_product['node']['variants']['edges'][0]['node']['title'] != 'Default Title')
			{
				foreach($shopify_product['node']['variants']['edges'] as $variant)
				{
					$shopify_variation_ids[] = $variant['node']['id'];
				}
			}
			else
			{
				$shopify_product_ids[] = $shopify_product['node']['id'];
			}
		}

		$this->db->select('items.*,SUM(phppos_location_items.quantity) as quantity', FALSE);
		$this->db->from('items');
		$this->db->join('location_items','items.item_id = location_items.item_id','left');
		$this->db->where('items.deleted',0);
		$this->db->where_in('location_id',$this->ecommerce_store_locations);
		$this->db->where_in('ecommerce_product_id', $shopify_product_ids);
		$this->db->group_by('items.item_id');
		$items_result = $this->db->get();

		$items_info = array();
		foreach($items_result->result_array() as $item_result)
		{
			$items_info[$item_result['ecommerce_product_id']] = $item_result;
		}
				
		$this->db->select('item_variations.*,SUM(phppos_location_item_variations.quantity) as quantity', FALSE);
		$this->db->from('item_variations');
		$this->db->where('item_variations.deleted',0);
		$this->db->join('location_item_variations','item_variations.id = location_item_variations.item_variation_id','left');
		$this->db->where_in('location_id',$this->ecommerce_store_locations);
		$this->db->where_in('ecommerce_variation_id', $shopify_variation_ids);
		$this->db->group_by('item_variations.id');
		$items_variation_result = $this->db->get();

		$item_varations_info = array();
		foreach($items_variation_result->result_array() as $item_variation_result)
		{
			$item_varations_info[$item_variation_result['ecommerce_variation_id']] = $item_variation_result;
		}
		
		foreach($result_products as $shopify_product)
		{
			$quantity_step = stripos($shopify_product['node']['tags'], 'decimal') !== false ? $this->inventory_step_count_for_non_integer : 1;
			
			//Variant product
			if (count($shopify_product['node']['variants']['edges']) > 1 || $shopify_product['node']['variants']['edges'][0]['node']['title'] != 'Default Title')
			{
				
				foreach($shopify_product['node']['variants']['edges'] as $shopify_variation)
				{
					
					if (isset($item_varations_info[$shopify_variation['node']['id']]))
					{
						@$item_id=$item_varations_info[$shopify_variation['node']['id']]['item_id'];
						
						$item_quantity=$shopify_quantity="";
						$shopify_quantity=$shopify_variation['node']['inventoryQuantity']*$quantity_step;
						@$item_variation_id=$item_varations_info[$shopify_variation['node']['id']]['id'];
						if($item_variation_id!=NULL)
						{
							$item_quantity=$item_varations_info[$shopify_variation['node']['id']]['quantity'];
						}
						if($item_quantity==="" && $shopify_quantity==="")
						{
							//quantity field not available in shopifycommerce and phppos
							$actual_quantity=0;
						}
						else if($item_quantity==="")
						{
							//quantity field not available in phppos but available in shopifycommerce
							$actual_quantity=$shopify_quantity;
						}
						else if($shopify_quantity==="")
						{
							//quantity field not available in shopifycommerce but available in phppos
							$actual_quantity=$item_quantity;
						}
						else
						{
							//quantity field present both on shopifycommerce and phppos
							$prev_quantity=   $item_varations_info[$shopify_variation['node']['id']]['ecommerce_variation_quantity'];
							$pos_difference = $prev_quantity - $item_quantity;
							$shopify_difference = $prev_quantity - $shopify_quantity;
							$difference_sum	= $pos_difference + $shopify_difference;
							$actual_quantity = $prev_quantity - $difference_sum;
						}


						@$the_ecommerce_quantity = $item_varations_info[$shopify_variation['node']['id']]['ecommerce_variation_quantity']; 
						if ($actual_quantity != $the_ecommerce_quantity)
						{
							$this->db->where('ecommerce_variation_id', $shopify_variation['node']['id']);
							$this->db->update('item_variations',array('ecommerce_variation_quantity' => (int)$actual_quantity));
						}

						//update quantity to shopifycommerce
						if( $actual_quantity != $shopify_quantity )
						{
							$stock_set = array('ecommerce_inventory_item_id' => $item_varations_info[$shopify_variation['node']['id']]['ecommerce_inventory_item_id'],'manage_stock' => TRUE, 'stock_quantity' => (int)$actual_quantity);							
							$this->update_item_from_phppos_to_ecommerce($item_id,$stock_set);
							
						}
						//update quantity to phppos
						if( $actual_quantity != $item_quantity)
						{
							$difference = (int)$actual_quantity - (int)$item_quantity;
							$current_location_quantity= $this->Item_variation_location->get_location_quantity($item_variation_id,$this->ecommerce_store_location);
							$updated_quantity = $current_location_quantity + $difference;

							if($item_variation_id!=NULL && $difference!=0){
							$cron_job_entry=lang('shopify_cron_job_entry');
							$this->db->insert('inventory', 
								array(
									'trans_date'=>date('Y-m-d H:i:s'),
									'trans_current_quantity' => $updated_quantity,
									'trans_items' => $item_varations_info[$shopify_variation['node']['id']]['item_id'],
									'item_variation_id' => $item_varations_info[$shopify_variation['node']['id']]['id'],
									'trans_user'=>1,
									'trans_comment'=>$cron_job_entry,
									'trans_inventory'=> $difference,'location_id'=>$this->ecommerce_store_location
								)
							);

							$this->db->where(array('item_variation_id' => $item_variation_id,'location_id'=>$this->ecommerce_store_location));
							$this->log(lang("common_item_inventory_changed_in_system").' '.$item_variation_id .' ('.$updated_quantity.')');
							$this->db->update('location_item_variations',array('quantity'=>$updated_quantity));

							}
						}
					}
				}				
			}
			else //Regular non variant product
			{

				$item_quantity=$shopify_quantity="";
				$shopify_quantity=$shopify_product['node']['variants']['edges'][0]['node']['inventoryQuantity']*$quantity_step;
				
				@$item_id=$items_info[$shopify_product['node']['id']]['item_id'];
				if($item_id!=NULL)
				{
					$item_quantity=$items_info[$shopify_product['node']['id']]['quantity'];
				}
				if($item_quantity==="" && $shopify_quantity==="")
				{
					//quantity field not available in shopifycommerce and phppos
					$actual_quantity=0;
				}
				else if($item_quantity==="")
				{
					//quantity field not available in phppos but available in shopifycommerce
					$actual_quantity=$shopify_quantity;
				}
				else if($shopify_quantity==="")
				{
					//quantity field not available in shopifycommerce but available in phppos
					$actual_quantity=$item_quantity;
				}
				else
				{
					//quantity field present both on shopifycommerce and phppos
					$prev_quantity=   $items_info[$shopify_product['node']['id']]['ecommerce_product_quantity'];
					$pos_difference = $prev_quantity - $item_quantity;
					$shopify_difference = $prev_quantity - $shopify_quantity;
					$difference_sum	= $pos_difference + $shopify_difference;
					$actual_quantity = $prev_quantity - $difference_sum;
				}


				@$the_ecommerce_quantity = $items_info[$shopify_product['node']['id']]['ecommerce_product_quantity'];
				if ($actual_quantity != $the_ecommerce_quantity)
				{
					$this->db->where('ecommerce_product_id', $shopify_product['node']['id']);
					$this->db->update('items',array('ecommerce_product_quantity' => (int)$actual_quantity));
				}

				//update quantity to shopifycommerce
				if( $actual_quantity != $shopify_quantity )
				{
					$stock_set = array('manage_stock' => TRUE, 'stock_quantity' => (int)$actual_quantity);
					$this->update_item_from_phppos_to_ecommerce($item_id,$stock_set);
					$this->log("put : products/".$shopify_product['node']['id']);
					$this->log(lang('item inventory changed in shopify')." ".$shopify_product['node']['id'] .' ('.to_quantity($actual_quantity).')');
				}
				//update quantity to phppos
				if( $actual_quantity != $item_quantity)
				{
					$difference = (int)$actual_quantity - (int)$item_quantity;
					$current_location_quantity= $this->Item_location->get_location_quantity($item_id,$this->ecommerce_store_location);
					$updated_quantity = $current_location_quantity + $difference;;

					if($item_id!=NULL && $difference!=0){
					$cron_job_entry=lang('shopify_cron_job_entry');
					$this->db->insert('inventory',array('trans_date'=>date('Y-m-d H:i:s'),'trans_current_quantity' => $updated_quantity,'trans_items' => $item_id,'trans_user'=>1,'trans_comment'=>$cron_job_entry,'trans_inventory'=> $difference,'location_id'=>$this->ecommerce_store_location));

					$this->db->where(array('item_id' => $item_id,'location_id'=>$this->ecommerce_store_location));
					$this->log(lang("common_item_inventory_changed_in_system").' '.$item_id .' ('.$updated_quantity.')');
					$this->db->update('location_items',array('quantity'=>$updated_quantity));

					}
				}
			}
			
		}		
	}
	
	private function make_category($category_id)
	{
		$collection = array();
		$collection['collection']['title'] = $this->Category->get_full_path($category_id);
		$category = (array) $this->Category->get_info($category_id);

		if ($category['category_description'])
		{
			$collection['collection']['descriptionHtml'] = $category['category_description'];
		}

		if ($category['image_id'])
		{
			$url = shopify_app_file_url($category['image_id']);
			$collection['collection']['image']['src'] = $url;
			$collection['collection']['image']['altText'] = $this->Category->get_full_path($category_id);
		}

		$collection['collection']['ruleSet'] = array(
			'appliedDisjunctively' => FALSE,
			'rules' => array(
				array(
					'column' => 'TYPE',
					'relation' => 'EQUALS',
					'condition' => $this->Category->get_full_path($category_id),
				),
			),
		);
		
		return $collection;
		
	}

	public function check_collection_exists($collection_title) {
		$escaped_title = addslashes($collection_title);
	
		$query = <<<GQL
			query {
				collections(first: 1, query: "title:'$escaped_title'") {
					edges {
						node {
							id
							title
						}
					}
				}
			}
		GQL;
	
		$response = $this->run_graphql_query(['query' => $query]);
	
		if(!empty($response['data']['collections']['edges'])){
			return $response;
		}
		return NULL;
	}

	public function save_category($category_id)
	{
		if (!$this->check_shopify_paid())
		{
			$this->log(lang('shopify_not_paid'));
			return;
		}

		$category_title = $this->Category->get_full_path($category_id);
		$exist_ecommerce_collection = $this->check_collection_exists($category_title);
		if($exist_ecommerce_collection){
			$gid = $exist_ecommerce_collection['data']['collections']['edges'][0]['node']['id'];
			$parts = explode('/', $gid);
			$ecommerce_category_id = end($parts);
			$this->link_category($category_id, $ecommerce_category_id);
			return;
		}

		$collection = $this->make_category($category_id);

		$mutation = <<<GQL
			mutation createCollection(\$input: CollectionInput!) {
				collectionCreate(input: \$input) {
					collection { id }
					userErrors { field message }
				}
			}
GQL;

		$response = $this->run_graphql_query(['query' => $mutation, 'variables' => ['input' => $collection['collection']]]);
		if (isset($response['data']['collectionCreate']['collection']['id'])) {
			$gid = $response['data']['collectionCreate']['collection']['id'];
			$parts = explode('/', $gid);
			$ecommerce_category_id = end($parts);
			$this->link_category($category_id, $ecommerce_category_id);
		}
	}

	public function update_category($category_id)
	{
		if (!$this->check_shopify_paid())
		{
			$this->log(lang('shopify_not_paid'));
			return;
		}
		
		$category = $this->Category->get_info($category_id);
		if (!$category->ecommerce_category_id)
		{
			$this->save_category($category_id);
			return;
		}
		
		$collection = $this->make_category($category_id);

		$mutation = <<<GQL
			mutation collectionUpdate(\$input: CollectionInput!) {
				collectionUpdate(input: \$input) {
					collection { id }
					userErrors { field message }
				}
			}
GQL;

		$collection['collection']['id'] = "gid://shopify/Collection/{$category->ecommerce_category_id}";
		$variables = [
			'input' => $collection['collection']
		];

		$this->run_graphql_query(['query' => $mutation, 'variables' => $variables]);
	}

	public function delete_category($category_id)
	{
		if (!$this->check_shopify_paid())
		{
			$this->log(lang('shopify_not_paid'));
			return;
		}
		
		$category = $this->Category->get_info($category_id);

		$mutation = <<<GQL
			mutation deleteCollection(\$id: ID!) {
				collectionDelete(id: \$id) {
					deletedCollectionId
					userErrors { field message }
				}
			}
GQL;
		$variables = ['id' => "gid://shopify/Collection/{$category->ecommerce_category_id}"];
		$this->run_graphql_query(['query' => $mutation, 'variables' => $variables]);
	}

	public function save_tag($tag_name)
	{
		if (!$this->check_shopify_paid())
		{
			$this->log(lang('shopify_not_paid'));
			return;
		}
		
		//No need to do in shopify

	}

	public function delete_tag($tag_id)
	{
		if (!$this->check_shopify_paid())
		{
			$this->log(lang('shopify_not_paid'));
			return;
		}
		
		//No need to do in shopify
	}

	public function export_phppos_categories_to_ecommerce($root_category_id = null)
	{
		if (!$this->check_shopify_paid())
		{
			$this->log(lang('shopify_not_paid'));
			return;
		}
		
		//Categories --> Smart Collections
		
		foreach($this->Category->get_all_for_ecommerce() as $category_id => $category)
		{  		
			$this->log($this->Category->get_full_path($category_id));
				
			//New Smart Collection
			if (!$category['ecommerce_category_id'])
			{
				$this->save_category($category_id);
			}
			else
			{
				$this->update_category($category_id);
			}
			
		}
	}

	public function export_phppos_tags_to_ecommerce()
	{
		if (!$this->check_shopify_paid())
		{
			$this->log(lang('shopify_not_paid'));
			return;
		}
		
		//No need to do in shopify
	}

	function export_phppos_items_to_ecommerce()
	{
		if (!$this->check_shopify_paid())
		{
			$this->log(lang('shopify_not_paid'));
			return;
		}
		
		$this->log(lang("export_phppos_items_to_ecommerce"));
		
		//Use these items and export them to shopify data format
		//In pos are items can be varient or non varient. It needs to handle both cases
		//Also needs to handle if the product changes from non varient to varient and visa versa
		$items_to_export_to_shopify = $this->get_items_for_ecommerce();
		
		while ($item = $items_to_export_to_shopify->unbuffered_row('object'))
		{
			if (!$item->deleted)
			{
				$this->save_item($item);
			}
		}
	}
	
	function get_graphql_from_product_id($product_id)
	{
		$query = <<<QUERY
			query getProduct(\$id: ID!) {
				product(id: \$id) {
					id
					title
					description
					descriptionHtml
					productType
					tags
					totalInventory
					createdAt
					updatedAt
					vendor
					handle
					variants(first: 100) {
						edges {
							node {
								id
								title
								selectedOptions {
									name
									optionValue {
										id
										name
									}
								}
								image {
									id
									url
									altText
								}
								price
								compareAtPrice
								inventoryQuantity
								taxable
								inventoryItem {
									id
									measurement {
										weight {
											unit
											value
										}
									}
									unitCost {
										amount
									}
								}
								sku
								barcode
								createdAt
								updatedAt
							}
						}
					}
					images(first: 100) {
						edges {
							node {
								id
								url
								altText
							}
						}
					}
				}
			}
QUERY;
	    $return = $this->run_graphql_query(["query" => $query, 'variables' => ["id" => "gid://shopify/Product/$product_id"]]);
		return $return;
	}
	
	function import_ecommerce_items_into_phppos()
	{
	    if (!$this->check_shopify_paid())
	    {
	        $this->log(lang('shopify_not_paid'));
	        return;
	    }

	    $this->log(lang("import_ecommerce_items_into_phppos"));

	    // Recursive function to handle pagination
	    $this->process_shopify_products('process_import_ecommerce_items_into_phppos');
	}

	private function process_shopify_products($processCallback, $after = null)
	{		
	    $afterCursor = $after ? ", after: \"$after\"" : "";

		$query = <<<QUERY
			query {
				products(first: 5$afterCursor) {
					edges {
						node {
							id
							title
							description
							descriptionHtml
							productType
							tags
							totalInventory
							createdAt
							updatedAt
							vendor
							handle
							variants(first: 100) {
								edges {
									node {
										id
										title
										selectedOptions {
											name
											optionValue {
												id
												name
											}
										}
										image {
											id
											url
											altText
										}
										price
										compareAtPrice
										inventoryQuantity
										taxable
										inventoryItem {
											id
											measurement {
												weight {
													unit
													value
												}
											}
											unitCost {
												amount
											}
										}
										sku
										barcode
										createdAt
										updatedAt
									}
								}
							}
							images(first: 100) {
								edges {
									node {
										id
										url
										altText
									}
								}
							}
						}
						cursor
					}
					pageInfo {
						hasNextPage
						endCursor
					}
				}
			}
QUERY;

	    $response = $this->run_graphql_query(["query" => $query]);
	    $this->covertIdsToNumber($response);
	    // Dynamically call the passed-in callback function for processing items
	    $this->{$processCallback}($response);

	    // Check if there is a next page and recursively fetch more
	    $pageInfo = $response['data']['products']['pageInfo'];
	    if ($pageInfo['hasNextPage']) {
	        $this->process_shopify_products($processCallback, $pageInfo['endCursor']);
	    }
	}	
	public function import_ecommerce_item_into_phppos($shopify_product)
	{
	    if (!$this->check_shopify_paid())
	    {
	        $this->log(lang('shopify_not_paid'));
	        return;
	    }
        $gidParts         = explode('/', $shopify_product['node']['id']); // e.g. "gid://shopify/Product/1234567"
        $shopifyNumericId = end($gidParts);
		
		$this->db->from('items');
		$this->db->where('ecommerce_product_id', $shopifyNumericId);
		$result = $this->db->get();

		$phppos_item = array();
		if($result->num_rows() > 0)
		{
			$phppos_item = $result->row_array();
		}
		
	    $this->covertIdsToNumber($shopify_product);	
		$this->add_update_item_from_ecommerce_to_phppos($shopify_product, $phppos_item);
	}
		
	private function covertIdsToNumber(&$data) {
	    foreach ($data as $key => &$value) {
	        if (is_array($value)) {
	            // Recursive call for nested arrays
	            $this->covertIdsToNumber($value);
	        } elseif ($key === 'id' && is_string($value)) {
	            // Extract numeric part if the key is 'id' and value is a string
	            $parts = explode("/", $value);
	            $value = end($parts); // Replace with numeric ID
	        }
	    }
	}
	
	private function process_import_ecommerce_items_into_phppos($response)
	{
		if (!$this->check_shopify_paid())
		{
			$this->log(lang('shopify_not_paid'));
			return;
		}
		
		if ($response === FALSE)
		{
			return;
		}
		
		$products = $response['data']['products']['edges'];
		
		$ecom_ids = array_map(function($product) {
		    return $product['node']['id'];
		}, $products);
						
		if (!empty($ecom_ids))
		{
			if(is_array($ecom_ids))
			{
				$this->db->from('items');

				$this->db->group_start();
				$ecom_ids_chunk = array_chunk($ecom_ids,25);
				foreach($ecom_ids_chunk as $ecom_ids)
				{
					$this->db->or_where_in('ecommerce_product_id',$ecom_ids);
				}
				$this->db->group_end();
				$result = $this->db->get();

				$phppos_items = array();
				while($row = $result->unbuffered_row('array'))
				{
					$phppos_items[$row['ecommerce_product_id']] = $row;
				}
			}
		}
		
		foreach($products as $product)
		{
			$item_row = isset($phppos_items[$product['node']['id']]) ? $phppos_items[$product['node']['id']] : FALSE;
			$item_id = isset($phppos_items[$product['node']['id']]['item_id']) ? $phppos_items[$product['node']['id']]['item_id'] : FALSE;
		
			$item_last_modified = isset($item_row['last_modified']) ? strtotime($item_row['last_modified']) : 0;
			$ecommerce_last_modified = strtotime($product['node']['updatedAt']);

			if($ecommerce_last_modified > $item_last_modified)
			{
				$inventory_id = $product['node']['variants']['edges'][0]['node']['inventoryItem']['id'];
				$query = <<<GQL
					query getInv(\$id: ID!) {
						inventoryItem(id: \$id) {
							unitCost { amount }
						}
					}
GQL;

				$inventory_item_response = $this->run_graphql_query(['query' => $query, 'variables' => ['id' => $inventory_id]]);
				if ($inventory_item_response && isset($inventory_item_response['data']['inventoryItem']['unitCost']['amount']))
				{
					$product['node']['variants']['edges'][0]['node']['inventoryItem']['unitCost']['amount'] = $inventory_item_response['data']['inventoryItem']['unitCost']['amount'];
				}
				
				for($k=0;$k<count($product['node']['variants']['edges']); $k++)
				{
					if (isset($product['node']['variants']['edges'][$k]['node']['inventoryItem']['id']) && $product['node']['variants']['edges'][$k]['node']['inventoryItem']['id'])
					{
						$inventory_id = $product['node']['variants']['edges'][$k]['node']['inventoryItem']['id'];
						$query = <<<GQL
							query getInv(\$id: ID!) {
								inventoryItem(id: \$id) {
									unitCost { amount }
								}
							}
GQL;
						$inventory_item_response = $this->run_graphql_query(['query' => $query, 'variables' => ['id' => $inventory_id]]);

						if ($inventory_item_response && isset($inventory_item_response['data']['inventoryItem']['unitCost']['amount']))
						{
							$product['node']['variants']['edges'][$k]['node']['inventoryItem']['unitCost']['amount'] = $inventory_item_response['data']['inventoryItem']['unitCost']['amount'];
						}
					}
				}
				$item_id = $this->add_update_item_from_ecommerce_to_phppos($product, $item_row);
				$item_row = (array)$this->Item->get_info($item_id);
			}
		}
	}

	private function add_update_item_from_ecommerce_to_phppos($shopify_product, $item_row = array())
	{
		if (!$this->check_shopify_paid())
		{
			$this->log(lang('shopify_not_paid'));
			return;
		}

		$cur_timezone = date_default_timezone_get();
		$shopify_product_last_modified_time = $this->convert_shopify_to_pos_time($shopify_product['node']['updatedAt'], $cur_timezone);
		if($item_row){
			if($shopify_product_last_modified_time < $item_row['last_modified']){
				$this->log(('Shopify item data has expired.'));
				return;
			}
		}

		$tags_raw = $shopify_product['node']['tags'];
		$has_decimal_tag = false;
		
		if (is_array($tags_raw)) {
			// Search through each tag for a partial match
			$has_decimal_tag = !empty(array_filter($tags_raw, function($tag) {
				return stripos($tag, 'decimal') !== false;
			}));
		} elseif (is_string($tags_raw)) {
			// Direct substring match
			$has_decimal_tag = stripos($tags_raw, 'decimal') !== false;
		}

		$quantity_step = $has_decimal_tag == false ? $this->inventory_step_count_for_non_integer : 1;
		
		$this->log(lang("add_update_item_from_ecommerce_to_phppos").": ".$shopify_product['node']['title']);

		//make sure to save back to the right number field
		$sync_field = $this->config->item('sku_sync_field') ? $this->config->item('sku_sync_field') : 'item_number';
		
		static $phppos_cats;

		if (!$phppos_cats)
		{
			$this->load->model('Category');
			$phppos_cats = $this->Category->get_all_categories_and_sub_categories_as_indexed_by_name_key(FALSE);
		}

		static $suppliers;

		if (!$suppliers)
		{
			$this->load->model('Supplier');
			foreach($this->Supplier->get_all()->result_array() as $supplier_row)
			{
				if (isset($supplier_row['company_name']) && $supplier_row['company_name'])
				{
					$suppliers[$supplier_row['company_name']] = $supplier_row['person_id'];
				}
		
				if (isset($supplier_row['first_name']) && $supplier_row['first_name'])
				{
					$suppliers[$supplier_row['first_name'].' '.$supplier_row['last_name']] = $supplier_row['person_id'];
				}
			}
		}
		
		
		$item_id = isset($item_row['item_id']) ? $item_row['item_id'] : false;
		$product_name = $shopify_product['node']['title'];
		$weight = $shopify_product['node']['variants']['edges'][0]['node']['inventoryItem']['measurement']['weight']['value'];
		$weight_unit = $shopify_product['node']['variants']['edges'][0]['node']['inventoryItem']['measurement']['weight']['unit'];
		$product_id = $shopify_product['node']['id'];
		$quantity = $shopify_product['node']['variants']['edges'][0]['node']['inventoryQuantity']*$quantity_step;
		
		//We only want to save item number if we have no variants (which means 1 variants as all things are variants in shopify)
		$item_number = $shopify_product['node']['variants']['edges'][0]['node']['sku'] && count($shopify_product['node']['variants']['edges']) == 1 ? $shopify_product['node']['variants']['edges'][0]['node']['sku'] : FALSE;
		$barcode = $shopify_product['node']['variants']['edges'][0]['node']['barcode'] && count($shopify_product['node']['variants']['edges']) == 1 ? $shopify_product['node']['variants']['edges'][0]['node']['barcode'] : FALSE;
		$product_description = $shopify_product['node']['descriptionHtml'] ? $shopify_product['node']['descriptionHtml'] : $shopify_product['node']['description'];
		$product_short_description = $shopify_product['node']['descriptionHtml'] ? $shopify_product['node']['descriptionHtml'] : $shopify_product['node']['description'];
		$last_modified =  $shopify_product_last_modified_time ? $shopify_product_last_modified_time : date('Y-m-d H:i:s');
		$product_category=$shopify_product['node']['productType'];
		$product_variants = $shopify_product['node']['variants']['edges'];
		$product_tags = $shopify_product['node']['tags'];
		$taxable = TRUE;
		
		$inventory_sum = 0;
		
		if (count($product_variants) > 0) {
			foreach ($product_variants as $product_variant) {
				$inventory_sum += $product_variant['node']['inventoryQuantity'];
			}
		}
		if ($inventory_sum == 0) {
			//return;
		}

		if (isset($shopify_product['node']['variants']['edges'][0]['node']['taxable']))
		{
			$taxable = (boolean)$shopify_product['node']['variants']['edges'][0]['node']['taxable'];
		}
		
		if($product_category)
		{
			if (isset($phppos_cats[str_replace(' > ','|',strtoupper($product_category))]) || isset($phppos_cats[strtoupper($product_category)]))
			{
				$product_category = $phppos_cats[strtoupper($product_category)];
			}
			else
			{
				$product_category = $this->Category->save($product_category);
				//We want to do this so the cache gets broken
				$phppos_cats = NULL;
			}
		}
		else
		{
			$product_category = NULL;
		}
		
		
		$item_array = array(
			'name'=>$product_name,
			'description' => $product_short_description,
			'long_description' => $product_description,
			'category_id'=>$product_category,
			'ecommerce_product_id'=>$product_id,
			'ecommerce_inventory_item_id' => $shopify_product['node']['variants']['edges'][0]['node']['inventoryItem']['id'],
			'ecommerce_last_modified' => $last_modified,
			'last_modified' => $last_modified,
			'tax_included' => $this->config->item('prices_include_tax') ? 1 : 0,
			'weight' => $weight,
			'weight_unit' => $weight_unit,
			'override_default_tax' => $taxable ? 0 : 1,
			'ecommerce_first_variation_id' => $shopify_product['node']['variants']['edges'][0]['node']['id'],
		);
		
		
		if ($shopify_product['node']['vendor'])
		{
			if (isset($suppliers[$shopify_product['node']['vendor']]))
			{
				$item_array['supplier_id'] = $suppliers[$shopify_product['node']['vendor']];
			}
			else
			{
				//Make a new supplier and save
				$person_data = array('first_name' => '', 'last_name' => '');
				$supplier_data = array('company_name' => $shopify_product['node']['vendor']);
				$this->Supplier->save_supplier($person_data, $supplier_data);
				$item_array['supplier_id'] = $supplier_data['person_id'];
				$suppliers[$shopify_product['node']['vendor']] = $item_array['supplier_id'];
			}
		}
		//New item
		if (!$item_id)
		{
			$item_array['commission_percent'] = NULL;
			$item_array['commission_fixed'] = NULL;
			$item_array['commission_percent_type'] = '';
		}
		else
		{
			//Don't overwrite category for existing items in case we are using parent/child
			unset($item_array['category_id']);
		}
		
		if ($product_variants[0]['node']['price'] && !$this->config->item('online_price_tier'))
		{
			if (!(count($product_variants) > 1 || $product_variants[0]['node']['title'] != 'Default Title')){
				$item_array['unit_price'] = $product_variants[0]['node']['price'];
			}
		}
		
		//Non variations
		if (!(count($product_variants) > 1 || $product_variants[0]['node']['title'] != 'Default Title'))
		{
			if ($product_variants[0]['node']['compareAtPrice'])
			{
				$item_array['promo_price'] =  $item_array['unit_price'];
				$item_array['start_date'] = NULL;
				$item_array['end_date'] = NULL;
				$item_array['unit_price'] = $product_variants[0]['node']['compareAtPrice'];
			}
			else
			{
				$item_array['promo_price'] =  NULL;
			}		
		}
		if (isset($shopify_product['node']['variants']['edges'][0]['node']['inventoryItem']['unitCost']['amount']) && $shopify_product['node']['variants']['edges'][0]['node']['inventoryItem']['unitCost']['amount'])
		{
			if (!(count($product_variants) > 1 || $product_variants[0]['node']['title'] != 'Default Title')){
				$item_array['cost_price'] = $shopify_product['node']['variants']['edges'][0]['node']['inventoryItem']['unitCost']['amount'];
			}
		}
		
		if ($item_number)
		{
			if ($sync_field != 'item_id')
			{
				$item_array[$sync_field] = $item_number;
			}

			if(!$item_id)
			{
				$this->load->model('Item');
				$item_id = $this->Item->get_item_id($item_number);
			}
		}
		
		if ($barcode)
		{
			//Save the barcode field as the other field we didn't use for $sync_field
			if ($sync_field == 'item_number')
			{
				$item_array['product_id'] = $barcode;
			}
			elseif($sync_field == 'product_id')
			{
				$item_array['item_number'] = $barcode;
			}
			
		}


		$this->load->model('Item_location');
		$item_location_info = $this->Item_location->get_info($item_id,$this->config->item('ecom_store_location') ? $this->config->item('ecom_store_location') : 1);
		$this->load->model('Item');

		//If we cannot save item then stop trying anything else
		if (!$this->Item->save($item_array,$item_id))
		{
			return;
		}
		
		$new_item = !$item_id;

		$item_id = isset($item_array['item_id']) ? $item_array['item_id'] : $item_id;

		if(count($product_tags)>0)
		{
			$this->load->model('Tag');
			$this->Tag->save_tags_for_item($item_id, implode(',',$product_tags));
		}
		
		if (isset($shopify_product['node']['images']['edges'][0]['node']['id']) && $shopify_product['node']['images']['edges'][0]['node']['id'])
		{			
			foreach($shopify_product['node']['images']['edges'] as $shopify_image)
			{
				$image_file_id = $this->get_image_file_id_for_ecommerce_image($shopify_image['node']['id']);

				if(!$image_file_id)
				{
					@$image_contents = file_get_contents_curl($shopify_image['node']['url']);
					$tmpFilename = tempnam(ini_get('upload_tmp_dir'), 'shopify');
					file_put_contents($tmpFilename,$image_contents);

					$config['image_library'] = 'gd2';
					$config['source_image']	= $tmpFilename;
					$config['create_thumb'] = FALSE;
					$config['maintain_ratio'] = TRUE;
					$config['width']	 = 1200;
					$config['height']	= 900;
					$this->image_lib->initialize($config);
					$this->image_lib->resize();
					$this->load->model('Appfile');
					$image_contents = file_get_contents($tmpFilename);


					if ($image_contents)
					{
						$image_file_id = $this->Appfile->save(clean_filename($shopify_image['node']['url']), $image_contents);
					}
					
					if (isset($image_file_id))
					{
						$this->Item->add_image($item_id, $image_file_id);
						$this->Item->link_image_to_ecommerce($image_file_id, $shopify_image['node']['id']);

						//Features image
						if ($shopify_product['node']['images']['edges'][0]['node']['id'] == $shopify_image['node']['id'])
						{
							$this->Item->set_main_image($item_id, $image_file_id);
						}
					}
				}
				
				//TODO see if we have image metadata and/or variation linkage
  			// $this->Item->save_image_metadata($image_file_id, $shopify_image['name'],$shopify_image['alt']);
			}
		}


		if (count($product_variants) > 1 || $product_variants[0]['node']['title'] != 'Default Title')
		{

			if( !$new_item ){
				$item_variations = $this->get_item_variations_for_ecommerce($item_id, true);
			} else {
				$item_variations = array();
			}

			foreach($product_variants as $product_variant)
			{
				$new_variation = FALSE;
				
				$variant_id = $product_variant['node']['id'];
				$gidVarParts         = explode('/', $variant_id);
		        $ecommerce_variation_id = end($gidVarParts);

				$exist_item_variation = NULL;
				foreach($item_variations as $item_variation){
					if($item_variation['ecommerce_variation_id'] == $ecommerce_variation_id){
						$exist_item_variation = $item_variation;
						break;
					}
				}				

				$price = $product_variant['node']['price'];
				
				if (isset($product_variant['node']['cost']))
				{
					$cost = $product_variant['node']['cost'];
				}
				$sku = $product_variant['node']['sku'];
				$barcode = $product_variant['node']['barcode'];
				$shopify_image_id = $product_variant['node']['image']['id'];
				$attribute_value_ids = array();
				
				for($k = 0; $k < count($product_variant['node']['selectedOptions']);$k++)
				{
					$option_value = $product_variant['node']['selectedOptions'][$k]['optionValue']['name'];
					
					if ($option_value === NULL)
					{
						break;
					}
					$attr_name =$product_variant['node']['selectedOptions'][$k]['name'];

					//Get Exist attribute id
					$attr_id_exist = NULL;
					if($exist_item_variation){
						foreach($exist_item_variation['attributes'] as $attribute){
							if($attribute['attribute_name'] == $attr_name){
								$attr_id_exist = $attribute['attribute_id'];
								break;
							}
						}
					}

					if (!$this->Item_attribute->attribute_name_exists($attr_name,$item_id) && !$this->Item_attribute->attribute_name_exists($attr_name))
					{
						$item_attr_data = array('name' => $attr_name, 'item_id' => $item_id);
						
						$attribute_ids_to_save = array();
						$attribute_ids_to_save[] = $this->Item_attribute->save($item_attr_data);
						$this->Item_attribute->save_item_attributes($attribute_ids_to_save, $item_id, false);
					}
					else
					{
						$attribute_ids_to_save = array();
						//Item level
						$attr_id_local = $this->Item_attribute->get_attribute_id($attr_name,$item_id);
						//Global level
						$attr_id_global = $this->Item_attribute->get_attribute_id($attr_name);

						if($attr_id_exist && $attr_id_exist == $attr_id_local) {
							$attribute_ids_to_save[] = $attr_id_local;
						} elseif ($attr_id_exist && $attr_id_exist == $attr_id_global){
							$attribute_ids_to_save[] = $attr_id_global;
						} elseif ($attr_id_local) {
							$attribute_ids_to_save[] = $attr_id_local;
						} elseif ($attr_id_global) {
							$attribute_ids_to_save[] = $attr_id_global;
						}
						$this->Item_attribute->save_item_attributes($attribute_ids_to_save, $item_id, false);
					}

					$attribute_id = NULL;

					//item level
					$attr_id_local = $this->get_attribute_id_from_ecommerce_attribute_name($attr_name, $item_id);
					//global level
					$attr_id_global = $this->get_attribute_id_from_ecommerce_attribute_name($attr_name, NULL);

					if($attr_id_exist && $attr_id_exist == $attr_id_local){
						$attribute_id = $attr_id_local;
					} elseif ($attr_id_exist && $attr_id_exist == $attr_id_global){
						$attribute_id = $attr_id_global;
					} elseif ($attr_id_local) {
						$attribute_id = $attr_id_local;
					} elseif ($attr_id_global) {
						$attribute_id = $attr_id_global;
					}
				
					if (!$this->Item_attribute_value->exists($option_value, $attribute_id))
					{
						$attribute_value_ids_to_save = array();
						$attribute_value_ids_to_save[] = $this->Item_attribute_value->save($option_value, $attribute_id);
						
						$this->Item_attribute_value->save_item_attribute_values($item_id, $attribute_value_ids_to_save);
					}
					else
					{
						$attribute_value_ids_to_save = array();
						$attribute_value_ids_to_save[] = $this->Item_attribute_value->get_attribute_value_id($option_value,$attribute_id);
						
						$this->Item_attribute_value->save_item_attribute_values($item_id, $attribute_value_ids_to_save);
					}

					$attribute_value_id = $this->lookup_attribute_value_id_from_attribute_id_and_option($attribute_id, $option_value, $item_id);
					if (!$attribute_value_id)
					{
						//global
						$attribute_value_id = $this->lookup_attribute_value_id_from_attribute_id_and_option($attribute_id, $option_value);
						$attribute_value_ids[]=$attribute_value_id;
					}
					else
					{
						//item level
						$attribute_value_ids[]=$attribute_value_id;
					}
				}
				
				//attempt to match with existing variations
				$variation_id = $this->Item_variations->lookup($item_id, $attribute_value_ids);
								
				$old_item_variation = NULL;
				if (!$variation_id)
				{
					$new_variation = TRUE;
				} else {
					$old_item_variation = $this->Item_variations->get_info($variation_id);
				}
				
				$ecommerce_last_modified = date('Y-m-d H:i:s',strtotime($product_variant['node']['updatedAt']));
				
				$item_variation = array(
					'item_id' => $item_id,
					'ecommerce_variation_id' => $variant_id,
					'ecommerce_inventory_item_id' => $product_variant['node']['inventoryItem']['id'],
					'ecommerce_last_modified' => $ecommerce_last_modified,
					'last_modified' => $ecommerce_last_modified,
					'item_number' => $sku ? $sku : null,
					'deleted' => 0,
				);
				
				if (isset($cost))
				{
					$item_variation['cost_price'] = $cost;					
				}
				
				
				
				if ($price  && !$this->config->item('online_price_tier'))
				{
					if($old_item_variation && $old_item_variation->unit_price === NULL && $price == 0){
						// no change
					} else if($old_item_variation && $old_item_variation->unit_price != $price) {
						$item_variation['unit_price'] = $price;
					} else if($new_variation){
						$item_variation['unit_price'] = $price;
					}
				}


				if ($product_variant['node']['compareAtPrice'])
				{
					$item_variation['promo_price'] =  $item_variation['unit_price'];
					$item_variation['unit_price'] = $product_variant['node']['compareAtPrice'];
					$item_variation['start_date'] = NULL;
					$item_variation['end_date'] = NULL;
				}
				else
				{
					$item_variation['promo_price'] = NULL;
				}


				$variation_id = $this->Item_variations->save($item_variation, $variation_id, $attribute_value_ids);
				
				
				if ($barcode)
				{
					$this->load->model('Additional_item_numbers');
					$this->Additional_item_numbers->save_variation($item_id, $variation_id, array($barcode));				
				}
				
				
				
				if ($shopify_image_id)
				{
					$this->Item->set_variation_for_ecommerce_image($shopify_image_id,$variation_id);
				}

				//This is a brand new variation we want to make sure we setup stock correctly
				if ($product_variant['node']['inventoryQuantity'] !== NULL)
				{
					$quantity = $product_variant['node']['inventoryQuantity']*$quantity_step;
					$ecommerce_product_quantity_data = array(
						'ecommerce_variation_quantity' => $quantity,
					);
					$this->Item_variations->save($ecommerce_product_quantity_data, $variation_id);

					if($new_variation){
						$this->db->insert('inventory', 
							array(
								'trans_date'=>date('Y-m-d H:i:s'),
								'trans_current_quantity' => $quantity,
								'trans_items' => $item_id,
								'item_variation_id' => $variation_id,
								'trans_user'=> 1,
								'trans_comment'=> 'Shopify ADD',
								'trans_inventory'=> $quantity,
								'location_id'=> $this->ecommerce_store_location
							)
						);
					}else{
						$item_variation_location_info = $this->Item_variation_location->get_info($variation_id, $this->ecommerce_store_location);
						$quantity_change = $quantity - (is_numeric($item_variation_location_info->quantity) ? $item_variation_location_info->quantity : 0);
							
						if($quantity_change != 0){
							$this->db->insert('inventory', 
								array(
									'trans_date'=>date('Y-m-d H:i:s'),
									'trans_current_quantity' => $quantity,
									'trans_items' => $item_id,
									'item_variation_id' => $variation_id,
									'trans_user'=> 1,
									'trans_comment'=> 'Shopify Update',
									'trans_inventory'=> $quantity_change,
									'location_id'=> $this->ecommerce_store_location
								)
							);
						}
					}

				  	$item_variation_location_data = array(
						'item_variation_id'=>$variation_id,
						'location_id'=>$this->ecommerce_store_location,
						'quantity'=>$product_variant['node']['inventoryQuantity']
		     	 	);
					$item_variation_location_data = array('item_variation_id'=>$variation_id,'location_id'=>$this->ecommerce_store_location,'quantity'=>$product_variant['node']['inventoryQuantity']);
					$this->load->model('Item_variation_location');
					$this->Item_variation_location->save($item_variation_location_data, $variation_id, $this->ecommerce_store_location);
				}
				
			}
		}
		else //Regular products
		{
			//This is a brand new item we want to make sure we setup stock correctly
			if ($quantity !== NULL)
			{
				$ecommerce_product_quantity_data = array('ecommerce_product_quantity' => $quantity);
				$this->Item->save($ecommerce_product_quantity_data,$item_id);

				if($new_item){
					$this->db->insert('inventory', 
						array(
							'trans_date'=>date('Y-m-d H:i:s'),
							'trans_current_quantity' => $quantity,
							'trans_items' => $item_id,
							'trans_user'=> 1,
							'trans_comment'=> 'Shopify ADD',
							'trans_inventory'=> $quantity,
							'location_id'=>$this->ecommerce_store_location
						)
					);
				}else{
					$quantity_change = $quantity - $item_location_info->quantity;
					if($quantity_change != 0){
						$this->db->insert('inventory', 
							array(
								'trans_date'=>date('Y-m-d H:i:s'),
								'trans_current_quantity' => $quantity,
								'trans_items' => $item_id,
								'trans_user'=> 1,
								'trans_comment'=> 'Shopify Update',
								'trans_inventory'=> $quantity_change,
								'location_id'=>$this->ecommerce_store_location
							)
						);
					}
				}
				$location_item_array = array(
					'item_id'=>$item_id,
					'location_id'=>$this->ecommerce_store_location,
					'quantity'=>$quantity
				);
				$this->load->model('Item_location');
				$this->Item_location->save($location_item_array, $item_id, $this->ecommerce_store_location);
			}
		}
		
		
		//make sure to reset last modified data so it has right data and doesnt't double sync. 
		//last_modified get changes after intial save due to other mods to items
		$last_modified_data = array(
			'ecommerce_last_modified' => $last_modified,
			'last_modified' => $last_modified
		);
		
		$this->Item->save($last_modified_data,$item_id);
	}

	public function delete_item_image($ecommerce_product_id, $ecommerce_image_ids){

	    // Make sure the app is "paid" or authorized to interact with Shopify
	    if (!$this->check_shopify_paid()) 
		{
	        $this->log(lang('shopify_not_paid'));
	        return;
	    }

		// Define the GraphQL mutation to delete product images
		$mutation = <<<GQL
			mutation productDeleteImages(\$productId: ID!, \$imageIds: [ID!]!) {
				productDeleteImages(productId: \$productId, imageIds: \$imageIds) {
					deletedImageIds
					product {
						id
						title
						images(first: 5) {
							nodes {
								id
							}
						}
					}
					userErrors {
						field
						message
					}
				}
			}
GQL;

		// Build array of full Shopify GIDs for the image IDs
		$image_ids = array_map(function($ecommerce_image_id) {
			return 'gid://shopify/ProductImage/' . $ecommerce_image_id;
		}, $ecommerce_image_ids);

		// Prepare GraphQL variables
		$variables = [
			'productId' => 'gid://shopify/Product/' . $ecommerce_product_id,
			'imageIds'  => $image_ids,
		];

	    try {
	        // Execute the GraphQL query using the Shopify PHP SDK
	        // The exact syntax here depends on how you've initialized your client:
	        // e.g. $this->run_graphql_query($mutation, $variables)
	        // or a custom method. Adjust accordingly.
			$response = $this->run_graphql_query([
	            'query'     => $mutation,
	            'variables' => $variables,
	        ]);
	        // Handle response (check for errors, confirm deletion, etc.)
	        if (isset($response['body']['errors'])) 
			{
	            // Handle GraphQL errors
	            $this->log('Errors occurred while deleting product image via GraphQL:', $response['body']['errors']);
	        } 
	    } 
		catch (\Exception $e) {
	        // Handle any exceptions from the client
	        $this->log('Exception while deleting product image via GraphQL: ' . $e->getMessage());
	    }		  
	}

	public function delete_item($item_id)
	{
	    // Make sure the app is "paid" or authorized to interact with Shopify
	    if (!$this->check_shopify_paid()) 
		{
	        $this->log(lang('shopify_not_paid'));
	        return;
	    }


	    // Retrieve your internal mapping from item to Shopify product ID
	    $shopify_product_id = $this->get_ecommerce_product_id_for_item_id($item_id);

		$this->unlink_item($item_id);

	    // Prepare the GraphQL mutation for deleting a product
	    $mutation = <<<'GQL'
			mutation productDelete($input: ProductDeleteInput!) {
			productDelete(input: $input) {
				deletedProductId
				userErrors {
				field
				message
				}
			}
			}
GQL;

	    // Build the variables array
	    // Note: Shopify expects the product "id" in the format gid://shopify/Product/<ID>
	    $variables = [
	        'input' => [
	            'id' => 'gid://shopify/Product/' . $shopify_product_id,
	        ],
	    ];

	    try {
	        // Execute the GraphQL query using the Shopify PHP SDK
	        // The exact syntax here depends on how you've initialized your client:
	        // e.g. $this->run_graphql_query($mutation, $variables)
	        // or a custom method. Adjust accordingly.
			$response = $this->run_graphql_query([
	            'query'     => $mutation,
	            'variables' => $variables,
	        ]);
	        // Handle response (check for errors, confirm deletion, etc.)
	        if (isset($response['body']['errors'])) 
			{
	            // Handle GraphQL errors
	            $this->log('Errors occurred while deleting product via GraphQL:', $response['body']['errors']);
	        } 
	    } 
		catch (\Exception $e) {
	        // Handle any exceptions from the client
	        $this->log('Exception while deleting product via GraphQL: ' . $e->getMessage());
	    }
	}
	public function delete_items($item_ids)
	{
		if (!$this->check_shopify_paid())
		{
			$this->log(lang('shopify_not_paid'));
			return;
		}
		
		foreach($item_ids as $item_id)
		{
			$this->delete_item($item_id);
		}
	}

	public function undelete_item($item_id)
	{
		if (!$this->check_shopify_paid())
		{
			$this->log(lang('shopify_not_paid'));
			return;
		}
		
		$this->reset_item($item_id);
		$this->save_item_from_phppos_to_ecommerce($item_id);
	}

	public function undelete_items($item_ids)
	{
		if (!$this->check_shopify_paid())
		{
			$this->log(lang('shopify_not_paid'));
			return;
		}
		
		foreach($item_ids as $item_id)
		{
			$this->undelete_item($item_id);
		}
	}
	
	public function undelete_all()
	{
		if (!$this->check_shopify_paid())
		{
			$this->log(lang('shopify_not_paid'));
			return;
		}
		
		//don't implement. Woo does NOT also
	}
	

	function import_ecommerce_tags_into_phppos()
	{
		if (!$this->check_shopify_paid())
		{
			$this->log(lang('shopify_not_paid'));
			return;
		}
		
		//No need to do in shopify
	}

	function import_ecommerce_categories_into_phppos()
	{
		if (!$this->check_shopify_paid())
		{
			$this->log(lang('shopify_not_paid'));
			return;
		}
		
		//No need to do in shopify
	}

	function import_ecommerce_attributes_into_phppos()
	{
		if (!$this->check_shopify_paid())
		{
			$this->log(lang('shopify_not_paid'));
			return;
		}
		
		//No need to do in shopify
	}

	function export_phppos_attributes_to_ecommerce()
	{
		if (!$this->check_shopify_paid())
		{
			$this->log(lang('shopify_not_paid'));
			return;
		}
		
		//No need to do in shopify
	}

	function import_ecommerce_orders_into_phppos()
	{		
		if (!$this->check_shopify_paid())
		{
			$this->log(lang('shopify_not_paid'));
			return;
		}
		
		$this->log(lang("import_ecommerce_orders_into_phppos"));
		
		$statusQuery = $this->config->item('ecommerce_only_sync_completed_orders') ? "status:closed" : "status:any";

		$after = null;
		do {
			$afterCursor = $after ? ", after: \"$after\"" : "";
			$query = <<<GQL
				query {
					orders(first: 50$afterCursor, query: "$statusQuery") {
						edges {
							node {
								id
								name
								createdAt
								processedAt
								cancelledAt
								closedAt
								updatedAt
								currencyCode
								email
								displayFinancialStatus
								displayFulfillmentStatus
								customer {
									id
									firstName
									lastName
									email
								}
								currentSubtotalPriceSet {
									shopMoney {
										amount
										currencyCode
									}
								}
								currentTotalDiscountsSet {
									shopMoney {
										amount
										currencyCode
									}
								}
								currentTotalPriceSet {
									shopMoney {
										amount
										currencyCode
									}
								}
								shippingAddress {
									name
									address1
									address2
									city
									province
									country
									zip
									phone
								}
								billingAddress {
									name
									address1
									address2
									city
									province
									country
									zip
									phone
								}
								discountApplications(first: 10) {
									edges {
										node {
											targetSelection
											allocationMethod
											targetType
											value {
												... on MoneyV2 {
													amount
													currencyCode
												}
												... on PricingPercentageValue {
													percentage
												}
											}
										}
									}
								}
								lineItems(first: 250) {
									edges {
										node {
											id
											title
											quantity
											sku
											product {
												id
												title
											}
											variant {
												id
												title
											}
											originalUnitPriceSet {
												shopMoney {
												amount
												currencyCode
												}
											}
											discountedTotalSet {
												shopMoney {
													amount
													currencyCode
												}
											}
											taxLines {
												price
												rate
											}
											taxable
										}
									}
								}
							}
						}
						pageInfo {
							hasNextPage
							endCursor
						}
					}
				}
GQL;

			$response = $this->run_graphql_query(['query' => $query]);
			$this->process_import_ecommerce_orders_into_phppos($response);
			$pageInfo = $response['data']['orders']['pageInfo'];
			$after = $pageInfo['hasNextPage'] ? $pageInfo['endCursor'] : null;
		} while ($after);
	}
	
	function save_custom_line_item($line_unit_price,$line_cost_price,$total_tax,$item_id,$sale_id,$line_index,$quantity=1)
	{
		if (!$this->check_shopify_paid())
		{
			$this->log(lang('shopify_not_paid'));
			return;
		}
		
		$line_unit_price = (float)$line_unit_price;
		$line_cost_price = (float)$line_cost_price;
		$total_tax = (float)$total_tax;

		if ($line_unit_price)
		{
			if ($line_unit_price)
			{
				$tax_percent = (float)($total_tax/$line_unit_price)*100;
			}
			else
			{
				$tax_percent = 0;
			}

			$sales_items = array();

			$sales_items['sale_id'] = $sale_id;
			$sales_items['item_id'] = $item_id;
			$line_unit_price = $line_unit_price;

			$sales_items['quantity_purchased'] = $quantity;
			$sales_items['line'] = $line_index;
			$sales_items['item_unit_price'] = $line_unit_price;
			$sales_items['item_cost_price'] = $line_cost_price;

			$sales_items['subtotal']=$line_unit_price*$quantity;
			$sales_items['total']=($line_unit_price*$quantity)+$total_tax;
			$sales_items['tax']=$total_tax;
			$sales_items['profit']=0;

			$this->db->insert('sales_items',$sales_items);

			if ($tax_percent)
			{
				$sales_items_taxes = array(
					'name' => lang('common_sales_tax_1'),
					'sale_id' => $sale_id,
					'item_id' => $item_id,
					'line' => $line_index,
					'percent' => round($tax_percent,2),
				);

				$this->db->insert('sales_items_taxes',$sales_items_taxes);
			}
		}

	}

 	private function save_line_item($line_item,$sale_id,$line_index)
	{
		if (!$this->check_shopify_paid())
		{
			$this->log(lang('shopify_not_paid'));
			return;
		}
		
		$sales_items = array();

		$shopify_product_id = $line_item['product_id'];
		$shopify_variation_id = $line_item['variant_id'];
		$phppos_item_id = $this->get_item_id_for_ecommerce_product($shopify_product_id);
		$phppos_variation_id = $this->get_variation_id_for_ecommerce_product_variation($shopify_variation_id);

		$sales_items['sale_id'] = $sale_id;
		$sales_items['item_id'] = $phppos_item_id;
		$sales_items['item_variation_id'] = $phppos_variation_id;
		$quantity = $line_item['current_quantity']??$line_item['quantity']??0;
		$subtotal = $line_item['price'] * $quantity;
		
		$total_tax = 0;
		$tax_percent = 0;
		
		foreach($line_item['tax_lines'] as $taxes)
		{
			$total_tax += $taxes['price'];
			$tax_percent += $taxes['rate']*100;
		}

		$sales_items['quantity_purchased'] = $quantity;
		$sales_items['line'] = $line_index;
		$sales_items['item_unit_price'] = $line_item['price'];
		$item_info = $this->Item->get_info($phppos_item_id);
		$item_location_info = $this->Item_location->get_info($phppos_item_id);
		$variation_info = $this->Item_variations->get_info($phppos_variation_id);

		if ($variation_info && $variation_info->unit_price)
		{
			$sales_items['regular_item_unit_price_at_time_of_sale'] = $variation_info->unit_price;
		}
		else
		{
			$sales_items['regular_item_unit_price_at_time_of_sale'] = ($item_location_info && $item_location_info->unit_price) ? $item_location_info->unit_price : $item_info->unit_price;
		}


		if ($variation_info && $variation_info->cost_price)
		{
			$sales_items['item_cost_price'] = $variation_info->cost_price;
		}
		else
		{
			$sales_items['item_cost_price'] = $item_location_info->cost_price ? $item_location_info->cost_price : $item_info->cost_price;
		}

		$profit = ((double)$sales_items['item_unit_price']* (double)$quantity) - ((double)$sales_items['item_cost_price'] * (double)$quantity);

		$sales_items['subtotal']=$subtotal;
		$sales_items['total']=$subtotal+$total_tax;
		$sales_items['tax']=$total_tax;
		$sales_items['profit']= $profit;

		$this->db->insert('sales_items',$sales_items);

		if ($tax_percent)
		{
			$sales_items_taxes = array(
				'name' => lang('common_sales_tax_1'),
				'sale_id' => $sale_id,
				'item_id' => $phppos_item_id,
				'line' => $line_index,
				'percent' => round($tax_percent,2),
			);

			$this->db->insert('sales_items_taxes',$sales_items_taxes);
		}
	}

	function save_delivery($order,$sale_id,$customer_id)
	{
		if (!$this->check_shopify_paid())
		{
			$this->log(lang('shopify_not_paid'));
			return;
		}
		
		$actual_shipping_date = $order['closed_at'] ? date('Y-m-d H:i:s',strtotime($order['closed_at'])) : NULL;
		$estimated_shipping_date = $order['processed_at'] ? date('Y-m-d H:i:s',strtotime($order['processed_at'])) : NULL;

		$data = array(
			'sale_id' => $sale_id,
			'shipping_address_person_id' => $customer_id,
			'actual_shipping_date' =>$actual_shipping_date,
			'estimated_shipping_date' =>$estimated_shipping_date,
		);
		
		$this->Delivery->save($data);
	}
	
	private function get_sale_totals($order)
	{
		if (!$this->check_shopify_paid())
		{
			$this->log(lang('shopify_not_paid'));
			return;
		}
		
		$return = array('total_quantity_purchased' => 0,'profit' => 0);

		$line_items = $order['line_items'];
		foreach($line_items as $line_item)
		{
			$shopify_product_id = $line_item['product_id'];
			$shopify_variation_id = $line_item['variant_id'];

			$phppos_item_id = $this->get_item_id_for_ecommerce_product($shopify_product_id);
			$phppos_variation_id = $this->get_variation_id_for_ecommerce_product_variation($shopify_variation_id);

			$quantity = $line_item['current_quantity'] ?? $line_item['quantity'] ?? 0;
			$unit_subtotal = $line_item['price'];

			$item_info = $this->Item->get_info($phppos_item_id);
			$item_location_info = $this->Item_location->get_info($phppos_item_id);
			$variation_info = $this->Item_variations->get_info($phppos_variation_id);


			if ($variation_info && $variation_info->cost_price)
			{
				$item_cost_price = $variation_info->cost_price;
			}
			else
			{
				$item_cost_price = $item_location_info->cost_price ? $item_location_info->cost_price : $item_info->cost_price;
			}
			$return['profit'] += ((double)$unit_subtotal * (double)$quantity) - ((double)$item_cost_price * (double)$quantity);
			$return['total_quantity_purchased'] += $quantity;
		}
		return $return;
	}

	private function save_shopify_customer_from_order($order)
	{
		if (!$this->check_shopify_paid())
		{
			$this->log(lang('shopify_not_paid'));
			return;
		}

		$customer = NULL;
		@$customer_shipping = $order['shipping_address'];
		
		if (isset($order['billing_address']) && !empty($order['billing_address']))
		{
			$customer_billing = $order['billing_address'];
			$customer = array_merge($customer_billing,$customer_shipping);			
		}
		elseif(isset($customer_shipping))
		{
			$customer = $customer_shipping;
		}


		//If this info is empty for shipping then get from billing
		$empty_shipping_key_checks = array('first_name','last_name','phone','address1','address2','city','state','postcode','country','company');
		foreach($empty_shipping_key_checks as $key_check)
		{
			if(isset($customer_billing))
			{
				if(!isset($customer[$key_check]) && !$customer[$key_check])
				{
					$customer[$key_check] = $customer_billing[$key_check];
				}
			}
		}
		
		
		$sale_customer_id = NULL;
		//Existing customer lookup by email
		if ($order['email'] && ($phppos_customer_info = $this->Customer->get_info_by_email($order['email'])))
		{
			$sale_customer_id = $phppos_customer_info->person_id;
		}
		elseif (isset($order['phone']) && ($phppos_customer_info = $this->Customer->get_info_by_phone(alphanumplus($order['phone']))))
		{
			$sale_customer_id = $phppos_customer_info->person_id;
		}
		elseif ($customer && $customer['address_1'] && ($phppos_customer_info = $this->Customer->get_info_by_address_1($customer['address1'])))
		{
			$sale_customer_id = $phppos_customer_info->person_id;
		}
		elseif ($customer && $customer['first_name'] && $customer['last_name'] && ($phppos_customer_info = $this->Customer->get_info_by_full_name($customer['first_name'].' '.$customer['last_name'])))
		{
			$sale_customer_id = $phppos_customer_info->person_id;
		}
		
		if ($customer)
		{
			$person_data = array(
				'first_name'=>$customer['first_name'] ?? '',
				'last_name'=>$customer['last_name'] ?? '',
				'email'=>$order['email'],
				'phone_number'=>$order['phone'] ?? '',
				'address_1'=>$customer['address1'] ?? '',
				'address_2'=>$customer['address2'] ?? '',
				'city'=>$customer['city'] ?? '',
				'state'=>$customer['province'] ?? '',
				'zip'=>$customer['zip'] ?? '',
				'country'=>$customer['country'] ?? '',
			);

			$customer_data=array(
				'company_name' => $customer['company'] ? $customer['company'] : '',
			);

			$this->Customer->save_customer($person_data, $customer_data,$sale_customer_id);

			$sale_customer_id = isset($person_data['person_id']) && $person_data['person_id'] ? $person_data['person_id'] : $sale_customer_id;
		}
		
		return $sale_customer_id;
	}
	
	public function process_import_order($order, $import_manual_sync = false)
	{
		if (!$this->check_shopify_paid())
		{
			$this->log(lang('shopify_not_paid'));
			return;
		}

		if ($this->config->item('ecommerce_only_sync_completed_orders'))
		{
			if ($order['fulfillment_status'] != 'fulfilled')
			{
				return;
			}
		}

		$this->log(lang('common_import_order').' #'.$order['id']);
		$this->log('order content:'.json_encode($order));

		$sales_data = array();
		
		$sales_totals = $this->get_sale_totals($order);
		$shopify_id = $order['id'];
		$customer_id = $this->save_shopify_customer_from_order($order);
		$sale_id = $this->get_sale_id_for_ecommerce_order_id($order['id']);
		$exist_sale_info = NULL;
		$ecommerce_cron_sync_operations_settings = unserialize($this->config->item('ecommerce_cron_sync_operations'));

		$sales_data['ecommerce_status'] = $order['fulfillment_status']?? 'unfulfilled';
		$sales_data['ecommerce_financial_status'] = $order['financial_status']?? '';

		$cur_timezone = date_default_timezone_get();
		$shopify_sale_last_modified_time = $this->convert_shopify_to_pos_time($order['updated_at'], $cur_timezone);
		if($sale_id){
			$exist_sale_info = $this->Sale->get_info($sale_id)->row_array();
			if($shopify_sale_last_modified_time <= $exist_sale_info['last_modified']){
				$this->log(('Shopify order data has expired.'));
				return;
			}

			if(
				$exist_sale_info['ecommerce_financial_status'] == 'refunded' 
				|| $exist_sale_info['ecommerce_financial_status'] == 'voided'
			){
				//This is final.
				return;
			}

			if($order['financial_status'] == 'refunded'){
				// This order was cancelled
				$reason = $order['cancel_reason']??$order['refunds'][0]['note']; // Optional: customer, fraud, etc.
				$ecommerce_restock = $order['refunds'][0]['restock']; // Optional: restock.
				$sales_data['ecommerce_restock'] = $ecommerce_restock ? 1 : 0; // Restock inventory
				$sales_data['ecommerce_reason'] = $reason;
				$sales_data['deleted'] = 1;
				$this->log('Shopify order cancelled: ' . $order['name'] . ' (ID: ' . $order['id'] . ')');
			} else if($order['financial_status'] == 'partially_refunded'){
				//Todo
			} else {
				//Todo
			}
		} else {
			if($order['financial_status'] == 'refunded'){
				// This order was cancelled and manual sync (init)
				$reason = $order['cancel_reason']?? $order['refunds'][0]['note'] ?? "Order canceled"; // Optional: customer, fraud, etc.
				$ecommerce_restock = $order['refunds'][0]['restock']; // Optional: restock.

				$sales_data['ecommerce_restock'] = $ecommerce_restock ? 1 : 0 ;// Restock inventory
				$sales_data['ecommerce_reason'] = $reason;// return resone
				$sales_data['deleted'] = 1; // return resone
			} else if($order['financial_status'] == 'partially_refunded'){
				//Todo
			}
		}

		$sales_data['last_modified'] = $shopify_sale_last_modified_time?$shopify_sale_last_modified_time : date('Y-m-d H:i:s');

		$sales_data['employee_id'] = 1;
		
		if (!$sale_id && $this->config->item('import_ecommerce_orders_suspended'))
		{	
			$sales_data['suspended'] = $this->config->item('ecommerce_suspended_sale_type_id');
		}
		
		$sales_data['sale_time'] = date('Y-m-d H:i:s',strtotime($order['processed_at']));
		$sales_data['location_id'] = $this->ecommerce_store_location;
		$sales_data['customer_id'] = $customer_id;
		$sales_data['is_ecommerce'] = 1;
				
		$sales_data['subtotal'] = $order['current_subtotal_price']?? $order['subtotal_price'] ?? 0;
		$sales_data['total'] = $order['current_total_price'] ?? $order['total_price'] ?? 0;
		$sales_data['tax'] = $order['current_total_tax'] ?? $order['total_tax'] ?? 0;
		
		$sales_data['profit'] = $sales_totals['profit'] ?? 0;
		$sales_data['total_quantity_purchased'] = $sales_totals['total_quantity_purchased'] ?? 0;
		$sales_data['comment'] = 'shopify #'.$shopify_id.' #'.$order['order_number'];
		$sales_data['ecommerce_order_id'] = $shopify_id;
		$sales_data['payment_type'] = lang('common_online');
		$sales_data['discount_reason'] = '';
		$sales_data['cc_ref_no'] = '';

		if ($sale_id)
		{
			// get exist sales items
			// $sale_items = $this->Sale->get_sale_items($sale_id)->result_array();
			if(!$import_manual_sync && in_array('sync_inventory_changes', $ecommerce_cron_sync_operations_settings)){

				$sales_data_state = -1;
				$change_comment = "";
				if($order['financial_status'] == 'refunded'){
					$sales_data_state = 1;
					$change_comment = "Refunded";
					if($order['refunds'][0]['restock']){
						$edit_line_items = $order['refunds'][0]['refund_line_items'];
					} else {
						$edit_line_items = [];
					}
				} else if($order['financial_status'] == 'partially_refunded'){
					$sales_data_state = 1;
					$change_comment = "Particial Refunded";
					if($order['refunds'][count($order['refunds']) - 1]['restock']){
						// step by step : check log particial refunded ids and process after last log particial refunded id
						$edit_line_items = $order['refunds'][count($order['refunds']) - 1]['refund_line_items'];
					}else{
						$edit_line_items = array();
					}
				}

				foreach($edit_line_items as $edit_line_item)
				{
					$shopify_product_id = $edit_line_item['line_item']['product_id'];
					$shopify_variation_id = $edit_line_item['line_item']['variant_id'];
					$phppos_item_id = $this->get_item_id_for_ecommerce_product($shopify_product_id);
					$tags = implode(',',$this->Tag->get_tags_for_item($phppos_item_id));
					$quantity_step = stripos($tags, 'decimal') !== false ? $this->inventory_step_count_for_non_integer : 1;
					$phppos_variation_id = $this->get_variation_id_for_ecommerce_product_variation($shopify_variation_id);
					$location_id = $this->ecommerce_store_location;

					$quantity = $edit_line_item['quantity'] * $quantity_step * $sales_data_state;

					if($quantity == 0) continue;

					$inv_data = array
					(
						'trans_date' => date('Y-m-d H:i:s'),
						'trans_items' => $phppos_item_id,
						'trans_user' => 1,
						'trans_comment' => $this->config->item('sale_prefix').' '.$sale_id. ' ' . $change_comment,
						'trans_inventory' => $quantity,
						'location_id' => $location_id,
					);
					
					if ($phppos_variation_id){
						$inv_data['item_variation_id'] = $phppos_variation_id;
						$cur_item_variation_location_info = $this->Item_variation_location->get_info($phppos_variation_id, $location_id);
						$this->Item_variation_location->save_quantity($cur_item_variation_location_info->quantity + $quantity, $phppos_variation_id, $location_id);
						$cur_item_variation_location_info = $this->Item_variation_location->get_info($phppos_variation_id, $location_id);
						$inv_data['trans_current_quantity'] = $cur_item_variation_location_info->quantity;
					} elseif ($phppos_item_id){
						//Normal item
						$cur_item_location_info = $this->Item_location->get_info($phppos_item_id,$location_id);
						$this->Item_location->save_quantity($cur_item_location_info->quantity + $quantity, $phppos_item_id, $location_id);
						$cur_item_location_info = $this->Item_location->get_info($phppos_item_id,$location_id);
						$inv_data['trans_current_quantity'] = $cur_item_location_info->quantity;				
					}
					
					if ($phppos_variation_id || $phppos_item_id)
					{
						$this->Inventory->insert($inv_data);
					}
				}
			}

			$this->db->where('sale_id', $sale_id);
			$this->db->update('sales',$sales_data);

			//Delete sale data
			$this->db->delete('sales_payments', array('sale_id' => $sale_id));
			$this->db->delete('sales_items_taxes', array('sale_id' => $sale_id));
			$this->db->delete('sales_items', array('sale_id' => $sale_id));
			$this->db->delete('sales_item_kits_taxes', array('sale_id' => $sale_id));
			$this->db->delete('sales_item_kits', array('sale_id' => $sale_id));
			$this->db->delete('sales_coupons', array('sale_id' => $sale_id));
			$this->db->delete('sales_deliveries', array('sale_id' => $sale_id));
		}
		else
		{
			$this->db->insert('sales',$sales_data);
			$sale_id = $this->db->insert_id();

			if(!$import_manual_sync && in_array('sync_inventory_changes', $ecommerce_cron_sync_operations_settings)){
				// update_inventory_from_sale
				$line_items = $order['line_items'];
				foreach($line_items as $line_item)
				{
					$shopify_product_id = $line_item['product_id'];
					$shopify_variation_id = $line_item['variant_id'];
					$phppos_item_id = $this->get_item_id_for_ecommerce_product($shopify_product_id);
					$tags = implode(',',$this->Tag->get_tags_for_item($phppos_item_id));
					$quantity_step = stripos($tags, 'decimal') !== false ? $this->inventory_step_count_for_non_integer : 1;
					$phppos_variation_id = $this->get_variation_id_for_ecommerce_product_variation($shopify_variation_id);
					$quantity = (($line_item['current_quantity'] ? $line_item['current_quantity'] : $line_item['quantity']) * $quantity_step) * -1;

					$location_id = $this->ecommerce_store_location;
								
					$inv_data = array
					(
						'trans_date' => date('Y-m-d H:i:s'),
						'trans_items' => $phppos_item_id,
						'trans_user' => 1,
						'trans_comment' => $this->config->item('sale_prefix').' '.$sale_id,
						'trans_inventory' => $quantity,
						'location_id' => $location_id,
					);
					
					if ($phppos_variation_id){
						$inv_data['item_variation_id'] = $phppos_variation_id;
						$cur_item_variation_location_info = $this->Item_variation_location->get_info($phppos_variation_id, $location_id);
						$this->Item_variation_location->save_quantity($cur_item_variation_location_info->quantity + $quantity, $phppos_variation_id, $location_id);
						$cur_item_variation_location_info = $this->Item_variation_location->get_info($phppos_variation_id, $location_id);
						$inv_data['trans_current_quantity'] = $cur_item_variation_location_info->quantity;
					} elseif ($phppos_item_id){
						//Normal item
						$cur_item_location_info = $this->Item_location->get_info($phppos_item_id,$location_id);
						$this->Item_location->save_quantity($cur_item_location_info->quantity + $quantity, $phppos_item_id, $location_id);
						$cur_item_location_info = $this->Item_location->get_info($phppos_item_id,$location_id);
						$inv_data['trans_current_quantity'] = $cur_item_location_info->quantity;				
					}
					
					if ($phppos_variation_id || $phppos_item_id)
					{
						$this->Inventory->insert($inv_data);
					}
				}
		
			}
		}

		$this->db->insert('sales_payments',
			array(
				'sale_id'=> $sale_id, 'payment_date' => $sales_data['sale_time'] ,'payment_type' =>lang('common_online'),
				'payment_amount' =>  $sales_data['total'], 'paid_online' => (($order['financial_status'] == 'paid' || $order['financial_status'] == 'partially_refunded') ? 1 : 0)
			)
	   	);

		if ($customer_id)
		{
			$this->save_delivery($order,$sale_id,$customer_id);
		}

		$line_items = $order['line_items'];

		$counter = 0;
		foreach($line_items as $line_item)
		{
			$this->save_line_item($line_item, $sale_id, $counter);
			$counter++;
		}

		if (isset($order['total_shipping_price_set']['shop_money']['amount']) && (float)$order['total_shipping_price_set']['shop_money']['amount'])
		{
			$this->save_custom_line_item($order['total_shipping_price_set']['shop_money']['amount'],$order['total_shipping_price_set']['shop_money']['amount'],0,$this->Item->create_or_update_delivery_item(FALSE),$sale_id,$counter, 1);
			$counter++;
		}

	}
	
	function process_import_ecommerce_orders_into_phppos($response)
	{
		if (!$this->check_shopify_paid())
		{
			$this->log(lang('shopify_not_paid'));
			return;
		}
		
		if ($response === FALSE)
		{
				return;
		}

		$orders = $response['data']['orders']['edges'] ?? [];
		foreach($orders as $oIndex=> $edge)
		{
			$order = $this->convert_graphql_order_to_rest($edge['node']);
			$this->process_import_order($order, true);
		}
	}

	function convert_graphql_order_to_rest(array $gqlOrder)
	{
		$node = $gqlOrder['node'] ?? $gqlOrder;
	
		$order = [
			'id' => $this->extract_numeric_id($node['id']),
			'admin_graphql_api_id' => $node['id'],
			'name' => $node['name'] ?? null,
			'created_at' => $node['createdAt'] ?? null,
			'updated_at' => $node['updatedAt'] ?? null,
			'processed_at' => $node['processedAt'] ?? null,
			'cancelled_at' => $node['cancelledAt'] ?? null,
			'closed_at' => $node['closedAt'] ?? null,
			'currency' => $node['currencyCode'] ?? null,
			'email' => $node['email'] ?? null,
			'buyer_accepts_marketing' => $node['buyerAcceptsMarketing'] ?? false,
			'customer' => [
				'id' => isset($node['customer']['id']) ? $this->extract_numeric_id($node['customer']['id']) : null,
				'first_name' => $node['customer']['firstName'] ?? '',
				'last_name' => $node['customer']['lastName'] ?? '',
				'email' => $node['customer']['email'] ?? '',
			],
			'client_details' => [
				'browser_ip' => $node['clientDetails']['browserIp'] ?? null,
				'user_agent' => $node['clientDetails']['userAgent'] ?? null,
				'accept_language' => $node['clientDetails']['acceptLanguage'] ?? null,
				'session_hash' => $node['clientDetails']['sessionHash'] ?? null,
			],
			'current_subtotal_price' => $node['currentSubtotalPriceSet']['shopMoney']['amount'] ?? '0.00',
			'current_total_discounts' => $node['currentTotalDiscountsSet']['shopMoney']['amount'] ?? '0.00',
			'total_price' => $node['currentTotalPriceSet']['shopMoney']['amount'] ?? '0.00',
			'line_items' => [],
			'shipping_lines' => [],
			'discount_codes' => [],
			'financial_status' => strtolower($node['displayFinancialStatus']) ?? null,
			'fulfillment_status' => strtolower($node['displayFulfillmentStatus']) ?? null,
		];
	
		// Line Items
		foreach ($node['lineItems']['edges'] ?? [] as $edge) {
			$item = $edge['node'];
			$order['line_items'][] = [
				'id' => $this->extract_numeric_id($item['id']),
				'title' => $item['title'] ?? '',
				'quantity' => $item['quantity'] ?? 0,
				'sku' => $item['sku'] ?? '',
				'price' => $item['originalUnitPriceSet']['shopMoney']['amount'] ?? '0.00',
				'taxable' => $item['taxable'] ?? false,
				'product_id' => isset($item['product']['id']) ? $this->extract_numeric_id($item['product']['id']) : null,
				'variant_id' => isset($item['variant']['id']) ? $this->extract_numeric_id($item['variant']['id']) : null,
				'tax_lines' => array_map(function ($tax) {
					return [
						'price' => $tax['price'] ?? '0.00',
						'rate' => $tax['rate'] ?? 0.0,
					];
				}, $item['taxLines'] ?? []),
			];
		}
	
		// Shipping Lines
		foreach ($node['shippingLines'] ?? [] as $shipping) {
			$order['shipping_lines'][] = [
				'title' => $shipping['title'] ?? '',
				'price' => $shipping['priceSet']['shopMoney']['amount'] ?? '0.00',
			];
		}
	
		// Discounts
		foreach ($node['discountApplications']['edges'] ?? [] as $edge) {
			$discount = $edge['node'];
			$value = $discount['value'];
			$amount = isset($value['amount']) ? $value['amount'] : null;
			$percentage = isset($value['percentage']) ? $value['percentage'] : null;
			$order['discount_codes'][] = [
				'code' => $discount['code'] ?? '',
				'amount' => $amount ?? ($percentage ? '0.00' : ''),
				'type' => $percentage ? 'percentage' : 'fixed_amount',
			];
		}
	
		return $order;
	}
	
	function extract_numeric_id($gid)
	{
		// Extracts the last segment of a Shopify GID (e.g., 'gid://shopify/Order/123456' → 123456)
		return intval(substr($gid, strrpos($gid, '/') + 1));
	}

	function get_tax_class_rates($phppos_tax_class_id,$use_cache = TRUE)
	{
		if (!$this->check_shopify_paid())
		{
			$this->log(lang('shopify_not_paid'));
			return;
		}
		
		//No need to do in shopify
	}

	function get_tax_classes($use_cache = TRUE)
	{
		if (!$this->check_shopify_paid())
		{
			$this->log(lang('shopify_not_paid'));
			return;
		}
		
		//No need to do in shopify
	}

	function import_tax_classes_into_phppos()
	{
		if (!$this->check_shopify_paid())
		{
			$this->log(lang('shopify_not_paid'));
			return;
		}
		
		//No need to do in shopify
	}

	function export_tax_classes_into_phppos()
	{
		if (!$this->check_shopify_paid())
		{
			$this->log(lang('shopify_not_paid'));
			return;
		}
		
		//No need to do in shopify
	}

	public function save_tax_class($tax_class_id)
	{
		if (!$this->check_shopify_paid())
		{
			$this->log(lang('shopify_not_paid'));
			return;
		}
		
		//No need to do in shopify
	}

	function import_shipping_classes_into_phppos()
	{
		if (!$this->check_shopify_paid())
		{
			$this->log(lang('shopify_not_paid'));
			return;
		}
		
		//No need to do in shopify
	}
	
	private function make_product_data($item)
	{	
		if (!$this->check_shopify_paid())
		{
			$this->log(lang('shopify_not_paid'));
			return;
		}
					
		$item_id = $item->item_id;
		$this->load->model('Item_location');
		$item_location_info = $this->Item_location->get_info($item_id,$this->config->item('ecom_store_location') ? $this->config->item('ecom_store_location') : 1);
		
		$this->load->model('Item_variations');
		$variations = $this->Item_variations->get_all($item_id);
		$this->load->model('Item_taxes_finder');
		
		$taxable = $this->Item_taxes_finder->is_taxable($item_id);
		
		//Need to figure out quantity
		$quantity = $item->quantity;
		
		if ($this->config->item('online_price_tier'))
		{
			$this->load->model('Tier');
			$this->load->model('Item_location');
			$this->load->model('Item');
			$online_price = $this->Item->get_sale_price(array('item_id' => $item_id,'tier_id' => $this->config->item('online_price_tier')));	
		}
		else
		{
			$online_price = to_currency_no_money($item_location_info->unit_price ? $item_location_info->unit_price : $item->unit_price);
		}
				
		$data = array(
			'title' =>$item->name,
			'body_html' =>$item->long_description ? $item->long_description : $item->description,
			'product_type' => $this->Category->get_full_path($item->category_id),
			'published_scope' => 'web',
			'published' => TRUE,
			'status' => $item->item_inactive ? 'archived' : 'active',
			'inventory_management' => (isset($item->is_service) && $item->is_service) ? NULL : 'shopify',
			'ecommerce_product_quantity' => $this->ecommerce_store_locations ? $this->get_item_quantity($item_id) : $item->quantity
		);

		if($item->shopify_item_level_inventory_policy){
			$data['inventory_policy'] = $item->shopify_item_level_inventory_policy;
		} else {
			$data['inventory_policy'] = 'DENY';
		}
		if ($item->supplier_id)
		{
			$data['vendor'] = $this->Supplier->get_name($item->supplier_id);
		}
				
		$item_variations = $this->get_item_variations_for_ecommerce($item_id, TRUE);
		
		
		if (count($item_variations) == 0)
		{
			if ($item->ecommerce_first_variation_id)
			{
				$data['variants'][0]['id'] = $item->ecommerce_first_variation_id;				
			}
			
			$data['variants'][0]['price'] = $online_price;		
			$data['variants'][0]['cost'] = $item->cost_price;		
			$data['variants'][0]['inventory_management'] = (isset($item->is_service) && $item->is_service) ? NULL : 'shopify';		
			$data['variants'][0]['taxable'] = $taxable;
			$data['variants'][0]['weight'] = $item->weight;
			
			if ($item->weight_unit)
			{
				$data['variants'][0]['weight_unit'] = $item->weight_unit;
			}
			
			if($item->shopify_item_level_inventory_policy){
				$data['variants'][0]['inventory_policy'] = $item->shopify_item_level_inventory_policy;
			} else {
				$data['variants'][0]['inventory_policy'] = 'DENY';
			}
			
			if (!$item_location_info->promo_price)
			{
				if ($item->promo_price)
				{
					if (!$item->start_date && !$item->end_date)
					{
						$data['variants'][0]['price'] = to_currency_no_money($item->promo_price);;		
						$data['variants'][0]['compare_at_price'] = $online_price;
					}	
				}
			}
			else
			{
				if ($item_location_info->promo_price)
				{
					if (!$item->start_date && !$item->end_date)
					{
						$data['variants'][0]['price'] = to_currency_no_money($item_location_info->promo_price);;		
						$data['variants'][0]['compare_at_price'] = $online_price;
					}
				}
			}
		}
			
		$sync_field = $this->config->item('sku_sync_field') ? $this->config->item('sku_sync_field') : 'item_number';
		
		if (count($item_variations) == 0)
		{
			if($item->$sync_field)
			{
				$data['variants'][0]['sku'] = $item->$sync_field;
			}
			//Save the barcode field as the other field we didn't use for $sync_field
			if ($sync_field == 'item_number' && $item->product_id)
			{
				$data['variants'][0]['barcode'] = $item->product_id;
			}
			elseif($sync_field == 'product_id' && $item->item_number)
			{
				$data['variants'][0]['barcode'] = $item->item_number;			
			}
			elseif($item->item_number)
			{			
				$data['variants'][0]['barcode'] = $item->item_number;			
			}
		}
		
		$item_images = $this->get_all_item_images_for_ecommerce_with_main_image_1st($item_id);
		
		if(count($item_images) > 0 && !$this->config->item('do_not_upload_images_to_ecommerce'))
		{
			$data['images'] = array();
			
			$this->load->model('Appfile');
			
			foreach($item_images as $item_image)
			{
				$image_data = array('src' => shopify_app_file_url($item_image['image_id']));
				if($item_image['ecommerce_image_id'])
				{
					$image_data['id'] = $item_image['ecommerce_image_id'];
				}
				$data['images'][] = $image_data;
			}
		}
		elseif(count($item_images)  == 0 && !$this->config->item('do_not_upload_images_to_ecommerce'))
		{
			$data['images'] = array();
		}
		
		if (isset($item->tags))
		{
			$data['tags'] = $item->tags;
		}
		if ($item->supplier_id)
		{
			$data['vendor'] = $this->Supplier->get_name($item->supplier_id);
		}
		
		$options_for_variations = array();
		
		foreach($item_variations as $variation_id => $item_variation)
		{			
			$variation = array();
			
			$shopify_variation_id = $item_variation['ecommerce_variation_id'];
			
			if ($shopify_variation_id)
			{
				$variation['id'] = $shopify_variation_id;
			}
			
			$variation['taxable'] = $taxable;
			
			if ($item->weight_unit)
			{
				$variation['weight'] = $item->weight;
				$variation['weight_unit'] = $item->weight_unit ? $item->weight_unit : 'POUNDS';
			}
			if ($item_variation['item_number'])
			{
				$variation['sku'] = $item_variation['item_number'];
			}
			
			if ($this->config->item('online_price_tier'))
			{
				$this->load->model('Tier');
				$this->load->model('Item_location');
				$this->load->model('Item');
				$online_price = $this->Item->get_sale_price(array('item_id' => $item_id,'variation_id' => $item_variation['id'],'tier_id' => $this->config->item('online_price_tier')));	
			}
			else
			{
				$online_price = $item_variation['unit_price'] ? to_currency_no_money($item_variation['unit_price']) : '';
			}
			
			$variation['price'] = $online_price;
			$variation['cost'] = $item_variation['cost_price'] ?? $item->cost_price;
			
						
			if ($item_variation['promo_price'])
			{
				if (!$item_variation['start_date'] && !$item_variation['end_date'])
				{
					$variation['price'] = to_currency_no_money($item_variation['promo_price']);		
					$variation['compare_at_price'] = $online_price;
				}
			}
			
			
			$k=1;
			
			foreach($item_variation['attributes'] as $attribute)
			{
				$option_name = $attribute['attribute_name'];
				$option = $attribute['attribute_value_name'];
				$variation['option'.$k] = $option;		
				$variation['inventory_management'] = (isset($item->is_service) && $item->is_service) ? NULL : 'shopify';
				
				if (!isset($options_for_variations[$option_name]))
				{
					$options_for_variations[$option_name]['name'] = $option_name;
					$options_for_variations[$option_name]['values'] = array();
				}
				
				if (!in_array($option,$options_for_variations[$option_name]['values']))
				{
					$options_for_variations[$option_name]['values'][] = $option;
				}
				
				$k++;
			}
			
			$data['variants'][] = $variation;
			
		}
		
		$options = array();
		foreach($options_for_variations as $the_option)
		{
			$options[] = $the_option;
		}
		
		if (count($options) > 0)
		{
			$data['options'] = $options;		
		}
		
		$return = array();
		$return['product'] = $data;
		
		return $return;
	}

	function get_shopify_webhooks(){
		$query = <<<GQL
			query {
				webhookSubscriptions(first: 250) {
					edges {
						node { id topic }
					}
				}
			}
		GQL;
		$response = $this->run_graphql_query(['query' => $query]);
		return $response;
	}

	function delete_shopify_webhooks_if_needed()
	{
		$query = <<<GQL
			query {
				webhookSubscriptions(first: 250) {
					edges {
						node { id topic }
					}
				}
			}
GQL;
		$response = $this->run_graphql_query(['query' => $query]);
		$needed_hook_topics_to_delete = array(
			'PRODUCTS_CREATE',
			'PRODUCTS_UPDATE',
			'PRODUCTS_DELETE',
			'ORDERS_CREATE',
			'ORDERS_UPDATED',
			'ORDERS_EDITED',
			'ORDERS_DELETE',
		);

		if($response){
			foreach($response['data']['webhookSubscriptions']['edges'] as $edge){
				if (in_array($edge['node']['topic'],$needed_hook_topics_to_delete)){
					$mutation = <<<GQL
						mutation delHook(\$id: ID!) { webhookSubscriptionDelete(id: \$id) { userErrors { field message } } }
GQL;
					$this->run_graphql_query(['query' => $mutation, 'variables' => ['id' => $edge['node']['id']]]);
				}
			}
		}
	}

	function create_shopify_webhook($topic, $address)
	{
		$mutation = <<<GQL
			mutation createWebhook(\$topic: WebhookSubscriptionTopic!, \$callback: URL!) {
				webhookSubscriptionCreate(topic: \$topic, webhookSubscription: {callbackUrl: \$callback}) {
					webhookSubscription { id }
					userErrors { field message }
				}
			}
GQL;
		$response = $this->run_graphql_query(['query' => $mutation, 'variables' => ['topic' => $topic, 'callback' => $address]]);
		if (isset($response['data']['webhookSubscriptionCreate']['webhookSubscription']['id'])) {
			return $response['data']['webhookSubscriptionCreate']['webhookSubscription']['id'];
		}
		return FALSE;
	}

	public function update_inventory_from_sale($order, $reverse_inventory = false)
	{
		$line_items = $order['line_items'];

		$counter = 0;
		
		$sale_id = $this->get_sale_id_for_ecommerce_order_id($order['id']);
		
		if ($reverse_inventory && $sale_id)
		{
			//Call delete to reverse inventory and then undelete it
			$this->Sale->delete($sale_id, false, false, false);
			$this->db->where('sale_id',$sale_id);
			$this->db->update('sales', array('deleted' => 0,'deleted_by'=>NULL, 'last_modified' => date('Y-m-d H:i:s')));
			
		}
		//If a refunded order don't add back stock, just stick with reverse
		if ($order['financial_status'] == 'refunded')
		{
			return;
		}
		
		
		foreach($line_items as $line_item)
		{
			
			$shopify_product_id = $line_item['product_id'];
			$shopify_variation_id = $line_item['variant_id'];
			$phppos_item_id = $this->get_item_id_for_ecommerce_product($shopify_product_id);
			$tags = implode(',',$this->Tag->get_tags_for_item($phppos_item_id));
			$quantity_step = stripos($tags, 'decimal') !== false ? $this->inventory_step_count_for_non_integer : 1;
			$phppos_variation_id = $this->get_variation_id_for_ecommerce_product_variation($shopify_variation_id);
			$quantity = (($line_item['fulfillable_quantity'] ? $line_item['fulfillable_quantity'] : $line_item['quantity'])*$quantity_step)*-1;
		
			
			$location_id = $this->ecommerce_store_location;
						
			$inv_data = array
			(
				'trans_date'=>date('Y-m-d H:i:s'),
				'trans_items'=>$phppos_item_id,
				'trans_user'=>1,
				'trans_comment'=>$this->config->item('sale_prefix').' '.$sale_id,
				'trans_inventory'=>$quantity,
				'location_id' => $location_id,
			);
			
			if ($phppos_variation_id)
			{
				$inv_data['item_variation_id'] = $phppos_variation_id;
				$cur_item_variation_location_info = $this->Item_variation_location->get_info($phppos_variation_id,$location_id);
				$this->Item_variation_location->save_quantity($cur_item_variation_location_info->quantity + $quantity, $phppos_variation_id, $location_id);
				$cur_item_variation_location_info = $this->Item_variation_location->get_info($phppos_variation_id,$location_id);
				$inv_data['trans_current_quantity'] = $cur_item_variation_location_info->quantity;
				
			}
			elseif($phppos_item_id) //Normal item
			{
				$cur_item_location_info = $this->Item_location->get_info($phppos_item_id,$location_id);
				$this->Item_location->save_quantity($cur_item_location_info->quantity + $quantity, $phppos_item_id, $location_id);
				$cur_item_location_info = $this->Item_location->get_info($phppos_item_id,$location_id);
				$inv_data['trans_current_quantity'] = $cur_item_location_info->quantity;				
			}
			
			if ($phppos_variation_id || $phppos_item_id)
			{
				$this->Inventory->insert($inv_data);
			}
		}
		
	}
	
	function adjust_inventory($item_id, $item_variation_id, $adjust_qty, $comment)
	{
		$adjust_qty = (int)$adjust_qty;
		
	    $item_info = $this->Item->get_info($item_id);
		$tags = implode(',',$this->Tag->get_tags_for_item($item_id));
		
		$quantity_step = stripos($tags, 'decimal') !== false ? $this->inventory_step_count_for_non_integer : 1;

	    if (!$item_variation_id) 
		{
	        // Fetch product and its first variant using GraphQL
	        $query = <<<GRAPHQL
				query GetProduct(\$productId: ID!) {
					product(id: \$productId) {
						variants(first: 1) {
							edges {
								node {
									id
									inventoryItem {
										id
									}
									inventoryQuantity
								}
							}
						}
					}
				}
GRAPHQL;

			$variables = [
	            'productId' => 'gid://shopify/Product/' . $this->get_ecommerce_product_id($item_id),
	        ];

	        $response = $this->run_graphql_query(['query' => $query, 'variables' => $variables]);
	        $product = $response['data']['product']['variants']['edges'][0]['node'];

	        $current_stock = $product['inventoryQuantity'];
	        $new_quantity = $current_stock + $adjust_qty;

	        // Adjust inventory level using GraphQL mutation
	        // Adjust inventory level using GraphQL mutation
	        $mutation = <<<GRAPHQL
				mutation inventoryAdjustQuantities(\$input: InventoryAdjustQuantitiesInput!) {
					inventoryAdjustQuantities(input: \$input) {
						userErrors {
							field
							message
						}
						inventoryAdjustmentGroup {
							createdAt
							reason
							referenceDocumentUri
							changes {
								name
								delta
							}
						}
					}
				}
GRAPHQL;
			
			$variables = [
			    "input" => [
			        "reason" => "correction",
			        "name" => "available",
			        "changes" => [
			            [
			                "delta" => $adjust_qty/$quantity_step,
			                "inventoryItemId" => $product['inventoryItem']['id'],
			                "locationId" => "gid://shopify/Location/" . $this->config->item('shopify_location_id')
			            ]
			        ],
			    ],
			];
			
	        $this->run_graphql_query(['query' => $mutation, 'variables' => $variables]);
			
	    } 
		elseif ($item_variation_id) 
		{
	        // Fetch variant by ID using GraphQL
	        $query = <<<GRAPHQL
				query GetVariant(\$variantId: ID!) {
					productVariant(id: \$variantId) {
						id
						inventoryItem {
							id
						}
						inventoryQuantity
					}
				}
GRAPHQL;

	        $variables = [
	            'variantId' => 'gid://shopify/ProductVariant/' . $this->get_ecommerce_variation_id_for_variation($item_variation_id),
	        ];

	        $response = $this->run_graphql_query(['query' => $query, 'variables' => $variables]);
	        $variant = $response['data']['productVariant'];

	        $current_stock = $variant['inventoryQuantity'];
	        $new_quantity = $current_stock + $adjust_qty;

	        // Adjust inventory level using GraphQL mutation
	        $mutation = <<<GRAPHQL
				mutation inventoryAdjustQuantities(\$input: InventoryAdjustQuantitiesInput!) {
					inventoryAdjustQuantities(input: \$input) {
						userErrors {
							field
							message
						}
						inventoryAdjustmentGroup {
						createdAt
						reason
						referenceDocumentUri
							changes {
								name
								delta
							}
						}
					}
				}
GRAPHQL;
			
			$variables = [
			    "input" => [
			        "reason" => "correction",
			        "name" => "available",
			        "changes" => [
			            [
			                "delta" => $adjust_qty/$quantity_step,
			                "inventoryItemId" => $variant['inventoryItem']['id'],
			                "locationId" => "gid://shopify/Location/" . $this->config->item('shopify_location_id')
			            ]
			        ],
			    ],
			];
	        $this->run_graphql_query(['query' => $mutation, 'variables' => $variables]);
	    }
	}
	
	
	function delete_all_items()
	{


		$cursor = null;
		$hasNextPage = true;

		while ($hasNextPage) {
		    // GraphQL query to fetch products using pagination.
		    $queryProducts = <<<'GRAPHQL'
		    query getProducts($cursor: String) {
		      products(first: 250, after: $cursor) {
		        edges {
		          node {
		            id
		          }
		        }
		        pageInfo {
		          hasNextPage
		          endCursor
		        }
		      }
		    }
GRAPHQL;

		    $variables = ['cursor' => $cursor];
		    $responseProducts = $this->run_graphql_query([
		        "query"     => $queryProducts,
		        "variables" => $variables,
		    ]);
		    $dataProducts = $responseProducts;

		    if (!isset($dataProducts['data']['products']['edges'])) {
		        echo "No products found.\n";
		        break;
		    }

		    // Loop through each product.
		    foreach ($dataProducts['data']['products']['edges'] as $edge) {
		        $productId = $edge['node']['id'];
		        echo "Deleting product: {$productId}\n";

		        // Mutation to delete the product.
				$mutationProduct = <<<QUERY
				  mutation productDelete(\$input: ProductDeleteInput!, \$synchronous: Boolean!) {
				    productDelete(synchronous: \$synchronous, input: \$input) {
				      deletedProductId
				      productDeleteOperation {
				        id
				        status
				        deletedProductId
				      }
				    }
				  }
QUERY;

				$variables = [
				  "synchronous" => false,
				  "input" => [
				    "id" => "$productId",
				  ],
				];
		        $mutationVariables = ['id' => $productId];
		        $deleteProductResponse = $this->run_graphql_query([
		            "query"     => $mutationProduct,
		            "variables" => $variables,
		        ]);
		        $deleteProductData = $deleteProductResponse;

		        if (!empty($deleteProductData['data']['productDelete']['userErrors'])) {
		            foreach ($deleteProductData['data']['productDelete']['userErrors'] as $error) {
		                echo "Error deleting product: " . $error['message'] . "\n";
		            }
		        } else {
		            echo "Successfully deleted product: {$productId}\n";
		        }
		    }

		    // Update pagination information.
		    $pageInfo    = $dataProducts['data']['products']['pageInfo'];
		    $hasNextPage = $pageInfo['hasNextPage'];
		    $cursor      = $pageInfo['endCursor'];
		}

		echo "Finished processing products.\n";
	}
	
	function delete_all_collections()
	{
		//make sure to only delete php pos collections
		
		$cursor = null;
		$hasNextPage = true;

		while ($hasNextPage) {
		    // GraphQL query to fetch collections with a matching title using the pagination cursor.
		    $query = <<<'GRAPHQL'
		    query getCollections($cursor: String) {
		      collections(first: 10, after: $cursor) {
		        edges {
		          node {
		            id
		            title
		          }
		        }
		        pageInfo {
		          hasNextPage
		          endCursor
		        }
		      }
		    }
GRAPHQL;

		    // Pass the cursor if available.
		    $variables = ['cursor' => $cursor];
		    $response  = $this->run_graphql_query([
		        "query"     => $query,
		        "variables" => $variables,
		    ]);
		    $data = $response;

		    if (!isset($data['data']['collections']['edges'])) {
		        echo "No collections found.\n";
		        break;
		    }

		    // Loop through each collection returned.
		    foreach ($data['data']['collections']['edges'] as $edge) {
		        $collectionId    = $edge['node']['id'];
		        $collectionTitle = $edge['node']['title'];
				
				if (strpos($collectionTitle, '>') === false) {
				    continue;
				}
				
		        echo "Deleting collection: {$collectionTitle} ({$collectionId})\n";

		        // Mutation to delete the collection.
		        $mutation = <<<QUERY
  mutation collectionDelete(\$input: CollectionDeleteInput!) {
    collectionDelete(input: \$input) {
      deletedCollectionId
      shop {
        id
        name
      }
      userErrors {
        field
        message
      }
    }
  }
QUERY;

				$mutationVariables = [
				  "input" => [
				    "id" => $collectionId,
				  ],
				];

		        $deleteResponse = $this->run_graphql_query([
		            "query"     => $mutation,
		            "variables" => $mutationVariables,
		        ]);
		        $deleteData = $deleteResponse;

		        if (!empty($deleteData['data']['collectionDelete']['userErrors'])) {
		            foreach ($deleteData['data']['collectionDelete']['userErrors'] as $error) {
		                echo "Error deleting collection: " . $error['message'] . "\n";
		            }
		        } else {
		            echo "Successfully deleted collection: {$collectionId}\n";
		        }
		    }

		    // Update the cursor and pagination info.
		    $pageInfo   = $data['data']['collections']['pageInfo'];
		    $hasNextPage = $pageInfo['hasNextPage'];
		    $cursor     = $pageInfo['endCursor'];
		}

		echo "Finished processing collections.\n";
	}
	
}
	
?>