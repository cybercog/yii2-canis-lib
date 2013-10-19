<?php
	echo '<div class="flash-'.(!empty($error) ? 'error' : 'success') .'">';
	echo isset($message) ? $message : "Unknown error!";
	if (!empty($errors)) {
		echo '<ul>';
		foreach ($errors as $error) {
			echo '<li>'. $error .'</li>';
		}
		echo '</ul>';
	}
	echo '</div>';
?>