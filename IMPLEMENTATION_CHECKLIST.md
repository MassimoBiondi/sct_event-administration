# 📋 Implementation Checklist - Modern Deployment

## Phase 1: Local Setup (30 minutes)

- [ ] Review `DEPLOYMENT_STRATEGY.md` for full context
- [ ] Ensure you have Git installed: `git --version`
- [ ] Ensure SSH key pair exists: `ls ~/.ssh/id_*.pub`
- [ ] Repository is on GitHub: https://github.com/MassimoBiondi/sct_event-administration
- [ ] Local changes are committed: `git status`

## Phase 2: Server Setup (30 minutes)

### Configure SSH Access
- [ ] Add SSH public key to server: `ssh-copy-id user@server.com`
- [ ] Test SSH access: `ssh user@server.com "echo OK"`
- [ ] Confirm no password prompt required

### Prepare Server Environment
```bash
# SSH to server
ssh user@server.com

# Create backup of current plugin
cd /var/www/html/wp-content/plugins
cp -r sct_event-administration sct_event-administration.backup.$(date +%Y%m%d)

# Remove old plugin (it's backed up)
rm -rf sct_event-administration

# Clone from GitHub
git clone https://github.com/MassimoBiondi/sct_event-administration.git

# Verify
cd sct_event-administration
git log --oneline -3
```

**Checklist:**
- [ ] Backup created
- [ ] Repository cloned
- [ ] Plugin files present
- [ ] No errors during clone

## Phase 3: Configure Deploy Script (15 minutes)

### Update `deploy.sh`
```bash
# Edit deploy.sh in your local plugin directory
nano wp-content/plugins/sct_event-administration/deploy.sh
```

Find and update:
```bash
SERVER_USER="your-username"
SERVER_HOST="events.swissclubtokyo.com"
PLUGIN_PATH="/var/www/html/wp-content/plugins/sct_event-administration"
```

**Checklist:**
- [ ] SERVER_USER matches SSH username
- [ ] SERVER_HOST is correct domain/IP
- [ ] PLUGIN_PATH matches server path
- [ ] Script saved

## Phase 4: Test Deployment (20 minutes)

### Test with Dry Run
```bash
# Go to plugin directory
cd wp-content/plugins/sct_event-administration

# Run tests
./pre-deploy-test.sh

# Should output:
# ✅ All pre-deployment tests passed!
```

**Checklist:**
- [ ] Pre-deploy tests pass
- [ ] No uncommitted changes
- [ ] All PHP files valid

### Test Deploy Script
```bash
# Make a harmless test change
echo "# Test" >> README.md
git add README.md
git commit -m "test: Deploy script test"
git push origin master

# Run deploy
./deploy.sh

# Check server
ssh user@server.com "cd /var/www/html/wp-content/plugins/sct_event-administration && git log --oneline -1"

# Should show your "test: Deploy script test" commit

# Revert test
git revert HEAD
git push origin master
./deploy.sh
```

**Checklist:**
- [ ] Deploy script runs without errors
- [ ] Changes appear on server
- [ ] Revert works correctly

## Phase 5: Version Control Workflow (Optional but Recommended)

### Create Release Branch Strategy
```bash
# Create a release for current code
./release.sh 2.10.9

# Verify release on GitHub
# https://github.com/MassimoBiondi/sct_event-administration/releases
```

**Checklist:**
- [ ] Release created on GitHub
- [ ] ZIP file generated automatically
- [ ] Version bumped in plugin file

## Phase 6: Cleanup

### Remove Old ZIP Files
```bash
# On your server, remove old ZIP backups
ssh user@server.com

cd /var/www/html/wp-content/plugins

# List old files
ls -la *.zip

# Remove them (they're backed up in Git now)
rm -f sct_event-administration*.zip
rm -f sct-forms*.zip
```

**Checklist:**
- [ ] Old ZIP files removed
- [ ] Directory is clean
- [ ] Git history preserved

### Update Documentation
- [ ] Add deployment instructions to team docs
- [ ] Share with team members who deploy
- [ ] Create cheat sheet for common tasks

## Phase 7: Daily Operations

### Regular Deployment
```bash
# Make changes locally
git add .
git commit -m "Feature: Description of change"
git push origin master

# Deploy to production
./deploy.sh
```

### Creating Releases
```bash
# When ready to mark official version
./release.sh 2.11.0
```

### If Something Goes Wrong
```bash
# View what changed
ssh user@server.com "cd /var/www/html/wp-content/plugins/sct_event-administration && git diff HEAD~1"

# Rollback one commit
ssh user@server.com "cd /var/www/html/wp-content/plugins/sct_event-administration && git revert HEAD && git push origin master"

# Or deploy specific version
ssh user@server.com "cd /var/www/html/wp-content/plugins/sct_event-administration && git checkout v2.10.8 && git push origin master"
```

## Verification Checklist

After completing all phases:

- [ ] Local commits go directly to production without manual steps
- [ ] No ZIP files are used anymore
- [ ] Full commit history is available on server
- [ ] Deployment takes less than 1 minute
- [ ] Rollback is simple (one command)
- [ ] Team understands the workflow

## Troubleshooting Quick Reference

| Problem | Solution |
|---------|----------|
| SSH permission denied | Run `ssh-copy-id -i ~/.ssh/id_ed25519.pub user@host` |
| "Uncommitted changes" error | Run `git status` and commit or stash changes |
| Deploy script not found | Ensure you're in plugin directory: `cd wp-content/plugins/sct_event-administration` |
| Changes don't appear on server | SSH in and run: `cd /path && git pull origin master` |
| Need to undo deployment | On server: `git revert HEAD && git push origin master` |
| Version mismatch | Run `./release.sh` to bump version officially |

## Git Workflow Cheat Sheet

```bash
# View status
git status

# See what changed
git diff

# Commit changes
git add .
git commit -m "Description of change"

# Push to GitHub
git push origin master

# Deploy to production
./deploy.sh

# Create release
./release.sh 2.11.0

# View commit history
git log --oneline -10

# Rollback last commit
git revert HEAD
git push origin master
./deploy.sh

# See differences between local and server
git diff origin/master

# Create feature branch
git checkout -b feature/name
# ... make changes ...
git push origin feature/name
# Create PR on GitHub for review

# Switch back to master
git checkout master
git pull origin master
```

## Success Indicators

You've successfully implemented modern deployment when:

✅ You can deploy without using any ZIP files
✅ Deployment happens with one command (`./deploy.sh`)
✅ All versions are tracked in Git history
✅ You can easily rollback changes
✅ Multiple developers can work on the same plugin
✅ Staging and production can use same workflow

---

**Next: Start with Phase 1 today, complete Phase 4 by end of week!**
