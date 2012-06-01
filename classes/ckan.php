<?php
/**
 * Adaptation of Sean Burlington's code designed for the Drupal CKAN Module
 * by Sean Hudson, City of Arvada
 *
 * @author Sean Burlington sean@practicalweb.co.uk
 * @copyright 2010 PracticalWeb Ltd (UK company number 06427950) 
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */
class Ckan {
	/* start -- some setting you might want to change */
	public $url = 				'http://colorado.ckan.net/';
	public $group = 			'arvada'; // the default group for your catalog (this is the group on CKAN)
	public $tag_filters = 		array('arvada','colorado'); // these tags will not show in tag results in your catalog
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

	public function __construct($url=null){
		if ($url){
			$this->url=$url;
		}
	}

	private function transfer($url){

		$ch = curl_init($this->url . $url);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		$result = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);
		if ($info['http_code'] != 200){
			$result = $info['http_code'] . ' : ' . $this->error_codes["$info[http_code]"];
		}
		if (!$result){
			$result = "No Result";
		}

		return json_decode($result);
	}
	
	/* filter tag reaults and omit certain tags you don't want to show */
	public function stringFilter($array) {
		foreach($array as $obj) {
			if(!in_array($obj, $this->tag_filters)) {
				$objs[] = $obj;
			}
		}
		return $objs;
	}
	
	
	/* search packages - offset is the number of the first result and limit is the number of results to return. Specify either rank or the field to sort the results by */
	public function searchAll($string='',$offset='0',$limit='20',$order='rank'){
		if($order == '') {
			$order = 'rank'; // CKAN API >= 1.4 empty value not allowed, default is rank		
		}	
		$results = $this->transfer('api/2/search/package?all_fields=1&offset='.$offset.'&limit='.$limit.'&order_by='.$order.'&q='.urlencode($string).'&groups='.urlencode($this->group));
		if (!$results->count){
			$results = array('error' => 'Search Error', 'msg' => 'No results were found.');
		}
		return $results;
	}
	
	/* search resources - offset is the number of the first result and limit is the number of results to return. Specify either rank or the field to sort the results by */
	public function searchResources($format,$offset='0',$limit='20'){
		$sql_results = $this->searchAll('',0,1000,'');
		
		// go through each and get those with specific format
		foreach($sql_results as $result) {
			$results .= $result['results']['resources']['format'].'<br />';
		}
		
		return $results;
	}
	
	/* get pkgs with tag - offset is the number of the first result and limit is the number of results to return. Specify either rank or the field to sort the results by */
	public function getTag($tag,$offset='0',$limit='20',$order='') {
		$results = $this->transfer('/api/rest/tag/'.urlencode($tag).'?all_fields=1&offset='.$offset.'&limit='.$limit.'&order_by='.$order);
		if (!$results){
			$results = array('error' => 'Search Error', 'msg' => 'No results were found for tag \''.$tag.'\'.');
		}
		return $results;
	}

   	/* get a specific package */
	public function getPackage($package){
		$package = $this->transfer('api/2/rest/package/'.urlencode($package));
		if (!$package){
			$package = array('error' => 'Package Load Error', 'msg' => 'Sorry that package could not be loaded.');
		}
		
		return $package;
	}

	/* get a list of all packages */
	public function getPackageList(){
		$list =  $this->transfer('api/2/rest/package');
		if (!is_array($list)){
			$list = array('error' => 'Package List Error', 'msg' => 'Sorry the packages could not be loaded.');
		}
		return $list;
	}
	
	/* get a specific group */
	public function getGroup($group){
		$group = $this->transfer('api/2/rest/group/' . urlencode($group) );
		if (!$group->name){
			$group = array('error' => 'Group Error', 'msg' => 'Sorry that group could not be loaded.');
		}
		return $group;
	}
	
	/* get a list of all groups */
	public function getGroupList(){
		$groupList = $this->transfer('api/2/rest/group');
		if (!is_array($groupList)){
			$groupList = array('error' => 'Group List Error', 'msg' => 'Sorry the groups could not be loaded.');
		}
		return $groupList;
	}
	
	/* get a list of tags for cloud */
	public function getTagList(){
		$pkgs = $this->searchAll('',0,1000,'');
		$tags = array();
		foreach($pkgs->results as $result) {
			if(isset($result->tags)) {
				foreach($result->tags as $tag) {
					$tags[] .= $tag;
				}
			}
		}
		$tags = array_count_values($this->stringFilter($tags));
		
		return $tags;
	}
}
?>