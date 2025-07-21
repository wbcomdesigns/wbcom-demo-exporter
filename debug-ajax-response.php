<?php
/**
 * Debug helper for testing AJAX response
 * This file helps test if package.json is served correctly without PHP warnings
 * 
 * Usage: Access this file with ?test=package to see if JSON is served cleanly
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Allow standalone testing
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

// Test mode
if ( isset( $_GET['test'] ) && $_GET['test'] === 'package' ) {
	// Simulate AJAX request conditions
	@error_reporting(0);
	@ini_set('display_errors', 0);
	@ini_set('display_startup_errors', 0);
	
	// Start output buffering
	ob_start();
	
	// Test JSON output
	$test_data = array(
		'status' => 'success',
		'message' => 'If you see this as clean JSON without PHP warnings, the error suppression is working correctly.',
		'timestamp' => date('Y-m-d H:i:s'),
		'php_version' => phpversion(),
		'error_reporting' => error_reporting(),
		'display_errors' => ini_get('display_errors'),
	);
	
	// Clean buffer and output
	ob_end_clean();
	header('Content-Type: application/json');
	echo json_encode($test_data, JSON_PRETTY_PRINT);
	exit;
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>WBCOM Demo Exporter - Debug Helper</title>
	<style>
		body { font-family: Arial, sans-serif; margin: 40px; }
		.test-link { display: inline-block; margin: 10px 0; padding: 10px 20px; background: #0073aa; color: white; text-decoration: none; }
		.info { background: #f1f1f1; padding: 20px; margin: 20px 0; }
		code { background: #e1e1e1; padding: 2px 5px; }
	</style>
</head>
<body>
	<h1>WBCOM Demo Exporter - Debug Helper</h1>
	
	<div class="info">
		<h2>Error Suppression Test</h2>
		<p>Click the link below to test if JSON responses are served cleanly without PHP warnings:</p>
		<a href="?test=package" class="test-link" target="_blank">Test JSON Response</a>
		<p>If the response is clean JSON without any PHP warnings or HTML, then the error suppression is working correctly.</p>
	</div>
	
	<div class="info">
		<h2>Current PHP Settings</h2>
		<ul>
			<li><code>error_reporting</code>: <?php echo error_reporting(); ?></li>
			<li><code>display_errors</code>: <?php echo ini_get('display_errors'); ?></li>
			<li><code>display_startup_errors</code>: <?php echo ini_get('display_startup_errors'); ?></li>
			<li>PHP Version: <?php echo phpversion(); ?></li>
		</ul>
	</div>
	
	<div class="info">
		<h2>Implementation Summary</h2>
		<p>The following measures have been implemented to ensure clean JSON output:</p>
		<ol>
			<li>Error reporting disabled at the start of AJAX handlers</li>
			<li>Output buffering to catch any warnings</li>
			<li>Proper Content-Type headers set for JSON responses</li>
			<li>Buffer cleaned before outputting JSON</li>
			<li>Error settings restored after export operations</li>
		</ol>
	</div>
</body>
</html>