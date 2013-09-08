<?php
/**
 * The template for displaying the footer.
 *
 * Contains footer content and the closing of the
 * #main and #page div elements.
 *
 * @package WordPress
 * @subpackage Twenty_Twelve
 * @since Twenty Twelve 1.0
 */
?>
	</div><!-- #main .wrapper -->
	
</div><!-- #page -->
<footer class="customFooter template-front-page two-sidebars">
		<?php get_sidebar( 'front' ); ?>
</footer>
<?php wp_footer(); ?>
</body>
</html>