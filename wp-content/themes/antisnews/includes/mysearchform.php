<form method="get" id="mysearchform" action="<?php bloginfo('url'); ?>"> <input type="text" onblur="this.value = this.value || this.defaultValue; " onfocus="this.value='';" value="<?php _e("Search","Antisnews");?> ...<?php the_search_query(); ?>" name="s" id="mys" /> <input type="submit" id="mygo" value="" alt="<?php _e('Search'); ?>" title="<?php _e('Search'); ?>" /></form>
