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

Once set up, the SonarQube analysis will:

- ✅ Run automatically on pushes to `main` and `next` branches
- ✅ Run on pull requests from the same repository  
- ✅ Generate coverage reports from PHPUnit tests
- ✅ Include PHPStan static analysis results
- ✅ Analyze both PHP (API) and TypeScript/JavaScript (PWA) code

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
