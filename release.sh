#!/bin/bash
# release.sh - Create a versioned release
# Usage: ./release.sh 2.10.8
# This script:
# 1. Updates version in plugin file
# 2. Commits changes
# 3. Creates git tag
# 4. Pushes to GitHub

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

if [ -z "$1" ]; then
    echo -e "${RED}❌ Usage: ./release.sh <version>${NC}"
    echo "Example: ./release.sh 2.10.8"
    exit 1
fi

VERSION="$1"
MAIN_FILE="sct_event-administraion.php"  # Note: Spelling matches your file

# Validate version format
if ! [[ "$VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    echo -e "${RED}❌ Invalid version format. Use semantic versioning (e.g., 2.10.8)${NC}"
    exit 1
fi

echo -e "${YELLOW}📦 Releasing version $VERSION...${NC}"

# Update version in main plugin file
if [ -f "$MAIN_FILE" ]; then
    sed -i '' "s/Version: [0-9.]\+/Version: $VERSION/" "$MAIN_FILE"
    echo -e "${GREEN}✅ Updated version in $MAIN_FILE${NC}"
fi

# Update version in composer.json if it exists
if [ -f "composer.json" ]; then
    sed -i '' "s/\"version\": \"[0-9.]*\"/\"version\": \"$VERSION\"/" composer.json
    echo -e "${GREEN}✅ Updated version in composer.json${NC}"
fi

# Commit changes
git add -A
git commit -m "Release v$VERSION"
echo -e "${GREEN}✅ Created commit${NC}"

# Create tag
git tag -a "v$VERSION" -m "Release version $VERSION"
echo -e "${GREEN}✅ Created git tag v$VERSION${NC}"

# Push to GitHub
git push origin master
git push origin "v$VERSION"
echo -e "${GREEN}✅ Pushed to GitHub${NC}"

echo -e "${GREEN}✅ Release v$VERSION complete!${NC}"
echo ""
echo "GitHub Release URL:"
echo "https://github.com/MassimoBiondi/sct_event-administration/releases/tag/v$VERSION"
