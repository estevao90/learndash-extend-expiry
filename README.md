# learndash-extend-expiry

Allow user purchase a course extension for a LearnDash course with a fixed expiry date.

[![Integration](https://github.com/estevao90/learndash-extend-expiry/workflows/Integration/badge.svg)](https://github.com/estevao90/learndash-extend-expiry/actions?query=workflow%3AIntegration)

## Development

```sh
# install dependencies
composer install
npm install
```

### PayPal IPN test

Use [localtunnel](https://github.com/localtunnel/localtunnel) to test PayPal IPN.

```sh
# install localtunnel
npm install -g localtunnel

# start tunnel to port 80
# put the generated url in the LD PayPal settings configurations in the "PayPal Notify URL" options
# example: https://swift-eagle-45.loca.lt/sfwd-lms/paypal
lt -p 80 --print-requests true
```

### Helpful Commands

```sh
# PHP WordPress lint
composer php:lint

# fix PHP fixable errors
composer php:fix

# same to css e js
composer js:lint
composer js:fix
composer css:lint
composer css:fix

# run all lints
composer lint

# run all fixes
composer fix
```
