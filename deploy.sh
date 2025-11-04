#!/bin/bash
# deploy.sh - Simple deployment script
# Place this in the root of your sct_event-administration repository
# Usage: ./deploy.sh

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}🚀 Starting deployment...${NC}"

# Check if we're in a git repository
if [ ! -d ".git" ]; then
    echo -e "${RED}❌ Not in a git repository!${NC}"
    exit 1
fi

# Get current branch
BRANCH=$(git rev-parse --abbrev-ref HEAD)
if [ "$BRANCH" != "master" ]; then
    echo -e "${YELLOW}⚠️  Warning: Not on master branch (current: $BRANCH)${NC}"
    read -p "Continue? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

# Check for uncommitted changes
if ! git diff-index --quiet HEAD --; then
    echo -e "${RED}❌ Uncommitted changes detected!${NC}"
    git status
    exit 1
fi

# Get version from composer.json or git tag
VERSION=$(git describe --tags --always 2>/dev/null || echo "dev")

echo -e "${GREEN}✅ Pushing to GitHub...${NC}"
git push origin master

echo -e "${GREEN}✅ Pushing tags...${NC}"
git push origin --tags 2>/dev/null || true

# Deploy to production server
echo -e "${GREEN}✅ Deploying to production server...${NC}"

# CONFIGURE THIS WITH YOUR SERVER DETAILS
SERVER_USER="user"
SERVER_HOST="events.swissclubtokyo.com"
PLUGIN_PATH="/var/www/html/wp-content/plugins/sct_event-administration"

# Deploy via SSH
ssh "$SERVER_USER@$SERVER_HOST" "cd $PLUGIN_PATH && git pull origin master && echo '✅ Deployed successfully!'"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Deployment complete! (v$VERSION)${NC}"
else
    echo -e "${RED}❌ Deployment failed!${NC}"
    exit 1
fi
