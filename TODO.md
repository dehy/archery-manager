# Project TODO List

This document contains improvement opportunities, technical debt items, and enhancement ideas for Archery Manager.

## 🔴 High Priority - Security & Stability

### Security
- [ ] **Add Sentry exception tracking in DiscordController** (lines 49, 73)
  - Currently has `// TODO add sentry exception` comments
  - Errors in Discord webhook handling are silently ignored
  - Should log to Sentry for monitoring

- [ ] **Review and strengthen CSRF protection**
  - Audit all forms for CSRF token inclusion
  - Verify AJAX requests include CSRF tokens
  - Test CSRF protection in all POST/PUT/DELETE endpoints

- [ ] **Implement rate limiting**
  - Add rate limiting for login attempts
  - Rate limit FFTA sync operations
  - Protect public pre-registration form from spam

- [ ] **Security audit of file uploads**
  - Review VichUploader configuration
  - Verify MIME type validation
  - Check file size limits
  - Ensure uploaded files are not executable

### Performance & Reliability
- [ ] **Fix Docker permission issues for Rector and PHPStan**
  - Currently fails with temp directory permissions
  - Prevents running full QA suite
  - Blocks automated code quality checks

- [ ] **Optimize FFTA profile picture downloads**
  - Currently downloads files and calculates checksums
  - TODO in FftaHelper.php line 174: "check image date (with its filename) instead"
  - Could significantly speed up sync operations

- [ ] **Add database connection pooling**
  - Improve performance under load
  - Reduce connection overhead

- [ ] **Implement query result caching**
  - Cache expensive FFTA queries
  - Cache event lists and participant counts
  - Use Doctrine query result cache

## 🟡 Medium Priority - Code Quality & Maintainability

### Testing
- [ ] **Increase test coverage**
  - Current coverage unknown
  - Add unit tests for all Helper classes
  - Add integration tests for Controllers
  - Test FFTA scraper with fixtures

- [ ] **Add E2E tests**
  - Test complete user flows (registration → participation → profile)
  - Test admin workflows
  - Test FFTA sync integration

- [ ] **Test mobile responsiveness**
  - Automated visual regression tests
  - Mobile viewport testing
  - Touch interaction testing

### Code Quality
- [ ] **Resolve registration redirect TODO**
  - RegistrationController line 109: "@TODO Change the redirect on success"
  - Handle or remove flash message
  - Improve post-registration UX

- [ ] **Remove IcsFactory "hacksw" placeholder**
  - Line 178 has placeholder PRODID
  - Should use proper product identifier
  - Update to standard ICS export

- [ ] **Add PHP type declarations everywhere**
  - Ensure all methods have return types
  - Add parameter types where missing
  - Use typed properties consistently

- [ ] **Extract magic strings to constants**
  - Hardcoded strings in controllers
  - Flash message types
  - Route names

- [ ] **Refactor FftaScrapper**
  - 724 lines is very long
  - Extract methods for authentication, parsing, sync
  - Separate concerns (HTTP client, DOM parsing, entity mapping)
  - Add proper error handling

### Documentation
- [ ] **Add PHPDoc to all public methods**
  - Especially in Helper classes
  - Document parameters and return types
  - Add usage examples for complex methods

- [ ] **Document FFTA integration flow**
  - How authentication works
  - Data mapping strategy
  - Error handling approach
  - Sync frequency and triggers

- [ ] **Create API documentation**
  - Document API Platform endpoints
  - Add examples for each endpoint
  - Include authentication requirements

## 🟢 Low Priority - Features & Enhancements

### User Experience
- [ ] **Add loading indicators**
  - Show spinner during AJAX requests
  - Loading state for modal content
  - Progress bar for FFTA sync

- [ ] **Improve error messages**
  - More user-friendly error text
  - Specific guidance on how to fix issues
  - Better validation messages in forms

- [ ] **Add confirmation dialogs**
  - Confirm before deleting events
  - Confirm before removing group members
  - Confirm before critical FFTA operations

- [ ] **Implement infinite scroll or pagination**
  - For licensee list (trombinoscope)
  - For event calendar
  - For results tables

- [ ] **Add search functionality**
  - Search licensees by name
  - Filter events by type, date, group
  - Search results and competitions

- [ ] **Improve mobile navigation**
  - Add breadcrumbs
  - Better back button handling
  - Optimize tap targets for mobile

### Features
- [ ] **Add notification system**
  - Email notifications for event changes
  - Notifications for new competitions
  - Reminders for upcoming events
  - Browser push notifications

- [ ] **Export functionality**
  - Export licensee list to Excel/CSV
  - Export event calendar to ICS (improve existing)
  - Export results to PDF
  - Bulk data export for backup

- [ ] **Advanced group management**
  - Nested groups (sub-groups)
  - Group hierarchies
  - Automatic group assignment rules

- [ ] **Statistics dashboard**
  - Participation rates by licensee
  - Event attendance trends
  - Competition results analytics
  - Training frequency reports

- [ ] **Multi-language support**
  - Add i18n for French/English
  - Translate all UI strings
  - Language switcher in UI

- [ ] **Calendar improvements**
  - Drag-and-drop event scheduling
  - Recurring event editing
  - iCal subscription feed
  - Google Calendar sync

- [ ] **Equipment tracking enhancements**
  - Equipment maintenance logs
  - Replacement reminders
  - Equipment lending system
  - Inventory management

### FFTA Integration
- [ ] **Add retry mechanism for FFTA sync**
  - Automatic retry on transient failures
  - Exponential backoff
  - Better error reporting

- [ ] **FFTA webhook support**
  - If FFTA offers webhooks, use them
  - Real-time sync instead of polling
  - Reduce load on FFTA servers

- [ ] **Conflict resolution UI**
  - When FFTA data conflicts with local data
  - Allow manual resolution
  - Show diff of changes

- [ ] **Sync status dashboard**
  - Last sync time
  - Sync errors and warnings
  - Pending sync operations
  - Manual sync triggers

## 🔵 Technical Debt

### Dependencies
- [ ] **Update TypeScript to latest**
  - Currently on 4.9.5
  - Latest is 5.x
  - May require code updates

- [ ] **Update Chart.js to v4**
  - Currently on 3.x
  - v4 has breaking changes
  - Review migration guide

- [ ] **Review all npm packages for updates**
  - Check for security vulnerabilities
  - Update to latest compatible versions
  - Test thoroughly after updates

- [ ] **Review PHP dependencies**
  - Check for newer versions
  - Look for security advisories
  - Update Symfony bundles

### Infrastructure
- [ ] **Add CI/CD pipeline**
  - GitHub Actions or GitLab CI
  - Automated testing on PR
  - Automated deployment
  - Code quality checks

- [ ] **Improve Docker setup**
  - Multi-stage builds for smaller images
  - Better caching strategy
  - Development vs production configs
  - Health checks for all services

- [ ] **Add database backup strategy**
  - Automated daily backups
  - Point-in-time recovery
  - Backup verification
  - Restore testing

- [ ] **Set up staging environment**
  - Mirror production setup
  - Test migrations before production
  - User acceptance testing

### Monitoring & Observability
- [ ] **Add application metrics**
  - Response time monitoring
  - Error rate tracking
  - User activity metrics
  - FFTA sync performance

- [ ] **Implement structured logging**
  - JSON log format
  - Correlation IDs for requests
  - Log levels properly configured
  - Log rotation

- [ ] **Add health check endpoints**
  - Database connectivity
  - FFTA API availability
  - File storage access
  - Email service status

- [ ] **Set up alerting**
  - Alert on high error rates
  - Alert on failed FFTA syncs
  - Alert on performance degradation
  - Alert on security events

## 📊 Performance Optimizations

- [ ] **Implement lazy loading for images**
  - Profile pictures in trombinoscope
  - Event attachments
  - Practice advice images

- [ ] **Add CDN for static assets**
  - Serve images from CDN
  - Cache compiled JS/CSS
  - Reduce server load

- [ ] **Optimize database indexes**
  - Review slow queries
  - Add indexes for common queries
  - Consider composite indexes

- [ ] **Implement Redis caching**
  - Cache session data
  - Cache frequently accessed data
  - Reduce database load

- [ ] **Add service worker for offline support**
  - Cache essential resources
  - Offline-first for viewing data
  - Background sync for forms

## 🎨 UI/UX Improvements

- [ ] **Design system documentation**
  - Document color palette
  - Component library
  - Typography guidelines
  - Spacing system

- [ ] **Accessibility audit (WCAG 2.1)**
  - Screen reader testing
  - Keyboard navigation
  - Color contrast ratios
  - ARIA labels

- [ ] **Dark mode support**
  - Add dark theme
  - Theme switcher
  - Respect system preference

- [ ] **Animation improvements**
  - Page transitions
  - Loading animations
  - Micro-interactions
  - Smooth scrolling

## 🔧 Developer Experience

- [ ] **Add pre-commit hooks**
  - Run PHP CS Fixer
  - Run PHPStan
  - Run tests
  - Prevent bad commits

- [ ] **Improve local development setup**
  - One-command setup
  - Better documentation
  - Sample data seeding
  - Development utilities

- [ ] **Add code generation tools**
  - Entity scaffolding
  - CRUD generator
  - Form type generator
  - Test case generator

- [ ] **Create contribution guidelines**
  - CONTRIBUTING.md
  - Code of conduct
  - PR template
  - Issue templates

---

## Priority Matrix

### Do First (High Impact, Quick Wins)
1. Add Sentry exception tracking
2. Fix Rector/PHPStan Docker permissions
3. Add loading indicators
4. Optimize FFTA image downloads

### Schedule (High Impact, More Effort)
1. Increase test coverage
2. Add CI/CD pipeline
3. Implement notification system
4. Refactor FftaScrapper

### Delegate or Defer (Lower Impact, Quick)
1. Update TypeScript version
2. Add dark mode
3. Improve error messages
4. Add pre-commit hooks

### Eliminate or Revisit (Lower Impact, More Effort)
1. Multi-language support (unless needed)
2. Nested groups (complex, unclear benefit)
3. Service worker (nice-to-have)

---

**Last Updated**: November 4, 2025  
**Status**: Living document - update as priorities change
