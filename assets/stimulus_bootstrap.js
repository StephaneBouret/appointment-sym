import { startStimulusApp } from '@symfony/stimulus-bundle';
import ThemeController from './controllers/theme_controller.js';
import ResetPassword from './controllers/reset_password_controller.js';
import DeleteAccountController from './controllers/delete_account_controller.js';
import AddressAutocompleteController from './controllers/address_autocomplete_controller.js';
import RegisterController from './controllers/register_controller.js';
import AppointmentTypeController from './controllers/appointment_type_controller.js';
import AjaxFormController from './controllers/ajax_form_controller.js';
import CalendarController from './controllers/calendar_controller.js';
import NamesFormatter from './controllers/names_formatter_controller.js';
import AppointmentCheckoutController from './controllers/appointment_checkout_controller.js';
import AppointmentCancelController from './controllers/appointment-cancel_controller.js';
import AppointmentRescheduleController from './controllers/appointment_reschedule_controller.js';

const app = startStimulusApp();
// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);
app.register('theme', ThemeController);
app.register('reset-password', ResetPassword);
app.register('delete-account', DeleteAccountController);
app.register('address-autocomplete', AddressAutocompleteController);
app.register('register', RegisterController);
app.register('appointment-type', AppointmentTypeController);
app.register('ajax-form', AjaxFormController);
app.register('calendar', CalendarController);
app.register('names-formatter', NamesFormatter);
app.register("appointment-checkout", AppointmentCheckoutController);
app.register('appointment-cancel', AppointmentCancelController);
app.register('appointment-reschedule', AppointmentRescheduleController);
