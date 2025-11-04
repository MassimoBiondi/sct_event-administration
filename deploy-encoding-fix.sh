#!/bin/bash

# Email Encoding Fix Deployment Script
# Deploys the must-use plugin to force 8bit email encoding on production

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Email Encoding Fix - Deployment${NC}"
echo "=================================="
echo ""

# Check if we're in the right directory
if [ ! -f "sct_event-administraion.php" ]; then
    echo -e "${RED}Error: Not in plugin directory${NC}"
    echo "Run this script from the plugin root directory"
    exit 1
fi

# Get the WordPress root directory
WP_ROOT=$(cd ../../.. && pwd)
MU_PLUGINS_DIR="$WP_ROOT/wp-content/mu-plugins"

echo -e "${YELLOW}WordPress Root:${NC} $WP_ROOT"
echo -e "${YELLOW}MU-Plugins Directory:${NC} $MU_PLUGINS_DIR"
echo ""

# Create mu-plugins directory if it doesn't exist
if [ ! -d "$MU_PLUGINS_DIR" ]; then
    echo -e "${YELLOW}Creating mu-plugins directory...${NC}"
    mkdir -p "$MU_PLUGINS_DIR"
    echo -e "${GREEN}✓ Created${NC}"
fi

# Copy the must-use plugin
if [ -f "wp-content/mu-plugins/email-encoding-force.php" ]; then
    echo -e "${YELLOW}Deploying must-use plugin...${NC}"
    cp "wp-content/mu-plugins/email-encoding-force.php" "$MU_PLUGINS_DIR/email-encoding-force.php"
    chmod 644 "$MU_PLUGINS_DIR/email-encoding-force.php"
    echo -e "${GREEN}✓ Deployed${NC}"
    echo ""
    echo -e "${GREEN}File Location:${NC} $MU_PLUGINS_DIR/email-encoding-force.php"
else
    echo -e "${RED}Error: Must-use plugin not found in wp-content/mu-plugins/email-encoding-force.php${NC}"
    exit 1
fi

echo ""
echo -e "${GREEN}✓ Deployment Complete${NC}"
echo ""
echo "Next steps:"
echo "1. Verify file exists: ls -l $MU_PLUGINS_DIR/email-encoding-force.php"
echo "2. Send a test registration email"
echo "3. Check email headers for 'Content-Transfer-Encoding: 8bit'"
echo "4. If still quoted-printable, contact hosting provider"
