# GitHub Secrets Setup Guide

## Required Secrets for CI

To run tests in GitHub Actions, set up the following secrets in your repository:

### How to Add Secrets

1. Go to your repository on GitHub
2. Click **Settings** → **Secrets and variables** → **Actions**
3. Click **New repository secret**
4. Add each secret below

### Test Database Secrets

| Secret Name | Description | Example Value |
|------------|-------------|---------------|
| `DB_TEST_USERNAME` | PostgreSQL test user | `rd_blog_test` |
| `DB_TEST_PASSWORD` | PostgreSQL test password | `secure_random_password_here` |
| `DB_TEST_DATABASE` | PostgreSQL test database | `rd_blog_test` |

### Important Notes

**These are TEST credentials only:**
- Used exclusively in CI for running automated tests
- Database exists only during test execution (~2 minutes)
- Automatically destroyed after each CI run
- Not connected to any production or development data

**Security level:** Low risk (ephemeral test environment)  
**Best practice:** Still use secrets to avoid exposing any credentials in git history

### Fallback Values

If secrets are not configured, the workflow uses stronger fallback defaults:
- Username: `rd_blog_test`
- Password: `ci_temp_9K7mP2xQ8vL4nR6wE5tY` (random-looking default)
- Database: `rd_blog_test`

**Note:** While fallbacks use stronger passwords than typical test defaults, configuring proper secrets is still recommended for security best practices.

**Why fallbacks exist:** To allow quick testing without initial secret setup. The CI database is ephemeral (destroyed after ~2 minutes) and never exposed publicly.

### Generate Secure Password

```bash
# Generate a random secure password
openssl rand -base64 32
```

### Complete Setup Steps

```bash
# 1. Generate a secure password
DB_PASSWORD=$(openssl rand -base64 32)

# 2. Add to GitHub Secrets:
#    - DB_TEST_USERNAME: rd_blog_test
#    - DB_TEST_PASSWORD: [paste generated password]
#    - DB_TEST_DATABASE: rd_blog_test
```

### Verification

After adding secrets, push a commit to trigger CI:

```bash
git commit --allow-empty -m "Test CI with secrets"
git push
```

Check Actions tab to verify tests pass with configured secrets.

## Other Environment Secrets (If Needed)

For additional services or production deployments, you might add:

| Secret Name | Purpose |
|------------|---------|
| `APP_KEY` | Laravel encryption key (if needed) |
| `MAIL_*` | Email service credentials |
| `AWS_*` | S3 storage credentials |

**Current setup:** Only database secrets are required for CI tests.
