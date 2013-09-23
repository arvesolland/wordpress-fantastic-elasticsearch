<?php
namespace elasticsearch;

/**
* This class provides numerous helper methods for working with facet information returned from elastic search results.
*
* @license http://opensource.org/licenses/MIT
* @author Paris Holley <mail@parisholley.com>
* @version 2.0.0
**/
class Faceting{
	/**
	* A convenient method that aggregates the results of all the other methods in the class. Example of output:
	*
	* <code>
	* 	array(
	*		// the keys are names of fields and/or taxonomies
	* 		'taxonomy' => array(
	* 			'available' => array(
	* 				'taxonomy1'	=> array(
	* 					'count'	=> 10,
	* 					'slug'	=> 'taxonomy1',
	* 					'name'	=> 'Taxonomy One',
	* 					'font'	=> 24
	* 				)
	* 			),
	* 			'selected' => array(
	* 				'taxonomy2'	=> array(
	* 					'slug'	=> 'taxonomy2',
	* 					'name'	=> 'Taxonomy Two'
	* 				)
	* 			),
	* 			'total' => 10
	* 		),
	* 		'rating' => array(
	* 			'available' => array(
	* 				'10-20' => array(
	* 					'count'	=> 4,
	* 					'slug'	=> '10-20',
	* 					'to'	=> 20,
	* 					'from'	=> 10
	* 				)			
	* 			),
	* 			'total' => 4
	* 		)
	* 	)
	* </code>
	* 
	* @param string $minFont The minimum font size to use for display in a tag cloud (defaults to : 12)
	* @param string $maxFont The maximum font size to use for display in a tag cloud (defaults to : 24)
	* 
	* @return array An associative array where the keys represent the data point with a list of selected and/or available options.
	**/
	static function all($minFont = 12, $maxFont = 24){
		$options = array();

		foreach(Config::taxonomies() as $tax){
			$options[$tax] = self::taxonomy($tax);
		}



		$numeric = Config::option('numeric');

		foreach(Config::fields() as $field){
			if(isset($numeric[$field])){
				$options[$field] = self::range($field);
			}
		}



		foreach($options as $name => &$field){
			foreach($field['available'] as &$available){
				$available['font'] = self::cloud($field['available'], $available, $minFont, $maxFont);
			}
		}
		
		return $options;
	}

	/**
	* Analyse query parameters for range slugs and determine which facets are selected vs. which are available for the given field. Example of output:
	* 
	* <code>
	* 	array(
	* 		'available' => array(
	* 			'10-20' => array(
	* 				'count'	=> 4,
	* 				'slug'	=> '10-20',
	* 				'to'	=> 20,
	* 				'from'	=> 10
	* 			)			
	* 		),
	* 		'selected' => array(
	* 			'-20' => array(
	* 				'slug'	=> '-20',
	* 				'to'	=> 20
	* 			)			
	* 		),
	* 		'total' => 4
	* 	)
	* 	</code>
	* 
	* @param string $field The field to determine range facet information about
	*
	* @return array An associative array based on example provided
	**/
	static function range($field){
		global $wp_query;

		$facets = $wp_query->facets;

		$result = array(
			'selected' => array(),
			'available' => array(),
			'total' => 0
		);

		$ranges = Config::ranges($field);



		if($ranges){
			foreach($ranges as $slug => $range){
				$split = explode('-', $slug);

				$item = array(
					'slug' => $slug,
					'count' => $facets[$field][$slug],
					'to' => $split[1],
					'from' => $split[0]
				);

				if(isset($_GET[$field]) && in_array($slug, $_GET[$field]['and'])){
					$result['selected'][$slug] = $item;
				}else if($item['count'] > 0){
					$result['available'][$slug] = $item;
					$result['total'] += $item['count'];
				}
			}
		}

		print 'ranges: ';
		debug($result);

		return $result;
	}

	/**
	* Analyse query parameters for taxonomoy slugs and determine which facets are selected vs. which are available for the given field. Example of output:
	* 
	* <code>
	* 	array(
	* 		'available' => array(
	* 			'taxonomy1' => array(
	* 				'count' => 10,
	* 				'slug'	=> 'taxonomy1',
	* 				'name'	=> 'Taxonomy One',
	* 				'font'	=> 24
	* 			)
	* 		),
	* 		'selected' => array(
	* 			'taxonomy2'	=> array(
	* 				'slug'	=> 'taxonomy2',
	* 				'name'	=> 'Taxonomy Two'
	* 			)
	* 		),
	* 		'total' => 10
	* 	)
 	* 	</code>
	* 
	* @param string $field The taxonomy type to retrieve facet information about
	* 
	* @return array An associative array based on example provided
	**/
	static function taxonomy($tax){
		global $wp_query;

		$facets = $wp_query->facets;

		

		$taxonomy = array(
			'selected' => array(),
			'available' => array(),
			'total' => 0
		);

		if(isset($facets[$tax])){
			foreach(get_terms($tax) as $term){
				$item = array(
					'name' => $term->name ?: $term->slug,
					'slug' => $term->slug
				);

				

				$current_url_param = $_GET[$tax];
				
				
				if (isset($current_url_param)){
					if (is_array($current_url_param)) {
						$isSelected = false;

						if (is_array($current_url_param['and'])){
							//print ('DB1');
							if (in_array($term->slug, $current_url_param['and'])) {
								//print ('DB2');
								
								$taxonomy['selected'][$term->slug] = $item;
								//print $tax . ' - ' . $term->slug . ' selected 1<br/>'; 
								$isSelected = true;
							} 
						}
						
						if (in_array($term->slug, $current_url_param)) {
							$taxonomy['selected'][$term->slug] = $item;
							//print $tax . ' - ' . $term->slug . ' selected <br/>'; 
							$isSelected = true;
						} 
						
					} 
				} 
				if (!$isSelected){
					$count = $item['count'] = $facets[$tax][$term->slug];

					if($count > 0){
						//print $tax . ' - ' . $term->slug . ' available 3 <br/>'; 	
						$taxonomy['available'][$term->slug] = $item;
						$taxonomy['total'] += $item['count'];
					}
				}
				//debug($taxonomy);

				/*
				if(isset($_GET[$tax]) && in_array($term->name, $_GET[$tax]['and'])){
					$taxonomy['selected'][$term->name] = $item;
				}else if(isset($facets[$tax][$term->slug])){
					$count = $item['count'] = $facets[$tax][$term->slug];

					if($count > 0){
						$taxonomy['available'][$term->slug] = $item;
						$taxonomy['total'] += $item['count'];
					}
				}
				*/
			}
		}

		return $taxonomy;
	}

	/**
	* Will calculate a font size based on the total number of results for the given item in a collection of items. Example of output:
	* 
	* <code>
	* 	array(
	* 		'available' => array(
	* 			'taxonomy1' => array(
	* 				'count' => 10,
	* 				'slug'	=> 'taxonomy1',
	* 				'name'	=> 'Taxonomy One',
	* 				'font'	=> 24
	* 			)
	* 		),
	* 		'selected' => array(
	* 			'taxonomy2' => array(
	* 				'slug'	=> 'taxonomy2',
	* 				'name'	=> 'Taxonomy Two'
	* 			)
	* 		),
	* 		'total' => 10
	* 	)
 	* </code>
	* 
	* @param array $items An array of arrays that contain a key called 'count'
	* @param array $item An item out of the array that you wish to calculate a font size
	* @param string $minFont The minimum font size to use for display in a tag cloud (defaults to : 12)
	* @param string $maxFont The maximum font size to use for display in a tag cloud (defaults to : 24)
	* 
	* @return integer The calculated font size
	**/
	static function cloud($items, $item, $min = 12, $max = 24){
		$maxTotal = 0;

		foreach($items as $itm){
			if(log($itm['count']) > $maxTotal){
				$maxTotal = log($itm['count']);
			}
		}
		if ($maxTotal > 0){
			return floor((log($item['count']) / $maxTotal) * ($max - $min) + $min);
		} 
		return 0;
		
	}

	/**
	* Modifies the provided URL by appending query parameters for faceted searching.
	* 
	* @param string $url The URL of the page that supports F.E.S
	* @param string $type The data point you wish to enable faceting for (ie: a field name or taxonomy name)
	* @param string $value The value/slug that was provided by another method call in this class
	* @param string $operation Whether the facet should query using 'and' or 'or' (defaults to and)
	* 
	* @return string The URL modified to support faceting
	**/
	static function urlAdd($url, $type, $value, $operation = 'and'){
		$filter = $_GET;

		//print $url .' | '.$type.' | '.$value;

		$op = $operation;

		if(isset($filter[$type])){
			$op = array_keys($filter[$type]);
			$op = $op[0];
			$current_values = $filter[$type][$op];
		}

		//print 'current values:';debug($current_values);


		if ( isset($current_values) ) {
			array_push($current_values, $value);
			$filter[$type][$op] = $current_values;
		} else {
			$filter[$type][$op][0] = $value;
		}

		//debug($filter);
	

		$url = new \Purl\Url($url);



		$url->query->setData($filter);
		//debug($url->getUrl());
		return $url->getUrl();
	}

	/**
	* Modifies the provided URL by removing query parameters that control faceting.
	* 
	* @param string $url The URL of the page that supports F.E.S
	* @param string $type The data point you wish to remove faceting for (ie: a field name or taxonomy name)
	* @param string $value The value/slug that was provided in the URL (query parameters)
	* 
	* @return string The URL modified to remove faceting for the provided data point
	**/
	static function urlRemove($url, $type, $value){
		$filter = $_GET;

		//debug($filter);

		$operation = isset($filter[$type]['and']) ? 'and' : 'or';

		if(isset($filter[$type][$operation])){
			$index = array_search($value, $filter[$type][$operation]);

			if($index !== false){
				unset($filter[$type][$operation][$index]);

				if(count($filter[$type][$operation]) == 0){
					unset($filter[$type][$operation]);
				}

				if(count($filter[$type]) == 0){
					unset($filter[$type]);
				}
			}
		}

		$url = new \Purl\Url($url);
		$url->query->setData($filter);

		return $url->getUrl();
	}

	static function getTaxFacet($facets, $tax, $title, $type = 'standard'){
		//debug($tax);
		//debug($facets[$tax]);
		$html = "";
		if ( isset($facets[$tax]) ) {
			$html .= '<div class="search-facet">';
			$html .= '<h5>' . $title . '</h5>';

	        $html .= '<ul>';
	        foreach($facets[$tax]['selected'] as $option){
	            $url = Faceting::urlRemove(home_url(), $tax, $option['slug']);
	            $html .= '<li><a href="' . $url . '">(x) ' . $option['name'] . ' <a/></li>';
	        }
	        $html .= '</ul>';	

	        $html .= '<ul>';
	        foreach($facets[$tax]['available'] as $option){
	            $url = Faceting::urlAdd(home_url(), $tax, $option['slug']);
	            $html .= '<li><a href="' . $url . '">' . $option['name'] . ' <a/></li>';
	        }
	        $html .= '</ul>';
	        $html .= '</div>';

		}
		return $html;
		

	}

	static function getFacetField($facets, $field, $title, $type = 'standard'){
		//debug($tax);
		//debug($facets[$tax]);
		$html = "";
		//debug($facets);
		$facet_array = self::setupFacetField($facets, $field);
		//debug($facet_array);

		if ( isset($facet_array) ) {
			$html .= '<div class="search-facet">';
			$html .= '<h5>' . $title . '</h5>';
	        $html .= '<ul>';
	        if (isset($facet_array['selected'])) {
		        foreach($facet_array['selected'] as $option){
		        	//debug($option);
		            $url = Faceting::urlRemove(home_url(), $field, $option['name']);
		            $html .= '<li class="selectedFacetItem"><a href="' . $url . '"><span class="removeFacetSymbol">(x)</span> ' . $option['name'] . ' </a></li>';
		        }
		        $html .= '</ul>';	
	    	}

	    	if (isset($facet_array['available'])) {
		        $html .= '<ul>';
		        foreach($facet_array['available'] as $option){
		            $url = Faceting::urlAdd(home_url(), $field, $option['name']);
		            $html .= '<li class="availableFacetItem"><a href="' . $url . '">' . $option['name'] . ' ('.$option['count'].')</a></li>';
		        }
		        $html .= '</ul>';
	    	}
	    	$html .= '</div>';

		}
		return $html;
		

	}

	static function setupFacetField($facets, $field) {
			$facet_array = array();
			$current_url_param = $_GET[$field];
			
				
			if(isset($facets[$field])){
			//debug($facets[$field]);

			foreach($facets[$field] as $term => $count){
				$item = array(
					'name' => $term,
					'count' => $count
				);
				$current_url_param = $_GET[$field];
				
				
				if (isset($current_url_param)){
					if (is_array($current_url_param)) {
						$isSelected = false;

						if (is_array($current_url_param['and'])){
							//print ('DB1');
							if (in_array($term, $current_url_param['and'])) {
								//print ('DB2');
								
								$facet_array['selected'][$term] = $item;
								//print $tax . ' - ' . $term->slug . ' selected 1<br/>'; 
								$isSelected = true;
							} 
						}
						
						if (in_array($term, $current_url_param)) {
							$facet_array['selected'][$term] = $item;
							//print $tax . ' - ' . $term->slug . ' selected <br/>'; 
							$isSelected = true;
						} 
						
					} 
				} 
				if (!$isSelected){
					$count = $item['count'] = $facets[$field][$term];

					if($count > 0){
						//print $tax . ' - ' . $term->slug . ' available 3 <br/>'; 	
						$facet_array['available'][$term] = $item;
						$facet_array['total'] += $item['count'];
					}
				}
				
			}
		}
		return $facet_array;
	}
}

?>
