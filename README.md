# Test CPP gateway for local development

This module provides a **fake** gateway used to help with acceptance testing of the client to the gateway.

Use this module if you are enhancing or updating the [nswdpc/silverstripe-nsw-customerpaymentsplatform module](https://github.com/nswdpc/silverstripe-nsw-customerpaymentsplatform)

The gateway form itself consists of a 'payment reference' field and a payment button.

## Features

+ Fake, configurable controller
+ Get an Oauth2 access token for payment and refund requests
+ Request payment endpoint
+ Sign the 'payment' payload and return a JWT
+ Gateway 'payment' form
+ Payment completion POSTback to the configured paymentendpoint url provided by `silverstripe/silverstripe-omnipay`
+ Refund endpoint
+ Immediate failure testing (422)
+ Fail-with-retry testing (50x)

## Requirements

+ `nswdpc/silverstripe-nsw-customerpaymentsplatform` module

### Installation

The only supported method of installing this module and its requirements is via `composer` as part of a Silverstripe install.

```shell
composer require --dev nswdpc/silverstripe-nsw-customerpaymentsplatform-fakegateway
```

## License

This module is made available as an Open Source project under the [BSD-3-Clause](./LICENSE.md) license.

## Configuration

[Configuration documentation is available](./docs/en/001_index.md)

## Maintainers

+ [dpcdigital@NSWDPC:~$](https://dpc.nsw.gov.au)


## Bugtracker

We welcome bug reports, pull requests and feature requests on the Github Issue tracker for this project.

Please review the [code of conduct](./code-of-conduct.md) prior to opening a new issue.

## Security

If you have found a security issue with this module, please email digital[@]dpc.nsw.gov.au in the first instance, detailing your findings.

## Development and contribution

If you would like to make contributions to the module please ensure you raise a pull request and discuss with the module maintainers.

Please review the [code of conduct](./code-of-conduct.md) prior to completing a pull request.
