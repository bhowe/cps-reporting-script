# CSP Report Handler

A secure PHP endpoint for receiving and processing Content Security Policy (CSP) violation reports. This handler validates incoming CSP reports, implements rate limiting, and forwards reports via email using SendGrid.

## Features

- Secure CSP report processing with origin validation
- Rate limiting to prevent abuse
- Email notifications via SendGrid
- Input sanitization and validation
- Comprehensive error handling
- JSON payload validation
- Size limit enforcement

## Requirements

- PHP 7.4 or higher
- SendGrid PHP library
- Write access to temporary directory (for rate limiting)
- Composer for dependency management

## Installation

1. Clone the repository:
```bash
git clone https://github.com/yourusername/csp-report-handler.git
cd csp-report-handler
```

2. Install dependencies using Composer:
```bash
composer require sendgrid/sendgrid
```

3. Copy the configuration template:
```bash
cp config.example.php config.php
```

4. Update your configuration in `config.php`:
```php
$emailTo = "security@yourdomain.com";
$emailFrom = "csp-reports@yourdomain.com";
$sendgridApiKey = 'YOUR_SENDGRID_API_KEY';
$allowedOrigin = 'https://yourdomain.com';
```

## Configuration

### Required Settings

- `$emailTo`: Email address where reports will be sent
- `$emailFrom`: Email address used as the sender
- `$sendgridApiKey`: Your SendGrid API key
- `$allowedOrigin`: The domain allowed to submit reports

### Rate Limiting Settings

- `$rateLimitFile`: Path to the rate limit tracking file
- `$maxRequests`: Maximum number of requests allowed per time window
- `$timeWindow`: Time window in seconds for rate limiting

## Usage

### Setting Up Your CSP Header

Add this endpoint as your CSP report-uri:

```html
Content-Security-Policy-Report-Only: default-src 'self'; report-uri /path/to/csp-handler.php
```

Or for enforced CSP:

```html
Content-Security-Policy: default-src 'self'; report-uri /path/to/csp-handler.php
```

### Email Report Format

Reports are sent in plain text format with the following sections:

```
CSP Violation Report
===================

Document-uri: https://example.com/page
Violated-directive: script-src
Blocked-uri: https://malicious-site.com/script.js

Server Details:
Time: 2024-12-12 13:45:23
IP: 192.168.1.1
Origin: https://example.com
```

## Security Features

1. Origin Validation
   - Ensures reports only come from allowed domains

2. Rate Limiting
   - Prevents DoS attacks
   - Configurable request limits and time windows

3. Input Validation
   - Size limits on payloads
   - JSON structure validation
   - Content-Type verification

4. Output Sanitization
   - HTML encoding of report data
   - Sanitized email content

## Error Handling

The endpoint returns appropriate HTTP status codes:

- 204: Success
- 400: Invalid JSON
- 403: Invalid origin
- 413: Payload too large
- 415: Invalid content type
- 429: Rate limit exceeded
- 500: Email sending failure

## Logging

All errors are logged using PHP's `error_log()` function, including:
- Invalid origins
- Rate limit violations
- JSON parsing errors
- SendGrid API errors

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/improvement`)
3. Commit your changes (`git commit -am 'Add rate limiting feature'`)
4. Push to the branch (`git push origin feature/improvement`)
5. Create a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Security Considerations

- Keep your SendGrid API key secure
- Regularly monitor logs for abuse patterns
- Consider implementing additional validation for your specific needs
- Ensure proper file permissions on the rate limit file

## Troubleshooting

### Common Issues

1. Rate Limit File Permissions
```bash
chmod 644 /tmp/csp_rate_limit.json
```

2. SendGrid Connection Issues
```php
error_log("SendGrid Error: " . $e->getMessage());
```

Check your PHP error logs for detailed error messages.

## Support

For issues, questions, or contributions, please open an issue on the GitHub repository.
