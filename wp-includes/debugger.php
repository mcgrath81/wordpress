<?php
function debug_view ( $what, $exit ) {
    echo '<pre>';
    if ( is_array( $what ) )  {
        print_r ( $what );
    } else {
        var_dump ( $what );
    }
    echo '</pre>';
	if($exit == true){exit();};
}
?>