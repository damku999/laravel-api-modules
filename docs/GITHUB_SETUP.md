# GitHub Actions & Email Reporting Setup

This document explains how to set up automated testing with email reporting for the Laravel API Modules package.

## 🚀 Quick Setup Guide

### 1. GitHub Secrets Configuration

To enable email notifications, you need to configure the following GitHub Secrets in your repository:

1. Go to your GitHub repository
2. Click on **Settings** → **Secrets and variables** → **Actions**
3. Add the following secrets:

#### Required Secrets:

| Secret Name | Description | Example Value |
|-------------|-------------|---------------|
| `SMTP_USERNAME` | Gmail address for sending emails | `webmonks.in@gmail.com` |
| `SMTP_PASSWORD` | Gmail App Password (not regular password) | `abcd efgh ijkl mnop` |

#### Optional Secrets:

| Secret Name | Description | When Needed |
|-------------|-------------|-------------|
| `CODECOV_TOKEN` | Codecov integration token | Only for private repos |

### 2. Gmail App Password Setup

Since the email goes to `webmonks.in@gmail.com`, you need to create an App Password:

1. Go to [Google Account Security](https://myaccount.google.com/security)
2. Enable 2-Factor Authentication (required for App Passwords)
3. Go to **App passwords**
4. Select **Mail** and **Other (custom)**
5. Enter "Laravel API Modules CI" as the app name
6. Use the generated 16-character password as `SMTP_PASSWORD`

### 3. Repository Settings

Ensure these settings are configured in your GitHub repository:

#### Actions Permissions:
- Go to **Settings** → **Actions** → **General**
- Set **Actions permissions** to "Allow all actions and reusable workflows"
- Enable **Allow GitHub Actions to create and approve pull requests**

#### Pages (for coverage reports):
- Go to **Settings** → **Pages**
- Set source to "GitHub Actions"

## 📧 Email Notification Features

### What Gets Sent:

- **HTML Email** with styled test results
- **Test Summary** with pass/fail status for each job
- **Coverage Report** as attachment (when available)
- **Direct Links** to workflow run and coverage reports
- **Automatic Release Notes** on successful main branch builds

### Email Content:

```
🧪 Laravel API Modules - Test Results [✅ PASSED]

📊 Test Summary
✅ Unit Tests: PASSED
🛡️ Security Analysis: PASSED  
🔍 Code Quality: PASSED
⚡ Performance: PASSED

🔗 Quick Links
- View Workflow Run
- View Code Coverage

Generated at: 2025-08-27 10:30:45 UTC
```

### When Emails Are Sent:

- ✅ **On every push** to `main` and `develop` branches
- ✅ **On every pull request** to `main` and `develop` branches  
- ✅ **Daily scheduled runs** at 2 AM UTC
- ✅ **Always sent** regardless of pass/fail status

## 🧪 Testing Matrix

The CI pipeline runs comprehensive tests across multiple environments:

### Operating Systems:
- Ubuntu Latest (Primary)
- Windows Latest  
- macOS Latest

### PHP Versions:
- PHP 7.4, 8.0, 8.1, 8.2, 8.3

### Laravel Versions:
- Laravel 8.x, 9.x, 10.x, 11.x

### Compatibility Matrix:
```
✅ PHP 7.4 + Laravel 8.x, 9.x
✅ PHP 8.0 + Laravel 8.x, 9.x, 10.x
✅ PHP 8.1 + Laravel 9.x, 10.x, 11.x
✅ PHP 8.2 + Laravel 9.x, 10.x, 11.x (Primary for coverage)
✅ PHP 8.3 + Laravel 10.x, 11.x
```

## 📊 Quality Checks

### 1. Unit Tests (Pest PHP):
```bash
composer test-coverage
```
- **Goal**: 100% code coverage
- **Framework**: Pest PHP with Laravel integration
- **Output**: HTML coverage report + XML for Codecov

### 2. Security Analysis (Psalm):
```bash
composer psalm
```
- **Security scan** with Psalm static analysis
- **SARIF report** uploaded to GitHub Security tab
- **Vulnerability detection** and security recommendations

### 3. Code Quality (PHPStan + PHP CS Fixer):
```bash
composer analyse
composer format-dry
```
- **Level 8 PHPStan** analysis (strictest)
- **PSR-12 compliance** checking
- **Code style** enforcement

### 4. Performance Benchmarks:
```bash
php scripts/performance_test.php
```
- **FileSystem cache** performance testing
- **Module generation** benchmarking
- **Memory usage** analysis

## 🎯 Coverage Goals & Metrics

### Target Coverage:
- **Unit Tests**: 100% line coverage
- **Branch Coverage**: 95% minimum
- **CRAP Score**: < 10 for all methods
- **Complexity**: < 15 per method

### Quality Metrics:
- **PHPStan**: Level 8 compliance (no errors)
- **Psalm**: Security analysis passed
- **PHP CS Fixer**: PSR-12 compliant
- **Performance**: All benchmarks within thresholds

## 🔄 Workflow Triggers

### Push Events:
```yaml
on:
  push:
    branches: [ main, develop ]
```

### Pull Requests:
```yaml
on:
  pull_request:
    branches: [ main, develop ]
```

### Scheduled Runs:
```yaml
on:
  schedule:
    - cron: '0 2 * * *'  # Daily at 2 AM UTC
```

## 📈 Automated Releases

### Release Criteria:
- ✅ All tests pass
- ✅ Security analysis passes
- ✅ Code quality checks pass
- ✅ Push to `main` branch

### Release Process:
1. **Automated tagging** with `v1.{run_number}`
2. **Release notes** generation with test results
3. **Artifact attachment** (coverage reports)
4. **Email notification** to `webmonks.in@gmail.com`

### Release Notes Template:
```markdown
🎉 Automated Release - All Tests Passed!

## ✅ Test Results
- Unit Tests: PASSED
- Security Analysis: PASSED  
- Code Quality: PASSED
- Performance Tests: PASSED

## 📊 Coverage
View detailed coverage report in the workflow artifacts.
```

## 🛠️ Local Development

### Run Full Quality Suite:
```bash
composer quality
```

### Individual Commands:
```bash
# Run tests with coverage
composer test-coverage

# Static analysis
composer analyse

# Security analysis  
composer psalm

# Code formatting
composer format

# Performance tests
php scripts/performance_test.php
```

### Watch Mode (Development):
```bash
composer test-watch
```

## 🔧 Troubleshooting

### Email Not Receiving:

1. **Check Gmail App Password**: Must be 16-character app-specific password
2. **Verify 2FA**: Gmail requires 2-factor authentication for app passwords
3. **Check Spam Folder**: CI emails might be filtered
4. **Repository Secrets**: Ensure secrets are added to the correct repository

### Workflow Failures:

1. **Check Dependencies**: Ensure all composer dependencies are compatible
2. **PHP Version**: Verify PHP/Laravel version compatibility in matrix
3. **File Permissions**: Windows/macOS might have different file system behaviors
4. **Memory Limits**: PHPStan requires 2GB memory limit

### Coverage Issues:

1. **Missing Tests**: Ensure all classes have corresponding test files
2. **Xdebug**: Coverage requires Xdebug PHP extension
3. **Excluded Files**: Check `phpunit.xml` exclusions
4. **Private Methods**: Consider testing private methods via public interfaces

## 📞 Support

If you encounter issues with the testing setup:

1. **Check Workflow Logs**: GitHub Actions provides detailed logs
2. **Review Email Content**: Failed runs include diagnostic information  
3. **Local Testing**: Run `composer quality` locally first
4. **GitHub Issues**: Report setup issues in the repository

---

**Last Updated**: 2025-08-27  
**Maintained By**: WebMonks Technologies