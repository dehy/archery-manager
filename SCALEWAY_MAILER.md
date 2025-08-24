# Scaleway Mailer Integration

This project is configured to use Scaleway's email service for sending emails in production.

## Setup

### 1. Create a Scaleway Account
- Go to [Scaleway Console](https://console.scaleway.com/)
- Create an account if you don't have one

### 2. Create a Transactional Email Project
- Navigate to "Transactional Email" in the Scaleway console
- Create a new project
- Note down the Project ID

### 3. Generate API Key
- Go to "Credentials" in your Scaleway console
- Generate a new API key with email permissions
- Note down the API key (keep it secure!)

### 4. Configure Environment Variables

For production deployment, set these environment variables:

```bash
# Option 1: Using Scaleway API (recommended)
MAILER_DSN=scaleway+api://YOUR_PROJECT_ID:YOUR_API_KEY@default

# Option 2: Using Scaleway SMTP
MAILER_DSN=scaleway+smtp://YOUR_PROJECT_ID:YOUR_API_KEY@default
```

### 5. Email Configuration

The email service is configured in `src/Service/EmailService.php` with:
- **From address**: `noreply@archerymanager.com`
- **Templates**: Located in `templates/email/`
- **Email verification**: Automatically sent on user registration

### 6. Development vs Production

- **Development**: Uses MailHog (`smtp://mailhog:1025`) for local email testing
- **Production**: Uses Scaleway mailer for real email delivery

### 7. Testing

#### Local Development
1. Start MailHog: `docker compose up mailhog`
2. Visit http://localhost:8025 to see captured emails
3. Register a user via API to test email sending

#### Production
1. Configure Scaleway credentials in `.env.prod`
2. Deploy the application
3. Register a user to test real email delivery

## Email Templates

Email templates are located in `templates/email/`:
- `verification.html.twig` - Email verification template

## Monitoring

Monitor email delivery in the Scaleway console:
- Delivery rates
- Bounce rates
- Complaint rates
- Email logs

## Security Notes

- Never commit API keys to version control
- Use environment variables for sensitive configuration
- Regularly rotate API keys
- Monitor email usage to detect abuse
