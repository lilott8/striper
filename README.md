Drupal-based Stripe integration module

## Configuration

 1) Create the following keys in the `keys` module
    1) stripe_test_publishable
    2) stripe_test_secret
    3) stripe_live_publishable
    4) stripe_live_secret
    
 2) Populate the respective keys with their values from stripe
 3) Sync your plans
 4) Build a content type that uses the `striper plan` referencing a `striper` and limited to 1
 