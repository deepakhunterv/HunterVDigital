# HunterVDigital

Agency plugin for custom WordPress client features.

## What this plugin includes

- Dashboard welcome widget with instruction text and embedded training videos.
- Settings page to manage videos, instructions, Elementor safety, and GitHub updates.
- Elementor safety guard to hide WordPress editor links on Elementor-built pages.
- GitHub release-based update checker (default repo: `deepakhunterv/HunterVDigital`).

## Fixing GitHub PR merge conflicts

If GitHub reports conflicts in your PR:

1. Checkout your feature branch locally.
2. Pull/rebase the latest target branch (`main` or your deployment branch).
3. Resolve conflicts in `huntervd-client-training-dashboard.php` by keeping:
   - the latest `Version` header,
   - the latest `DEFAULT_SETTINGS` values,
   - the latest updater hooks and Elementor safety logic.
4. Run a syntax check:
   ```bash
   php -l huntervd-client-training-dashboard.php
   ```
5. Commit the conflict resolution and push the branch again.
