<?php

namespace Arcanum\Sdk;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Arcanum\Sdk\Exception\ApiException;
use Arcanum\Sdk\Exception\NotFoundException;
use Arcanum\Sdk\Exception\UnauthorizedException;
use Arcanum\Sdk\Exception\ValidationException;
use Arcanum\Sdk\Exception\RateLimitException;
use Arcanum\Sdk\Model\ArcanumDecryptedSecret;
use Arcanum\Sdk\Model\ArcanumEncryptedSecret;
use Arcanum\Sdk\Model\ArcanumProject;
use Arcanum\Sdk\Model\ArcanumSecretField;
use Arcanum\Sdk\Model\ArcanumSelfInfo;
use Arcanum\Sdk\Model\ArcanumToken;
use Arcanum\Sdk\Model\ArcanumUser;
use Arcanum\Sdk\Model\ArcanumVault;

class Client
{
    /**
     * @param string $apiKey Arcanum API Key.
     * @param string $apiSecret Arcanum API Secret.
     * @param string $baseUrl Base URL for the API. 
     * @param GuzzleClient|null $httpClient Optional custom Guzzle client.
     */
    public function __construct(
        private ?string $apiKey = null,
        private ?string $apiSecret = null,
        private ?string $baseUrl = null,
        private ?GuzzleClient $httpClient = null
    ) {
        $this->apiKey = $apiKey ?? $_ENV['ARCANUM_API_KEY'];
        $this->apiSecret = $apiSecret ?? $_ENV['ARCANUM_API_SECRET'];
        $this->baseUrl = rtrim($baseUrl ?? $_ENV['ARCANUM_API_BASE_URL'] , '/');
        if (empty($this->apiKey) || empty($this->apiSecret)) {
            throw new \InvalidArgumentException('API Key and Secret are required.');
        }
        $this->httpClient = $httpClient ?? new GuzzleClient([
            'base_uri' => $this->baseUrl . '/',
            'timeout' => 10.0,
        ]);
    }

    /**
     * Makes an HTTP request to the Arcanum API.
     *
     * @param string $method HTTP Method (GET, POST, PUT, DELETE)
     * @param string $path API endpoint path (e.g., '/vault/list')
     * @param array $options Guzzle request options (e.g., 'json', 'query')
     * @return array Decoded JSON response body.
     * @throws NotFoundException When the requested resource is not found (404).
     * @throws UnauthorizedException When authentication fails or access is denied (401, 403).
     * @throws ValidationException When the request data is invalid (400, 422).
     * @throws RateLimitException When rate limit is exceeded (429).
     * @throws ApiException On other API or network errors.
     */
    protected function request(string $method, string $path, array $options = []): array
    {
        $defaultOptions = [
            RequestOptions::HEADERS => [
                'Authorization' => "Bearer {$this->apiKey}:{$this->apiSecret}",
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ];

        $requestOptions = array_merge_recursive($defaultOptions, $options);
        if (strtoupper($method) === 'GET' || strtoupper($method) === 'DELETE' || !isset($options[RequestOptions::JSON])) {
            unset($requestOptions[RequestOptions::HEADERS]['Content-Type']);
        }
        try {
            $response = $this->httpClient->request($method, ltrim($path, '/'), $requestOptions);
            $body = $response->getBody()->getContents();
            if (empty($body)) {
                if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                    return [];
                } else {
                    throw new ApiException("API returned status code {$response->getStatusCode()} with an empty body.", $response->getStatusCode());
                }
            }
            $decoded = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new ApiException('Failed to decode JSON response: ' . json_last_error_msg());
            }

            return $decoded;
        } catch (RequestException $e) {
            $message = "API request failed: " . $e->getMessage();
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
            $errors = [];
            $retryAfter = null;

            if ($e->hasResponse()) {
                $responseBody = $e->getResponse()->getBody()->getContents();
                $errorDetails = json_decode($responseBody, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    if (isset($errorDetails['message'])) {
                        $message .= " - API Message: " . $errorDetails['message'];
                    }
                    if (isset($errorDetails['errors'])) {
                        $errors = $errorDetails['errors'];
                    }
                } elseif (!empty($responseBody)) {
                    $message .= " - Response: " . $responseBody;
                }

                if ($statusCode === 429 && $e->getResponse()->hasHeader('Retry-After')) {
                    $retryAfter = (int) $e->getResponse()->getHeader('Retry-After')[0];
                }
            }

            throw match ($statusCode) {
                404 => new NotFoundException($message, $statusCode, $e),
                401, 403 => new UnauthorizedException($message, $statusCode, $e),
                400, 422 => new ValidationException($message, $statusCode, $e, $errors),
                429 => new RateLimitException($message, $statusCode, $e, $retryAfter),
                default => new ApiException($message, $statusCode, $e),
            };
        } catch (\Throwable $e) {
            throw new ApiException("An unexpected error occurred: " . $e->getMessage(), 0, $e);
        }
    }

    private function validateNotEmpty(string $value, string $fieldName): void
    {
        if (empty(trim($value))) {
            throw new \InvalidArgumentException("{$fieldName} cannot be empty.");
        }
    }

    private function validateId(string|int $id, string $fieldName = 'ID'): void
    {
        if (empty($id)) {
            throw new \InvalidArgumentException("{$fieldName} cannot be empty.");
        }
        if (is_string($id) && !ctype_digit($id) && !is_numeric($id)) {
            throw new \InvalidArgumentException("{$fieldName} must be a valid number.");
        }
    }

    private function validateSlug(string $slug, string $fieldName = 'Slug'): void
    {
        $this->validateNotEmpty($slug, $fieldName);
        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            throw new \InvalidArgumentException("{$fieldName} must be a valid slug (lowercase letters, numbers, and hyphens only).");
        }
    }

    private function validateNetId(string $netId): void
    {
        $this->validateNotEmpty($netId, 'NetID');
    }

    // --- Vaults Endpoints ---

    /**
     * List all vaults accessible to the current API key.
     *
     * @return ArcanumVault[] Array of ArcanumVault objects.
     * @throws UnauthorizedException When authentication fails or access is denied.
     * @throws ApiException On API or network errors.
     */
    public function listVaults(): array
    {
        $vaults = $this->request('GET', '/vault/list');
        $vaultArray = [];
        foreach ($vaults as $vault) {
            $vaultArray[] = new ArcanumVault(
                $vault['id'],
                $vault['name'],
                $vault['description']
            );
        }
        return $vaultArray;
    }

    /**
     * Create a new vault.
     *
     * @param array $data Vault data including 'name' (required) and 'description' (optional).
     * @return ArcanumVault The created vault object.
     * @throws \InvalidArgumentException When required fields are missing.
     * @throws ValidationException When the vault data is invalid.
     * @throws UnauthorizedException When authentication fails or access is denied.
     * @throws ApiException On API or network errors.
     */
    public function createVault(array $data): ArcanumVault
    {
        if (empty($data['name'])) {
            throw new \InvalidArgumentException('Vault name is required.');
        }
        $vault = $this->request('POST', '/vault/create', [RequestOptions::JSON => $data]);
        return new ArcanumVault(
            $vault['id'],
            $vault['name'],
            $vault['description'] ?? ''
        );
    }

    /**
     * Grant a user access to a vault.
     *
     * @param string $vaultName The name of the vault.
     * @param string $netId The NetID of the user to grant access to.
     * @return array API response.
     * @throws \InvalidArgumentException When vault name or NetID is empty.
     * @throws NotFoundException When the vault or user is not found.
     * @throws UnauthorizedException When authentication fails or access is denied.
     * @throws ApiException On API or network errors.
     */
    public function grantVaultAccess(string $vaultName, string $netId): array
    {
        $this->validateNotEmpty($vaultName, 'Vault name');
        $this->validateNetId($netId);
        return $this->request('PUT', "/vault/{$vaultName}/{$netId}");
    }

    /**
     * Revoke a user's access to a vault.
     *
     * @param string $vaultName The name of the vault.
     * @param string $netId The NetID of the user to revoke access from.
     * @return array API response.
     * @throws \InvalidArgumentException When vault name or NetID is empty.
     * @throws NotFoundException When the vault or user is not found.
     * @throws UnauthorizedException When authentication fails or access is denied.
     * @throws ApiException On API or network errors.
     */
    public function revokeVaultAccess(string $vaultName, string $netId): array
    {
        $this->validateNotEmpty($vaultName, 'Vault name');
        $this->validateNetId($netId);
        return $this->request('DELETE', "/vault/{$vaultName}/{$netId}");
    }

    // --- Projects Endpoints ---

    /**
     * List all projects available to the API key.
     *
     * @return ArcanumProject[]|null List of ArcanumProject objects or null if no projects are available.
     * @throws UnauthorizedException When authentication fails or access is denied.
     * @throws ApiException On API or network errors.
     */
    public function listProjects(): ?array
    {
        $projects = $this->request('GET', '/project/list');
        if (empty($projects)) {
            return null;
        }
        $project_array = [];
        foreach ($projects as $project) {
            $project_array[] = new ArcanumProject(
                $project['id'],
                $project['name'],
                $project['description'],
                $project['slug'],
                new ArcanumUser($project['owner']['id'], $project['owner']['netId'], $project['owner']['name'], $project['owner']['authorities']),
                $this->processSecrets($project['secrets'])
            );
        }
        return $project_array;
    }

    /**
     * Create a new project.
     *
     * @param array $data Project data including 'name' (required), 'description' (optional), etc.
     * @return ArcanumProject The created project object.
     * @throws \InvalidArgumentException When required fields are missing.
     * @throws ValidationException When the project data is invalid.
     * @throws UnauthorizedException When authentication fails or access is denied.
     * @throws ApiException On API or network errors.
     */
    public function createProject(array $data): ArcanumProject
    {
        if (empty($data['name'])) {
            throw new \InvalidArgumentException('Project name is required.');
        }
        $project = $this->request('POST', '/project/new', [RequestOptions::JSON => $data]);
        return new ArcanumProject(
            $project['id'],
            $project['name'],
            $project['description'] ?? '',
            $project['slug'],
            new ArcanumUser($project['owner']['id'], $project['owner']['netId'], $project['owner']['name'], $project['owner']['authorities']),
            isset($project['secrets']) ? $this->processSecrets($project['secrets']) : []
        );
    }

    /**
     * Get a project by its slug.
     *
     * @param string $slug The project slug.
     * @return ArcanumProject The project object.
     * @throws \InvalidArgumentException When slug is empty or invalid.
     * @throws NotFoundException When the project is not found.
     * @throws UnauthorizedException When authentication fails or access is denied.
     * @throws ApiException On API or network errors.
     */
    public function getProject(string $slug): ArcanumProject
    {
        $this->validateSlug($slug, 'Project slug');
        $project = $this->request('GET', "/project/{$slug}");
        return new ArcanumProject(
            $project['id'],
            $project['name'],
            $project['description'] ?? '',
            $project['slug'],
            new ArcanumUser($project['owner']['id'], $project['owner']['netId'], $project['owner']['name'], $project['owner']['authorities']),
            isset($project['secrets']) ? $this->processSecrets($project['secrets']) : []
        );
    }

    public function editProject(string $slug, array $data): ArcanumProject
    {
        $this->validateSlug($slug, 'Project slug');
        $project = $this->request('PUT', "/project/{$slug}", [RequestOptions::JSON => $data]);
        return new ArcanumProject(
            $project['id'],
            $project['name'],
            $project['description'] ?? '',
            $project['slug'],
            new ArcanumUser($project['owner']['id'], $project['owner']['netId'], $project['owner']['name'], $project['owner']['authorities']),
            isset($project['secrets']) ? $this->processSecrets($project['secrets']) : []
        );
    }

    public function deleteProject(string $slug): array
    {
        $this->validateSlug($slug, 'Project slug');
        return $this->request('DELETE', "/project/{$slug}");
    }

    public function modifyProjectSecret(string $projectSlug, string $secretId, bool $add = true): array
    {
        $method = $add ? 'PUT' : 'DELETE';
        return $this->request($method, "/project/{$projectSlug}/secret/{$secretId}");
    }

    public function addProjectSecret(string $projectSlug, string $secretId): array
    {
        return $this->modifyProjectSecret($projectSlug, $secretId, true);
    }

    public function removeProjectSecret(string $projectSlug, string $secretId): array
    {
        return $this->modifyProjectSecret($projectSlug, $secretId, false);
    }

    /**
     * Grant a user access to a project with a specific role.
     *
     * @param string $slug The project slug.
     * @param string $netId The NetID of the user to grant access to.
     * @param string $role The role to grant ('viewer', 'editor', or 'admin').
     * @return array API response.
     * @throws \InvalidArgumentException When slug, netId is invalid or role is not allowed.
     * @throws NotFoundException When the project or user is not found.
     * @throws UnauthorizedException When authentication fails or access is denied.
     * @throws ApiException On API or network errors.
     */
    public function grantProjectAccess(string $slug, string $netId, string $role): array
    {
        $this->validateSlug($slug, 'Project slug');
        $this->validateNetId($netId);
        $allowedRoles = ['viewer', 'editor', 'admin'];
        if (!in_array(strtolower($role), $allowedRoles, true)) {
            throw new \InvalidArgumentException("Role must be one of: " . implode(', ', $allowedRoles));
        }
        return $this->request('PUT', "/project/{$slug}/" . strtolower($role) . "/{$netId}");
    }

    public function grantProjectViewAccess(string $slug, string $netId): array
    {
        return $this->grantProjectAccess($slug, $netId, 'viewer');
    }

    public function grantProjectEditorAccess(string $slug, string $netId): array
    {
        return $this->grantProjectAccess($slug, $netId, 'editor');
    }

    public function grantProjectAdminAccess(string $slug, string $netId): array
    {
        return $this->grantProjectAccess($slug, $netId, 'admin');
    }

    public function revokeProjectAccess(string $slug, string $netId): array
    {
        $this->validateSlug($slug, 'Project slug');
        $this->validateNetId($netId);
        return $this->request('DELETE', "/project/{$slug}/revoke/{$netId}");
    }

    public function getProjectAuthorities(string $slug): array
    {
        return $this->request('GET', "/project/{$slug}/authorities");
    }

    public function processBulkProjectAccess(string $slug, array $data): array
    {
        return $this->request('POST', "/project/{$slug}/authorities/bulk", [RequestOptions::JSON => $data]);
    }


    // --- Tokens Endpoints ---

    /**
     * @return ArcanumToken[]
     */
    public function listTokens(): array
    {
        $tokens = $this->request('GET', '/token/list');
        $tokenArray = [];
        foreach ($tokens as $token) {
            $tokenArray[] = new ArcanumToken(
                $token['principal'],
                $token['apiKey'] ?? null,
                null,
                $token['userToken'],
                new ArcanumUser($token['owner']['id'], $token['owner']['netId'], $token['owner']['name'], $token['owner']['authorities']),
                $token['expiry'],
                $token['authorities']
            );
        }
        return $tokenArray;
    }

    public function getTokenInfo(): ArcanumSelfInfo
    {
        $info = $this->request('GET', '/token/me');
        return new ArcanumSelfInfo(
            $info['principal'],
            $info['encryptedSecret'] ?? '',
            $info['userToken'],
            new ArcanumUser($info['owner']['id'], $info['owner']['netId'], $info['owner']['name'], $info['owner']['authorities']),
            $info['expiry'],
            $info['authorities']
        );
    }

    public function createToken(array $data): ArcanumToken
    {
        if (empty($data['principal'])) {
            throw new \InvalidArgumentException('Token principal is required.');
        }
        $token = $this->request('POST', '/token/create', [RequestOptions::JSON => $data]);
        return new ArcanumToken(
            $token['principal'],
            $token['apiKey'] ?? null,
            $token['apiSecret'] ?? null,
            $token['userToken'],
            new ArcanumUser($token['owner']['id'], $token['owner']['netId'], $token['owner']['name'], $token['owner']['authorities']),
            $token['expiry'],
            $token['authorities']
        );
    }

    public function revokeToken(array $data): array
    {
        if (empty($data['principal'])) {
            throw new \InvalidArgumentException('Token principal is required.');
        }
        return $this->request('POST', '/token/revoke', [RequestOptions::JSON => $data]);
    }

    private function processSecrets(array $secrets): array
    {
        $secret_array = [];
        foreach ($secrets as $secret) {
            $secret_array[] = new ArcanumEncryptedSecret(
                $secret['id'],
                $secret['name'],
                $secret['azureId'],
                $secret['fields'],
                new ArcanumVault($secret['vault']['id'], $secret['vault']['name'], $secret['vault']['description']),
                new ArcanumUser($secret['owner']['id'], $secret['owner']['netId'], $secret['owner']['name'], $secret['owner']['authorities'])
            );
        }
        return $secret_array;
    }

    // --- Secrets Endpoints ---

    /**
     * List all encrypted secrets that are accessible to the current API key.
     *
     * @return ArcanumEncryptedSecret[] List of ArcanumEncryptedSecret objects.
     * @throws UnauthorizedException When authentication fails or access is denied.
     * @throws ApiException On API or network errors.
     */
    public function listSecrets(): array
    {
        $secrets = $this->request('GET', '/secret/list');
        return $this->processSecrets($secrets);
    }

    /**
     * Create a new secret.
     *
     * @param array $data Secret data including name, vault, fields, etc.
     * @return array API response with the created secret details.
     * @throws ValidationException When the secret data is invalid.
     * @throws UnauthorizedException When authentication fails or access is denied.
     * @throws ApiException On API or network errors.
     */
    public function createSecret(array $data): array
    {
        return $this->request('POST', '/secret/new', [RequestOptions::JSON => $data]);
    }

    /**
     * Get a decrypted secret by its ID.
     *
     * @param string|int $id The secret ID.
     * @return ArcanumDecryptedSecret|null The decrypted secret object, or null if not found.
     * @throws NotFoundException When the secret is not found.
     * @throws UnauthorizedException When authentication fails or access is denied.
     * @throws ApiException On API or network errors.
     */
    public function getSecret(string|int $id): ?ArcanumDecryptedSecret
    {
        $id = (string) $id;
        $secret = $this->request('GET', "/secret/{$id}");
        if (empty($secret)) {
            return null;
        }
        $fields = [];
        foreach ($secret['fields'] as $field) {
            $fields[] = new ArcanumSecretField(
                $field['name'],
                $field['slug'],
                $field['value']
            );
        }
        return new ArcanumDecryptedSecret(
            $secret['name'],
            $secret['slug'],
            $secret['description'],
            $secret['vault'],
            $fields
        );
    }

    public function getSecretField(string $secretId, string $fieldSlug): array
    {
        return $this->request('GET', "/secret/{$secretId}/field/{$fieldSlug}");
    }

    public function updateSecret(string $id, array $data): array
    {
        return $this->request('PUT', "/secret/{$id}", [RequestOptions::JSON => $data]);
    }

    public function deleteSecret(string $id): array
    {
        return $this->request('DELETE', "/secret/{$id}");
    }

    public function grantSecretAccess(string $id, string $netId, string $role): array
    {
        $this->validateId($id, 'Secret ID');
        $this->validateNetId($netId);
        $allowedRoles = ['viewer', 'editor', 'admin'];
        if (!in_array(strtolower($role), $allowedRoles, true)) {
            throw new \InvalidArgumentException("Role must be one of: " . implode(', ', $allowedRoles));
        }
        return $this->request('PUT', "/secret/{$id}/" . strtolower($role) . "/{$netId}");
    }

    public function grantSecretViewAccess(string $id, string $netId): array
    {
        return $this->grantSecretAccess($id, $netId, 'viewer');
    }

    public function grantSecretEditorAccess(string $id, string $netId): array
    {
        return $this->grantSecretAccess($id, $netId, 'editor');
    }

    public function grantSecretAdminAccess(string $id, string $netId): array
    {
        return $this->grantSecretAccess($id, $netId, 'admin');
    }

    public function revokeSecretAccess(string $id, string $netId): array
    {
        $this->validateId($id, 'Secret ID');
        $this->validateNetId($netId);
        return $this->request('DELETE', "/secret/{$id}/revoke/{$netId}");
    }

    public function getSecretAuthorities(string $id): array
    {
        return $this->request('GET', "/secret/{$id}/authorities");
    }

    public function processBulkSecretAccess(string $id, array $data): array
    {
        return $this->request('POST', "/secret/{$id}/authorities/bulk", [RequestOptions::JSON => $data]);
    }

    // --- Users Endpoints ---

    public function getSelfUserInfo(): ArcanumUser
    {
        $user = $this->request('GET', '/user/me');
        return new ArcanumUser(
            $user['id'],
            $user['netId'],
            $user['name'],
            $user['authorities']
        );
    }

    /**
     * @return ArcanumUser[]
     */
    public function listUsersForSharing(): array
    {
        $users = $this->request('GET', '/user/list');
        $userArray = [];
        foreach ($users as $user) {
            $userArray[] = new ArcanumUser(
                $user['id'],
                $user['netId'],
                $user['name'],
                $user['authorities']
            );
        }
        return $userArray;
    }

    /**
     * @return ArcanumUser[]
     */
    public function listUsersForAdmin(): array
    {
        $users = $this->request('GET', '/user/list/admin');
        $userArray = [];
        foreach ($users as $user) {
            $userArray[] = new ArcanumUser(
                $user['id'],
                $user['netId'],
                $user['name'],
                $user['authorities']
            );
        }
        return $userArray;
    }

    public function grantUserAuthority(string $netId, array $data): array
    {
        $this->validateNetId($netId);
        if (empty($data['authority'])) {
            throw new \InvalidArgumentException('Authority is required.');
        }
        return $this->request('PUT', "/user/{$netId}/authority", [RequestOptions::JSON => $data]);
    }

    public function revokeUserAuthority(string $netId, array $data): array
    {
        $this->validateNetId($netId);
        if (empty($data['authority'])) {
            throw new \InvalidArgumentException('Authority is required.');
        }
        return $this->request('DELETE', "/user/{$netId}/authority", [RequestOptions::JSON => $data]);
    }

    // --- Convenience Methods ---

    /**
     * Get an encrypted secret by its name, optionally filtered by vault.
     *
     * @param string $name The secret name.
     * @param string|null $vaultName Optional vault name to filter by.
     * @return ArcanumEncryptedSecret|null The encrypted secret object, or null if not found.
     * @throws UnauthorizedException When authentication fails or access is denied.
     * @throws ApiException On API or network errors.
     */
    public function getSecretByName(string $name, ?string $vaultName = null): ?ArcanumEncryptedSecret
    {
        $secrets = $this->listSecrets();
        foreach ($secrets as $secret) {
            if ($secret->name === $name) {
                if ($vaultName === null || $secret->vault->name === $vaultName) {
                    return $secret;
                }
            }
        }
        return null;
    }

    /**
     * Get all secrets in a specific vault.
     *
     * @param string $vaultName The vault name.
     * @return ArcanumEncryptedSecret[] Array of encrypted secrets in the vault.
     * @throws UnauthorizedException When authentication fails or access is denied.
     * @throws ApiException On API or network errors.
     */
    public function getSecretsByVault(string $vaultName): array
    {
        $secrets = $this->listSecrets();
        return array_values(array_filter($secrets, fn($secret) => $secret->vault->name === $vaultName));
    }

    /**
     * Search for secrets by name (case-insensitive partial match).
     *
     * @param string $searchTerm The search term to match against secret names.
     * @return ArcanumEncryptedSecret[] Array of matching encrypted secrets.
     * @throws UnauthorizedException When authentication fails or access is denied.
     * @throws ApiException On API or network errors.
     */
    public function findSecretsByName(string $searchTerm): array
    {
        $secrets = $this->listSecrets();
        return array_values(array_filter($secrets, fn($secret) =>
            stripos($secret->name, $searchTerm) !== false
        ));
    }

    /**
     * Get a project by its name.
     *
     * @param string $name The project name.
     * @return ArcanumProject|null The project object, or null if not found.
     * @throws UnauthorizedException When authentication fails or access is denied.
     * @throws ApiException On API or network errors.
     */
    public function getProjectByName(string $name): ?ArcanumProject
    {
        $projects = $this->listProjects();
        if ($projects === null) {
            return null;
        }
        foreach ($projects as $project) {
            if ($project->name === $name) {
                return $project;
            }
        }
        return null;
    }

    /**
     * Search for projects by name (case-insensitive partial match).
     *
     * @param string $searchTerm The search term to match against project names.
     * @return ArcanumProject[]|null Array of matching projects, or null if no projects available.
     * @throws UnauthorizedException When authentication fails or access is denied.
     * @throws ApiException On API or network errors.
     */
    public function findProjectsByName(string $searchTerm): ?array
    {
        $projects = $this->listProjects();
        if ($projects === null) {
            return null;
        }
        return array_values(array_filter($projects, fn($project) =>
            stripos($project->name, $searchTerm) !== false
        ));
    }

    /**
     * Get all projects owned by a specific user.
     *
     * @param string $netId The NetID of the user.
     * @return ArcanumProject[]|null Array of projects owned by the user, or null if no projects available.
     * @throws UnauthorizedException When authentication fails or access is denied.
     * @throws ApiException On API or network errors.
     */
    public function getProjectsByOwner(string $netId): ?array
    {
        $projects = $this->listProjects();
        if ($projects === null) {
            return null;
        }
        return array_values(array_filter($projects, fn($project) =>
            $project->owner->netId === $netId
        ));
    }

    /**
     * Get a vault by its name.
     *
     * @param string $name The vault name.
     * @return ArcanumVault|null The vault object, or null if not found.
     * @throws UnauthorizedException When authentication fails or access is denied.
     * @throws ApiException On API or network errors.
     */
    public function getVaultByName(string $name): ?ArcanumVault
    {
        $vaults = $this->listVaults();
        foreach ($vaults as $vault) {
            if ($vault->name === $name) {
                return $vault;
            }
        }
        return null;
    }

    /**
     * Get a decrypted secret by its name, optionally filtered by vault.
     * This is a convenience method that combines getSecretByName() and getSecret().
     *
     * @param string $name The secret name.
     * @param string|null $vaultName Optional vault name to filter by.
     * @return ArcanumDecryptedSecret|null The decrypted secret object, or null if not found.
     * @throws NotFoundException When the secret is not found.
     * @throws UnauthorizedException When authentication fails or access is denied.
     * @throws ApiException On API or network errors.
     */
    public function getDecryptedSecretByName(string $name, ?string $vaultName = null): ?ArcanumDecryptedSecret
    {
        $encryptedSecret = $this->getSecretByName($name, $vaultName);
        if ($encryptedSecret === null) {
            return null;
        }
        return $this->getSecret($encryptedSecret->id);
    }
}
