#!/bin/bash

# =============================================================================
# HARVESTA - Azure Deployment Script (Simplified)
# =============================================================================
# Deployment via Azure Portal (manual) karena CLI ada authorization issues
# Script ini hanya untuk persiapan dan dokumentasi commands
# =============================================================================

set -e

echo "======================================"
echo "HARVESTA - Azure Deployment Prep"
echo "======================================"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
RESOURCE_GROUP="harvesta-rg"
ACR_NAME="harvestaacr"
APP_SERVICE_PLAN="harvesta-plan"
WEB_APP_NAME="harvesta-its"
LOCATION="westus"
IMAGE_NAME="harvesta"
IMAGE_TAG="latest"

echo -e "${YELLOW}Configuration:${NC}"
echo "  Resource Group: $RESOURCE_GROUP"
echo "  Location: $LOCATION"
echo "  ACR Name: $ACR_NAME"
echo "  Web App: $WEB_APP_NAME"
echo ""

# Check prerequisites
echo -e "${YELLOW}Checking prerequisites...${NC}"

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo -e "${RED}✗ Docker is not running${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Docker is running${NC}"

# Check if az CLI is available
if ! command -v az &> /dev/null; then
    echo -e "${RED}✗ Azure CLI not found${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Azure CLI is installed${NC}"

# Check git status
if [[ -n $(git status -s) ]]; then
    echo -e "${YELLOW}⚠ Warning: You have uncommitted changes${NC}"
    git status -s
fi
echo ""

echo -e "${YELLOW}======================================"
echo "DEPLOYMENT STEPS (MANUAL VIA PORTAL)"
echo "======================================${NC}"
echo ""

echo "Since Azure CLI has authorization issues, follow these steps in Azure Portal:"
echo ""

echo -e "${GREEN}STEP 1: Create Resource Group${NC}"
echo "  1. Go to: https://portal.azure.com"
echo "  2. Search 'Resource groups' → Create"
echo "  3. Name: $RESOURCE_GROUP"
echo "  4. Region: West US"
echo ""

echo -e "${GREEN}STEP 2: Create Container Registry (ACR)${NC}"
echo "  1. Search 'Container registries' → Create"
echo "  2. Name: $ACR_NAME"
echo "  3. Resource group: $RESOURCE_GROUP"
echo "  4. Location: West US"
echo "  5. SKU: Basic"
echo "  6. After created: Settings → Access keys → Enable 'Admin user'"
echo ""

echo -e "${GREEN}STEP 3: Build & Push Docker Image${NC}"
echo "  After ACR is created, run these commands:"
echo ""
echo -e "${YELLOW}  # Login to ACR${NC}"
echo "  az acr login --name $ACR_NAME"
echo ""
echo -e "${YELLOW}  # Build image in Azure (cloud build)${NC}"
echo "  az acr build --registry $ACR_NAME --image $IMAGE_NAME:$IMAGE_TAG --image $IMAGE_NAME:v1.0.0 ."
echo ""
echo "  This will:"
echo "    - Upload source code to Azure"
echo "    - Build Docker image in the cloud (no local build needed!)"
echo "    - Push to ACR automatically"
echo "    - Takes ~5-10 minutes"
echo ""

echo -e "${GREEN}STEP 4: Create App Service Plan${NC}"
echo "  1. Search 'App Service plans' → Create"
echo "  2. Name: $APP_SERVICE_PLAN"
echo "  3. OS: Linux"
echo "  4. Region: West US"
echo "  5. Pricing: Basic B1 (~\$13/month)"
echo ""

echo -e "${GREEN}STEP 5: Create Web App${NC}"
echo "  1. Search 'App Services' → Create → Web App"
echo "  2. Name: $WEB_APP_NAME"
echo "  3. Publish: Container"
echo "  4. OS: Linux"
echo "  5. Region: West US"
echo "  6. Plan: $APP_SERVICE_PLAN"
echo "  7. Container tab:"
echo "     - Image Source: Azure Container Registry"
echo "     - Registry: $ACR_NAME"
echo "     - Image: $IMAGE_NAME"
echo "     - Tag: $IMAGE_TAG"
echo ""

echo -e "${GREEN}STEP 6: Configure Environment Variables${NC}"
echo "  1. Web App → Environment variables → Advanced edit"
echo "  2. Paste JSON from AZURE_DEPLOY_GUIDE.md (Section 'Option B')"
echo "  3. Click Apply → Confirm"
echo ""

echo -e "${GREEN}STEP 7: Monitor & Test${NC}"
echo "  1. Web App → Monitoring → Log stream"
echo "  2. Wait 5-10 minutes for first boot"
echo "  3. Test: https://$WEB_APP_NAME.azurewebsites.net"
echo "  4. Login: admin@example.com / Hilmy080700!"
echo ""

echo -e "${YELLOW}======================================"
echo "ENVIRONMENT VARIABLES SUMMARY"
echo "======================================${NC}"
echo ""
echo "You need to set these in Azure Portal:"
echo ""
echo "APP_KEY=base64:6l6p6p8YUj7oLOjLBByFeiyHYrXtkWj/pzuFdU8M6uE="
echo ""
echo "Database (Supabase):"
echo "  DB_HOST=aws-0-ap-southeast-1.pooler.supabase.com"
echo "  DB_PORT=5432"
echo "  DB_DATABASE=postgres"
echo "  DB_USERNAME=postgres.sbwgznlzluoyvguopzqu"
echo "  DB_PASSWORD=FG8P0A3tu0gMAKO2"
echo ""
echo "Storage (Supabase S3):"
echo "  PHOTO_S3_ACCESS_KEY_ID=1edb28d92c2be89b5b18baeb89611067"
echo "  PHOTO_S3_SECRET_ACCESS_KEY=2042ecf8a874a4ad8edb48214f306405bfaf01d22dbca7eafb52e597595f58bd"
echo ""
echo "Full list: See AZURE_DEPLOY_GUIDE.md"
echo ""

echo -e "${YELLOW}======================================"
echo "READY TO DEPLOY"
echo "======================================${NC}"
echo ""
echo "Next steps:"
echo "  1. Open Azure Portal: https://portal.azure.com"
echo "  2. Follow steps above to create resources"
echo "  3. After ACR is ready, come back and run:"
echo ""
echo -e "${GREEN}     az acr login --name $ACR_NAME${NC}"
echo -e "${GREEN}     az acr build --registry $ACR_NAME --image $IMAGE_NAME:$IMAGE_TAG .${NC}"
echo ""
echo "  4. Continue with Web App setup in Portal"
echo ""
echo "Full guide: AZURE_DEPLOY_GUIDE.md"
echo ""
echo -e "${GREEN}Good luck! 🚀${NC}"
