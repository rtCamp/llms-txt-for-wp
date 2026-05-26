<?php
/**
 * Helper class for Markdown operations.
 *
 * @package LLMsTxtForWP
 */

use League\HTMLToMarkdown\HtmlConverter;

class LLMS_Txt_Markdown {

	/**
	 * Convert HTML to Markdown using a reliable library.
	 *
	 * @param string $html The HTML content.
	 * @return string
	 */
	public static function convert( $html ) {
		$markdown_arguments = array(
			'strip_tags' => true,
		);

		/**
		 * Filter the arguments used for converting HTML to Markdown.
		 */
		$markdown_arguments = apply_filters( 'llms_txt_markdown_arguments', $markdown_arguments );

		$converter = new HtmlConverter( $markdown_arguments );
		return $converter->convert( $html );
	}

	/**
	 * Convert a post object to Markdown.
	 *
	 * @param WP_Post $post         The post object.
	 * @param bool    $include_meta Whether to include meta information like title and date.
	 * @return string
	 */
	public static function convert_post_to_markdown( $post, $include_meta = true ) {
		if ( ! $post ) {
			return '';
		}

		$modified  = ! empty( $post->post_modified_gmt ) && '0000-00-00 00:00:00' !== $post->post_modified_gmt ? $post->post_modified_gmt : $post->post_modified;

		$cache_version = defined( 'LLMS_TXT_VERSION' ) ? LLMS_TXT_VERSION : '';
		$cache_key     = 'llms_post_md_' . $post->ID . '_' . ( $include_meta ? '1' : '0' ) . '_' . md5( $cache_version );

		// Cache only the converted Markdown before runtime filters, so that
		// llms_txt_markdown_content (and llms_txt_markdown_arguments inside
		// self::convert()) always run on every call and are never short-circuited.
		$cached = get_transient( $cache_key );
		if ( is_array( $cached ) && isset( $cached['modified'], $cached['content'] ) && $cached['modified'] === $modified ) {
			$markdown = $cached['content'];
		} else {
			$markdown = '';

			if ( $include_meta ) {
				$markdown .= '# ' . esc_html( $post->post_title ) . "\n\n";

				// Add post meta.
				$markdown .= '*Published:* ' . esc_html( get_the_date( 'Y-m-d', $post ) ) . "\n";
				$markdown .= '*Author:* ' . esc_html( get_the_author_meta( 'display_name', $post->post_author ) ) . "\n\n";
			}

			// Convert content using the convert method.
			$content   = apply_filters( 'the_content', $post->post_content );
			$markdown .= self::convert( $content );

			// Allow filtering the final Markdown content before caching.
			$markdown = apply_filters( 'llms_txt_markdown_content', $markdown, $post );

			set_transient( $cache_key, array( 'modified' => $modified, 'content' => $markdown ), WEEK_IN_SECONDS );
		}

		return $markdown;
	}
}
