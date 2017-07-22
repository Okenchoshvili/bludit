<?php defined('BLUDIT') or die('Bludit CMS.');

// ============================================================================
// Variables
// ============================================================================

// Array with pages, each page is a Page Object
// Filtered by pagenumber, amount of items per page and sorted by date/position
/*
	array(
		0 => Page Object,
		1 => Page Object,
		...
		N => Page Object
	)
*/
$pages = array();

// Array with pages, each page is a Page Object
// Filtered by pagenumber and amount of items per page
/*
	array(
		"pageKey1" => Page Object,
		"pageKey2" => Page Object,
		...
		"pageKeyN" => Page Object,
	)
*/
$pagesByKey = array();

// Page filtered by the user, is a Page Object
$page = $Page = false;

// Array with pages order by parent
// Sorted by position or date
/*
	array(
		PARENT => array(
			0 => Page Object,
			...,
			N => Page Object),
		"parentKey1" => array(
			0 => Page Object,
			...,
			N => Page Object),
		"parentKey2" => array(
			0 => Page Object,
			...,
			N => Page Object),
		...
		"parentKeyN" => array(
			0 => Page Object,
			...,
			N => Page Object),
	)
*/
$pagesByParent = array(PARENT=>array());

// Array with pages order by parent and by key
/*
	array(
		PARENT => array(
			"parentKey1" => Page Object,
			...,
			"parentKeyN" => Page Object),
		"parentKey1" => array(
			"childKeyA" => Page Object,
			...,
			"childKeyB" => Page Object),
		"parentKey2" => array(
			"childKeyJ" => Page Object,
			...,
			"childKeyO" => Page Object),
		...
		"parentKeyN" => array(
			"childKeyW" => Page Object,
			...,
			"childKeyZ" => Page Object),
	)
*/
$pagesByParentByKey = array(PARENT=>array());

// ============================================================================
// Main
// ============================================================================

// Execute the scheduler
if( $dbPages->scheduler() ) {
	// Reindex tags
	reindexTags();

        // Reindex categories
        reindexCategories();

	// Add to syslog
	$Syslog->add(array(
		'dictionaryKey'=>'page-published-from-scheduler',
		'notes'=>''
	));
}

// Generate pages parent tree, only published pages
buildPagesByParent(true);

// Set home page is the user defined one
if( $Site->homepage() && $Url->whereAmI()==='home' ) {
	$pageKey = $Site->homepage();
	if( $dbPages->exists($pageKey) ) {
		$Url->setSlug($pageKey);
		$Url->setWhereAmI('page');
	}
}

// Build specific page
if( $Url->whereAmI()==='page' ) {
        // Build the page
	$page = $Page = buildPage( $Url->slug() );

	// The page doesn't exist
	if($page===false) {
		$Url->setNotFound(true);
	}
	// The page is not published, scheduled or draft
	elseif( $page->scheduled() || $page->draft() ) {
		$Url->setNotFound(true);
	}
	else {
		$pages[0] = $page;
	}
}
elseif( $Url->whereAmI()==='tag' ) {
	buildPagesByTag();
}
elseif( $Url->whereAmI()==='category' ) {
        buildPagesByCategory();
}
elseif( $Url->whereAmI()==='home' ) {
        buildPagesForHome();
}
elseif( $Url->whereAmI()==='admin' ) {
        buildPagesForAdmin();
}

// Set page 404 not found
if( $Url->notFound() ) {
	$Url->setWhereAmI('page');
	$page = buildPage('error');
	$pages[0] = $page;
}