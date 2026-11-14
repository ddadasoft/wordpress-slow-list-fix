<?php
/**
 * Plugin Name: Query Meter (sample)
 * Description: 페이지 아래에 이 요청의 DB 쿼리 수와 PHP 생성 시간을 표시합니다. 측정용입니다.
 */

add_action(
	'wp_footer',
	function () {
		global $wpdb;
		$queries = (int) $wpdb->num_queries;
		$ms      = (int) round( timer_stop( 0, 6 ) * 1000 );
		printf(
			'<div id="query-meter" style="position:fixed;right:12px;bottom:12px;padding:8px 12px;background:#111;color:#fff;font:14px/1.4 monospace;border-radius:6px;z-index:9999">DB 쿼리 %d개 · 생성 %d ms</div>',
			$queries,
			$ms
		);
		printf( "\n<!-- query-meter: queries=%d time_ms=%d -->\n", $queries, $ms );
	},
	9999
);
