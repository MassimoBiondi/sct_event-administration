# 🚀 Updated Deployment Strategy - Quick Start

## What Changed?

**Old workflow (❌ Manual):**
1. Make changes locally
2. Create ZIP manually
3. Upload via browser to server
4. Extract and replace files
5. Test

**New workflow (✅ Automated):**
1. Make changes locally
2. Git push
3. One command: `./deploy.sh`
4. Done!

---

## Getting Started (5 minutes)

### 1. Configure Your Server Details

Edit `deploy.sh` and update these lines:
```bash
SERVER_USER="user"                    # Your SSH user
SERVER_HOST="events.swissclubtokyo.com"  # Your server hostname
PLUGIN_PATH="/var/www/html/wp-content/plugins/sct_event-administration"
```

### 2. Set Up SSH Key Authentication (One-time)
```bash
# Generate SSH key locally (if you don't have one)
ssh-keygen -t ed25519 -C "your-email@example.com"

# Copy key to server
ssh-copy-id -i ~/.ssh/id_ed25519.pub user@events.swissclubtokyo.com

# Test connection (should not ask for password)
ssh user@events.swissclubtokyo.com "echo 'SSH works!'"
```

### 3. Clone Repository on Server (One-time)
```bash
# SSH into your server
ssh user@events.swissclubtokyo.com

# Navigate to plugins directory
cd /var/www/html/wp-content/plugins

# Remove old plugin directory if it exists
rm -rf sct_event-administration

# Clone the repository
git clone https://github.com/MassimoBiondi/sct_event-administration.git
cd sct_event-administration

# Done! Plugin is now git-controlled
```

---

## Daily Workflow

### Making a Change
```bash
cd sct_event-administration
git checkout -b feature/my-feature

# Make changes...
# Test locally...

git add .
git commit -m "Feature: Add new functionality"
git push origin feature/my-feature

# Open PR on GitHub for code review
# Merge PR to master
```

### Deploying to Production
```bash
# Make sure everything is committed
git status

# Deploy!
./deploy.sh

# That's it! Your changes are live
```

---

## Creating a Release (for Version Bumping)

When you want to mark an official release:

```bash
# Create and push a release
./release.sh 2.10.9

# This automatically:
# 1. Updates version in plugin file
# 2. Creates a git commit
# 3. Creates a git tag
# 4. Pushes to GitHub
# 5. GitHub Actions creates a ZIP backup
```

You'll see output like:
```
✅ Deployment complete! (v2.10.9)

GitHub Release URL:
https://github.com/MassimoBiondi/sct_event-administration/releases/tag/v2.10.9
```

---

## Testing Before Deployment

Run tests to catch issues early:

```bash
# Before pushing, run tests
./pre-deploy-test.sh

# Checks:
# ✅ PHP syntax errors
# ✅ Common mistakes (console.log, var_dump, etc)
# ✅ Git status clean
# ✅ Required files present
# ✅ Version consistency
```

---

## Troubleshooting

### Deploy fails with "Permission denied"
```bash
# Fix SSH access
ssh-copy-id -i ~/.ssh/id_ed25519.pub user@events.swissclubtokyo.com

# Test again
./deploy.sh
```

### "Not on master branch" warning
```bash
# Make sure you're on master before deploying
git checkout master
git pull origin master
./deploy.sh
```

### Changes not showing up on server
```bash
# SSH to server and check
ssh user@events.swissclubtokyo.com
cd /var/www/html/wp-content/plugins/sct_event-administration
git log --oneline -5  # See recent commits

# Manually pull if needed
git pull origin master
```

### Rollback to previous version
```bash
# On server
ssh user@events.swissclubtokyo.com
cd /var/www/html/wp-content/plugins/sct_event-administration

# See previous versions
git log --oneline

# Roll back to specific version
git checkout 1a2b3c4

# Or rollback just one commit
git revert HEAD
git push origin master
```

---

## GitHub Actions Automatic Backups

Every time you create a release (using `./release.sh`), GitHub automatically:
1. Creates a ZIP file
2. Uploads it to GitHub Releases
3. Keeps it for 30 days

**View backups:** https://github.com/MassimoBiondi/sct_event-administration/releases

---

## Cleanup Tasks

### Remove Old ZIP Files
You can now safely delete all the old ZIP files cluttering your plugins directory:

```bash
cd /var/www/html/wp-content/plugins

# Remove old plugin ZIPs (keep only git-cloned version)
rm -f sct_event-administration*.zip
rm -f sct-forms*.zip
rm -f sct_title_excerpt*.zip

# Done! Much cleaner
```

### Add to .gitignore
Create/update `.gitignore` in plugin root:
```
# Don't track zip files anymore
*.zip
*.bak
*.log

# Development
.DS_Store
.vscode/
.idea/
node_modules/
vendor/

# Secrets
.env
.env.local
```

---

## Comparison: Before vs After

### Before (Manual)
```
Create change → Manual ZIP → Browser upload → Server extraction → 4-5 min
```

### After (Automated)
```
Create change → Git push → ./deploy.sh → Automatic pull → 30 seconds
```

### Benefits of Git-based Deployment

| Benefit | Before | After |
|---------|--------|-------|
| Deployment time | 4-5 min | 30 sec |
| Version history | Manual backups | Full git history |
| Rollback | Restore from backup | `git revert` |
| Multiple plugins | Manual for each | Works for all |
| Team collaboration | Share ZIP files | Native git workflow |
| Staging → Production | Manual copy | Same script |
| Accidental changes | No recovery | Full commit history |
| Storage | Many ZIP files | Minimal (git) |

---

## Next Steps

1. **This week**: 
   - [ ] Configure server details in `deploy.sh`
   - [ ] Set up SSH key authentication
   - [ ] Clone repository on server
   - [ ] Test one deployment

2. **Next week**:
   - [ ] Remove old ZIP files
   - [ ] Update `.gitignore`
   - [ ] Document process for your team

3. **Later**:
   - [ ] Set up other plugins (sct-forms, sct_voting, etc.) the same way
   - [ ] Add staging server for testing
   - [ ] Add automated testing (PHPUnit)

---

## Support & Questions

If you need help:
1. Check GitHub: https://github.com/MassimoBiondi/sct_event-administration
2. Review deployment logs: `ssh user@server.com "cd /path && git log"`
3. Test locally first: Make changes, run `pre-deploy-test.sh`

---

**You're ready to deploy! 🚀**

```bash
./deploy.sh
```
