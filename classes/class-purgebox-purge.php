<?php
/**
 * PurgeBox base class
 */
require_once dirname(  __FILE__  ). '/class-purgebox-plugin.php';

/**
 * PurgeBox fook setting.
 * @package RedBox
 * @author ShoheiTai
 * @copyright
 * 2016 REDBOX All Rights Reserved.
 */
class PurgeBox_Purge extends PurgeBox_Plugin {

	/**
	 * API Object.
	 * @var PurgeBox_API
	 */
	protected $_API = null;

	/**
	 * Priority of action.
	 * @var integer
	 */
	protected $_action_priority = 99;

	/**
	 * Default constructor.
	 */
	public function __construct() {
		// Setup API
		$this->_API = $this->_get_api();
		if( isset( $this->_API ) ) {
		 	 
			add_action( 'transition_post_status', array( $this,'purge_post_status' ),$this->_action_priority, 3 );
			add_action( 'delete_post', array( $this,'pre_delete_post' ),$this->_action_priority);
			
			add_action( 'edit_category', array( $this, 'purge_all' ), $this->_action_priority );
			add_action( 'edit_link_category', array( $this, 'purge_all' ), $this->_action_priority );
			add_action( 'edit_post_tag', array( $this, 'purge_all' ), $this->_action_priority );

			$hooks = array( 'comment_post', 'edit_comment', 'trashed_comment', 'untrashed_comment', 'deleted_comment' );
			foreach($hooks as $hook) {
				add_action( $hook, array( $this, 'purge_all' ), $this->_action_priority );
			}

			$hooks = array( 'deleted_link', 'edit_link', 'add_link' );
			foreach($hooks as $hook) {
				add_action( $hook, array( $this, 'purge_all' ), $this->_action_priority );
			}

			// Purge all
			$hooks = array(  'switch_theme', 'update_option_sidebars_widgets', 'widgets.php', 'update_option_theme_mods_'. get_option( 'stylesheet' ), 'purge_box_purge_all' );
			foreach($hooks as $hook) {
				add_action( $hook, array( $this, 'purge_all' ), $this->_action_priority );
			}
		}
	}

	/**
	 * Sends a purge request for the given url.
	 * @params $url URL to purge from the cache server.
	 */
	public function purge( $urls ) {
		$this->_API->purge( $urls );
	}

	/**
	 * Purges all pages on the site.
	 */
	public function purge_all() {

		// 現在のユーザーが 'purge_all' ケーパビリティを持っているかチェック
		if (!current_user_can('purge_all')) {
			// 権限がない場合はエラーメッセージを表示して処理を停止
			// wp_die('You do not have sufficient permissions to access this feature.');
		} else {
			// 権限がある場合は処理を続行
			$this->_API->purge_all();
		}

	}

	/**
	 * Purges common pages.
	 * @return array|boolean
	 */
	public function purge_common( $call = true ) {
		$home = parse_url( home_url() );
		$urls = array( '/', '/feed', '/feed/atom' );
		if( isset( $home['path'] ) ) {
			foreach( $urls as &$url ) {
				$url = $home['path']. $url;
			}
		}
		return $call ? $this->purge( $urls ) : $urls;
	}

	/**
	 * Purges posts and pages on update.
	 * @return array|boolean
	 */
	public function purge_post( $postId, $call = true ) {
		$urls = array(  get_permalink( $postId )  );
		return $call ? $this->purge( $urls ) : $urls;
	}

	/**
	 * Purges objects that depend on the post.
	 * @return array|boolean
	 */
	public function purge_post_dependencies( $postId, $call = true ) {
		$urls = array();
		$urls = array_merge(
			$urls,
			$this->purge_common( false ),
			$this->purge_categories( $postId, false ),
			$this->purge_archives( $postId, false ),
			$this->purge_tags( $postId, false )
		);
		return $call ? $this->purge( $urls ) : $urls;
	}

	/**
	 * Purges categories associated with a post.
	 * @params $postId Id of the post.
	 * @return array|boolean
	 */
	public function purge_categories( $postId, $call = true ) {
		$urls = array();
		$categories = get_the_category( $postId );
		foreach( $categories as $cat ) {
			$urls = array_merge( $urls, $this->purge_category( $cat->cat_ID, false ) );
		}
		return $call ? $this->purge( $urls ) : $urls;
	}

	/**
	 * Purges post comments.
	 * @return array|boolean
	 */
	public function purge_comments( $commentId, $call = true ) {
		$comment = get_comment( $commentId );
		$approved = $comment->comment_approved;
		$urls = array();

		if ( $approved == 1 || $approved == 'trash' ) {
			$postId = $comment->comment_post_ID;
			$urls[] = '/?comments_popup='. $postId;
		}
		return $call ? $this->purge( $urls ) : $urls;
	}

	/**
	 * Purges links.
	 */
	public function purge_links() {
		if ( is_active_widget( false, false, 'links' ) ) {
			  $this->purge_all();
		}
	}

	/**
	 * Purges post categories.
	 * @param $categoryId Id of the category to purge.
	 * @return array|boolean
	 */
	public function purge_category( $categoryId, $call = true ) {
		$urls = array();
		if ( is_active_widget( false, false, 'categories' ) ) {
			$this->purge_all();
		} else {
			$urls[] = get_category_link( $categoryId );
		}
		return $call ? $this->purge( $urls ) : $urls;
	}

	/**
	 * Purges link categories.
	 * @param $categoryId Id of the category to purge.
	 */
	public function purge_link_category( $categoryId ) {
		if ( is_active_widget( false, false, 'links' ) ){
			$this->purge_all();
		}
	}

	/**
	 * Purges a tag category.
	 * @params $categoryId Id of the category to purge.
	 * @return array|boolean
	 */
	public function purge_tag_category( $categoryId, $call = true ) {
		$urls = array(  get_tag_link( $categoryId )  );
		return $call ? $this->purge( $urls ) : $urls;
	}

	/**
	 * Purges archives pages.
	 * @params $postId Id of the post that triggered the purge.
	 * @return array|boolean
	 */
	public function purge_archives( $postId, $call = true ) {
		$urls = array(
			get_day_link( get_post_time( 'Y', false, $postId ),
			get_post_time( 'm', true, $postId ),
			get_post_time( 'd', true, $postId ) ),
			get_month_link( get_post_time( 'Y', false, $postId ),
			get_post_time( 'm', true, $postId ) ),
			get_year_link( get_post_time( 'Y', false, $postId ) ),
		 );

		return $call ? $this->purge( $urls ) : $urls;
	}

	/**
	 * Purges tags associated with a post.
	 * @params $postId Id of the post being purged.
	 * @return array|boolean
	 */
	public function purge_tags( $postId, $call = true ) {
		$urls = array();
		$tags = wp_get_post_tags( $postId );
		foreach ( $tags as $tag ) {
			$urls = array_merge( $urls, $this->purge_tag_category( $tag->term_id, false ) );
		}
		return $call ? $this->purge( $urls ) : $urls;
	}

	/**
	 * Handles post status purges.
	 * @params string $new_status
	 * @params string $old_status
	 * @params WP_Post $post
	 * @params boolean $call
	 * @return array|boolean
	 */
	public function purge_post_status( $new_status, $old_status, $post, $call = true ) {

		 // handle the issue with double calling when the new editor is used
		 // See: https://github.com/WordPress/gutenberg/issues/15094
			$page = $post->post_name;

		 	if ($new_status == 'publish' || $old_status == 'publish') {
				if ($new_status === $old_status) {
					if (get_transient($page.'_purged') === false)
						$this->purge_all();			
				} else {
					$this->purge_all();
				}
				set_transient($page.'_purged','1',3);
			}
	}

	
	public function pre_delete_post($post_id) {
		 if ( get_post_status ( $post_id ) == 'publish' ) $this->purge_all();
	}

	/**
	 * Get API object.
	 * @return PurgeBox_API|null
	 */
	protected function _get_api() {
		$API = null;
		if( self::_api_available() ) {
			$version = '2'; // always set to ver 2
			$api_key = self::_get_option( 'api_key' );
			$group = self::_get_option( 'group' );
			$API = new PurgeBox_API( $version, $api_key, $group );
		}
		return $API;
	}
}
