# Ifthenpay-CreditCard-Payment-Processor
This Joomla plugin integrates Ifthenpay's credit card payment system with Guru LMS, allowing for seamless credit card transactions directly within the LMS platform. The plugin supports multilingual functionality and includes features such as a configurable error page URL and a callback system for successful payments.

## Features

- **Custom Payment Processor:** Integrated with Ifthenpay for credit card payments.
- **Loader Display:** Informs users about redirection to the payment page.
- **Error Handling:** Configurable error page URL in plugin settings.
- **Multilingual Support:** Available in English and Portuguese.
- **Callback Handling:** Updates order status to "Paid" upon successful payment.

## Installation

1. Download the plugin package from the GitHub repository.
2. Log in to your Joomla admin panel.
3. Go to `Extensions` > `Manage` > `Install`.
4. Upload the plugin package and click `Upload & Install`.
5. Navigate to `Extensions` > `Plugins` and find the "Credit Card Payment Processor" plugin.
6. Enable the plugin and configure the settings.

## Configuration

1. **Error Page URL:** Set the URL where users are redirected in case of payment failure.
2. **Callback URL:** Configure the URL for Ifthenpay to send payment notifications. Use the following format:

## Usage

1. **Checkout Process:** Users can select the credit card payment option during checkout within Guru LMS. A loader will indicate redirection to the Ifthenpay payment page.
2. **Error Handling:** If payment fails, users will be redirected to the configured error page.
3. **Successful Payment:** Upon successful payment, Ifthenpay will trigger a callback to update the order status to "Paid" and redirect users to the order page with a "Payment Successful" message.

## API Documentation

The plugin uses Ifthenpay's credit card API to initiate payments: https://helpdesk.ifthenpay.com/en/support/solutions/articles/79000123125-api-credit-card

## Testing
For testing, use the Ifthenpay sandbox environment and request a test CCARD_KEY from Ifthenpay. Trigger real payment tests to ensure that the callback and all functionalities work as expected.

## License
This plugin is licensed under the MIT License.
