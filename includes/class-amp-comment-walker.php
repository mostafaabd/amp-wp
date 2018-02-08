<?php
/**
 * Class AMP_Comment_Walker
 *
 * @package AMP
 */

/**
 * Class AMP_Comment_Walker
 *
 * Walker to wrap comments in mustache tags for amp-template.
 */
class AMP_Comment_Walker extends Walker_Comment {

	/**
	 * The original comments arguments.
	 *
	 * @since 0.7
	 * @var array
	 */
	public $args;

	/**
	 * Holds the timestamp of the most recent comment in a thread.
	 *
	 * @since 0.7
	 * @var array
	 */
	private $comment_thread_age = array();

	/**
	 * Starts the element output.
	 *
	 * @since 0.7.0
	 *
	 * @see Walker::start_el()
	 * @see wp_list_comments()
	 * @global int        $comment_depth
	 * @global WP_Comment $comment
	 *
	 * @param string     $output Used to append additional content. Passed by reference.
	 * @param WP_Comment $comment Comment data object.
	 * @param int        $depth Optional. Depth of the current comment in reference to parents. Default 0.
	 * @param array      $args Optional. An array of arguments. Default empty array.
	 * @param int        $id Optional. ID of the current comment. Default 0 (unused).
	 */
	public function start_el( &$output, $comment, $depth = 0, $args = array(), $id = 0 ) {

		$new_out = '';
		parent::start_el( $new_out, $comment, $depth, $args, $id );

		if ( 'div' === $args['style'] ) {
			$tag = '<div';
		} else {
			$tag = '<li';
		}
		$comment_time_stamp = strtotime( $comment->comment_date );
		$new_tag            = $tag . ' data-sort-time="' . esc_attr( $comment_time_stamp ) . '"';

		if ( ! empty( $this->comment_thread_age[ $comment->comment_ID ] ) && $this->comment_thread_age[ $comment->comment_ID ] !== $comment_time_stamp ) {
			// Only add data-update-time if not the update time is not the same.
			$new_tag .= ' data-update-time="' . esc_attr( $this->comment_thread_age[ $comment->comment_ID ] ) . '"';
		} elseif ( ! empty( $this->comment_thread_age[ $comment->comment_ID ] ) && count( $this->comment_thread_age ) === 1 ) {
			// Add the current timestamp to the last item to defeat polling the same URL to defeat caching.
			$new_tag .= ' data-update-time="' . esc_attr( current_time( 'timestamp' ) ) . '"';
		}

		$output .= $new_tag . substr( ltrim( $new_out ), strlen( $tag ) );
		// Remove comment age if exists.
		if ( isset( $this->comment_thread_age[ $comment->comment_ID ] ) ) {
			unset( $this->comment_thread_age[ $comment->comment_ID ] );
		}
	}

	/**
	 * Output amp-list template code and place holder for comments.
	 *
	 * @since 0.7
	 * @see Walker::paged_walk()
	 *
	 * @param WP_Comment[] $elements List of comment Elements.
	 * @param int          $max_depth The maximum hierarchical depth.
	 * @param int          $page_num The specific page number, beginning with 1.
	 * @param int          $per_page Per page counter.
	 *
	 * @return string XHTML of the specified page of elements.
	 */
	public function paged_walk( $elements, $max_depth, $page_num, $per_page ) {
		if ( empty( $elements ) || $max_depth < -1 ) {
			return '';
		}

		$this->build_thread_latest_date( $elements );

		$args = array_slice( func_get_args(), 4 );

		return parent::paged_walk( $elements, $max_depth, $page_num, $per_page, $args[0] );
	}

	/**
	 * Find the timestamp of the latest child comment of a thread to set the updated time.
	 *
	 * @since 0.7
	 *
	 * @param WP_Comment[] $elements The list of comments to get thread times for.
	 * @param int          $time $the timestamp to check against.
	 * @param bool         $is_child Flag used to set the the value or return the time.
	 * @return int Latest time.
	 */
	protected function build_thread_latest_date( $elements, $time = 0, $is_child = false ) {

		foreach ( $elements as $element ) {

			$children  = $element->get_children();
			$this_time = strtotime( $element->comment_date );
			if ( ! empty( $children ) ) {
				$this_time = $this->build_thread_latest_date( $children, $this_time, true );
			}
			if ( $this_time > $time ) {
				$time = $this_time;
			}
			if ( false === $is_child ) {
				$this->comment_thread_age[ $element->comment_ID ] = $time;
			}
		}

		return $time;
	}
}
