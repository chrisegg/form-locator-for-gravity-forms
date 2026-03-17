<?php
/**
 * GitHub Push-to-Deploy for WordPress
 *
 * Drop-in webhook receiver. Place in plugin or theme root.
 * Configure via deploy-config.php (copy from deploy-config.example.php).
 *
 * @see https://docs.github.com/en/webhooks/using-webhooks/validating-webhook-deliveries
 * @see https://docs.github.com/en/rest/repos/contents#download-a-repository-archive-zip
 */

// Only accept POST
if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
	http_response_code(405);
	header( 'Content-Type: application/json' );
	echo json_encode( [ 'error' => 'Method not allowed' ] );
	exit;
}

// Load config (try local first, then wp-config constants)
$config_file = __DIR__ . '/deploy-config.php';
if ( file_exists( $config_file ) ) {
	$config = include $config_file;
} else {
	// Fallback to wp-config.php if it exists
	// wp-config.php is in WordPress root (one level above wp-content)
	$wp_config = dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-config.php';
	if ( file_exists( $wp_config ) ) {
		require_once $wp_config;
		$config = [
			'webhook_secret' => defined( 'GITHUB_DEPLOY_SECRET' ) ? GITHUB_DEPLOY_SECRET : '',
			'github_token'   => defined( 'GITHUB_DEPLOY_TOKEN' ) ? GITHUB_DEPLOY_TOKEN : '',
			'repo'           => defined( 'GITHUB_DEPLOY_REPO' ) ? GITHUB_DEPLOY_REPO : '',
			'branch_filter'  => defined( 'GITHUB_DEPLOY_BRANCH' ) ? GITHUB_DEPLOY_BRANCH : null,
		];
	} else {
		$config = [];
	}
}

if ( empty( $config['webhook_secret'] ) || empty( $config['repo'] ) ) {
	http_response_code(500);
	header( 'Content-Type: application/json' );
	echo json_encode( [
		'error'   => 'Configuration missing',
		'message' => 'Create deploy-config.php from deploy-config.example.php and set webhook_secret and repo.',
	] );
	exit;
}

$target_dir = __DIR__;
$log_file   = __DIR__ . '/deploy.log';

function deploy_log( $message, $log_file ) {
	$entry = date( 'Y-m-d H:i:s' ) . ' ' . $message . "\n";
	@file_put_contents( $log_file, $entry, FILE_APPEND | LOCK_EX );
}

function respond( $data, $code = 200 ) {
	http_response_code( $code );
	header( 'Content-Type: application/json' );
	echo json_encode( $data );
	exit;
}

// Get raw body for signature verification
$raw_body = file_get_contents( 'php://input' );
if ( $raw_body === false ) {
	deploy_log( 'ERROR: Could not read request body', $log_file );
	respond( [ 'error' => 'Invalid request' ], 400 );
}

// Verify webhook signature
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
if ( empty( $signature ) ) {
	deploy_log( 'ERROR: Missing X-Hub-Signature-256', $log_file );
	respond( [ 'error' => 'Missing signature' ], 401 );
}

$expected = 'sha256=' . hash_hmac( 'sha256', $raw_body, $config['webhook_secret'] );
if ( ! hash_equals( $expected, $signature ) ) {
	deploy_log( 'ERROR: Invalid webhook signature', $log_file );
	respond( [ 'error' => 'Invalid signature' ], 401 );
}

$payload = json_decode( $raw_body, true );
if ( json_last_error() !== JSON_ERROR_NONE ) {
	deploy_log( 'ERROR: Invalid JSON payload', $log_file );
	respond( [ 'error' => 'Invalid payload' ], 400 );
}

// Handle ping (GitHub sends this when webhook is created)
if ( ( $_SERVER['HTTP_X_GITHUB_EVENT'] ?? '' ) === 'ping' ) {
	deploy_log( 'INFO: Ping received', $log_file );
	respond( [ 'message' => 'Pong' ] );
}

// Only process push events
if ( ( $_SERVER['HTTP_X_GITHUB_EVENT'] ?? '' ) !== 'push' ) {
	respond( [ 'message' => 'Ignored (not a push event)' ] );
}

// Verify repo matches
$repo_full_name = $payload['repository']['full_name'] ?? '';
if ( strtolower( $repo_full_name ) !== strtolower( $config['repo'] ) ) {
	deploy_log( "ERROR: Repo mismatch: expected {$config['repo']}, got {$repo_full_name}", $log_file );
	respond( [ 'error' => 'Repository mismatch' ], 403 );
}

// Optional branch filter
$ref = $payload['ref'] ?? '';
if ( ! empty( $config['branch_filter'] ) ) {
	$expected_ref = 'refs/heads/' . $config['branch_filter'];
	if ( $ref !== $expected_ref ) {
		deploy_log( "INFO: Ignored push to {$ref} (filter: {$config['branch_filter']})", $log_file );
		respond( [ 'message' => "Ignored (branch filter: {$config['branch_filter']})" ] );
	}
}

// Extract ref (e.g. refs/heads/main -> main)
$ref_name = preg_replace( '#^refs/heads/#', '', $ref );
if ( empty( $ref_name ) ) {
	$ref_name = 'HEAD';
}

$owner = $payload['repository']['owner']['login'] ?? '';
$repo  = $payload['repository']['name'] ?? '';
if ( empty( $owner ) || empty( $repo ) ) {
	deploy_log( 'ERROR: Missing owner or repo in payload', $log_file );
	respond( [ 'error' => 'Invalid payload' ], 400 );
}

// Build zipball URL
$zipball_url = "https://api.github.com/repos/{$owner}/{$repo}/zipball/{$ref_name}";

// Download zip
$context_options = [
	'http' => [
		'method'  => 'GET',
		'header'  => "Accept: application/vnd.github+json\r\nUser-Agent: GitHub-Push-Deploy\r\n",
		'timeout' => 60,
	],
];
if ( ! empty( $config['github_token'] ) ) {
	$context_options['http']['header'] .= "Authorization: Bearer {$config['github_token']}\r\n";
}
$context = stream_context_create( $context_options );
$zip_data = @file_get_contents( $zipball_url, false, $context );

if ( $zip_data === false ) {
	$error = error_get_last();
	deploy_log( "ERROR: Failed to download zipball: " . ( $error['message'] ?? 'unknown' ), $log_file );
	respond( [ 'error' => 'Failed to download from GitHub' ], 502 );
}

// Check for GitHub API errors (JSON response instead of zip)
if ( strpos( $zip_data, '{' ) === 0 ) {
	$err = json_decode( $zip_data, true );
	$msg = $err['message'] ?? 'GitHub API error';
	deploy_log( "ERROR: GitHub API: {$msg}", $log_file );
	respond( [ 'error' => "GitHub: {$msg}" ], 502 );
}

// Extract to temp directory
$temp_dir = sys_get_temp_dir() . '/github-deploy-' . uniqid();
if ( ! mkdir( $temp_dir, 0755, true ) ) {
	deploy_log( 'ERROR: Could not create temp directory', $log_file );
	respond( [ 'error' => 'Server error' ], 500 );
}

$zip_path = $temp_dir . '/archive.zip';
file_put_contents( $zip_path, $zip_data );

$zip = new ZipArchive();
if ( $zip->open( $zip_path ) !== true ) {
	deploy_log( 'ERROR: Invalid zip archive', $log_file );
	respond( [ 'error' => 'Invalid archive' ], 500 );
}

$extract_dir = $temp_dir . '/extract';
mkdir( $extract_dir, 0755, true );
$zip->extractTo( $extract_dir );
$zip->close();

// Find root folder (GitHub uses owner-repo-sha/)
$entries = scandir( $extract_dir );
$root_folder = null;
foreach ( $entries as $e ) {
	if ( $e !== '.' && $e !== '..' && is_dir( $extract_dir . '/' . $e ) ) {
		$root_folder = $e;
		break;
	}
}

if ( ! $root_folder ) {
	deploy_log( 'ERROR: No root folder in archive', $log_file );
	respond( [ 'error' => 'Invalid archive structure' ], 500 );
}

$source_dir = $extract_dir . '/' . $root_folder;

// Copy files to target, skip deploy-config.php
$copied = 0;
$iterator = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator( $source_dir, RecursiveDirectoryIterator::SKIP_DOTS ),
	RecursiveIteratorIterator::SELF_FIRST
);

foreach ( $iterator as $item ) {
	$relative = substr( $item->getPathname(), strlen( $source_dir ) + 1 );
	$target   = $target_dir . '/' . $relative;

	// Skip deploy-config.php (preserve local config)
	if ( basename( $relative ) === 'deploy-config.php' || strpos( $relative, '/deploy-config.php' ) !== false ) {
		continue;
	}

	if ( $item->isDir() ) {
		if ( ! is_dir( $target ) ) {
			mkdir( $target, 0755, true );
		}
	} else {
		$target_parent = dirname( $target );
		if ( ! is_dir( $target_parent ) ) {
			mkdir( $target_parent, 0755, true );
		}
		if ( copy( $item->getPathname(), $target ) ) {
			$copied++;
		}
	}
}

// Cleanup temp (recursive delete)
$cleanup = function ( $path ) use ( &$cleanup ) {
	if ( ! is_dir( $path ) ) {
		@unlink( $path );
		return;
	}
	foreach ( scandir( $path ) as $e ) {
		if ( $e !== '.' && $e !== '..' ) {
			$cleanup( $path . '/' . $e );
		}
	}
	@rmdir( $path );
};
$cleanup( $temp_dir );

$commit_msg = $payload['head_commit']['message'] ?? '';
$commit_id  = substr( $payload['head_commit']['id'] ?? '', 0, 7 );

deploy_log( "SUCCESS: Deployed {$copied} files (commit {$commit_id})", $log_file );

respond( [
	'message' => 'Deployed successfully',
	'commit'  => $commit_id,
	'files'   => $copied,
	'repo'    => $repo_full_name,
	'ref'     => $ref_name,
] );
