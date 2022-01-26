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
# put the generated url in the LD PayPal settings configurations in the "PayPal Notify URL" option
# example: https://swift-eagle-45.loca.lt/sfwd-lms/paypal
lt -p 80 --print-requests true
```

### stripe-cli

Use [stripe-cli](https://stripe.com/docs/stripe-cli) to test the LearnDash buy option.

Cards to test can be found [here](https://stripe.com/docs/testing#cards).

```sh
# login into stripe account
stripe login

# listen and forward events to LearnDash
# add the output "webhook signing secret" in the LearnDash Stripe configuration in the "Test Endpoint Secret" option
stripe listen --forward-to http://localhost/?learndash-integration=stripe
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
