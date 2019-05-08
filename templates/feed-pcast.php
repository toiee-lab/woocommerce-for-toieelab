<?php
/**
 * ユーザーを認証して出力を制御する
 *
 */

header('Content-Type: '.feed_content_type('rss-http').'; charset='.get_option('blog_charset'), true);
echo '<?xml version="1.0" encoding="' . get_option( 'blog_charset' ) . '"?' . '>' . "\n";
//header( 'Content-Type: text/plain; charset=utf8' );
?>
<rss xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" version="2.0">
<?php
$channel_obj            = get_queried_object();
$channel                = get_fields( $channel_obj );
$channel['id']          = $channel_obj->term_id;
$channel['url']         = get_term_link( $channel_obj );
$channel['title']       = $channel_obj->name;
$channel['description'] = $channel_obj->description;

/* ユーザーを識別するトークンを使ってユーザーを検索し、アクセス制限を設定する */

global $wcr_content;
$add_user_message       = '';
$add_user_message_email = '';
$is_user = false;

/* このチャンネルのアクセス制限を調べる */
if ( true === $channel['restrict'] ) {
	$has_access = false;

	$token = get_query_var( 'wcrtoken', '' );
	if ( '' !== $token ) {
		$user_query = get_users(
			[
				'meta_key'   => 'wcrtoken',
				'meta_value' => $token,
			]
		);

		if ( count( $user_query ) ) {
			$is_user = true;
			$user_id = $user_query[0]->ID;
			$user    = get_userdata( $user_id );

			$uemail = $user->user_email;
			$ulname = $user->last_name;
			$ufname = $user->first_name;

			$add_user_message       = " (【ライセンスについて】この教材は、{$ulname} {$ufname} ({$uemail}) さんに対してのみ提供しています)";
			$add_user_message_email = ' (for ' . $uemail . ')';

			$has_access = $wcr_content->check_access( $channel['restrict_product'], $user_id );
		}
	}
} else {
	$has_access = true;
}


/* タイトル */
switch ( $channel_obj->taxonomy ) {
	case 'mdy_channel':
		$channel['title'] = '耳デミー : ' . $channel['title'];
		break;
	case 'pocketera':
		$channel['title'] = 'ポケてら : ' . $channel['title'];
		break;
	default:
}

/* カテゴリ */
$pcat    = explode( '&gt;', $channel['category'] );
$pcat[0] = trim( $pcat[0] );
if ( isset( $pcat[1] ) ) {
	$pcat[1] = trim( $pcat[1] );
} else {
	$pcat[1] = '';
}

/* explicit */
$explicit = ( $channel['explicit'] ) ? 'yes' : 'no';

/* block */
$block = ( $channel['block'] ) ? 'yes' : 'no';

/* dummy audio */
global $toiee_pcast;
$dummy_audio = $toiee_pcast->get_dummy_audio();



?>
	<channel>
		<title><?php echo esc_html( $channel['title'] ); ?></title>
		<link><?php echo esc_url( $channel['url'] ); ?></link>
		<language><?php echo esc_html( $channel['language'] ); ?></language>
		<copyright><?php echo date('Y'); ?> <?php echo esc_html( $channel['copyright'] ); ?></copyright>

		<itunes:subtitle><?php echo esc_html( $channel['subtitle'] ); ?></itunes:subtitle>
		<itunes:author><?php echo esc_html( $channel['author'] ); ?></itunes:author>
		<itunes:summary><?php echo esc_html( $channel['description'] ); ?></itunes:summary>
		<description><?php echo esc_html( $channel['description'] . "\n" . $add_user_message ); ?></description>

		<itunes:owner>
			<itunes:name><?php echo esc_html( $channel['owner_name'] ); ?></itunes:name>
			<itunes:email><?php echo esc_html( $channel['owner_email'] ); ?></itunes:email>
		</itunes:owner>

		<itunes:image href="<?php echo esc_url( $channel['image'] ); ?>" />

		<itunes:category text="<?php echo esc_html( $pcat[0] ); ?>">
			<itunes:category text="<?php echo esc_html( $pcat[1] ); ?>"/>
		</itunes:category>
		<itunes:explicit><?php echo esc_html( $explicit ); ?></itunes:explicit>
		<itunes:block><?php esc_html( $block ); ?></itunes:block>

<?php
while ( have_posts() ) :
	the_post();

	$post_url = get_permalink();

	$atts = get_fields();

	$content_a = explode( '--toiee-transcribe--', get_the_content() );
	$content   = '<a href="' . $post_url . '">詳細はこちら</a> ' . strip_shortcodes( $content_a[0] );
	$etype     = get_post_meta( get_the_author_posts(), 'enclosure_type', true );
	if ( '' === $etype ) {
		switch ( $atts['media'] ) {
			case 'audio':
				$etype = 'audio/mpeg';
				break;
			case 'video':
				$etype = 'video/mp4';
				break;
			case 'pdf':
				$etype = 'application/pdf';
				break;
		}
	}
	$explicit = $atts['explicit'] ? 'yes' : 'no';
	$pubdate  = date( 'r', get_the_time( 'U' ) );
	$guid     = get_the_guid() . '-' . $token;

	/* ユーザー認証 */
	$title_prefix = '';
	$restrict     = get_field( 'restrict' );
	if ( $restrict === true ) {
		$restrict = 'restrict';
	} else if ( $restrict === false ) {
		$restrict = 'open';
	}

	if ( false === $has_access ) {
		switch ( $restrict ) {
			case 'open':
				break;
			case 'free':
				if ( $is_user ) {
					break;
				}
			default: /* restrict */
				$atts['enclosure'] = $dummy_audio;
				$etype             = 'audio/mpeg';
				$title_prefix      = $toiee_pcast->get_restrcit_message();
				break;
		}
	}

	/* block */
	if ( 'open' === $restrict ) {
		$block = 'no';
	} else {
		$block = 'yes';
	}

	?>
		<item>
			<title><?php the_title( $title_prefix, '' ); ?></title>
			<itunes:author><?php echo esc_html( $channel['author'] ); ?></itunes:author>
			<itunes:subtitle><?php echo esc_html( $atts['subtitle'] ); ?></itunes:subtitle>
			<itunes:summary><?php echo wp_strip_all_tags( $content ); ?></itunes:summary>
			<itunes:description><![CDATA[ <?php echo strip_tags( $content, '<p><ol><ul><a><strong><em>' ); ?> ]]></itunes:description>
			<enclosure length="<?php echo esc_html( $atts['length'] ); ?>" type="<?php echo esc_html( $etype ); ?>" url="<?php echo esc_html( $atts['enclosure'] ); ?>"/>
			<guid><?php echo $guid; ?></guid>
			<pubDate><?php echo esc_html( $pubdate ); ?></pubDate>
			<itunes:duration><?php echo esc_html( $atts['duration'] ); ?></itunes:duration>
			<itunes:explicit><?php echo esc_html( $explicit ); ?></itunes:explicit>
			<itunes:block><?php esc_html( $block ); ?></itunes:block>
		</item>

		<?php
endwhile;
?>
	</channel>
</rss>
