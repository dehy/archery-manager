#!/bin/bash

# Test runner script for API Platform Archery Manager
# Usage: ./run-tests.sh [test-type]
# test-type: unit, api, functional, validation, all (default)

cd /app

echo "🏹 Running API Platform Archery Manager Tests"
echo "============================================="

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test type parameter (default: all)
TEST_TYPE=${1:-all}

# Function to run specific test suite
run_test_suite() {
    local suite=$1
    local description=$2
    
    echo -e "\n${YELLOW}📋 Running $description tests...${NC}"
    
    if [ "$suite" = "all" ]; then
        vendor/bin/simple-phpunit --testdox
    else
        vendor/bin/simple-phpunit tests/$suite --testdox
    fi
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ $description tests passed!${NC}"
        return 0
    else
        echo -e "${RED}❌ $description tests failed!${NC}"
        return 1
    fi
}

# Database setup for tests
echo -e "${YELLOW}🔧 Setting up test database...${NC}"
php bin/console doctrine:database:drop --force --env=test --quiet || true
php bin/console doctrine:database:create --env=test --quiet
php bin/console doctrine:migrations:migrate --no-interaction --env=test --quiet

# Run tests based on type
case $TEST_TYPE in
    "unit")
        run_test_suite "Unit" "Unit"
        ;;
    "api")
        run_test_suite "Api" "API Integration"
        ;;
    "functional")
        run_test_suite "Functional" "Functional"
        ;;
    "validation")
        run_test_suite "Validation" "Validation"
        ;;
    "all")
        echo -e "${YELLOW}🚀 Running all test suites...${NC}"
        
        # Track overall success
        OVERALL_SUCCESS=0
        
        run_test_suite "Unit" "Unit" || OVERALL_SUCCESS=1
        run_test_suite "Api" "API Integration" || OVERALL_SUCCESS=1
        run_test_suite "Functional" "Functional" || OVERALL_SUCCESS=1
        run_test_suite "Validation" "Validation" || OVERALL_SUCCESS=1
        
        echo -e "\n============================================="
        if [ $OVERALL_SUCCESS -eq 0 ]; then
            echo -e "${GREEN}🎉 All tests passed! Your archery app is ready to shoot! 🏹${NC}"
        else
            echo -e "${RED}💥 Some tests failed. Check the output above for details.${NC}"
            exit 1
        fi
        ;;
    *)
        echo -e "${RED}❌ Unknown test type: $TEST_TYPE${NC}"
        echo "Usage: $0 [unit|api|functional|validation|all]"
        exit 1
        ;;
esac
