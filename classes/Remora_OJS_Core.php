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
	function get_journal_path($journal_page, $asAjax = true){
		$page->url = ($asAjax) ? $this->journal_url.$journal_page."?ajax=".(bool) $asAjax : $this->journal_url.$journal_page;
		$page->output = stream_get_contents(( fopen($page->url, 'r')) );

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
	function get_abstract_by_id($article_id, $args = array()){
		$article_id = (int) $article_id;
		$article_page = "/article/view/".$article_id;
		$article = $this->get_journal_path($article_page, true);

		$abstract->type = 'abstract';

		// Load the output into a DOMDocument to get the values we need
		$doc = new DOMDocument();
		$doc->loadHTML('<?xml encoding="UTF-8">' . $article->output);

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
		$article_text = $doc->getElementById('articleAbstract');

		// Remove the <h4></h4> and <br> at the top
		if(get_class($article_text) == 'DOMElement') {
			$text_h4 = $article_text->getElementsByTagName('h4');
			$article_text->removeChild($text_h4->item(0));
			$text_br = $article_text->getElementsByTagName('br');
			$article_text->removeChild($text_br->item(0));
			$abstract->text = $doc->saveHTML($article_text);
		}

		// Filter out the excerpt from the text
		$excerpt = strip_tags($abstract->text);
		$excerpt_words = str_word_count($excerpt, 1);
		$excerpt_length = (array_key_exists('excerpt_length', $args) && is_int($args['excerpt_length']) ) ? $args['excerpt_length'] : 55;
		foreach($excerpt_words as $word){
			static $i;
			$i++;
			if($i > $excerpt_length) break;
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
		}
		else $issue_id = 'current';

		$issue_page = "/issue/".$issue_id."/showToc";
		$issue = $this->get_journal_path($issue_page, $asAjax);
		$issue->type = "issue";

		return $issue;
	}

	/**
	 * Outputs the DOM for a journal page
	 */
	function show_page($journal_page){
		// Load the output into a DOMDocument for modification
		$doc = new DOMDocument();
		$doc->loadHTML('<?xml encoding="UTF-8">' . $journal_page->output);

		$this->make_links_local(&$doc);
		$this->clean_html($doc);

		echo $doc->saveHTML();
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
		$translations = array(
			'\/article\/view\/(\d*)\/?$' => '/\1',
			'\/article\/view\/(\d*)\/(.*)' => '/\1/?galley=\2'
			);

		foreach ($dom->getElementsByTagName('a') as $dom_link) {
			$link = $dom_link->getAttribute('href');
			foreach($translations as $old => $new) {
				$old_url = $this->journal_url.$old;
				$new_url = $this->local_url.$new;
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

}