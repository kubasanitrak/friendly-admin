# Friendly Admin

Client-friendly WordPress admin: replace the dashboard with a selected page, and control which main sidebar menus each role can see.

Repository: https://github.com/kubasanitrak/friendly-admin

## Features (v0.1)

1. **Custom dashboard** — pick a published WP page; its content replaces the default dashboard widgets for selected roles. Unrestricted users keep the stock dashboard.
2. **Menu visibility** — per-role whitelist of main sidebar items. Administrator can be restricted. Unrestricted user IDs (and multisite super admins) always see all menus. Direct URLs to hidden screens redirect away.
3. **Chrome** — optionally hide admin notices and Help / Screen Options for restricted users; optional custom admin footer text.

Settings live under the top-level **Friendly Admin** menu (only unrestricted users can change them).

On activation, the activating user’s ID is added to the unrestricted list. On single-site installs, unrestricted access is **only** that list (WordPress `is_super_admin()` would otherwise treat every Administrator as unrestricted).

## Local development

1. Clone or symlink this folder into `wp-content/plugins/friendly-admin`.
2. Activate the plugin.
3. Open **Friendly Admin** while logged in as super admin (or an ID you will add to the unrestricted list).
4. Configure tabs: Nástěnka → Menu → Rozhraní → Přístup.

Styles: edit `admin/scss/admin.scss`, compile to `admin/css/admin.css` (e.g. `npx sass admin/scss/admin.scss admin/css/admin.css`).

## Releasing an update

1. Bump `Version` and `FA_VERSION` in `friendly-admin.php`.
2. Add a `## [x.y.z]` section to `CHANGELOG.md`.
3. Commit and push to `main`.
4. Tag and push (header `0.1.0` → tag `v0.1.0`):

```bash
git tag v0.1.0
git push origin v0.1.0
```

GitHub Actions builds `friendly-admin.zip` and publishes a GitHub Release. Sites update via [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) (vendored under `lib/plugin-update-checker/`).
