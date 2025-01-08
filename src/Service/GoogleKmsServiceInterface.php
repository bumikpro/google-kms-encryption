<?php

declare(strict_types=1);

namespace Drupal\module_name\Service;

/**
 * Interface for Google KMS encryption and decryption services.
 */
interface GoogleKmsServiceInterface {

  /**
   * Encrypts a user ID or string metadata.
   *
   * @param string|int $data
   *   The data to encrypt.
   *
   * @return string|null
   *   Encrypted token or NULL on failure.
   */
  public function encryptToken(string|int $data): ?string;

  /**
   * Encrypts sensitive user data or a token.
   *
   * @param string|array $data
   *   The data to encrypt (string or array).
   *
   * @return string|null
   *   Encrypted token or NULL on failure.
   */
  public function encryptUserData(string|array $data): ?string;

  /**
   * Decrypts an encrypted token using Google KMS.
   *
   * @param string $ciphertext
   *   The encrypted token (base64 encoded).
   *
   * @return string|null
   *   Decrypted plaintext token or null on failure.
   */
  public function decryptToken(string $ciphertext): ?string;

  /**
   * Logs an error and returns NULL.
   *
   * @param string $operation
   *   The operation being performed.
   * @param string|\Exception $messageOrException
   *   The error message or caught exception.
   *
   * @return null
   *   Always returns NULL.
   */
  public function logAndReturnNull(string $operation, string|\Exception $messageOrException): ?string;
}
