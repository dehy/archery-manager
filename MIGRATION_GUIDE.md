# 🏹 Archery Manager - Legacy to API Platform Migration Guide

## Overview

This guide outlines the complete migration process from your legacy Symfony application to the new API Platform-based structure.

## 🚀 Migration Summary

### ✅ Entities Successfully Migrated

1. **User** - Already migrated with API Platform annotations
2. **Club** - Updated with additional relationships  
3. **Licensee** - Enhanced with all legacy relationships
4. **License** - Already migrated
5. **Event** - Updated with inheritance hierarchy and legacy features
6. **Applicant** - ✨ Newly migrated with all legacy fields
7. **Arrow** - ✨ Newly migrated with owner relationship
8. **Bow** - ✨ Newly migrated with sight adjustments
9. **SightAdjustment** - ✨ Newly migrated
10. **Group** - ✨ Newly migrated with licensee relationships
11. **PracticeAdvice** - ✨ Newly migrated 
12. **EventParticipation** - ✨ Newly migrated with state management
13. **Result** - ✨ Newly migrated with comprehensive scoring
14. **ContestEvent** - ✨ Newly migrated (Event subclass)
15. **TrainingEvent** - ✨ Newly migrated (Event subclass)
16. **FreeTrainingEvent** - ✨ Newly migrated (Event subclass)

### 🎯 Key Improvements Made

#### 1. **API Platform Integration**
- All entities now have proper `#[ApiResource]` annotations
- RESTful endpoints automatically generated
- Nested resource relationships (e.g., `/users/{id}/licensees`)
- Schema.org semantic annotations for better API documentation

#### 2. **Modern PHP Features**
- **Enums**: Replaced old DBAL enum types with native PHP 8.1+ enums
- **Strong Typing**: All properties are properly typed
- **Constructor Property Promotion**: Cleaner code structure
- **Attributes**: Modern PHP 8 attribute syntax throughout

#### 3. **Enhanced Data Modeling**
- **Proper Inheritance**: Event hierarchy with discriminator mapping
- **Comprehensive Relations**: All entity relationships properly mapped
- **Validation**: Symfony validation constraints on all entities
- **Timestamps**: Proper created/updated timestamp management

#### 4. **Database Optimizations**
- **Indexes**: Performance indexes on frequently queried fields
- **Foreign Keys**: Proper referential integrity
- **Sequences**: PostgreSQL-optimized ID generation

## 📋 Migration Process

### Step 1: Run Database Migration
```bash
cd api/
php bin/console doctrine:migrations:migrate
```

### Step 2: Update Legacy Data (if needed)
The migration command is ready but needs customization for your data source:

```bash
php bin/console app:migrate-legacy-data
```

**Note**: You'll need to implement the `fetchLegacyData()` method in `MigrateLegacyDataCommand.php` based on your legacy database connection.

### Step 3: Verify API Endpoints

Your new API will have these endpoints:

#### Core Resources
- `GET /api/users` - List users
- `GET /api/clubs` - List clubs  
- `GET /api/licensees` - List licensees
- `GET /api/events` - List events

#### Nested Resources
- `GET /api/users/{id}/licensees` - User's licensees
- `GET /api/clubs/{id}/groups` - Club's groups
- `GET /api/licensees/{id}/bows` - Licensee's bows
- `GET /api/licensees/{id}/arrows` - Licensee's arrows
- `GET /api/events/{id}/participations` - Event participations
- `GET /api/events/{id}/results` - Event results

## 🔧 Type System Migration

### Legacy Enums → PHP 8.1 Enums

| Legacy Type | New Enum | Values |
|-------------|----------|---------|
| `GenderType` | `GenderType` | Male, Female, Other |
| `DisciplineType` | `DisciplineType` | Target, Indoor, Field, Nature, 3D, Para, Run |
| `LicenseActivityType` | `LicenseActivityType` | AC, AD, BB, CL, CO, TL |
| `LicenseAgeCategoryType` | `LicenseAgeCategoryType` | U11, U13, U15, U18, U21, S1, S2, S3, P, B, M, C, J, S, V, SV |
| `ArrowType` | `ArrowType` | Wood, Aluminum, Carbon, AluminumCarbon |
| `BowType` | `BowType` | Recurve, Compound, Traditional, Barebow |
| `FletchingType` | `FletchingType` | Plastic, Spinwings |
| `TargetTypeType` | `TargetTypeType` | Monospot, Trispot, Field, Animal, Beursault |
| `ContestType` | `ContestType` | Federal, International, Challenge33, Individual, Team |
| `EventParticipationStateType` | `EventParticipationStateType` | NotGoing, Interested, Registered |
| `PracticeLevelType` | `PracticeLevelType` | Beginner, Intermediate, Advanced |

## 🏗️ Architecture Changes

### Old Structure (Legacy)
```
src/
├── Entity/           # Mixed legacy entities
├── Controller/       # Traditional controllers
├── Form/            # Symfony forms
├── DBAL/Types/      # Custom DBAL types
└── Twig/            # Template extensions
```

### New Structure (API Platform)
```
api/src/
├── Entity/          # Clean API Platform entities
├── Type/           # Modern PHP enums
├── Repository/     # Doctrine repositories  
├── Tool/           # Migration commands
└── Controller/     # API Platform controllers (minimal)
```

## 📊 Data Relationships

### Core Entity Graph
```
Club
├── Groups (1:n)
│   └── Licensees (n:m)
├── Events (1:n)
│   ├── Participations (1:n)
│   └── Results (1:n)
└── Users (1:n)
    └── Licensees (1:n)
        ├── Licenses (1:n)
        ├── Bows (1:n)
        │   └── SightAdjustments (1:n)
        ├── Arrows (1:n)
        ├── PracticeAdvices (1:n)
        ├── EventParticipations (1:n)
        └── Results (1:n)
```

## 🔒 Security Considerations

1. **API Access Control**: Implement proper authentication/authorization
2. **Data Validation**: All inputs validated via Symfony constraints
3. **Encrypted Fields**: Sensitive data (FFTA credentials) remain encrypted
4. **Audit Trail**: DH/Auditor bundle tracks all changes

## 🧪 Testing Strategy

### 1. Unit Tests
- Test all entity relationships
- Validate enum conversions
- Check data integrity

### 2. Integration Tests  
- Test API endpoints
- Verify CRUD operations
- Check filtering/pagination

### 3. Migration Tests
- Test legacy data import
- Verify data consistency
- Performance benchmarks

## 📈 Performance Optimizations

1. **Database Indexes**: Added on all foreign keys and frequently queried fields
2. **Eager Loading**: Proper associations for N+1 query prevention  
3. **Pagination**: API Platform automatic pagination
4. **Caching**: Redis/Memcached ready for entity caching

## 🚦 Next Steps

1. **Run the migration** using the provided commands
2. **Customize the migration command** for your specific legacy data source
3. **Test the API endpoints** using the auto-generated OpenAPI documentation
4. **Update your frontend** to use the new API endpoints
5. **Implement authentication** for API access control
6. **Add monitoring** for the new API performance

## 🆘 Troubleshooting

### Common Issues

1. **Foreign Key Constraints**: Ensure proper migration order
2. **Enum Value Mismatches**: Check legacy data for invalid enum values
3. **Missing Relations**: Verify all entity relationships are properly mapped
4. **Performance**: Add indexes for slow queries

### Support Commands

```bash
# Check doctrine schema
php bin/console doctrine:schema:validate

# Debug specific entity
php bin/console doctrine:mapping:info --filter=Licensee

# Clear cache
php bin/console cache:clear
```

## 📞 Need Help?

The migration structure is comprehensive and handles most legacy scenarios. If you encounter specific issues with your data, the migration command can be customized to handle edge cases.

**Happy migrating! 🏹**
