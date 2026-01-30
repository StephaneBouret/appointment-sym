import { startStimulusApp } from '@symfony/stimulus-bundle';
import ThemeController from './controllers/theme_controller.js';
import ResetPassword from './controllers/reset_password_controller.js';
import DeleteAccountController from './controllers/delete_account_controller.js';
import AddressAutocompleteController from './controllers/address_autocomplete_controller.js';
import RegisterController from './controllers/register_controller.js';

const app = startStimulusApp();
// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);
app.register('theme', ThemeController);
app.register('reset-password', ResetPassword);
app.register('delete-account', DeleteAccountController);
app.register('address-autocomplete', AddressAutocompleteController);
app.register('register', RegisterController);
