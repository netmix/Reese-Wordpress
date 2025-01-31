<?php if( current_user_can('edit_usergroups')) : ?>

	<?php ef_the_message( $message ) ?>
	<?php ef_the_errors( $errors ) ?>

	<p><a href="<?php echo EDIT_FLOW_USERGROUPS_ADD_LINK ?>" class="button"><?php _e('+ Add Usergroup', 'edit-flow') ?></a></p>

	<?php if( !empty($usergroups) ) : ?>
		<?php $delete_nonce = wp_create_nonce('custom-status-delete-nonce'); ?>
		<table class="widefat usergroups fixed">
			<thead>
				<?php // TODO: throw this into a function ?>
				<th class="manage-column column-cb check-column" id="col-cb" scope="col">&nbsp;</th>
				<th class="manage-column column-name" id="col-name" scope="col"><?php _e('Name', 'edit-flow') ?></th>
				<th class="manage-column column-description" id="col-description" scope="col"><?php _e('Description', 'edit-flow') ?></th>
				<th class="manage-column column-users" id="col-users-count" scope="col"><?php _e('Users', 'edit-flow') ?></th>
			</thead>
			<tfoot>
				<?php // TODO: throw this into a function ?>
				<th class="manage-column column-cb check-column" id="col-cb" scope="col">&nbsp;</th>
				<th class="manage-column column-name" id="col-name" scope="col"><?php _e('Name', 'edit-flow') ?></th>
				<th class="manage-column column-description" id="col-description" scope="col"><?php _e('Description', 'edit-flow') ?></th>
				<th class="manage-column column-users" id="col-users-count" scope="col"><?php _e('Users', 'edit-flow') ?></th>
			</tfoot>
			<tbody>
		<?php foreach( $usergroups as $usergroup ) : ?>
			<?php
			$edit_link = ef_get_the_usergroup_edit_link( $usergroup->slug );
			$delete_link = ef_get_the_usergroup_delete_link( $usergroup->slug, $delete_nonce );
			?>
			<tr>
				<th class="check-column" scope="row"></th>
				<td>
					<a href="<?php echo $edit_link ?>"><?php echo $usergroup->name ?></a>
					<br/>
					<div class="row-actions">
						<span class="edit">
							<a href="<?php echo $edit_link; ?>">
								<?php _e( 'Edit', 'edit-flow' ); ?>
							</a>
							|
						</span>
						<span class="delete">
							<a href="<?php echo $delete_link ?>" class="delete:the-list:status-<?php echo esc_attr($usergroup->slug) ?> submitdelete" onclick="if(!confirm('Are you sure you want to delete this usergroup?')) return false;">
								<?php _e( 'Delete', 'edit-flow' ); ?>
							</a>
						</span>
					</div>
					
				</td>
				<td>
					<?php echo $usergroup->description ?>
				</td>
				<td>
					<?php echo $usergroup->get_user_count() ?>
				</td>
				
			</tr>
		<?php endforeach; ?>
			</tbody>
		</table>
	<?php else : ?>
		<p><?php echo sprintf( __('Aww, you haven\'t added any usergroups. <a href="%s">You could always add some!</a>', 'edit-flow'), EDIT_FLOW_USERGROUPS_ADD_LINK ); ?></p>
	<?php endif; ?>
<?php else : ?>
	
	<p><?php _e('Sorry, looks like you don\'t have permission to be here. Please contact your administrator.', 'edit-flow') ?></p>

<?php endif; ?>