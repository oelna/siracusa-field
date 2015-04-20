<?php
/*
Plugin Name: Siracusa Field
Plugin URI: https://github.com/oelna/siracusa-field/
Description: Hacked-together solution to add custom CSS and JS to individual posts, as mentioned in http://ars.to/1zkBqcJ
Version: 1.0
Author: Arno Richter
Author URI: http://arnorichter.de/
License: GPLv2
*/



function sf_custom_css() {
	echo '<style>
	#siracusa_field_box textarea,
	#siracusa_field_box input[type="text"] {
		width: 100%;
	}

	#siracusa_field_box h2 {
		font-size: 1em;
		font-weight: bold;
		margin-bottom: 0;
	}

	#siracusa_field_box ul {
		margin-top: 0;
	}

	#siracusa_field_box .comment {
		margin: 1em 0 0 0;
		font-size: 0.9em;
		font-style: italic;
	}

	#siracusa_field_box .sf-styles,
	#siracusa_field_box .sf-style-urls,
	#siracusa_field_box .sf-scripts,
	#siracusa_field_box .sf-script-urls {
		margin-bottom: 1em;
	}

	</style>';
}
add_action('admin_head', 'sf_custom_css');



function sf_footer_script() {
	?>
	<script>
		function sf_add_listitem(kind) {
			jQuery('.sf-'+kind+'-urls ul').append('<li><input type="text" class="url" name="sf_'+kind+'s[]" /></li>');
		}
	</script>
	<?php
}
add_action('in_admin_footer', 'sf_footer_script');



function siracusa_field() {
    add_meta_box('siracusa_field_box',
		'The Siracusa Field',
		'display_siracusa_field',
		'post',
		'normal',
		'high'
    );
}
add_action('admin_init', 'siracusa_field');



function display_siracusa_field( $post ) {

	$fields = json_decode(get_post_meta($post->ID, 'siracusa_field', true), true);
	wp_nonce_field('siracusa_fields_meta_'.$post->ID, '_siracusa_fields_nonce');
	?>

	<div class="sf-styles">
		<h2>Additional Styles</h2>
		<textarea name="sf_style"><?= $fields['style'] ?></textarea>
	</div>

	<div class="sf-style-urls">
		<h2>External CSS URLs</h2>
		<ul><?php if(empty($fields['styles'])): ?>
			<li><input type="text" name="sf_styles[]" value="" /></li>
		<?php else: foreach($fields['styles'] as $url):	?>
			<li><input type="text" name="sf_styles[]" value="<?= $url ?>" /></li>
		<?php endforeach; endif; ?>
		</ul>
		<a href="javascript:sf_add_listitem('style');">+ Add New</a>
		<p class="comment">Inserted before article</p>
	</div>

	<div class="sf-script-urls">
		<h2>External script URLs</h2>
		<ul><?php if(empty($fields['scripts'])): ?>
			<li><input type="text" name="sf_scripts[]" value="" /></li>
		<?php else: foreach($fields['scripts'] as $url): ?>
			<li><input type="text" name="sf_scripts[]" value="<?= $url ?>" /></li>
		<?php endforeach; endif; ?>
		</ul>
		<a href="javascript:sf_add_listitem('script');">+ Add New</a>
		<p class="comment">Inserted after article</p>
	</div>

	<div class="sf-scripts">
		<h2>Javascript</h2>
		<textarea name="sf_script"><?= $fields['script'] ?></textarea>
	</div>
    <?php
}



function add_siracusa_field( $id, $post ) {
	// check autosave
	if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}
        
        // check if nonce is set
        if(!isset($_POST['_siracusa_fields_nonce'])) {
        	return;
        }
        
        // does this user have the capability to 
        if(!current_user_can('edit_post', $id)) {
        	return;
        }
	
	check_admin_referer('siracusa_fields_meta_'.$id, '_siracusa_fields_nonce');
	
	$insert = array();

	if(isset($_POST['sf_style']) && !empty($_POST['sf_style'])) {
		$insert['style'] = $_POST['sf_style'];
	}

	if(isset($_POST['sf_styles']) && !empty($_POST['sf_styles'])) {
		$insert['styles'] = array_filter($_POST['sf_styles']);
	}

	if(isset($_POST['sf_scripts']) && !empty($_POST['sf_scripts'])) {
		$insert['scripts'] = array_filter($_POST['sf_scripts']);
	}

	if(isset($_POST['sf_script']) && !empty($_POST['sf_script'])) {
		$insert['script'] = $_POST['sf_script'];
	}

	if(!empty($insert)) update_post_meta($id, 'siracusa_field', json_encode(array_filter($insert)));
}
add_action('save_post', 'add_siracusa_field', 10, 2);



function output_siracusa_field() {
	global $post;
	global $siracusa_field;
	$i = 0;

	if(!is_single()) return;
	if(empty($siracusa_field)) $siracusa_field = json_decode(get_post_meta($post->ID, 'siracusa_field', true), true); //only load this once

	if(!empty($siracusa_field['styles'])) {
		foreach($siracusa_field['styles'] as $stylesheet) {
			$i++;
			wp_enqueue_style('siracusa_field_css_'.$i, $stylesheet);
		}
	}

	if(!empty($siracusa_field['scripts'])) {
		$i = 0;
		foreach($siracusa_field['scripts'] as $script) {
			$i++;
			wp_enqueue_script('siracusa_field_js_'.$i, $script, null, null, true);
		}
	}

}
add_action('wp_enqueue_scripts', 'output_siracusa_field');



function output_sf_embedded_style() {
	global $post;
	global $siracusa_field;

	if(!is_single()) return;
	if(empty($siracusa_field)) $siracusa_field = json_decode(get_post_meta($post->ID, 'siracusa_field', true), true); //only load this once

	if(!empty($siracusa_field['style'])) echo('<style>'.$siracusa_field['style'].'</style>');
}
add_action('wp_head', 'output_sf_embedded_style');



function output_sf_embedded_script() {
	global $post;
	global $siracusa_field;

	if(!is_single()) return;
	if(empty($siracusa_field)) $siracusa_field = json_decode(get_post_meta($post->ID, 'siracusa_field', true), true); //only load this once

	if(!empty($siracusa_field['script'])) echo('<script>'.$siracusa_field['script'].'</script>');
}
add_action('wp_footer', 'output_sf_embedded_script');

?>
