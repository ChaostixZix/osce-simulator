# PRD: Appwrite Migration File Creation Rules & Guidelines

## Background & Rationale

The OSCE Simulator project is migrating from SQLite/Redis to Appwrite database. During migration testing, we discovered critical limitations with Appwrite collections that require specific rules and constraints when creating migration files. This task creates comprehensive guidelines for developers to create efficient Appwrite migrations while respecting platform limitations.

**Referenced files/routes/models:**
- `/database/migrations/appwrite/` - Migration directory
- `app/Services/AppwriteService.php` - Service for Appwrite operations  
- `config/appwrite.php` - Appwrite configuration
- SQLite schema with 30+ tables requiring migration

## Objectives & Non-Goals

### Objectives
1. Create comprehensive rules for Appwrite migration file creation
2. Define attribute limits and size constraints per collection
3. Establish naming conventions for collections and attributes
4. Provide template structure for migration files
5. Document relationship handling between collections
6. Create validation checklist for migration files

### Non-Goals
- Actual data migration implementation
- Performance optimization of existing queries
- UI/frontend changes

## Architecture & Data Model

### Appwrite Limitations Discovered
- **Maximum attributes per collection**: ~8-12 attributes (varies by attribute size)
- **String attribute size limits**: Large text fields (>10KB) count heavily against limits
- **Required vs Optional conflict**: Cannot set default values for required attributes

### Migration File Structure
```php
<?php
use App\Appwrite\Migrations\Migration;
use App\Services\AppwriteService;
use Appwrite\Enums\IndexType;

return new class extends Migration
{
    public function up(AppwriteService $appwrite): void
    {
        $databaseId = config('appwrite.database_id');
        $collectionId = 'collection_name';
        
        $appwrite->ensureCollection($databaseId, $collectionId, 'Display Name');
        // Attributes and indexes
    }
    
    public function down(AppwriteService $appwrite): void
    {
        $databaseId = config('appwrite.database_id');
        $appwrite->deleteCollection($databaseId, 'collection_name');
    }
};
```

### Collection Design Patterns
1. **Core Collections**: Essential attributes only (max 8 attributes)
2. **Extended Data Collections**: Additional attributes linked by reference
3. **Reference Pattern**: Use string IDs instead of foreign keys

## API/Routes & Controllers

### AppwriteService Methods Usage
- `ensureCollection($databaseId, $collectionId, $name)` - Create collection
- `ensureStringAttribute($databaseId, $collectionId, $key, $size, $required)` - String fields
- `ensureIntegerAttribute($databaseId, $collectionId, $key, $required, $min, $max)` - Integer fields  
- `ensureDatetimeAttribute($databaseId, $collectionId, $key, $required)` - DateTime fields
- `ensureIndex($databaseId, $collectionId, $key, $type, $attributes)` - Create indexes

### Migration Commands
- `php artisan appwrite:migrate` - Run all pending migrations
- `php artisan appwrite:migrate test` - Test connectivity and show stats

## Rules & Constraints

### 1. Collection Naming Rules
- Use snake_case for collection IDs
- Max 32 characters for collection ID
- Use descriptive display names
- Examples: `osce_sessions`, `medical_tests`, `user_profiles`

### 2. Attribute Limits & Guidelines
- **Maximum 8 core attributes per collection**
- **String attributes**:
  - Small text: max 255 chars
  - Medium text: max 2000 chars  
  - Large text: max 5000 chars (use sparingly)
  - Avoid >10KB strings in core collections
- **Required attributes**: Never set default values (causes conflict)
- **Optional attributes**: Can have default values

### 3. Data Type Guidelines
- Use `string` for IDs (not integer references)
- Store decimals as strings (e.g., prices, costs)
- Use `integer` with min/max for enums and flags
- DateTime for timestamps (not unix timestamps)

### 4. Index Strategy
- Create unique indexes for natural keys
- Use compound indexes for common query patterns
- Index foreign key references
- Maximum 5 indexes per collection

### 5. Collection Split Strategy
When SQLite table has >8 essential attributes:
- **Core collection**: ID + 6-7 most critical attributes
- **Extended collection**: Additional attributes with reference to core
- **Example**: `osce_cases` → `osce_cases` + `case_ai_patient_data` + `case_test_categories`

### 6. Migration File Naming
- Format: `YYYY_MM_DD_HHMMSS_create_collection_name_collection.php`
- Order by dependencies (referenced collections first)
- Use sequential timestamps to avoid conflicts

### 7. Error Prevention Rules
- Always test with `php artisan appwrite:migrate test` first
- Create smallest viable collection first, then extend
- Never exceed 8 attributes in initial version
- Validate all attribute names are valid (no reserved words)

## Code Examples

### Minimal Collection Template
```php
// Essential attributes only - maximum 8 total
$appwrite->ensureStringAttribute($databaseId, $collectionId, 'name', 255, true);
$appwrite->ensureStringAttribute($databaseId, $collectionId, 'status', 50, false);
$appwrite->ensureIntegerAttribute($databaseId, $collectionId, 'is_active', false, 0, 1, 1);
$appwrite->ensureDatetimeAttribute($databaseId, $collectionId, 'created_at', false);

// Essential indexes
$appwrite->ensureIndex($databaseId, $collectionId, 'name_unique', IndexType::UNIQUE(), ['name']);
$appwrite->ensureIndex($databaseId, $collectionId, 'status_active_index', IndexType::KEY(), ['status', 'is_active']);
```

### Extended Data Pattern
```php
// Core collection: users (8 attributes max)
$appwrite->ensureCollection($databaseId, 'users', 'Users');
$appwrite->ensureStringAttribute($databaseId, 'users', 'name', 255, true);
$appwrite->ensureStringAttribute($databaseId, 'users', 'email', 255, true);
// ... max 6 more attributes

// Extended collection: user_profiles  
$appwrite->ensureCollection($databaseId, 'user_profiles', 'User Profiles');
$appwrite->ensureStringAttribute($databaseId, 'user_profiles', 'user_id', 50, true);
$appwrite->ensureStringAttribute($databaseId, 'user_profiles', 'bio', 5000, false);
// ... additional profile data
```

## Validation & Error Handling

### Pre-Migration Checklist
- [ ] Collection ID follows naming conventions
- [ ] Total attributes ≤ 8 for core collections
- [ ] No default values on required attributes  
- [ ] String sizes appropriate for content
- [ ] Indexes cover common query patterns
- [ ] Dependencies created in correct order

### Common Errors & Solutions
- **"Maximum attributes reached"**: Split into multiple collections
- **"Cannot set default for required"**: Remove default or make optional
- **"Collection already exists"**: Use unique collection names or handle existing

## Acceptance Criteria

### Comprehensive Rule Documentation
- [ ] All 7 rule categories documented with examples
- [ ] Attribute limit guidelines with size recommendations
- [ ] Collection splitting strategy with real examples
- [ ] Error prevention checklist

### Migration Template & Examples  
- [ ] Working template file structure
- [ ] Core collection example (≤8 attributes)
- [ ] Extended collection pattern example
- [ ] Index creation examples

### Validation Tools
- [ ] Pre-migration validation checklist
- [ ] Common error troubleshooting guide
- [ ] Testing procedure documentation

### Developer Guidelines
- [ ] Step-by-step migration creation process
- [ ] Naming convention standards
- [ ] Dependency ordering guidelines
- [ ] Code review checklist for migrations

## Test Plan

### Migration File Validation
- Create test migration with 8 attributes → should succeed
- Create test migration with 12 attributes → should fail
- Test required attribute with default value → should fail  
- Test proper collection splitting → should succeed

### Manual Testing Procedures
1. Create migration following all rules
2. Run `php artisan appwrite:migrate test`
3. Execute migration with `php artisan appwrite:migrate`
4. Verify collections created in Appwrite console
5. Test rollback with down() method

### Edge Case Testing
- Very long collection names
- Reserved keyword attribute names
- Maximum size string attributes
- Complex index combinations

## Rollout & Risks

### Migration Order
1. Create rule documentation
2. Update existing migration files to follow rules
3. Test all migrations in development
4. Deploy to staging for validation
5. Production migration with backups

### Potential Risks
- **Data loss**: Attribute limits may require field consolidation
- **Performance impact**: Multiple collections instead of joins
- **Development complexity**: More collections to manage

### Fallback Strategy
- Keep SQLite as backup during transition
- Document field mapping between SQLite and Appwrite
- Rollback procedure to revert to SQLite if needed

### Mitigation
- Comprehensive testing in development
- Staged rollout with monitoring
- Clear documentation of all changes
- Developer training on new patterns