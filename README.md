# PAY. Shopware plugin
---
- [Summary](#summary)
- [Quickstart](#quickstart)
- [Setup](#setup)
---
### Summary
This PAY. plugin provides all desired payment methods for your Shopware 5 webshop. Please refer to https://www.pay.nl (Dutch) for an overview of all features and services.
 
##### Available payment methods:
Bank Payments  | Creditcards | Gift cards & Vouchers | Pay by invoice | Others | 
:-----------: | :-----------: | :-----------: | :-----------: | :-----------: |
iDEAL + QR |Visa | VVV Cadeaukaart | AfterPay | PayPal |
Bancontact + QR |  Mastercard | Webshop Giftcard | Billink | WeChatPay | 
Giropay |American Express | FashionCheque |Focum AchterafBetalen.nl | AmazonPay |
MyBank | Carte Bancaire | Podium Cadeaukaart | Capayable Achteraf Betalen | Cashly | 
SOFORT | PostePay | Gezondheidsbon | Capayable Gespreid betalen | Pay Fixed Price (phone) |
Maestro | Dankort | Fashion Giftcard | Klarna | Instore Payments (POS) |
Bank Transfer | Cartasi | GivaCard | SprayPay | Przelewy24 | 
| Tikkie | | YourGift | Creditclick | | 
| | | Paysafecard |
### Requirements
- PHP 5.6 or higher
- Shopware version 5.4.0 or higher
- Tested up to Shopware version 5.6.6

### Quickstart
##### Installing
Download the latest .zip release and upload into *Configuration* > *Plugin Manager* > *Installed* > *Upload plugin*
##### Setup
1. Log into the Shopware admin
2. Go to *Configuration* > *Plugin Manager* > *Installed*
3. Scroll down or search for PAY.
4. Click Open
5. Enter the Token code, API token and serviceID (these can be found in the Pay.nl Admin Panel --> https://admin.pay.nl/programs/programs
6. Save the settings
7. Go to *Configuration* > *Payment methods*
8. Click on a payment method and click Active to make the payment method active or not.
9. Save the settings
Go to the *Manage* > *Services* tab in the Pay.nl Admin Panel to enable extra payment methods
