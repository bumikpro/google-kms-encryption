<?php

declare(strict_types=1);

namespace Drupal\module_name\Service;

use Drupal\key\KeyRepositoryInterface;
use Google\Cloud\Kms\V1\DecryptRequest;
use Google\Cloud\Kms\V1\EncryptRequest;
use Drupal\Core\Logger\LoggerChannelTrait;
use Google\Cloud\Kms\V1\Client\KeyManagementServiceClient;
use Google\Cloud\Core\Exception\ServiceException;

/**
 * Service to manage Google Cloud KMS encryption and decryption.
 */
class GoogleKmsService implements GoogleKmsServiceInterface {
  use LoggerChannelTrait;

  /**
   * Google Cloud KMS client for encryption and decryption.
   */
  protected ?KeyManagementServiceClient $kmsClient = NULL;

  /**
   * Key repository to manage secure storage of KMS configuration.
   */
  protected KeyRepositoryInterface $keyRepository;

  /**
   * Constructs the GoogleKmsService.
   *
   * @param \Drupal\key\KeyRepositoryInterface $key_repository
   *   The key repository to fetch KMS environment variables.
   */
  public function __construct(KeyRepositoryInterface $key_repository) {
    $this->keyRepository = $key_repository;
  }

  /**
   * Ensures the KMS client is initialized before use.
   */
  protected function ensureKmsClientInitialized(): void {
    if ($this->kmsClient === NULL) {
      $this->initializeKmsClient();
    }
  }

  /**
   * Initializes the KMS client dynamically using credentials from the Key module.
   */
  protected function initializeKmsClient(): void {
    try {
      $credentialsPath = $this->fetchCredentialsPath();
      $this->kmsClient = new KeyManagementServiceClient([
        'credentials' => $credentialsPath,
      ]);
    }
    catch (\Exception $e) {
      $this->logError('KMS Initialization', $e);
      $this->kmsClient = NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function encryptToken(string|int $data): ?string {
    $this->ensureKmsClientInitialized();

    if (!$this->kmsClient) {
      return $this->logAndReturnNull('Encryption', 'KMS client is not initialized.');
    }

    try {
      $timestamp = time();
      $random = random_int(100000, 999999);
      $app_secret = $this->getAppSecret();

      $hmac_token = hash_hmac('sha256', "{$data}|{$timestamp}|{$random}", $app_secret);
      $kmsName = $this->getKmsResourceName();

      $encryptRequest = (new EncryptRequest())
        ->setName($kmsName)
        ->setPlaintext($hmac_token);

      $response = $this->kmsClient->encrypt($encryptRequest);
      return base64_encode($response->getCiphertext());
    }
    catch (\Exception $e) {
      return $this->logAndReturnNull('Encryption', $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function encryptUserData(string|array $data): ?string {
    $this->ensureKmsClientInitialized();

    if (!$this->kmsClient) {
      return $this->logAndReturnNull('Encryption', 'KMS client is not initialized.');
    }

    try {
      $plaintext = is_array($data)
        ? json_encode($data, JSON_THROW_ON_ERROR)
        : $data;

      $kmsName = $this->getKmsResourceName();

      $encryptRequest = (new EncryptRequest())
        ->setName($kmsName)
        ->setPlaintext($plaintext);

      $response = $this->kmsClient->encrypt($encryptRequest);
      return base64_encode($response->getCiphertext());
    }
    catch (\Exception $e) {
      return $this->logAndReturnNull('Encryption', $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function decryptToken(string $ciphertext): ?string {
    $this->ensureKmsClientInitialized();

    if (!$this->kmsClient) {
      return $this->logAndReturnNull('Decryption', 'KMS client is not initialized.');
    }

    try {
      $kmsName = $this->getKmsResourceName();
      $decodedCiphertext = base64_decode($ciphertext, true);

      $decryptRequest = (new DecryptRequest())
        ->setName($kmsName)
        ->setCiphertext($decodedCiphertext);

      $response = $this->kmsClient->decrypt($decryptRequest);
      return $response->getPlaintext();
    }
    catch (\Exception $e) {
      return $this->logAndReturnNull('Decryption', $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function logAndReturnNull(string $operation, string|\Exception $messageOrException): ?string {
    $message = $messageOrException instanceof \Exception
      ? $messageOrException->getMessage()
      : $messageOrException;

    $this->getLogger('module_name')->error(
      '@operation failed: @message',
      [
        '@operation' => $operation,
        '@message' => $message,
      ]
    );
    return NULL;
  }

  /**
   * Fetches the path to the service account JSON from the Key module.
   */
  private function fetchCredentialsPath(): ?string {
    return $this->keyRepository
      ->getKey('google_kms_credentials')
      ?->getKeyValue();
  }

  /**
   * Retrieves the Google KMS resource path.
   */
  private function getKmsResourceName(): string {
    $key = $this->keyRepository->getKey('google_kms_keyring');
    $kmsConfig = json_decode(
      $key->getKeyValue(),
      true,
      512,
      JSON_THROW_ON_ERROR
    );

    return sprintf(
      'projects/%s/locations/%s/keyRings/%s/cryptoKeys/%s',
      $kmsConfig['project_id'],
      $kmsConfig['location'],
      $kmsConfig['key_ring'],
      $kmsConfig['key_name']
    );
  }

  /**
   * Retrieves the app secret for HMAC token generation.
   */
  private function getAppSecret(): string {
    return $this->keyRepository
      ->getKey('google_kms_app_secret')
      ->getKeyValue();
  }
}
