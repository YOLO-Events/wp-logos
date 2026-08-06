# Deploying WP Logos to WordPress.org

This document describes the steps to publish and update the **WP Logos** plugin on [WordPress.org](https://wordpress.org/plugins/) using the [10up/action-wordpress-plugin-deploy](https://github.com/10up/action-wordpress-plugin-deploy) GitHub Action.

---

## Overview

The deployment workflow is defined in [`.github/workflows/deploy-wordpress.yml`](.github/workflows/deploy-wordpress.yml).

It triggers automatically whenever a new **Git tag** is pushed to the repository. The workflow:

1. Checks out the code.
2. Installs Node.js dependencies (`npm ci`).
3. Builds the Gutenberg block assets (`npm run build`).
4. Uses the `10up/action-wordpress-plugin-deploy` action to sync the plugin to the WordPress.org SVN repository.

---

## Prerequisites

### 1. WordPress.org Account

You need a [WordPress.org](https://wordpress.org) account that is listed as a **contributor** or **author** of the `wp-logos` plugin on the plugin directory.

### 2. Plugin Listing on WordPress.org

The plugin must already be approved and listed at `https://wordpress.org/plugins/wp-logos/`. The SVN repository for the plugin is at:

```
https://plugins.svn.wordpress.org/wp-logos/
```

### 3. GitHub Repository Secrets

Add the following secrets to the GitHub repository (**Settings → Secrets and variables → Actions → New repository secret**):

| Secret name    | Description                                      |
|----------------|--------------------------------------------------|
| `SVN_USERNAME` | Your WordPress.org username                      |
| `SVN_PASSWORD` | Your WordPress.org password (or app password)    |

---

## Deployment Steps

### Step 1 — Prepare the Release

1. Update the plugin version number in the following files:
   - `wp-logos.php` — `Version:` header field
   - `readme.txt` — `Stable tag:` field and the `== Changelog ==` section

2. Commit and push the version bump changes to the main branch:
   ```bash
   git add wp-logos.php readme.txt
   git commit -m "chore: bump version to X.Y.Z"
   git push origin main
   ```

### Step 2 — Create and Push a Git Tag

The deploy workflow is triggered by a new tag. Create a tag that matches the plugin version:

```bash
git tag X.Y.Z
git push origin X.Y.Z
```

> The tag name is used as the **SVN tag** on WordPress.org (e.g. `1.0.1`). Keep it consistent with the `Stable tag` in `readme.txt`.

### Step 3 — Monitor the Workflow

1. Go to the **Actions** tab in the GitHub repository.
2. Find the **Deploy to WordPress.org** workflow run for your tag.
3. Monitor the build and deploy steps. The action will:
   - Build the plugin assets.
   - Checkout the SVN trunk and create a new SVN tag.
   - Sync the files (respecting any `.distignore` rules).
   - Commit to SVN.

### Step 4 — Verify on WordPress.org

Once the workflow completes successfully:

- Visit `https://wordpress.org/plugins/wp-logos/` to confirm the new version is listed.
- The SVN repository will have a new tag at `https://plugins.svn.wordpress.org/wp-logos/tags/X.Y.Z/`.

---

## Excluding Files from the Release

To exclude development files (e.g. `node_modules`, `src/`, test files) from the WordPress.org release, create a `.distignore` file in the repository root:

```
.git
.github
node_modules
src
package.json
package-lock.json
*.map
```

The `10up/action-wordpress-plugin-deploy` action automatically respects `.distignore` when syncing files to SVN.

---

## Troubleshooting

| Problem | Solution |
|---|---|
| Workflow does not trigger | Ensure the push is a **tag**, not a branch commit. |
| SVN authentication fails | Double-check `SVN_USERNAME` and `SVN_PASSWORD` secrets in GitHub. |
| Missing files in release | Review `.distignore` — ensure needed files are not excluded. |
| Build step fails | Run `npm ci && npm run build` locally to reproduce and fix. |
| Stable tag mismatch | Ensure the Git tag, `Stable tag` in `readme.txt`, and `Version` in `wp-logos.php` all match. |

---

## References

- [10up/action-wordpress-plugin-deploy](https://github.com/10up/action-wordpress-plugin-deploy)
- [WordPress.org Plugin Developer Handbook – Using Subversion](https://developer.wordpress.org/plugins/wordpress-org/how-to-use-subversion/)
- [WordPress.org Plugin Directory Guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)
