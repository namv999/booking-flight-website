# Project Structure

```
├── app
│   ├── Http
│   │   ├── Controllers
│   │   │   ├── Admin
│   │   │   │   ├── AircraftController.php
│   │   │   │   ├── AirlineController.php
│   │   │   │   ├── AirportController.php
│   │   │   │   ├── BaggageAddonController.php
│   │   │   │   ├── BookingController.php
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── FareClassController.php
│   │   │   │   ├── FlightController.php
│   │   │   │   └── PassportUpdateRequestController.php
│   │   │   ├── Auth
│   │   │   │   ├── AuthenticatedSessionController.php
│   │   │   │   ├── ConfirmablePasswordController.php
│   │   │   │   ├── EmailVerificationNotificationController.php
│   │   │   │   ├── EmailVerificationPromptController.php
│   │   │   │   ├── NewPasswordController.php
│   │   │   │   ├── PasswordController.php
│   │   │   │   ├── PasswordResetLinkController.php
│   │   │   │   ├── RegisteredUserController.php
│   │   │   │   └── VerifyEmailController.php
│   │   │   ├── BookingController.php
│   │   │   ├── BookingHistoryController.php
│   │   │   ├── BookingPassportUpdateController.php
│   │   │   ├── Controller.php
│   │   │   ├── FlightSearchController.php
│   │   │   ├── HomeController.php
│   │   │   ├── NewsletterController.php
│   │   │   ├── PaymentController.php
│   │   │   ├── ProfileController.php
│   │   │   ├── SavedPassengerController.php
│   │   │   ├── SeatSelectionController.php
│   │   │   └── UserDashboardController.php
│   │   ├── Middleware
│   │   │   └── EnsureUserIsAdmin.php
│   │   └── Requests
│   │       ├── Admin
│   │       │   ├── StoreAircraftRequest.php
│   │       │   ├── StoreAirlineRequest.php
│   │       │   ├── StoreAirportRequest.php
│   │       │   ├── StoreFareClassRequest.php
│   │       │   ├── StoreFlightRequest.php
│   │       │   ├── UpdateAircraftRequest.php
│   │       │   ├── UpdateAirlineRequest.php
│   │       │   ├── UpdateAirportRequest.php
│   │       │   ├── UpdateFareClassRequest.php
│   │       │   └── UpdateFlightRequest.php
│   │       ├── Auth
│   │       │   └── LoginRequest.php
│   │       ├── BaggageAddonRequest.php
│   │       ├── FlightFilterRequest.php
│   │       ├── FlightSearchRequest.php
│   │       ├── PassengerBookingRequest.php
│   │       ├── ProfileUpdateRequest.php
│   │       ├── SavedPassengerRequest.php
│   │       ├── SeatSelectionRequest.php
│   │       └── StorePassportUpdateRequest.php
│   ├── Mail
│   │   └── BookingPaidMail.php
│   ├── Models
│   │   ├── Aircraft.php
│   │   ├── Airline.php
│   │   ├── Airport.php
│   │   ├── BaggageAddon.php
│   │   ├── Booking.php
│   │   ├── BookingFlight.php
│   │   ├── FareClass.php
│   │   ├── Flight.php
│   │   ├── FlightSeat.php
│   │   ├── Passenger.php
│   │   ├── PassportUpdateRequest.php
│   │   ├── Payment.php
│   │   ├── SavedPassenger.php
│   │   ├── Seat.php
│   │   ├── Ticket.php
│   │   └── User.php
│   ├── Providers
│   │   └── AppServiceProvider.php
│   └── Services
│       ├── BookingCreationService.php
│       ├── BookingExpiryService.php
│       ├── FlightSearchService.php
│       ├── JourneyValidationService.php
│       ├── PassportRiskService.php
│       ├── PassportService.php
│       └── PassportUpdateService.php
├── bootstrap
│   ├── cache
│   │   ├── packages.php
│   │   └── services.php
│   ├── app.php
│   └── providers.php
├── config
│   ├── app.php
│   ├── auth.php
│   ├── booking.php
│   ├── cache.php
│   ├── countries.php
│   ├── database.php
│   ├── filesystems.php
│   ├── logging.php
│   ├── mail.php
│   ├── queue.php
│   ├── services.php
│   └── session.php
├── database
│   ├── factories
│   │   ├── AircraftFactory.php
│   │   ├── AirlineFactory.php
│   │   ├── AirportFactory.php
│   │   ├── BaggageAddonFactory.php
│   │   ├── BookingFactory.php
│   │   ├── BookingFlightFactory.php
│   │   ├── FareClassFactory.php
│   │   ├── FlightFactory.php
│   │   ├── FlightSeatFactory.php
│   │   ├── PassengerFactory.php
│   │   ├── PaymentFactory.php
│   │   ├── SavedPassengerFactory.php
│   │   ├── SeatFactory.php
│   │   ├── TicketFactory.php
│   │   └── UserFactory.php
│   ├── migrations
│   │   ├── 0001_01_01_000000_create_users_table.php
│   │   ├── 0001_01_01_000001_create_cache_table.php
│   │   ├── 0001_01_01_000002_create_jobs_table.php
│   │   ├── 2026_08_25_204541_create_airlines_table.php
│   │   ├── 2026_08_25_205240_create_airports_table.php
│   │   ├── 2026_08_25_205418_create_baggage_addons_table.php
│   │   ├── 2026_08_25_205513_create_fare_classes_table.php
│   │   ├── 2026_08_25_205540_create_aircrafts_table.php
│   │   ├── 2026_08_25_205600_create_seats_table.php
│   │   ├── 2026_08_25_205625_create_flights_table.php
│   │   ├── 2026_08_25_205648_create_flight_seats_table.php
│   │   ├── 2026_08_25_205705_create_bookings_table.php
│   │   ├── 2026_08_25_205727_create_booking_flights_table.php
│   │   ├── 2026_08_25_205753_create_passengers_table.php
│   │   ├── 2026_08_25_205823_create_saved_passengers_table.php
│   │   ├── 2026_08_25_205907_create_tickets_table.php
│   │   ├── 2026_08_25_205930_create_payments_table.php
│   │   ├── 2026_09_11_210731_add_soft_deletes_to_reference_tables.php
│   │   ├── 2026_09_11_211129_add_passport_fields_to_passengers_table.php
│   │   ├── 2026_09_11_211218_add_passport_fields_to_saved_passengers_table.php
│   │   ├── 2026_09_25_000000_create_password_reset_tokens_table.php
│   │   ├── 2026_10_13_105109_add_gender_and_issue_date_to_passenger_tables.php
│   │   └── 2026_10_18_100000_create_passport_update_requests_table.php
│   ├── seeders
│   │   ├── AircraftSeeder.php
│   │   ├── AirlineSeeder.php
│   │   ├── AirportSeeder.php
│   │   ├── BaggageAddonSeeder.php
│   │   ├── BookingFlightSeeder.php
│   │   ├── BookingSeeder.php
│   │   ├── BookingTotalSeeder.php
│   │   ├── DatabaseSeeder.php
│   │   ├── FareClassSeeder.php
│   │   ├── FlightSeatSeeder.php
│   │   ├── FlightSeeder.php
│   │   ├── MultiCityTestDataSeeder.php
│   │   ├── November30FlightSeeder.php
│   │   ├── October30FlightSeeder.php
│   │   ├── PassengerSeeder.php
│   │   ├── PaymentSeeder.php
│   │   ├── RoundTripTestDataSeeder.php
│   │   ├── SavedPassengerSeeder.php
│   │   ├── SeatSeeder.php
│   │   ├── TestSmallAircraftSeeder.php
│   │   ├── TicketSeeder.php
│   │   └── UserSeeder.php
│   └── database.sqlite
├── docs
│   ├── FINAL-PRESENTATION.md
│   └── PROJECT-STRUCTURE.md
├── public
│   ├── build
│   │   ├── assets
│   │   │   ├── account-xbHkVcVS.css
│   │   │   ├── admin-TTmtFQR2.css
│   │   │   ├── app-BY6qR0jq.js
│   │   │   ├── app-DwNoTy4G.css
│   │   │   ├── auth-CuQJTfw6.js
│   │   │   ├── dashboard-Bl2Bg7Yj.js
│   │   │   ├── flights-DtJqJoGM.css
│   │   │   ├── flights-IshcALp3.js
│   │   │   ├── home-Di7b5A2n.js
│   │   │   ├── home-yvTRVDXG.css
│   │   │   ├── layout-BTHgjAgw.js
│   │   │   ├── passenger-autofill-gy4m5K2B.js
│   │   │   ├── payment-1xc_Gp3i.js
│   │   │   ├── seat-map-B-Z7B1aK.js
│   │   │   └── seat-map-CDAHDveK.css
│   │   └── manifest.json
│   ├── css
│   │   └── app.css
│   ├── images
│   │   ├── article-charter-flight.avif
│   │   ├── article-travel-guide.png
│   │   ├── destination-bangkok.jpg
│   │   ├── destination-da-nang.jpg
│   │   ├── destination-ha-noi.jpg
│   │   ├── destination-ho-chi-minh.jpg
│   │   ├── destination-tokyo.jpg
│   │   └── hero-banner.jpg
│   ├── favicon.ico
│   ├── index.php
│   ├── js
│   └── robots.txt
├── resources
│   ├── css
│   │   ├── account.css
│   │   ├── admin.css
│   │   ├── app.css
│   │   ├── base.css
│   │   ├── flights.css
│   │   ├── home.css
│   │   └── seat-map.css
│   ├── js
│   │   ├── app.js
│   │   ├── auth.js
│   │   ├── bootstrap.js
│   │   ├── dashboard.js
│   │   ├── flights.js
│   │   ├── home.js
│   │   ├── layout.js
│   │   ├── passenger-autofill.js
│   │   ├── payment.js
│   │   └── seat-map.js
│   └── views
│       ├── admin
│       │   ├── aircrafts
│       │   │   ├── create.blade.php
│       │   │   ├── edit.blade.php
│       │   │   ├── index.blade.php
│       │   │   └── show.blade.php
│       │   ├── airlines
│       │   │   ├── create.blade.php
│       │   │   ├── edit.blade.php
│       │   │   ├── index.blade.php
│       │   │   └── show.blade.php
│       │   ├── airports
│       │   │   ├── create.blade.php
│       │   │   ├── edit.blade.php
│       │   │   ├── index.blade.php
│       │   │   └── show.blade.php
│       │   ├── baggage-addons
│       │   │   ├── _form.blade.php
│       │   │   ├── create.blade.php
│       │   │   ├── edit.blade.php
│       │   │   └── index.blade.php
│       │   ├── bookings
│       │   │   ├── index.blade.php
│       │   │   └── show.blade.php
│       │   ├── fare-classes
│       │   │   ├── create.blade.php
│       │   │   ├── edit.blade.php
│       │   │   ├── index.blade.php
│       │   │   └── show.blade.php
│       │   ├── flights
│       │   │   ├── create.blade.php
│       │   │   ├── edit.blade.php
│       │   │   ├── index.blade.php
│       │   │   └── show.blade.php
│       │   └── dashboard.blade.php
│       ├── auth
│       │   ├── confirm-password.blade.php
│       │   ├── forgot-password.blade.php
│       │   ├── login.blade.php
│       │   ├── register.blade.php
│       │   ├── reset-password.blade.php
│       │   └── verify-email.blade.php
│       ├── booking
│       │   ├── partials
│       │   │   ├── passenger-autofill-scripts.blade.php
│       │   │   ├── passenger-autofill.blade.php
│       │   │   └── passenger-baggage.blade.php
│       │   ├── passengers.blade.php
│       │   └── seats.blade.php
│       ├── booking-history
│       │   ├── partials
│       │   │   └── passport-update.blade.php
│       │   ├── index.blade.php
│       │   ├── show.blade.php
│       │   └── ticket-pdf.blade.php
│       ├── components
│       │   ├── auth-session-status.blade.php
│       │   ├── input-error.blade.php
│       │   └── modal.blade.php
│       ├── dashboard
│       │   ├── index.blade.php
│       │   └── user.blade.php
│       ├── emails
│       │   └── booking-paid.blade.php
│       ├── flights
│       │   └── results.blade.php
│       ├── layouts
│       │   ├── admin.blade.php
│       │   └── app.blade.php
│       ├── payment
│       │   └── show.blade.php
│       ├── profile
│       │   ├── partials
│       │   │   ├── delete-user-form.blade.php
│       │   │   ├── update-password-form.blade.php
│       │   │   └── update-profile-information-form.blade.php
│       │   └── edit.blade.php
│       ├── saved-passengers
│       │   ├── _form.blade.php
│       │   ├── create.blade.php
│       │   ├── edit.blade.php
│       │   └── index.blade.php
│       └── home.blade.php
├── routes
│   ├── auth.php
│   ├── console.php
│   └── web.php
├── storage
│   ├── app
│   │   ├── private
│   │   └── public
│   ├── framework
│   │   ├── cache
│   │   │   └── data
│   │   ├── sessions/...
│   │   ├── testing
│   │   └── views/...
│   └── logs
├── tests
│   ├── Concerns
│   │   └── CreatesInternationalBooking.php
│   ├── Feature
│   │   ├── Admin
│   │   │   ├── AdminBookingControllerTest.php
│   │   │   ├── AdminDashboardTest.php
│   │   │   ├── AdminPassportUpdateRequestTest.php
│   │   │   ├── AdminSidebarTest.php
│   │   │   ├── AircraftControllerTest.php
│   │   │   ├── AirlineControllerTest.php
│   │   │   ├── AirportControllerTest.php
│   │   │   ├── BaggageAddonControllerTest.php
│   │   │   ├── FareClassControllerTest.php
│   │   │   └── FlightControllerTest.php
│   │   ├── Auth
│   │   │   ├── AuthenticationTest.php
│   │   │   ├── EmailVerificationTest.php
│   │   │   ├── PasswordConfirmationTest.php
│   │   │   ├── PasswordResetTest.php
│   │   │   ├── PasswordUpdateTest.php
│   │   │   └── RegistrationTest.php
│   │   ├── BaggageBookingTest.php
│   │   ├── BookingExpiryServiceTest.php
│   │   ├── BookingHistoryTest.php
│   │   ├── ExampleTest.php
│   │   ├── FlightFilterTest.php
│   │   ├── FlightSearchTest.php
│   │   ├── MultiCityBookingFlowTest.php
│   │   ├── MultiCitySearchTest.php
│   │   ├── MultiLegBookingTest.php
│   │   ├── NewsletterTest.php
│   │   ├── PassengerBookingTest.php
│   │   ├── PassportBookingTest.php
│   │   ├── PassportRiskTest.php
│   │   ├── PassportUpdateRequestTest.php
│   │   ├── PaymentTest.php
│   │   ├── ProfileTest.php
│   │   ├── RoundTripBookingFlowTest.php
│   │   ├── RoundTripSearchTest.php
│   │   ├── SavedPassengerOptionsTest.php
│   │   ├── SavedPassengerTest.php
│   │   ├── SeatPrecheckTest.php
│   │   ├── SeatSelectionFlowTest.php
│   │   └── UserDashboardTest.php
│   ├── Unit
│   │   └── ExampleTest.php
│   └── TestCase.php
├── artisan
├── composer.json
├── composer.lock
├── package-lock.json
├── package.json
├── PHASE-2-COMPLETION-REVIEW.md
├── PHASE-COMPLETION-REPORT.md
├── PHASE-NEXT-UNUSED-FEATURES.md
├── phpunit.xml
├── postcss.config.js
├── README.md
├── tailwind.config.js
├── UI-GUIDE-SeatMap.md
└── vite.config.js
```
