<?php
/**
 * GitHub Push-to-Deploy Configuration
 *
 * Copy this file to deploy-config.php and fill in your values.
 * Add deploy-config.php to .gitignore - it must not be committed.
 *
 * @see deploy.php
 */
return [
	// Required. The secret you set when creating the GitHub webhook.
	// Generate a random string: openssl rand -hex 32
	'webhook_secret' => '',

	// Optional. GitHub Personal Access Token for private repositories.
	// Create at: GitHub → Settings → Developer settings → Personal access tokens
	// Required scope: repo
	'github_token' => '',

	// Required. Repository in "owner/repo" format. Must match the webhook payload.
	'repo' => 'chrisegg/form-locator-for-gravity-forms',

	// Optional. Only deploy when pushes occur to this branch.
	// Set to null to deploy on any branch push.
	// Example: 'main' or 'release'
	'branch_filter' => null,
];
