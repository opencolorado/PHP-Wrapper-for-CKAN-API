<?php

define("CATALOG_PATH",             	"");
//define("CAPTCHA_PUBLIC", 		    "##################");
//define("CAPTCHA_PRIVATE", 		"##################");

// open_data.php
include "header.php";

require_once 'Pager/Sliding.php'; // you need PEAR Pager installed on your server
require_once 'classes/database/DB_Pager_Sliding.php';
require_once 'classes/ckan.php';
require_once 'classes/recaptchalib.php';

$ckan = new Ckan();

if(isset($_GET['d'])) { // show selected dataset
	$pkg = $ckan->getPackage($_GET['d']);

	$display .= '<div id="ckan-right-col">
		<form id="page-search" action="search/" method="get">
			<input name="q" type="text" tabindex="1" class="search-text" id="search-field" value="'.$_REQUEST['q'].'" />
			<input type="submit" class="search-submit" value="Search" />
		</form>
		<h2 class="underline">Tags</h2>
		<div class="item-list">
			<ul>';
				if(isset($pkg->tags) && count($pkg->tags) > 1) {
					$pkg->tags = $ckan->stringFilter($pkg->tags);
					foreach($pkg->tags as $tag) {
						$display .= '<li><a href="/'.CATALOG_PATH.'/tag/'.$tag.'">'.$tag.'</a></li>';
					}
				}
				else { 
					$display .= '<li>This package has no tags.</li>';
				}
			$display .= '</ul>
		</div>
		
		<h2 class="underline">Rating</h2>
		<div class="rating-group">
			<div class="inline-rating">
				Rate this data set.<br />
				<ul class="stars default'.round($pkg->ratings_average).'star">
					<li class="one"><a href="'.$ckan->url.'/package/rate/'.$pkg->name.'?rating=1" title="1 Star">1</a></li>
					<li class="two"><a href="'.$ckan->url.'/package/rate/'.$pkg->name.'?rating=2" title="2 Star">2</a></li>
					<li class="three"><a href="'.$ckan->url.'/package/rate/'.$pkg->name.'?rating=3" title="3 Star">3</a></li>
					<li class="four"><a href="'.$ckan->url.'/package/rate/'.$pkg->name.'?rating=4" title="4 Star">4</a></li>
					<li class="five"><a href="'.$ckan->url.'/package/rate/'.$pkg->name.'?rating=5" title="5 Star">5</a></li>
				</ul>';
				if($pkg->ratings_count == 0) {
					$display .= 'No ratings yet – rate it now<br />';
				}
				else {
					$display .= 'Average rating: '.$pkg->ratings_average.'<br />';
				}
			$display .= '</div>

		</div>
		<h2 class="underline">Your Open License to the Datasets</h2>
		<p>The ORGANIZATION grants you a world-wide, royalty-free, non-exclusive license to use, modify, and distribute the datasets in all current and future media and formats for any lawful purpose.</p>
		<h2 class="underline">About This Catalog</h2>
		<p>This catalog was created in partnership with Open Colorado. For additional information visit <a href="http://opencolorado.org">http://opencolorado.org</a>.</p>
	</div>
	<div id="ckan-main-col">
		<div style="float:right;"><a href="'.$ckan->url.'/package/history/'.$pkg->name.'?format=atom&days=7"><img src="images/icons/socialmedia/rss_16.png" alt="Subscribe" height="12" width="12" /></a></div>
		<h2>'.$pkg->title.'</h2>
		<div class="ckan-name">
			('.$pkg->name.')
		</div>
		<div class="notes">
			<p>'.$pkg->notes.'</p>
		</div>

		<div class="resources">
			<h3>Resources</h3>
			<table class="ckan-table" cellpadding="0" cellspacing="0">
				<tr>
					<th>URL</th>
					<th>Format</th>
					<th>Description</th>
					<th>Hash</th>
				</tr>';
				if(isset($pkg->resources)) {
					foreach($pkg->resources as $resource) {
						$display .= '<tr>
							<td class="resource-field col1"><a href="'.$resource->url.'" class="download">Download</a></td>
							<td class="resource-field">'.$resource->format.'</td>
							<td class="resource-field">'.$resource->description.'</td>
							<td class="resource-field">&nbsp;</td>
						</tr>';
					}
				}
			$display .= '</table>
		</div>

		<div class="details">
			<h3>Details</h3>
			<table class="ckan-table" cellpadding="0" cellspacing="0">
				<tbody>
					<tr>
						<td class="package-label">Level of Government</td>
						<td class="package-details">'.$pkg->extras->level_of_government.'</td>
					</tr>
					<tr>
						<td class="package-label">Agency</td>';
						if(isset($pkg->maintainer_email) && trim($pkg->maintainer_email) != '') {
							$display .= '<td class="package-details"><a href="#" onclick="Popup=window.open(\''.recaptcha_mailhide_url(CAPTCHA_PUBLIC,CAPTCHA_PRIVATE,$pkg->maintainer_email).'\',\'Popup\',\'toolbar=no,location=no,status=no,menubar=no,scrollbars=no,resizable=no,width=500,height=300,left=430,top=23\'); return false;">'.$pkg->extras->agency.'</a></td>';
						}
						else {
							$display .= '<td class="package-details">'.$pkg->extras->agency.'</td>';
						}
					$display .= '</tr>
					<tr>
						<td class="package-label">Update Frequency</td>
						<td class="package-details">'.$pkg->extras->update_frequency.'</td>
					</tr>
					<tr>
						<td class="package-label">Temporal Coverage</td>
						<td class="package-details">'.$pkg->extras->{'temporal_coverage-from'};
						if(isset($pkg->extras->{'temporal_coverage-to'}) && trim($pkg->extras->{'temporal_coverage-to'}) != '') {
							$display .= ' - '.$pkg->extras->{'temporal_coverage-to'};
						}
						$display .= '</td>
					</tr>
					<tr>
						<td class="package-label">License</td>
						<td class="package-details"><a href="http://www.opendefinition.org/licenses/'.$pkg->license_id.'">'.$pkg->license.'</a></td>
					</tr>
					</tbody>
			</table>
		</div>
	<div>';
}

else { // show search results or default page
	// get total count
	if($_GET['t']) {
		// search tags
		$action = 'searchAll';
		$q = 'tags:'.$_GET['t'];
		$q_raw = $_GET['t'];
		$q_text = 'with the tag <strong>'.$_GET['t'].'</strong>';
	}
	else if($_GET['q']) {
		// search all
		$action = 'searchAll';
		$q = $_GET['q'];
		$q_raw = $_GET['q'];
		$q_text = 'for the search term <strong>'.$_GET['q'].'</strong>';
	}
	else if($_GET['f']) {
		// search data formats
		$action = 'searchResources';
		$q = $_GET['f'];
	}
	else {
		// show default
		$action = 'searchAll';
		$q = '';
		$q_raw = '';
		$q_text = '<strong>total data sets</strong>';
	}

	$pkgs = $ckan->$action($q,0,1000,'',$ckan->group);
	$total = $pkgs->count;

	$display .= '<div id="ckan-right-col">
		<form id="page-search" action="/'.CATALOG_PATH.'/search/" method="get">
			<input name="q" type="text" tabindex="1" class="search-text" id="search-field" value="'.$q_raw.'" />
			<input type="submit" class="search-submit" value="Search" />
		</form>
		<h2 class="underline">Popular Tags</h2>
		<div class="item-list">
			<div id="tagcloud">';

				$tagArray = $ckan->getTagList();
				foreach($tagArray as $key=>$value) {
					$display .= '<a href="/'.CATALOG_PATH.'/tag/'.$key.'" rel="'.$value.'" title="'.$value.'">'.$key.'</a> ';
				}

			$display .= '</div>
		</div>
		'.$related_links.'
		<h2 class="underline">Your Open License to the Datasets</h2>
		<p>ORGANIZATION grants you a world-wide, royalty-free, non-exclusive license to use, modify, and distribute the datasets in all current and future media and formats for any lawful purpose.</p>
		<h2 class="underline">About This Catalog</h2>
		<p>This catalog was created in partnership with Open Colorado. For additional information visit <a href="http://opencolorado.org">http://opencolorado.org</a>.</p>
	</div>
	<div id="ckan-main-col">';

		// display search results
		if($total > 0) {
			// Define pager settings
			$pager_params=array ('totalItems' => $total, 'perPage' => 10, 'delta' => 2, 'separator' => '', 'spacesBeforeSeparator' => 1, 
				'spacesAfterSeparator' => 1, 'urlVar' => 'page', 'altPrev' => 'Previous Page', 'altNext' => 'Next Page', 'altPage' => 'Page: ', 'curPageLinkClassName' => 'currentPage', 'linkClass' => 'pageLink'
			);
			$pager = &new DB_Pager_Sliding($pager_params);

			// Fetch the HTML links
			$pagination = $pager->getLinks();
			$rowsPerPage = $pager->getRowsPerPage();
			$selectBox = $pager->getPerPageSelect();

			$pkgs = $ckan->$action($q,$pager->getStartRow(),$pager->getRowsPerPage(),'',$ckan->group);

			// show search results
			$start = $pager->getStartRow() + 1;
			$end = $pager->getRowsPerPage() + $pager->getStartRow();
			if($end > $total) { $end = $total; }

			$display .= '<div id="num_results">
				Results <strong>'.$start.' - '.$end.'</strong> of <strong>'.$total.'</strong> '.$q_text.'
			</div>';

			// A loop for each row of the result set
			foreach($pkgs->results as $result) {
				$display .= '<div class="ckan-result-block '.$result->id.'">
					<h3 class="ckan-title"><a href="/'.CATALOG_PATH.'/'.$result->name.'/">'.$result->title.'</a></h3>
					<p>'.substr($result->notes,0,200).'...</p>
					<div class="ckan-results-tag-list">
						<div class="ckan-result-block-tag-title">Tags:</div>
						<ul class="ckan-tags">';
							if(isset($result->tags)) {
								$result->tags = $ckan->stringFilter($result->tags);
								if(count($result->tags) > 0) {
									foreach($result->tags as $tag) {
										$display .= '<li><a href="/'.CATALOG_PATH.'/tag/'.$tag.'">'.$tag.'</a></li>';
									}
								}
							}
						$display .= '</ul>
					</div>
					<div class="ckan-results-resources-list">
						<div class="ckan-result-block-format-title">Format:</div> 
						<ul class="ckan-resources">';
							if(isset($result->res_format)) {
								foreach($result->res_format as $res_format) {
									$display .= '<li>'.$res_format.'</li>';
								}
							}
							elseif(isset($result->resources)) {
								foreach($result->resources as $resource) {
									$display .= '<li>'.$resource->format.'</li>';
								}
							}
						$display .= '</ul><br />
					</div>
				</div>';
			}

			$display .= '<div id="pagination">
				'.$pagination['all'].'
			</div>';
		}
		else {
			$display .= '<p style="padding-top:10px;">No results found '.$q_text.'.</p>';
		}

	$display .= '</div>';
}

echo $display;

include "footer.php";
?>
