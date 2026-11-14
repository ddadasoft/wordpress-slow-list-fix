<?php
/**
 * 데모 데이터: 글쓴이 3명, 글 500개, 글마다 조회수(_ppl_views),
 * [popular_posts] 숏코드가 든 첫 화면 페이지.
 */
require_once '/wordpress/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

activate_plugin( 'popular-posts-list/popular-posts-list.php' );

$authors = array();
foreach ( array( 'writer1', 'writer2', 'writer3' ) as $login ) {
	$authors[] = wp_insert_user(
		array(
			'user_login' => $login,
			'user_pass'  => wp_generate_password(),
			'user_email' => $login . '@example.com',
			'role'       => 'author',
		)
	);
}

mt_srand( 42 );
wp_defer_term_counting( true );
wp_suspend_cache_invalidation( true );
for ( $i = 1; $i <= 500; $i++ ) {
	$id = wp_insert_post(
		array(
			'post_title'   => sprintf( '샘플 글 %03d', $i ),
			'post_content' => '데모용 본문입니다.',
			'post_status'  => 'publish',
			'post_author'  => $authors[ $i % 3 ],
			'post_date'    => gmdate( 'Y-m-d H:i:s', strtotime( '2026-01-01' ) + $i * 3600 ),
		)
	);
	add_post_meta( $id, '_ppl_views', mt_rand( 0, 100000 ) );
}
wp_suspend_cache_invalidation( false );
wp_defer_term_counting( false );

$page = wp_insert_post(
	array(
		'post_title'   => '인기 글',
		'post_content' => '<!-- wp:shortcode -->[popular_posts count="10"]<!-- /wp:shortcode -->',
		'post_status'  => 'publish',
		'post_type'    => 'page',
	)
);
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $page );
update_option( 'blogname', 'WordPress 수정 샘플' );
update_option( 'blogdescription', '' );
echo "seeded posts=500 front_page=$page\n";
