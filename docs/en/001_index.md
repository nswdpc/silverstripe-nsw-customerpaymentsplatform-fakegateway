# Documentation

This module should be used for acceptance testing of the CPP gateway client developer by NSW DPC Digital. It does not store or handle payment details.

You should not install this module in a production environment.

## Configuration

You are required to add the following configuration values to your local project configuration:

```yaml
---
Name: nswdpc-cpp-fakegatewayconfiguration-local
After:
  - '#nswdpc-cpp-fakegatewayconfiguration'
envvarset: 'USE_FAKE_GATEWAY'
---
NSWDPC\Payments\NSWGOVCPP\Agency\FakeGatewayController:
  enabled: true
  paymentCompletionUrl: 'http://<your host>/paymentendpoint/gateway/NSWGOVCPP/complete'
  jwtPrivateKey: |
    -----BEGIN RSA PRIVATE KEY-----
    A KEY VALUE
    -----END RSA PRIVATE KEY-----
SilverStripe\Omnipay\GatewayInfo:
  NSWGOVCPP:
    parameters:
      jwtPublicKey: |
        -----BEGIN PUBLIC KEY-----
        A KEY VALUE
        -----END PUBLIC KEY-----
      # get an access token
      accessTokenUrl: 'http://<your host>/fakecpp/v1/accesstoken'
      # request a payment
      requestPaymentUrl: 'http://<your host>/fakecpp/v1/requestpayment'
      # browser redirect to this URL
      gatewayUrl: 'http://<your host>/fakecpp/v1/gateway'
      # request a refund
      refundUrl: 'http://<your host>/fakecpp/v1/refund'
```

Choose a host name that you are using for development.

## JWT private/public key creation

To avoid embedding a key pair in version control/the module, you are required to create an RSA public/private key pair to test JWT encoding and decoding.

Store these values in the jwtPrivateKey and jwtPublicKey values in your project configuration as shown above, noting YAML multiline requirements.

## Docker

Depending on your setup, if you are using docker containers, using the container name could enable communications to be sent and received between the following URLs:

+ paymentCompletionUrl
+ accessTokenUrl
+ requestPaymentUrl
+ refundUrl
