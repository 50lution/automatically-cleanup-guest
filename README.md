# Shopware 6 - Automatically clean up guest accounts

This plugin supports automatically deleting guest accounts that are no longer in use to limit too many duplicate emails in the shop. Developed by inspiration from https://forum.shopware.com/t/gastkonten-automatisiert-loeschen/94778.

## Information
- It's automatically deleting guest accounts by a scheduleTask/cronjob, this cronjob is executed every hour.
- The plugin is only deleting guest accounts that no longer in use based on `core.loginRegistration.unusedGuestCustomerLifetime`
- The guest accounts can be deleted after they clicked on the close session guest on the frontend.
- Deleting guest accounts does not remove the associated orders. The orders are still available in the administration.

## Installation
- Download the plugin from the [GitHub releases] or clone this repository.
- Upload the zip file in the Shopware administration under Settings > System > Plugins > Upload plugin.
- Install and activate the plugin.

## Usage
If you want to delete guest accounts by yourself, you can use the following command:
```bash
./bin/console s50lution:clean-up-guest
```
