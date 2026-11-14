<?php
/**
 * Plugin Name: Popular Posts List (sample)
 * Description: [popular_posts count="10"] 숏코드. 기본은 수정 후이고, ?ppl=before 는 수정 전(글마다 쿼리)입니다.
 * Version: 1.1.0
 * License: GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const PPL_VIEWS_KEY = '_ppl_views';

add_shortcode( 'popular_posts', 'ppl_render_popular_posts' );

function ppl_is_before() {
	return isset( $_GET['ppl'] ) && 'before' === $_GET['ppl'];
}

function ppl_render_popular_posts( $atts ) {
	$atts  = shortcode_atts( array( 'count' => 10 ), $atts );
	$count = max( 1, (int) $atts['count'] );
	$rows  = ppl_is_before() ? ppl_rows_before( $count ) : ppl_rows_after( $count );
	$label = ppl_is_before() ? '수정 전' : '수정 후';

	$html = '<p class="ppl-label">' . esc_html( $label ) . '</p><ol class="ppl-list">';
	foreach ( $rows as $row ) {
		$html .= sprintf(
			'<li><a href="%s">%s</a> <span class="ppl-meta">조회 %s회 · 글쓴이 글 %d개</span></li>',
			esc_url( $row['url'] ),
			esc_html( $row['title'] ),
			number_format_i18n( $row['views'] ),
			$row['author_posts']
		);
	}
	return $html . '</ol>';
}

/**
 * 수정 전. 공개 글을 모두 가져온 뒤 글마다 조회수를 읽고, 목록 10개마다 글쓴이 글 수를 다시 센다.
 */
function ppl_rows_before( $count ) {
	global $wpdb;

	$ids  = get_posts(
		array(
			'numberposts' => -1,
			'post_type'   => 'post',
			'post_status' => 'publish',
			'fields'      => 'ids',
		)
	);
	$rows = array();
	foreach ( $ids as $id ) {
		$views  = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s",
				$id,
				PPL_VIEWS_KEY
			)
		);
		$rows[] = array(
			'id'    => $id,
			'views' => $views,
		);
	}
	usort(
		$rows,
		function ( $a, $b ) {
			return $b['views'] <=> $a['views'];
		}
	);

	$out = array();
	foreach ( array_slice( $rows, 0, $count ) as $row ) {
		$post  = get_post( $row['id'] );
		$out[] = array(
			'url'          => get_permalink( $post ),
			'title'        => get_the_title( $post ),
			'views'        => $row['views'],
			'author_posts' => (int) count_user_posts( $post->post_author ),
		);
	}
	return $out;
}

/**
 * 수정 후. 조회수 상위 N개와 글쓴이별 글 수를 쿼리 2개로 가져온다.
 */
function ppl_rows_after( $count ) {
	global $wpdb;

	$top = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT p.ID AS id, p.post_title AS title, p.post_name AS slug, p.post_author AS author,
				CAST(pm.meta_value AS UNSIGNED) AS views
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pm
				ON pm.post_id = p.ID AND pm.meta_key = %s
			WHERE p.post_type = 'post' AND p.post_status = 'publish'
			ORDER BY views DESC
			LIMIT %d",
			PPL_VIEWS_KEY,
			$count
		)
	);
	if ( ! $top ) {
		return array();
	}

	$authors = array();
	foreach ( $top as $row ) {
		$authors[] = (int) $row->author;
	}
	$placeholders = implode( ',', array_fill( 0, count( $authors ), '%d' ) );
	$counts_sql   = "SELECT post_author, COUNT(*) AS cnt FROM {$wpdb->posts}
		WHERE post_type = 'post' AND post_status = 'publish' AND post_author IN ($placeholders)
		GROUP BY post_author";
	$counts       = $wpdb->get_results( $wpdb->prepare( $counts_sql, $authors ) );
	$by_author    = array();
	foreach ( $counts as $count_row ) {
		$by_author[ (int) $count_row->post_author ] = (int) $count_row->cnt;
	}

	$out = array();
	foreach ( $top as $row ) {
		$out[] = array(
			'url'          => get_permalink( (int) $row->id ),
			'title'        => $row->title,
			'views'        => (int) $row->views,
			'author_posts' => $by_author[ (int) $row->author ] ?? 0,
		);
	}
	return $out;
}
