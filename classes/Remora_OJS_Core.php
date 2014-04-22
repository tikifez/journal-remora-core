<?php
/**
 * This class contains functions for retrieving data from a remora-ready journal install
 */
class Remora_OJS_Core {

	public $journal_url;
	public $local_url;

	function __construct(){
		$this->journal_url = cfct_get_option('cfct_ojs_url');
		$this->local_url = get_bloginfo('url').'/'.cfct_get_option('cfct_wp_journal_slug');
	}

	/**
	 * Fetches the output of a path from the journal specified in settings
	 *
	 * @path (string) Path in the journal to retrieve
	 * @asAjax (bool) Whether to retreive the file as AJAX
	 */
	function get_journal_path($journal_page, $asAjax = true, $source_only = false){

		if ( !is_object( $page ) ) {
			$page = new stdClass();
		}

		// Build the URL
		$page->url = ($asAjax) ? $this->journal_url.$journal_page."?ajax=".(bool) $asAjax : $this->journal_url.$journal_page;

		// Get any redirects for checking
		$page->page_headers = get_headers($page->url, 1);

		// If only the exact url with no redirects are desired, fail if that's not what we're getting

		if($source_only && $page->url != $page->page_headers['Location']) {
			return false;
		}

		// Get the resolved, rendered page
		$page->output = stream_get_contents(( fopen($page->url, 'r')) );

		// Get the page title
		$doc = new DOMDocument();
		$doc -> substituteEntities = false;
		$doc->loadHTML('<?xml encoding="UTF-8">' . $page->output);
		$title_el = $doc->getElementById('articleTitle');
		$page->title = $title_el->nodeValue;

		return $page;
	}

	/**
	 * Cleans up the messy OJS headers
	 */
	function clean_html(&$doc){
		// Remove <!DOCTYPE
		$doc->removeChild($doc->firstChild);

		// Remove <title></title>
		$titles = $body = $doc->getElementsByTagName('title');
		foreach($titles as $title)
			$title->parentNode->removeChild($title);

		// Remove <html><body></body></html> through replacement
		$body = $doc->getElementsByTagName('body');
		$doc->replaceChild($body->item(0)->firstChild, $doc->firstChild);

		return $dom;
	}

	/**
	 * Retrives an article from a remora-ready journal install
	 *
	 * Parameters:
	 * @journal_article_id - Required. Int. A valid journal article ID. Default: none.
	 * @asAjax - Bool. Should the article be retrieved as a DOM segment. Default: true.
	 * @journal_url - String. URL of the journal install. Default: null.
	 *
	 * Returns:
	 * DomDocument or null
	 */
	function get_article_by_id($article_id, $asAjax = true){
		$article_id = (int) $article_id;
		$article_page = "/article/view/".$article_id;
		$article = $this->get_journal_path($article_page, $asAjax);
		$article->type = 'article';

		return $article;
	}

	/**
	 * Retrieves an article abstract from a remora-ready journal install
	 *
	 * Parameters:
	 * @article_id - Required. Int. A valid journal article ID. Default: none.
	 * @args (array) An array of parameters. Currently supports:
	 *			excerpt_length (int) Length for the excerpt in words
	 *			more (string) Text placed at the end of the abbreviated text
	 *
	 * Returns:
	 * Array
	 */
	function get_abstract_by_id_old($article_id, $args = array()){
		$article_id = (int) $article_id;
		$article_page = "/article/view/".$article_id;
		$article = $this->get_journal_path($article_page, true);

		$abstract->type = 'abstract';

		// Load the output into a DOMDocument to get the values we need
		$doc = new DOMDocument();
		$dom -> substituteEntities = false;
		$doc->loadHTML('<?xml encoding="UTF-8">' . $article->output);

		// A bit of a workaround to get special characters to work with the saveXML method we have to use later
		// output
		//$docXML->text = $doc->saveHTML($article_text);

		// If there's no title, don't bother
		if(!$doc->getElementById('articleTitle')) return false;

		// Get the title
		$article_title = $doc->getElementById('articleTitle');
		$abstract->title = strip_tags($article_title->nodeValue);

		// Get the authors
		$article_authors = $doc->getElementById('authorString');
		$abstract->authors = strip_tags($article_authors->nodeValue);

		// Get the link
		$abstract->link = preg_replace('#\/article\/view\/(\d*)\/?$#', $this->local_url.'/\1', $article_page);

		// Get the abstract Text
		//$article_text->createEntityReference("amp");
		$article_text = $doc->getElementById('articleAbstract');
		// Remove the <h4></h4> and <br> at the top
		if(get_class($article_text) == 'DOMElement') {
			$text_h4 = $article_text->getElementsByTagName('h4');
			$article_text->removeChild($text_h4->item(0));
			while ($text_br = $article_text->getElementsByTagName('br')->item(0))
				$article_text->removeChild($text_br);
		}

		// Import the abstract dom node into a new domdocument to deal with PHP < 5.3.6
		$abstract->dom = new DomDocument();
		$abstract->dom->loadXML("<article></article>");
		$article_abstract_node = $abstract->dom->importNode($article_text, true);
		$abstract->dom->documentElement->appendChild($article_abstract_node);
		$abstract->text = $abstract->dom->saveHTML();


		// Filter out the excerpt from the text
		$excerpt = strip_tags($abstract->text);
		$excerpt = preg_replace('/\&nbsp;/', ' ', $excerpt);

		// Truncate the excerpt to the proper length
		$excerpt_words_length = str_word_count($excerpt);
		$excerpt_words = explode(' ', $excerpt);
		$excerpt_length = (is_int($args['excerpt_length']) ) ? $args['excerpt_length'] : 55;

		foreach($excerpt_words as $word){
			static $i;
			$i++;
			if($i > $excerpt_length) { $i = 0; break; }
			$abstract->excerpt .= $word.' ';
		}
		$abstract->excerpt .= (array_key_exists('more', $args)) ? $args['more'] : '[&hellip;]';

		// Get the galleys
		$article_galleys = $doc->getElementById('articleFullText');
		$abstract->galleys = array();
		if(gettype($article_galleys) == 'DomDocument') {

			foreach($article_galleys->getElementsByTagName('a') as $galley)
				$abstract->galleys[] = $doc->saveHTML($galley);
		}

		return $abstract;
	}

	/**
	 * Truncates a string to a set number of words
	 *
	 * @str - String to truncate
	 * @length - Number of words to truncate it to
	 */
	function truncate_string($str, $length = 55){

		$excerpt_words_length = str_word_count($str);
		$excerpt_words = explode(' ', $str);
		$excerpt_length = (is_int($length) ) ? $length : 55;

		foreach($excerpt_words as $word){
			static $i;
			$i++;
			if($i > $excerpt_length) { $i = 0; break; }
			$truncated .= $word.' ';
		}

		return $truncated;
	}

	function get_abstract_by_id($article_ids, $args = array()){
		extract($args);

		// get the articles using our new function
		for($i = 0; $article_ids[$i] != count($article_id); $i++) {
			$ids[] = (int) $article_ids[$i];
		}

		$abstracts = $this->get_ojs_abstracts($ids, $db, $excerpt_length);

		return $abstracts;



		// Remove the <h4></h4> and <br> at the top
		if(get_class($article_text) == 'DOMElement') {
			$text_h4 = $article_text->getElementsByTagName('h4');
			$article_text->removeChild($text_h4->item(0));
			while ($text_br = $article_text->getElementsByTagName('br')->item(0))
				$article_text->removeChild($text_br);
		}

		// Import the abstract dom node into a new domdocument to deal with PHP < 5.3.6
		$abstract->dom = new DomDocument();
		$abstract->dom->loadXML("<article></article>");
		$article_abstract_node = $abstract->dom->importNode($article_text, true);
		$abstract->dom->documentElement->appendChild($article_abstract_node);
		$abstract->text = $abstract->dom->saveHTML();


		// Filter out the excerpt from the text
		$excerpt = strip_tags($abstract->text);
		$excerpt = preg_replace('/\&nbsp;/', ' ', $excerpt);

		// Truncate the excerpt to the proper length
		$excerpt_words_length = str_word_count($excerpt);
		$excerpt_words = explode(' ', $excerpt);
		$excerpt_length = (is_int($args['excerpt_length']) ) ? $args['excerpt_length'] : 55;

		foreach($excerpt_words as $word){
			static $i;
			$i++;
			if($i > $excerpt_length) { $i = 0; break; }
			$abstract->excerpt .= $word.' ';
		}
		$abstract->excerpt .= (array_key_exists('more', $args)) ? $args['more'] : '[&hellip;]';

		// Get the galleys
		$article_galleys = $doc->getElementById('articleFullText');
		$abstract->galleys = array();
		if(gettype($article_galleys) == 'DomDocument') {

			foreach($article_galleys->getElementsByTagName('a') as $galley)
				$abstract->galleys[] = $doc->saveHTML($galley);
		}

		return $abstract;
	}

	/**
	 * Retrives an html galley from a remora-ready journal install
	 *
	 * Parameters:
	 * @journal_article_id - Required. Int. A valid journal article ID. Default: none.
	 * @requested_galley - Required. String. A valid journal article ID. Default: none.
	 * @asAjax - Bool. Should the article be retrieved as a DOM segment. Default: true.
	 *
	 * Returns:
	 * DomDocument, Redirect, or null
	 */
	function get_galley_by_article_id($article_id, $requested_galley, $asAjax = true){
		$article_id = (int) $article_id;
		$galley = preg_replace("/[^A-Za-z0-9_]/", "", (string) $requested_galley);

		// If the requested galley is HTML grab the DOM
		if(strpos($galley, 'htm') == 0 ){
			$galley_page = "/article/view/".$article_id."/".$galley;
			$galley = $this->get_journal_path($galley_page, $asAjax);
			$galley->type = 'galley';

			return $galley;
		}

		// Download other galleys

	}

	/**
	 * Retrives issue table of contents (TOC) from a remora-ready journal install
	 *
	 * Parameters:
	 * @journal_issue_id - Required. Int. A valid journal article ID. Default: none.
	 * @asAjax - Bool. Should the article be retrieved as a DOM segment. Default: false.
	 *
	 * Returns:
	 * DomDocument or null
	 */
	function get_issue_by_id($journal_issue_id = 'current', $asAjax = true){
		// Allow the issue id to be either "current" or an int
		if($journal_issue_id != 'current'){
			if(is_int($journal_issue_id)) $issue_id = (int) $journal_issue_id;
			$issue = $this->get_journal_path("/issue/view/".$issue_id."/showToc", $asAjax, true);
		}
		else $issue = $this->get_journal_path("/issue/current/showToc", $asAjax);

		if(!$issue) return $issue;
		$issue->type = "issue";

		return $issue;
	}

	/**
	 * Outputs the DOM for a journal page
	 */
	function show_page($journal_page){
		// Load the output into a DOMDocument for modification
		$pageEncoding = 'UTF-8';
		$doc = new DOMDocument();
		$doc->loadHTML('<?xml encoding="'.$pageEncoding.'">' . $journal_page->output);

		$this->make_links_local($doc);
		$this->clean_html($doc);

		echo $doc->saveHTML();
	}


	/**
	 * Gets the issue id from the get data
	 *
	 * Returns: Int or null.
	 */
	function get_requested_issue_id() {
		$issue_id = (int) $_GET['issue_id'];
		return ($issue_id) ? $issue_id : null ;
	}


	/**
	 * Gets the article id from the get data
	 *
	 * Returns: Int or null.
	 */
	function get_requested_article_id() {
		$article_id = (int) $_GET['article_id'];
		return ($article_id) ? $article_id : null ;
	}

	/**
	 * Gets the article id from the get data
	 *
	 * Returns: Int or null.
	 */
	function get_requested_galley_type() {
		$galley_type = preg_replace("/[^A-Za-z0-9_]/", "", (string) $_GET['galley']);
		return ($galley_type) ? $galley_type : null ;
	}

	/**
	 * Replaces link domains with the WordPress Remora path
	 *
	 * Parameters:
	 * @dom - DOM object
	 * @translations - array of paths to convert, values local to the wp journal page
	 *
	 * Returns:
	 * DOM object with links changed to local
	 */
	function make_links_local(&$dom, $translations = null){

		// These regular expressions deliberately lack enclosing tags because we're not done building them yet
		$translations = array(
			'\/article\/view\/(\d*)\/?$' => '/\1',
			'\/article\/view\/(\d*)\/(.*)' => '/\1/?galley=\2'
			);

		$specials = array(
			'pdf' => array('old' => '\/article\/view\/(\d*)\/(.*)', 'new' => '/article/download/\1/\2' )

			);

		foreach ($dom->getElementsByTagName('a') as $dom_link) {

			// Standard conversion to local
			$link = $dom_link->getAttribute('href');
			foreach($translations as $old => $new) {

				// Handle special cases
				$node_value = strtolower($dom_link->nodeValue);
				if(array_key_exists($node_value, $specials) ) {

					$old_url = $this->journal_url.$specials[$node_value]['old'];
					$new_url = $this->journal_url.$specials[$node_value]['new'];
				}

				// Standard conversion
				else {

					$old_url = $this->journal_url.$old;
					$new_url = $this->local_url.$new;
				}

				// Perform conversion
				$link = preg_replace('#'.$old_url.'#', $new_url, $link);
				$dom_link->setAttribute('href', $link);

			}
			$dom->saveHTML();
		}

		return $dom;
	}

	/**
	 * Loads pages which required direct interaction with the journal CMS
	 *
	 * Parameters:
	 * @page (string) The journal page to pull up
	 *
	 * Returns:
	 * HTML String
	 */

	function action_page($path, $id = null){
		echo $this->get_action_page($path, $id);
	}

	function get_action_page ($path, $id = null){
		$dom_id = ($id) ? 'id="'.$id.'"' : null;
		$src_path = ($path[0] == '/') ? $this->journal_url.$path : $this->journal_url.'/'.$path;

		$page = '<iframe src="'.$src_path.'" class="remora-frame" '.$dom_id.'></iframe>';

		return $page;
	}

	/**
	 * Gets the OJS abstracts direct from the db
	 *
	 * @abstracts - Array of abstract ids
	 *
	 * Returns array of abstract titles and abstracts
	 */
	function get_ojs_abstracts($abstract_ids, $db, $length){
		// if(!is_array($abstract_ids) ){
		// 	if(is_int($abstract_ids) )
		// 		$abstract_ids = array($abstract_ids);
		// 	else return false;
		// }

		$gof = new Gentleman_of_Fortune();
		$gof_conn = $gof->grapple_ojs($db);

		foreach($abstract_ids as $id) {

		for($i = 0; $i < count($abstract_ids); $i++)
			$abstract = $gof->get_abstract($id, $gof_conn);
			$abstract->link = preg_replace('#'.$this->journal_url.'\/article\/view\/(\d*)\/?$#', $this->local_url.'/\1', $this->journal_url.'/article/view/'.$id);

			$abstract->excerpt = $this->truncate_string($abstract->text, $length);

			$abstracts[] = $abstract;
		}

		$gof_conn->close();

		return $abstracts;
	}

	function ojs_abstract($abstract_id, $db){
		$abstracts = get_ojs_abstracts(array($abstract_id), $db);


		foreach($abstracts as $abstract) {

		// Filter out the excerpt from the text
			$excerpt = strip_tags($abstract->text);
			$excerpt = preg_replace('/\&nbsp;/', ' ', $excerpt);

		// Truncate the excerpt to the proper length
			$excerpt_words_length = str_word_count($excerpt);
			$excerpt_words = explode(' ', $excerpt);
			$excerpt_length = (is_int($args['excerpt_length']) ) ? $args['excerpt_length'] : 55;

			foreach($excerpt_words as $word){
				static $i;
				$i++;
				if($i > $excerpt_length) { $i = 0; break; }
				$abstract->excerpt .= $word.' ';
			}
			$abstract->excerpt .= (array_key_exists('more', $args)) ? $args['more'] : '[&hellip;]';

		// Get the galleys
			$article_galleys = $doc->getElementById('articleFullText');
			$abstract->galleys = array();
			if(gettype($article_galleys) == 'DomDocument') {

				foreach($article_galleys->getElementsByTagName('a') as $galley)
					$abstract->galleys[] = $doc->saveHTML($galley);
			}


		}
		return $abstract;
	}

}
