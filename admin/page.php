<?php
/**
 * Template for settings page.
 *
 * @author Rahul Aryan <rah12@live.com>
 * @package GitHook
 * @since 1.0.0
 */

$options      = get_option( 'githook_settings', [] );
$access_token = get_option( 'githook_access_token', '' );

$options = wp_parse_args( $options, array(
	'webhook_secret' => 'LObOneRMansiDBAThemE',
	'repos'          => [],
) );

$access_token = ! empty( $access_token ) ? '********' : '';

?>

<h1 class="wp-heading-inline"><?php esc_attr_e( 'GitHook Settings', 'githook' ); ?></h1>

<form method="POST" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row"><label for="access_token"><?php esc_attr_e( 'GitHub access token', 'githook' ); ?></label></th>
				<td>
					<input name="access_token" type="text" id="access_token" value="<?php echo esc_attr( $access_token ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'GitHub access token', 'githook' ); ?>">
					<p class="description" id="tagline-description"><?php esc_attr_e( 'Personal access token is required for private repository. You can obtain access token from your GitHub account', 'githook' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="webhook_secret"><?php esc_attr_e( 'WebHook Secret', 'githook' ); ?></label></th>
				<td>
					<input name="webhook_secret" type="text" id="webhook_secret" value="<?php echo esc_attr( $options['webhook_secret'] ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Secret password for webhook request.', 'githook' ); ?>">
					<p class="description" id="tagline-description"><?php esc_attr_e( 'An alpha numeric string. Try to make it at least 15 characters long.', 'githook' ); ?></p>
				</td>
			</tr>
			<tr id="repositories">
				<th scope="row">
					<label for="repositories"><?php esc_attr_e( 'Repository(s)', 'githook' ); ?></label>
				</th>
				<td>
					<div id="base-repo" class="base-repo" style="display:none">
						<a href="#" class="delete-repo button"><?php esc_attr_e( 'Delete Repository', 'githook' ); ?></a>
						<input name="repository[#][full_name]" type="text" value="" class="full_name regular-text" placeholder="i.e. username/reponame">
						<br>
						<br>
						<label>
							<input name="repository[#][is_plugin]" type="checkbox" class="is_plugin" value="1">
							<?php esc_attr_e( 'Is plugin? If unchecked then it will be considered as theme.', 'githook' ); ?>
						</label>
						<br>
						<br>
						<input name="repository[#][dir]" type="text" value="" class="dir regular-text" placeholder="i.e. twentytwelve">
						<p class="description" id="tagline-description"><?php esc_attr_e( 'Directory of theme or plugin. Only directory name NOT path. Content of this directory will be replaced by content pulled from GitHub.', 'githook' ); ?></p>
					</div>

					<a class="button" id="add-repo" href="#"><?php esc_attr_e( 'Add a repository', 'githook' ); ?></a>
				</td>
			</tr>
		</tbody>
	</table>
	<?php wp_nonce_field( 'githook-settings', '_wpnonce' ); ?>
	<input name="action" value="githook_settings" type="hidden" />
	<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Save settings', 'githook' ); ?>">
</form>

<div class="how-to">
	<label><?php esc_attr_e( 'Webhook URL. Add this url to GitHub payload url.', 'githook' ); ?></label>
	<input class="webhook-url" onClick="this.setSelectionRange(0, this.value.length)" value="<?php echo esc_url( home_url( '/?githook_action=true&secret=' . $options['webhook_secret'] ) ); ?>" />
	<p><?php esc_attr_e( 'Copy this only after adding webhook secret field.', 'githook' ); ?></p>
</div>

<script type="text/javascript">
	(function($){

		$(document).ready(function(){
			$repos = <?php echo wp_json_encode( $options['repos'] ); ?>;
			$.each($repos, function(index, repo){
				var html = $($('#base-repo').clone()).removeAttr('id');
				html.find('[name]').each(function(){
					$(this).attr('name', $(this).attr('name').replace('#', $('.base-repo').length));
				});
				html.insertBefore('#add-repo').show();
				html.find('.full_name').val(index);
				if(repo.type==='plugin')
					html.find('.is_plugin').prop('checked', true);
				html.find('.dir').val(repo.dir);
			});

			$('#add-repo').click(function(e){
				e.preventDefault();
				var html = $($('#base-repo').clone()).removeAttr('id');
				html.find('[name]').each(function(){
					$(this).attr('name', $(this).attr('name').replace('#', $('.base-repo').length));
				});
				html.insertBefore(this).show();
			});
			$('#repositories').on('click', '.delete-repo', function(e){
				e.preventDefault();
				$(this).closest('.base-repo').remove();
			});
		})
	})(jQuery)
</script>
<style>
	.base-repo {
		margin-bottom: 25px;
		border: solid 1px #ddd;
		background: #fff;
		padding: 15px;
	}
	a.delete-repo.button {
		float: right;
	}
	.how-to {
		margin-top: 15px;
	}
	.how-to p{
		margin: 0;
	}
	.how-to .webhook-url{
		background: #fff;
		display: table;
		padding: 4px 10px;
		color: #777;
		border: solid 1px #ddd;
		margin-top: 10px;
		width: 100%;
		max-width: 500px;
	}
</style>
