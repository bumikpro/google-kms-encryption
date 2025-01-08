
# Google KMS Encryption Service for Drupal 10

This module provides a service for integrating Google Cloud KMS (Key Management Service) with Drupal. It allows for secure encryption and decryption of sensitive data using Google's managed KMS infrastructure.

---

## Features
- **Encrypt sensitive data** (user IDs, tokens, or JSON payloads) via Google Cloud KMS.
- **Decrypt data** securely, ensuring confidentiality.
- **Seamless Drupal Integration** – Uses the Drupal `key` module to manage KMS credentials and configuration.
- **Centralized Error Logging** – Logs encryption and decryption errors to Drupal logs.

---

## Requirements
- Drupal 10.x
- PHP 8.3+
- Google Cloud KMS SDK
- `key` module (Drupal)
- Google Cloud IAM permissions for KMS operations.

---

## Installation

### 1. Install the Google Cloud KMS SDK via Composer:
```bash
composer require "google/cloud-kms:^1.14"
```

### 2. Register the Service in `services.yml`:
```yaml
services:
  module_name.kms_service:
    class: Drupal\module_name\Service\GoogleKmsService
    arguments: ['@key.repository']
```

---

## Configuration

### 1. Create Google KMS Keys:
- Log into Google Cloud.
- Create a KeyRing and CryptoKey in Google KMS.
- Grant your service account `roles/cloudkms.cryptoKeyEncrypterDecrypter` permissions.

### 2. Store KMS Credentials in Drupal Key Module:
- Go to **Configuration** > **System** > **Keys**.
- Add a new key:
  - **Key Type**: **File**
  - **Key File Path**: Put your path leading to credentials json file (pref outside code root in secured folder).
  - **Label**: `google_kms_credentials`

### 3. Add KMS Resource Name to Drupal:
- Add a second key:
  - **Key Type**: **Configuration**
  - **Key Value**:
    ```json
    {
      "project_id": "your-project-id",
      "location": "global",
      "key_ring": "my-key-ring",
      "key_name": "my-key"
    }
    ```
  - **Label**: `google_kms_keyring`

---

## Usage

### Encrypting Tokens:
```php
/** @var \Drupal\module_name\Service\GoogleKmsServiceInterface $kmsService */
$kmsService = \Drupal::service('module_name.google_kms_service');
$encrypted = $kmsService->encryptToken(12345);

if ($encrypted) {
  // Handle encrypted token.
}
```

### Encrypting User Data:
```php
$data = [
  'name' => 'John Doe',
  'email' => 'john.doe@example.com',
];
$encrypted = $kmsService->encryptUserData($data);
```

### Decrypting Data:
```php
$decrypted = $kmsService->decryptToken($encrypted);
```

---

## Error Handling
- All encryption and decryption errors are automatically logged to Drupal's watchdog logs:
```bash
drush ws
```

---

## Testing
You can test the encryption service by creating a simple controller or Drush command that invokes the `GoogleKmsService` methods or Unit/Functional Tests.

---

## Security Considerations
- Always restrict KMS access to the minimal set of permissions required for encryption and decryption.
- Rotate KMS keys periodically to enhance security.
- Audit logs regularly to detect unusual activities.

---

## License
This project is licensed under the MIT License.

---

## Contributing
Feel free to submit issues or open pull requests for improvements.

---

## Author
Nicolae Procopan
Senior Drupal Developer
thebumik@gmail.com
