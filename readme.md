# Arcanum PHP SDK

Official PHP SDK for Arcanum - a secure secrets management platform for storing and managing API keys, credentials, and sensitive configuration.

## Requirements

- PHP 7.4 or higher
- Guzzle HTTP client
- Valid Arcanum API credentials

## Installation

Install via Composer:

```bash
composer require aurora/arcanum-php-sdk
```

## Quick Start

```php
<?php
use Arcanum\Sdk\Client;

// Initialize the client
$client = new Client(
    apiKey: $_ENV['ARCANUM_API_KEY'],
    apiSecret: $_ENV['ARCANUM_API_SECRET']
);

// Start using -- list all projects, for instance
$projects = $client->listProjects();
foreach ($projects as $project) {
    echo "{$project->name}" . PHP_EOL;
}
```

## Configuration

```php
<?php
use Arcanum\Sdk\Client;

$client = new Client(
    apiKey: 'your-api-key',              // Required
    apiSecret: 'your-api-secret',        // Required
    httpClient: $customGuzzleClient      // optional,if you want to customize the HTTP client
);
```

### Environment Variables

If you don't pass the API key and secret directly, you can use environment variables:
```bash
ARCANUM_API_KEY=your-api-key
ARCANUM_API_SECRET=your-api-secret
```

## Common Usage Examples

### Working with Secrets

#### List All Secrets
```php
<?php
$secrets = $client->listSecrets();
foreach ($secrets as $secret) {
    echo "Secret: {$secret->name} in vault {$secret->vault->name}\n";
    echo "Fields: " . implode(', ', $secret->fields) . "\n";
}
```

#### Get a Decrypted Secret
```php
<?php
$secret = $client->getSecret(252);

// Access individual field values
$apiKey = $secret->getFieldValue('api-key');
$apiSecret = $secret->getFieldValue('api-secret');

// Check if a field exists
if ($secret->hasField('database-password')) {
    $password = $secret->getFieldValue('database-password');
}

// Get the field object
$field = $secret->getField('api-key');
echo "{$field->name}: {$field->value}\n";

// Convert to array
$secretArray = $secret->toArray();
```

#### Search for Secrets

```php
<?php
// Find secret by exact name
$secret = $client->getSecretByName('Database Credentials');

// Find secret by name in specific vault
$secret = $client->getSecretByName('API Keys', 'Production');

// Search secrets by partial name (case-insensitive)
$secrets = $client->findSecretsByName('credentials');

// Get all secrets in a vault
$secrets = $client->getSecretsByVault('Production');

// Get decrypted secret by name (combines search + decrypt)
$secret = $client->getDecryptedSecretByName('Database Credentials', 'Production');
if ($secret) {
    echo $secret->getFieldValue('username');
}
```

#### Create a New Secret
```php
<?php
$response = $client->createSecret([
    'name' => 'My API Keys',
    'vault' => 'Production',
    'description' => 'API keys for external service',
    'fields' => [
        ['name' => 'API Key', 'value' => 'key-123'],
        ['name' => 'API Secret', 'value' => 'secret-456'],
    ]
]);
```

#### Update a Secret
```php
<?php
$response = $client->updateSecret('252', [
    'name' => 'Updated Secret Name',
    'description' => 'Updated description'
]);
```

#### Delete a Secret
```php
<?php
$response = $client->deleteSecret('252');
```

### Working with Projects

#### List All Projects
```php
<?php
$projects = $client->listProjects();
foreach ($projects as $project) {
    echo "Project: {$project->name} ({$project->slug})\n";
    echo "Owner: {$project->owner->name}\n";
    echo "Secrets: " . count($project->secrets) . "\n";
}
```

#### Get a Specific Project
```php
<?php
$project = $client->getProject('web-application');

// Check if project has a specific secret
if ($project->hasSecret(252)) {
    $secret = $project->getSecretById(252);
}

// Get secret by name from project
$secret = $project->getSecretByName('API Keys');
```

#### Search for Projects
```php
<?php
// Find project by exact name
$project = $client->getProjectByName('Web Application');

// Search projects by partial name
$projects = $client->findProjectsByName('app');

// Get all projects owned by a user
$projects = $client->getProjectsByOwner('netid123');
```

#### Create a New Project
```php
<?php
$project = $client->createProject([
    'name' => 'Mobile Application',
    'description' => 'Mobile app project',
    'slug' => 'mobile-app' // Optional, auto-generated if not provided
]);

echo "Created project: {$project->name}\n";
```

#### Update a Project
```php
<?php
$project = $client->editProject('mobile-app', [
    'name' => 'Updated Mobile App',
    'description' => 'Updated description'
]);
```

#### Add/Remove Secrets from Project
```php
<?php
// Add a secret to a project
$client->addProjectSecret('mobile-app', '252');

// Remove a secret from a project
$client->removeProjectSecret('mobile-app', '252');
```

### Working with Vaults

#### List All Vaults
```php
<?php
$vaults = $client->listVaults();
foreach ($vaults as $vault) {
    echo "Vault: {$vault->name}\n";
    echo "Description: {$vault->description}\n";
}
```

#### Find a Vault
```php
<?php
$vault = $client->getVaultByName('Production');
if ($vault) {
    echo "Found vault ID: {$vault->id}\n";
}
```

#### Create a New Vault
```php
<?php
$vault = $client->createVault([
    'name' => 'Staging',
    'description' => 'Staging environment secrets'
]);

echo "Created vault: {$vault->name} (ID: {$vault->id})\n";
```

### Access Control Management

#### Grant Access to Projects
```php
<?php
// Grant viewer access
$client->grantProjectViewAccess('web-app', 'netid123');

// Grant editor access
$client->grantProjectEditorAccess('web-app', 'netid456');

// Grant admin access
$client->grantProjectAdminAccess('web-app', 'netid789');

// Or use the unified method
$client->grantProjectAccess('web-app', 'netid123', 'viewer');
```

#### Grant Access to Secrets
```php
<?php
// Grant viewer access to a secret
$client->grantSecretViewAccess('252', 'netid123');

// Grant editor access
$client->grantSecretEditorAccess('252', 'netid456');

// Grant admin access
$client->grantSecretAdminAccess('252', 'netid789');

// Or use the unified method
$client->grantSecretAccess('252', 'netid123', 'editor');
```

#### Revoke Access
```php
<?php
// Revoke project access
$client->revokeProjectAccess('web-app', 'netid123');

// Revoke secret access
$client->revokeSecretAccess('252', 'netid123');

// Revoke vault access
$client->revokeVaultAccess('Production', 'netid123');
```

#### Bulk Access Management
```php
<?php
// Process bulk project access changes
$client->processBulkProjectAccess('web-app', [
    'add' => [
        ['netId' => 'user1', 'role' => 'viewer'],
        ['netId' => 'user2', 'role' => 'editor']
    ],
    'remove' => ['user3']
]);

// Process bulk secret access changes
$client->processBulkSecretAccess('252', [
    'add' => [
        ['netId' => 'user1', 'role' => 'viewer']
    ],
    'remove' => ['user2']
]);
```

### Working with Tokens

#### List API Tokens
```php
<?php
$tokens = $client->listTokens();
foreach ($tokens as $token) {
    echo "Token: {$token->principal}\n";
    echo "Expires: " . $token->getExpiryDate()->format('Y-m-d H:i:s') . "\n";
    echo "Expired: " . ($token->isExpired() ? 'Yes' : 'No') . "\n";
}
```

#### Get Current Token Info
```php
<?php
$info = $client->getTokenInfo();
echo "Current principal: {$info->principal}\n";
echo "User token: " . ($info->userToken ? 'Yes' : 'No') . "\n";

// Check authorities
if ($info->hasAuthority('ROLE_ADMIN')) {
    echo "You have admin privileges\n";
}
```

### Working with Users

#### Get Current User Info
```php
<?php
$user = $client->getSelfUserInfo();
echo "User: {$user->name} ({$user->netId})\n";
echo "Authorities: " . implode(', ', $user->authorities) . "\n";

// Check if user has specific authority
if ($user->hasAuthority('ROLE_ADMIN')) {
    echo "User is an administrator\n";
}
```

#### List Users for Sharing
```php
<?php
$users = $client->listUsersForSharing();
foreach ($users as $user) {
    echo "{$user->name} - {$user->netId}\n";
}
```

#### Manage User Authorities (Admin Only)
```php
<?php
// Grant authority to user
$client->grantUserAuthority('netid123', [
    'authority' => 'ROLE_ADMIN'
]);

// Revoke authority from user
$client->revokeUserAuthority('netid123', [
    'authority' => 'ROLE_ADMIN'
]);
```

## Testing

Most testing is run against mock data, so you don't have to actually provide API credentials.

### Running Tests

```bash
# Run all tests
vendor/bin/pest

# Run only unit tests
vendor/bin/pest tests/Unit

# Run specific test file
vendor/bin/pest tests/Unit/Model/ArcanumProjectTest.php

# Run with coverage
vendor/bin/pest --coverage
```

### Integration Testing

To run tests against the real API, ensure your `.env` file is configured:

```bash
# Copy the example file
cp .env.example .env

# Edit with your credentials
ARCANUM_API_KEY=your_api_key
ARCANUM_API_SECRET=your_api_secret
ARCANUM_API_BASE_URL=aracanum_base_url
```

Then run:

```bash
vendor/bin/pest tests/Unit/ClientTest.php
```

## Credits

Developed and maintained by the UConn ITS Web Development team.
