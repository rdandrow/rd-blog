# GitHub Secrets Setup Guide

## Required Secrets for CI

To run tests in GitHub Actions, you **must** configure the following secrets:

### How to Add Secrets

1. Go to your repository on GitHub
2. Click **Settings** → **Secrets and variables** → **Actions**
3. Click **New repository secret**
4. Add each secret below

### Test Database Secrets (All Required)

| Secret Name | Description | Example Value |
|------------|-------------|---------------|
| `DB_TEST_USERNAME` | PostgreSQL test user | `rd_blog_test` |
| `DB_TEST_PASSWORD` | PostgreSQL test password | `secure_random_password_here` |
| `DB_TEST_DATABASE` | PostgreSQL test database | `rd_blog_test` |

### Important Notes

**This is a TEST credential only:**
- Used exclusively in CI for running automated tests
- Database exists only during test execution (~2 minutes)
- Automatically destroyed after each CI run
- Not connected to any production or development data

**Security level:** Low risk (ephemeral test environment)  
**Best practice:** No hardcoded passwords in git history, even for ephemeral environments

### Why No Fallback?

Previous versions included a fallback password, but this has been removed to:
- Avoid committing any credentials to git history
- Follow security best practices even for test environments
- Require explicit configuration before running CI
- Prevent accidental exposure of default credentials

**The workflow will fail fast with a clear error if the secret is not configured.**

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

After adding all three secrets, push a commit to trigger CI:

```bash
git commit --allow-empty -m "Test CI with configured secret"
git push
```

Check Actions tab to verify tests pass with the configured secrets.

## Other Environment Secrets (If Needed)

For additional services or production deployments, you might add:

| Secret Name | Purpose |
|------------|---------|
| `APP_KEY` | Laravel encryption key (if needed) |
| `MAIL_*` | Email service credentials |
| `AWS_*` | S3 storage credentials |

**Current setup:** Only database secrets are required for CI tests.
