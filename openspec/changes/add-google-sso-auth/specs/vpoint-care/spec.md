# VPoint Care Auth External Login Spec Delta

## ADDED Requirements

### Requirement: Google Login

The system SHALL allow configured users to authenticate to the admin panel using Google OAuth while preserving `MPengguna` as the internal user source.

#### Scenario: Existing active user logs in with Google

- GIVEN Google login is enabled
- AND an active `MPengguna` exists with the verified Google email or linked Google identity
- WHEN the user completes Google OAuth successfully
- THEN the system SHALL authenticate the matching `MPengguna`
- AND the system SHALL show only menus permitted by the user's role
- AND the system SHALL record a successful external login audit event

#### Scenario: Google email is not verified

- GIVEN Google login is enabled
- WHEN Google returns a profile with an unverified email
- THEN the system SHALL reject the login
- AND the system SHALL not create or link an internal user
- AND the system SHALL show a safe failure message

#### Scenario: Google domain is not allowed

- GIVEN Google login has one or more allowed domains configured
- WHEN the authenticated Google email domain is outside the allowed list
- THEN the system SHALL reject the login
- AND the system SHALL record a sanitized failure reason

### Requirement: SSO Login

The system SHALL allow configured users to authenticate to the admin panel using the configured SSO provider while preserving `MPengguna` as the internal user source.

#### Scenario: Existing active user logs in with SSO

- GIVEN SSO login is enabled
- AND an active `MPengguna` exists with the verified SSO email or linked SSO subject
- WHEN the user completes the SSO flow successfully
- THEN the system SHALL authenticate the matching `MPengguna`
- AND the system SHALL show only menus permitted by the user's role
- AND the system SHALL record a successful external login audit event

#### Scenario: SSO response is invalid

- GIVEN SSO login is enabled
- WHEN the callback response fails signature, state, nonce, issuer, audience, or required claim validation
- THEN the system SHALL reject the login
- AND the system SHALL not create or mutate a user account
- AND the system SHALL show a safe failure message

### Requirement: External User Registration

The system SHALL support creating a new internal user registration from Google or SSO only when registration is enabled and the external identity passes validation.

#### Scenario: New Google user registers

- GIVEN Google registration is enabled
- AND the Google profile has a verified email and allowed domain
- AND no `MPengguna` or external identity link exists for that email/provider subject
- WHEN the user completes Google OAuth
- THEN the system SHALL create a new internal user record with pending or inactive status
- AND the system SHALL store the provider identity link
- AND the system SHALL not grant admin panel access until approval rules are satisfied
- AND the system SHALL show a pending-registration response

#### Scenario: New SSO user registers

- GIVEN SSO registration is enabled
- AND the SSO profile has required validated claims
- AND no `MPengguna` or external identity link exists for that email/provider subject
- WHEN the user completes the SSO flow
- THEN the system SHALL create a new internal user record with pending or inactive status
- AND the system SHALL store the provider identity link
- AND the system SHALL not grant admin panel access until approval rules are satisfied
- AND the system SHALL show a pending-registration response

#### Scenario: Registration is disabled

- GIVEN external registration is disabled
- WHEN a valid external identity does not match an existing internal user
- THEN the system SHALL not create a new user
- AND the system SHALL show an instruction to contact an administrator

### Requirement: External Identity Linking

The system SHALL link Google and SSO identities to internal users safely and uniquely.

#### Scenario: First successful login links identity by email

- GIVEN an active `MPengguna` exists with the same verified email as the external provider profile
- AND no conflicting provider link exists
- WHEN the user completes external authentication successfully
- THEN the system SHALL create or update a provider identity link for that `MPengguna`
- AND future login SHALL match the provider subject directly

#### Scenario: Provider subject is linked to another user

- GIVEN a provider subject is already linked to one `MPengguna`
- WHEN another user attempts to authenticate with the same provider subject
- THEN the system SHALL reject the login
- AND the system SHALL not relink the identity automatically
- AND the system SHALL record a sanitized security audit event

### Requirement: External Auth UI

The system SHALL render Google and SSO login actions on the authentication screen only when the corresponding provider is enabled, using a polished, responsive, and secure user experience.

#### Scenario: Google and SSO are enabled

- WHEN a guest opens the admin login page
- THEN the page SHALL show the existing username/password form
- AND the page SHALL show `Masuk dengan Google` with a recognizable Google icon and safe loading state
- AND the page SHALL show `Masuk dengan SSO Perusahaan` with a secure company-login visual style
- AND the page SHALL separate external login actions from password login with clear helper text

#### Scenario: Provider is disabled

- WHEN a guest opens the admin login page
- THEN disabled providers SHALL not be shown as clickable login actions
- AND direct access to disabled provider routes SHALL be rejected safely

### Requirement: Secure External Auth Defaults

The system SHALL enforce secure-by-default external authentication behavior for Google and SSO.

#### Scenario: External authentication succeeds

- WHEN a user is authenticated through Google or SSO
- THEN the system SHALL regenerate the application session
- AND the system SHALL not store provider access tokens or refresh tokens unless a future approved feature requires them
- AND the system SHALL not log authorization codes, ID tokens, access tokens, refresh tokens, client secrets, or sensitive claims

#### Scenario: External authentication is rate limited

- WHEN repeated external login or callback attempts exceed configured limits
- THEN the system SHALL throttle the requests
- AND the system SHALL show a safe retry message
- AND the system SHALL record a sanitized audit event

#### Scenario: User has no approved role

- GIVEN external authentication succeeds
- WHEN the matching internal user is inactive, pending, rejected, or has no approved role
- THEN the system SHALL reject admin panel access
- AND the system SHALL show a safe pending or contact-admin message

### Requirement: Pending User Approval

The system SHALL prevent newly registered external users from accessing protected admin menus until their internal status, role, and permissions are approved.

#### Scenario: Pending user attempts to enter admin panel

- GIVEN a newly registered external user is pending approval
- WHEN the user attempts to enter `/admin`
- THEN the system SHALL reject panel access
- AND the system SHALL show a pending-approval message
- AND no protected menu SHALL be visible

#### Scenario: Administrator approves pending user

- GIVEN an administrator has permission to manage users
- WHEN the administrator approves a pending user and assigns a valid role
- THEN the user SHALL be allowed to authenticate through the linked external provider
- AND the user SHALL see only menus permitted by the assigned role


