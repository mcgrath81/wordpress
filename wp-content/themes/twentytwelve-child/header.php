<?php
/**
 * The Header for our theme.
 *
 * Displays all of the <head> section and everything up till <div id="main">
 *
 * @package WordPress
 * @subpackage Twenty_Twelve
 * @since Twenty Twelve 1.0
 */
?><!DOCTYPE html>
<!--[if IE 7]>
<html class="ie ie7" <?php language_attributes(); ?>>
<![endif]-->
<!--[if IE 8]>
<html class="ie ie8" <?php language_attributes(); ?>>
<![endif]-->
<!--[if !(IE 7) | !(IE 8)  ]><!-->
<html <?php language_attributes(); ?>>
<!--<![endif]-->
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
<meta name="viewport" content="width=device-width" />
<title><?php wp_title( '|', true, 'right' ); ?></title>
<link rel="profile" href="http://gmpg.org/xfn/11" />
<link rel="shortcut icon" href="<?php echo get_stylesheet_directory_uri(); ?>/favicon.ico" />
<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />
<?php // Loads HTML5 JavaScript file to add support for HTML5 elements in older IE versions. ?>
<!--[if lt IE 9]>
<script src="<?php echo get_template_directory_uri(); ?>/js/html5.js" type="text/javascript"></script>
<![endif]-->
<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<div id="page" class="hfeed site">
	<header id="masthead" class="site-header" role="banner">
		<div id="logo" class="logo"><img src="<?php echo bloginfo('stylesheet_directory');?>/images/ClonCrest1.png" alt="<?php bloginfo( 'name' ); ?>" width="120" height="113"/></div>
		<div id="gaalogo" class="gaalogo"><img src="<?php echo bloginfo('stylesheet_directory');?>/images/gaalogo.png" alt="<?php bloginfo( 'name' ); ?>" /></div>
		<hgroup>
			<h1 class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></h1>
			<h2 class="site-description"><?php bloginfo( 'description' ); ?></h2>
			<h3 class="founded">Cluain An Ultaigh/Ros Mor. Estd 1884</h3>
		</hgroup>
		<nav id="site-navigation" class="main-navigation" role="navigation">
			<h3 class="menu-toggle"><?php _e( 'Menu', 'twentytwelve' ); ?></h3>
			<a class="assistive-text" href="#content" title="<?php esc_attr_e( 'Skip to content', 'twentytwelve' ); ?>"><?php _e( 'Skip to content', 'twentytwelve' ); ?></a>
			<?php wp_nav_menu( array( 'theme_location' => 'primary', 'menu_class' => 'nav-menu' ) ); ?>
		</nav>
		<div id="social-media" class="social-media">
			<ul>
				<li class="facebook">
					<a href="https://www.facebook.com/clonoultyrossmore.gaa" title="Facebook" target="_blank">Facebook</a>
				</li>
				<li class="twitter">
					<a href="https://twitter.com/clonrossgaa" title="Twitter" target="_blank">Twitter</a>
				</li>
				<li class="wikipedia">
					<a href="http://en.wikipedia.org/wiki/Clonoulty-Rossmore_GAA" title="Wikipedia" target="_blank">Wikipedia</a>
				</li>
				<li class="youtube">
					<a href="http://www.youtube.com/user/ClonoultyRossmoreGAA?feature=watch" title="Youtube" target="_blank">Youtube</a>
				</li>
			</ul>
		</div>
		<?php $header_image = get_header_image();
		if ( ! empty( $header_image ) ) : ?>
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><img src="<?php echo esc_url( $header_image ); ?>" class="header-image" width="<?php echo get_custom_header()->width; ?>" height="<?php echo get_custom_header()->height; ?>" alt="" /></a>
		<?php endif; ?>	
	</header><!-- #masthead -->

	<div id="main" class="wrapper">