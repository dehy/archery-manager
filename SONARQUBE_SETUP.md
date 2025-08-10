# SonarQube Setup Instructions

## Prerequisites

To enable SonarQube analysis in this repository, you need to set up the following:

### 1. SonarCloud Project Setup

1. Go to [SonarCloud.io](https://sonarcloud.io)
2. Log in with your GitHub account
3. Import your repository `dehy/archery-manager`
4. The project key should be: `dehy_archery-manager`
5. Organization should be: `dehy`

### 2. GitHub Repository Secrets

Add the following secret to your GitHub repository settings:

- **Secret Name**: `SONAR_TOKEN`
- **Secret Value**: Generate this from SonarCloud:
  1. Go to SonarCloud → My Account → Security
  2. Generate a new token
  3. Copy the token value

### 3. Repository Settings

1. Go to your GitHub repository
2. Navigate to **Settings** → **Secrets and variables** → **Actions**
3. Click **New repository secret**
4. Add the `SONAR_TOKEN` secret

## Verification

Once set up, the comprehensive CI pipeline will:

- ✅ **Unit Tests**: PHPUnit with coverage + artifact upload
- ✅ **Code Quality**: PHPStan + PHP-CS-Fixer analysis  
- ✅ **Frontend Tests**: TypeScript + ESLint for PWA
- ✅ **SonarQube Analysis**: Reuses all artifacts (no duplicate execution)
- ✅ **E2E Tests**: Playwright integration tests (main/next only)
- ✅ **Deployment Check**: Docker build verification

## Optimized CI Pipeline

The workflow maximizes efficiency through smart artifact reuse:

### 🧪 **Unit Tests Job**
- Runs PHPUnit tests with Xdebug coverage
- Generates multiple report formats (Clover XML, JUnit XML)
- Uploads artifacts for downstream jobs
- Includes database setup and migrations

### 🔍 **Code Quality Job**  
- PHPStan static analysis (Level 2)
- PHP-CS-Fixer coding standards
- Runs in parallel with unit tests
- No database dependency

### 🌐 **Frontend Tests Job**
- TypeScript compilation checks
- ESLint linting for React/Next.js
- Future: Jest/Vitest unit tests
- Runs in parallel with backend jobs

### 📊 **SonarQube Analysis Job**
- Downloads all previous artifacts
- No test re-execution
- Comprehensive multi-language analysis
- Quality gate enforcement

### 🎭 **E2E Tests Job** (Branch pushes only)
- Full application stack via Docker Compose
- Playwright browser automation
- Real database interactions
- Artifact retention for debugging

### 🚀 **Deployment Check Job** (Branch pushes only)
- Docker image build verification
- Production configuration validation
- Deployment readiness confirmation

## Troubleshooting

### Common Issues

1. **"SONAR_TOKEN not found"**
   - Ensure the secret is added to repository settings
   - Check the secret name is exactly `SONAR_TOKEN`

2. **"Project not found"**
   - Verify the project key in `sonar-project.properties`
   - Ensure the project exists in SonarCloud

3. **"Analysis failed"**
   - Check the workflow logs for specific error messages
   - Verify the sonar-project.properties configuration

### Support

- 📖 [SonarCloud Documentation](https://docs.sonarcloud.io/)
- 🚀 [GitHub Actions for SonarCloud](https://github.com/SonarSource/sonarcloud-github-action)
