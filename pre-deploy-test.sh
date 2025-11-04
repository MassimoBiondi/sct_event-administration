#!/bin/bash
# pre-deploy-test.sh - Run tests before deployment
# Usage: ./pre-deploy-test.sh
# Checks PHP syntax and basic code quality

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}🧪 Running pre-deployment tests...${NC}"
echo ""

# Test 1: PHP Syntax Check
echo -e "${YELLOW}Test 1: PHP Syntax Check${NC}"
SYNTAX_ERRORS=0
while IFS= read -r -d '' file; do
    if ! php -l "$file" > /dev/null 2>&1; then
        echo -e "${RED}❌ Syntax error in: $file${NC}"
        php -l "$file"
        SYNTAX_ERRORS=$((SYNTAX_ERRORS + 1))
    fi
done < <(find . -name "*.php" -type f -not -path "./.git/*" -print0)

if [ $SYNTAX_ERRORS -eq 0 ]; then
    echo -e "${GREEN}✅ All PHP files have valid syntax${NC}"
else
    echo -e "${RED}❌ Found $SYNTAX_ERRORS PHP syntax errors${NC}"
    exit 1
fi
echo ""

# Test 2: Check for common mistakes
echo -e "${YELLOW}Test 2: Common Mistakes Check${NC}"
MISTAKES=0

# Check for console.log left in code
if grep -r "console\.log" --include="*.php" . > /dev/null 2>&1; then
    echo -e "${YELLOW}⚠️  Found console.log statements in PHP files${NC}"
    MISTAKES=$((MISTAKES + 1))
fi

# Check for var_dump left in code
if grep -r "var_dump\|print_r" --include="*.php" . --exclude-dir=".git" 2>/dev/null | grep -v "// " | grep -v "/\*" > /dev/null 2>&1; then
    echo -e "${YELLOW}⚠️  Found debug output (var_dump/print_r) in code${NC}"
    MISTAKES=$((MISTAKES + 1))
fi

# Check for TODO/FIXME comments
if grep -r "TODO\|FIXME" --include="*.php" . --exclude-dir=".git" 2>/dev/null; then
    echo -e "${YELLOW}⚠️  Found TODO/FIXME comments in code${NC}"
fi

if [ $MISTAKES -eq 0 ]; then
    echo -e "${GREEN}✅ No common mistakes found${NC}"
fi
echo ""

# Test 3: Check git status
echo -e "${YELLOW}Test 3: Git Status Check${NC}"
if git diff-index --quiet HEAD --; then
    echo -e "${GREEN}✅ No uncommitted changes${NC}"
else
    echo -e "${RED}❌ Uncommitted changes detected:${NC}"
    git status --short
    exit 1
fi
echo ""

# Test 4: Check for required files
echo -e "${YELLOW}Test 4: Required Files Check${NC}"
REQUIRED_FILES=(
    "sct_event-administraion.php"
    "includes/class-event-public.php"
    "includes/class-event-admin.php"
)

MISSING_FILES=0
for file in "${REQUIRED_FILES[@]}"; do
    if [ ! -f "$file" ]; then
        echo -e "${RED}❌ Missing required file: $file${NC}"
        MISSING_FILES=$((MISSING_FILES + 1))
    fi
done

if [ $MISSING_FILES -eq 0 ]; then
    echo -e "${GREEN}✅ All required files present${NC}"
else
    echo -e "${RED}❌ Missing $MISSING_FILES required files${NC}"
    exit 1
fi
echo ""

# Test 5: Check version consistency
echo -e "${YELLOW}Test 5: Version Consistency Check${NC}"
if [ -f "sct_event-administraion.php" ]; then
    VERSION=$(grep "Version:" sct_event-administraion.php | head -1 | sed 's/.*Version: //' | sed 's/ .*//')
    echo -e "${GREEN}✅ Plugin version: $VERSION${NC}"
fi
echo ""

echo -e "${GREEN}✅ All pre-deployment tests passed!${NC}"
echo -e "${BLUE}Ready to deploy.${NC}"
