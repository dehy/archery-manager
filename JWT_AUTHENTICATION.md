# Authentication API

This document describes the authentication system using JWT tokens with OAuth 2.0 principles.

## Overview

The authentication system provides:
- **JWT tokens** for stateless authentication
- **Email/password login** 
- **User registration** with email verification
- **Token refresh** for long-term sessions
- **Protected endpoints** requiring authentication

## Authentication Flow

1. **Register** a new user → Get JWT token + verification email
2. **Verify email** using token from email
3. **Login** with email/password → Get JWT token
4. **Access protected endpoints** using `Authorization: Bearer <token>`
5. **Refresh token** when needed

## Endpoints

### POST /register

Register a new user account.

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "SecurePassword123!",
  "givenName": "John",
  "familyName": "Doe",
  "gender": "male",
  "telephone": "+33123456789"
}
```

**Response (201 Created):**
```json
{
  "message": "User registered successfully",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "givenName": "John",
    "familyName": "Doe",
    "roles": ["ROLE_USER"],
    "isVerified": false
  },
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
}
```

### POST /login

Authenticate a user and get JWT token.

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "SecurePassword123!"
}
```

**Response (200 OK):**
```json
{
  "user": {
    "id": 1,
    "email": "user@example.com",
    "givenName": "John",
    "familyName": "Doe",
    "roles": ["ROLE_USER"],
    "isVerified": true
  },
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
}
```

### GET /me

Get current user information (requires authentication).

**Headers:**
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...
```

**Response (200 OK):**
```json
{
  "id": 1,
  "email": "user@example.com",
  "givenName": "John",
  "familyName": "Doe",
  "gender": "male",
  "telephone": "+33123456789",
  "roles": ["ROLE_USER"],
  "isVerified": true,
  "licensees": []
}
```

### POST /refresh-token

Get a new JWT token (requires authentication).

**Headers:**
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...
```

**Response (200 OK):**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
}
```

### POST /verify-email

Verify user email address using token from email.

**Request Body:**
```json
{
  "token": "abc123def456..."
}
```

**Response (200 OK):**
```json
{
  "message": "Email verified successfully"
}
```

## Token Usage

Include the JWT token in the `Authorization` header for all protected endpoints:

```bash
curl -H "Authorization: Bearer <your-jwt-token>" https://api.archerymanager.com/users
```

## Token Expiration

- **Access tokens** expire after 1 hour
- Use `/refresh-token` to get a new token without re-authentication
- Refresh tokens before expiration for seamless user experience

## Security Features

- **Password hashing** using Symfony's auto password hasher
- **Email verification** required for account activation
- **JWT tokens** with RSA256 signing
- **Stateless authentication** - no server-side sessions
- **Role-based access control** (RBAC)

## Error Responses

**401 Unauthorized:**
```json
{
  "message": "Missing credentials"
}
```

**400 Bad Request:**
```json
{
  "errors": {
    "email": "This value should be a valid email.",
    "password": "This value should not be blank."
  }
}
```

## Environment Configuration

**Development (.env):**
```bash
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=changeme
```

**Production:**
- Use strong passphrase
- Secure key storage
- HTTPS only
- Environment variables for secrets

## Testing

Run authentication tests:
```bash
docker compose exec php bin/phpunit tests/Functional/Auth/
```

Test with curl:
```bash
# Register
curl -X POST https://localhost/register \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"TestPass123!","givenName":"Test","familyName":"User","gender":"other"}'

# Login
curl -X POST https://localhost/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"TestPass123!"}'

# Access protected endpoint
curl -H "Authorization: Bearer <token>" https://localhost/me
```
