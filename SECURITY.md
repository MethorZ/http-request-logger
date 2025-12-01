# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | :white_check_mark: |

## Reporting a Vulnerability

We take security vulnerabilities seriously. If you discover a security issue, please report it responsibly.

### How to Report

1. **Do NOT** create a public GitHub issue for security vulnerabilities
2. Email the maintainer directly at: **methorz@spammerz.de**
3. Include:
   - Description of the vulnerability
   - Steps to reproduce
   - Potential impact
   - Suggested fix (if any)

### What to Expect

- **Acknowledgment**: Within 48 hours
- **Initial Assessment**: Within 7 days
- **Resolution Timeline**: Depends on severity (critical: ASAP, high: 30 days, medium: 90 days)

### After Resolution

- Security fixes will be released as patch versions
- Credit will be given to reporters (unless anonymity is requested)
- A security advisory will be published for significant vulnerabilities

## Security Best Practices

When using this package:

- **Keep dependencies updated** - Run `composer update` regularly
- **Use latest PHP version** - Security fixes are backported to supported versions only
- **Filter sensitive log context** - Don't log passwords, tokens, or PII
- **Secure log storage** - Protect log files from unauthorized access
- **Use log rotation** - Prevent log files from growing indefinitely

## Known Security Considerations

### Sensitive Data in Logs

This package logs request information. Be careful not to log sensitive data:

```php
// BAD: Logging sensitive headers
$logger->info('Request', [
    'headers' => $request->getHeaders(), // May contain Authorization!
]);

// GOOD: Filter sensitive headers
$headers = $request->getHeaders();
unset($headers['Authorization'], $headers['Cookie']);
$logger->info('Request', ['headers' => $headers]);
```

### What Gets Logged by Default

The middleware logs:
- Request method and URI (may contain query params)
- Response status code
- Execution time and memory usage
- Request ID

**Not logged by default:**
- Request body (may contain passwords)
- Response body (may contain sensitive data)
- Headers (may contain auth tokens)

### Request ID in Headers

The `X-Request-ID` header is added to responses:
- This is useful for debugging and tracing
- It does not expose sensitive information
- Can be disabled via constructor parameter

### Log Aggregation Security

When sending logs to external services:
- Use TLS/HTTPS for log transmission
- Implement log redaction for sensitive fields
- Follow data retention policies (GDPR, etc.)

## Contact

- **Security Issues**: methorz@spammerz.de
- **General Issues**: [GitHub Issues](https://github.com/MethorZ/http-request-logger/issues)

---

Thank you for helping keep this project secure!

