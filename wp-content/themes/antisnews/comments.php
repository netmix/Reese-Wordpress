<?php
// Do not delete these lines
	if (!empty($_SERVER['SCRIPT_FILENAME']) && 'comments.php' == basename($_SERVER['SCRIPT_FILENAME']))
		die ('Please do not load this page directly. Thanks!');

	if ( post_password_required() ) { ?>
		<p class="nocomments"><?php _e('This post is password protected. Enter the password to view comments.','Antisnews'); ?></p>
	<?php
		return;
	}
?>

<!-- You can start editing here. -->
<div class="commentareain">

<?php if ( have_comments() ) : ?>

	<h3><?php comments_number(__('No Comments','Antisnews'), __('One Comment','Antisnews'), __('% Comments','Antisnews') );?> <?php _e('for','Antisnews'); ?> &#8220;<?php the_title(); ?>&#8221;</h3>

	<ul class="commentlist">
	<?php wp_list_comments('avatar_size=48&callback=custom_comment&type=comment'); ?>
	</ul>    

	<div class="navigation">
		<div class="alignleft"><?php previous_comments_link() ?></div>
		<div class="alignright"><?php next_comments_link() ?></div>
		<div class="fix"></div>
	</div>
	<br />



    
	<?php global $comments_by_type; if ( $comments_by_type['pings'] ) : ?>
    <h2 id="pings"><?php _e('Trackbacks/Pingbacks','Antisnews'); ?></h2>
    <ul class="commentlist">
    <?php wp_list_comments('type=pings'); ?>
    </ul>
    <?php endif; ?>

    
 
<?php else : // this is displayed if there are no comments so far ?>

	<?php if ('open' == $post->comment_status) : ?>
		<!-- If comments are open, but there are no comments. -->

	 <?php else : // comments are closed ?>
		<!-- If comments are closed. -->
		<p class="nocomments"><?php _e('Comments are closed.','Antisnews'); ?></p>

	<?php endif; ?>

<?php endif; ?>

<?php if ('open' == $post->comment_status) : ?>

<div id="respond">

<h3><?php comment_form_title( __('Leave a Comment','Antisnews'), __('Leave a Comment to %s','Antisnews') ); ?></h3>
<div class="cancel-comment-reply">
	<p><small><?php cancel_comment_reply_link(); ?></small></p>
</div>

<?php if ( get_option('comment_registration') && !$user_ID ) : ?>

<p><?php _e('You must be','Antisnews'); ?> <a href="<?php echo get_option('siteurl'); ?>/wp-login.php?redirect_to=<?php echo urlencode(get_permalink()); ?>"><?php _e('logged 

in','Antisnews'); ?></a> <?php _e('to post a comment.','Antisnews'); ?></p>

<?php else : ?>
<form action="<?php echo get_option('siteurl'); ?>/wp-comments-post.php" method="post" id="commentform">

<?php if ( $user_ID ) : ?>

<p><?php _e('Logged in as','Antisnews'); ?> <a href="<?php echo get_option('siteurl'); ?>/wp-admin/profile.php"><?php echo $user_identity; ?></a>. <a href="<?php echo 

wp_logout_url(); ?>" title="<?php _e('Log out of this account','Antisnews'); ?>"><?php _e('Logout &raquo;','Antisnews'); ?></a></p>

<?php else : ?>

<p><input type="text" name="author" id="author" value="<?php echo $comment_author; ?>" size="22" tabindex="1" />
<label for="author"><small><?php _e('Name','Antisnews'); ?> <?php if ($req) echo _e('(required)','Antisnews'); ?></small></label></p>

<p><input type="text" name="email" id="email" value="<?php echo $comment_author_email; ?>" size="22" tabindex="2" />
<label for="email"><small><?php _e('Mail (will not be published)','Antisnews'); ?> <?php if ($req) echo _e('(required)','Antisnews'); ?></small></label></p>

<p><input type="text" name="url" id="url" value="<?php echo $comment_author_url; ?>" size="22" tabindex="3" />
<label for="url"><small><?php _e('Website','Antisnews'); ?></small></label></p>

<?php endif; ?>


<!--<p><small><strong>XHTML:</strong> You can use these tags: <?php echo allowed_tags(); ?></small></p>-->

<p><textarea name="comment" id="comment" style="width:97%;" rows="4" tabindex="4"></textarea></p>




<p><input name="submit" type="submit" class="button" id="submit" tabindex="5" value="<?php _e('Submit Comment','Antisnews'); ?>" />
<input type="hidden" name="comment_post_ID" value="<?php echo $id; ?>" />
</p>
<?php comment_id_fields(); ?>
<?php do_action('comment_form', $post->ID); ?>

</form>

<?php endif; // If logged in ?>



<div class="fix"></div>
</div> <!-- end #respond -->




<?php endif; // if you delete this the sky will fall on your head here below is where we edited?>

</div><!-- end #commentareain -->

