#!/bin/bash

# Test script to verify email functionality
echo "Testing email functionality..."

# Test user registration which should send a verification email
echo "Registering a test user..."

RESPONSE=$(curl -v -k -s -X POST \
  -H "Content-Type: application/ld+json" \
  -d '{
    "email": "mailhog-test@example.com",
    "password": "TestPassword123!",
    "givenName": "MailHog",
    "familyName": "Test",
    "gender": "other",
    "telephone": "+33123456789"
  }' \
  https://localhost/register)

echo "Response: $RESPONSE"

echo ""
echo "If successful, check MailHog at http://localhost:8025 to see the verification email!"
echo ""
echo "The email should contain:"
echo "- From: noreply@archerymanager.local"  
echo "- To: mailhog-test@example.com"
echo "- Subject: Verify your email address"
echo "- A verification token and link"
