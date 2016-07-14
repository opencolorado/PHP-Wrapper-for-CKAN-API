<?php
/**
 * Adaptation of Sean Burlington's code designed for the Drupal CKAN Module
 * Originally adapted by Sean Hudson, City of Arvada
 * 06/20/2016 - Revised by Ron Pringle, City of Boulder
 *
 * @author Sean Burlington sean@practicalweb.co.uk
 * @copyright 2010 PracticalWeb Ltd (UK company number 06427950) 
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */
class Ckan {
	/* start -- some setting you might want to change */
	public $url = 				'http://demo.opencolorado.org/';
	public $group = 			'boulder'; // the default group for your catalog (this is the group on CKAN)
	public $org =				'boulder-org'; // the default organization for your catalog (this is the organization on CKAN)
	public $tag_filters = 			array('boulder','colorado', 'City of Boulder'); // these tags will not show in tag results in your catalog
	/* end -- some setting you might want to change */
	
	private $errors = array( 
								'0'  =>   'Network Error?',
							  	'301'  =>   'Moved Permanently',
							  	'400'  =>   'Bad Request',
							  	'403'  =>   'Not Authorized',
							  	'404'  =>   'Not Found',
							  	'409'  =>   'Conflict (e.g. name already exists)',
							  	'500'  =>   'Internal Server Error', 
	);

	public function __construct($url = null)
	{
		if ($url)
		{
			$this->url = $url;
		}
	}

	private function transfer($url)
	{
		$ch = curl_init($this->url . $url);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		$result = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);
		if ($info['http_code'] != 200)
		{
			$result = '{"' . $info['http_code'] . '":"' . $this->errors["$info[http_code]"] . '"}';
		}
		if (!$result)
		{
			$result = "No Result";
		}

		return json_decode($result);
	}
	
	// Filter tag results and omit certain tags you don't want to show
	public function stringFilter($array)
	{
		$objs = array();
		foreach ($array as $obj)
		{
			if (!in_array($obj, $this->tag_filters))
			{
				$objs[] = $obj;
			}
		}
		return $objs;
	}
	
	
	// Search packages - offset is the number of the first result and limit is the number of results to return.
	public function searchAll($string='', $offset = '0', $limit = '20', $order = 'rank', $direction = 'desc')
	{
		// CKAN API >= 1.4 empty value not allowed, defaults to rank
	    	$order = ($order == '') ? 'rank' : $order;
        	// CKAN API >= 3 include sort direction as well, defaults to desc
        	$direction = ($direction == '') ? 'desc' : $direction;
        	// CKAN API >= 3 include a space between search string and organization
        	$string = ($string != '') ? $string . ' ' : $string;
        
		$results = $this->transfer('api/3/action/package_search?q=' . urlencode($string) . 'organization:' . urlencode($this->org) . '&start=' . $offset . '&rows=' . $limit . '&sort=' . $order . '%20' . $direction);
		if (!$results->result->count)
		{
			$results = array('error' => 'Search Error', 'msg' => 'No results were found.');
		}
		return $results;
	}
	
	// Search resources - offset is the number of the first result and limit is the number of results to return.
	public function searchResources($format, $offset = 0, $limit = 20)
	{
		$sql_results = $this->searchAll('', 0, 1000, '');
		
		// Go through each and get those with specific format
		foreach ($sql_results as $result)
		{
			$results .= $result['results']['resources']['format'] . '<br />';
		}
		
		return $results;
	}
	
	// Get pkgs with tag - offset is the number of the first result and limit is the number of results to return.
	public function getTag($tag, $offset = 0, $limit = 20, $order = '')
	{
		$results = $this->transfer('/api/rest/tag/' . urlencode($tag) . '?all_fields=1&offset=' . $offset . '&limit=' . $limit . '&order_by=' . $order);
		if (!$results)
		{
			$results = array('error' => 'Search Error', 'msg' => 'No results were found for tag \'' . $tag . '\'.');
		}
		return $results;
	}

   	// Get a specific package
	public function getPackage($package)
	{
		$package = $this->transfer('api/2/rest/package/' . urlencode($package));
		if (!$package)
		{
			$package = array('error' => 'Package Load Error', 'msg' => 'Sorry that package could not be loaded.');
		}
		
		return $package;
	}

	// Get a list of all packages
	public function getPackageList()
	{
		$list = $this->transfer('api/2/rest/package');
		if (!is_array($list))
		{
			$list = array('error' => 'Package List Error', 'msg' => 'Sorry the packages could not be loaded.');
		}
		return $list;
	}
	
	// Get a specific organization
	public function getOrganization($id,
                                    	$include_datasets = 0,
                                    	$include_extras = 1,
                                    	$include_users = 1,
                                    	$include_groups = 1,
                                    	$include_tags = 1,
                                    	$include_followers = 1
                                    	)
	{

        	$params = '?id=' . urlencode($id); // Required. Name or ID of organization
        	$params .= '&include_datasets=' . $include_datasets; // Defaults to no
        	$params .= '&include_extras=' . $include_extras; // Defaults to yes
        	$params .= '&include_users=' . $include_users; // Defaults to yes
        	$params .= '&include_groups=' . $include_groups; // Defaults to yes
        	$params .= '&include_tags=' . $include_tags; // defaults to yes
        	$params .= '&include_followers=' . $include_followers; // defaults to yes
        	$org = $this->transfer('api/3/action/organization_show' . $params);
        	if (!isset($org->success))
        	{
            		$org = array('error' => 'Organization Error', 'msg' => 'Sorry that organization could not be loaded.');
        	}
        	return $org;
    	}
    	
    	// Get a list of organizations
    	public function getOrganizationList($sort = 'name asc',
                                            $limit = 10,
                                            $offset = 0,
                                            $orgs = 'all',
                                            $all_fields = 0,
                                            $include_extras = 0,
                                            $include_tags = 0,
                                            $include_groups = 0,
                                            $include_users = 0)
    	{
	        $params = '?sort=' . urlencode($sort); // Defaults to name asc
	        $params .= '&limit=' . $limit; // Defaults to 10
	        $params .= '&offset=' . $offset; // Defaults to 0
	        $params = ($orgs != 'all') ? $params . '&orgs=' . urlencode($orgs) : $params; // Optional, string list of orgs to include
	        $params .= '&all_fields=' . $all_fields; // Defaults to no
	        $params .= '&include_extras=' . $include_extras; // Defaults to no
	        $params .= '&include_tags=' . $include_tags; // Defaults to no
	        $params .= '&include_groups=' . $include_groups; // Defaults to no
	        $params .= '&include_users=' . $include_users; // Defaults to no
	        
	        $orgList = $this->transfer('api/3/action/organization_list' . $params);
	        
	        if (!isset($orgList->success))
	        {
	            $orgList = array('error' => 'Organization List Error', 'msg' => 'Sorry the list of organizations could not be loaded.');
	        }
	        return $orgList;
    	}
    	
	// Get a specific group
	public function getGroup($id,
                             $include_datasets = 0,
                             $include_extras = 1,
                             $include_users = 1,
                             $include_groups = 1,
                             $include_tags = 1,
                             $include_followers = 1)
	{
		$params = '?id=' . urlencode($id); // Required. Name or ID of group
		$params .= '&include_datasets=' . $include_datasets; // Defaults to no
		$params .= '&include_extras=' . $include_extras; // Defaults to yes
		$params .= '&include_users=' . $include_users; // Defaults to yes
		$params .= '&include_groups=' . $include_groups; // Defaults to yes
		$params .= '&include_tags=' . $include_tags; // Defaults to yes
		$params .= '&include_followers=' . $include_followers; // Defaults to yes
        
        	$group = $this->transfer('api/3/action/group_show/' . $params);
		if (!isset($group->success))
		{
			$group = array('error' => 'Group Error', 'msg' => 'Sorry that group could not be loaded.');
		}
		return $group;
	}
	
	// Get a list of groups
	public function getGroupList($sort = 'name asc',
                                 $limit = 10,
                                 $offset = 0,
                                 $groups = 'all',
                                 $all_fields = 0,
                                 $include_extras = 0,
                                 $include_tags = 0,
                                 $include_groups = 0,
                                 $include_users = 0)
	{
	    	$params = '?sort=' . urlencode($sort); // Defaults to name asc. Allowed fields are ‘name’, ‘package_count’ and ‘title’
        	$params .= '&limit=' . $limit; // Defaults to 10
        	$params .= '&offset=' . $offset; // Defaults to 0
        	$params = ($groups != 'all') ? $params . '&orgs=' . urlencode($groups) : $params; // Optional, string list of groups to include
        	$params .= '&all_fields=' . $all_fields; // Defaults to no
        	$params .= '&include_extras=' . $include_extras; // Defaults to no
        	$params .= '&include_tags=' . $include_tags; // Defaults to no
        	$params .= '&include_groups=' . $include_groups; // Defaults to no
        	$params .= '&include_users=' . $include_users; // Defaults to no
        
        
		$groupList = $this->transfer('api/3/action/group_list' . $params);
		if (!isset($groupList->success))
		{
			$groupList = array('error' => 'Group List Error', 'msg' => 'Sorry the groups could not be loaded.');
		}
		return $groupList;
	}
	
	// Get a list of tags for popular tags list
	public function getTagList()
	{
		$pkgs = $this->searchAll('', 0, 1000, '');
		$tags = array();
		
		foreach ($pkgs->result->results as $result)
		{
			if (isset($result->tags))
			{
				foreach ($result->tags as $tag)
				{
					$tags[] .= $tag->display_name;
				}
			}
		}
		// Filter for tags we don't want included
        	$tags = $this->stringFilter($tags);
        	// Create an array of counted tags
		$tags = array_count_values($tags);
		// Sort tags from most frequent to least
        	arsort($tags);
        	
		return $tags;
	}
}
?>
