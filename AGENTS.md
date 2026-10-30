# Project packaging instructions

- Every release ZIP must contain the plugin directory's **contents directly at the archive root**: `assets/`, `includes/`, `languages/`, `templates/`, `woocommerce-toasts.php`, and the other approved runtime files.
- Never add a containing plugin/project folder inside the ZIP. Zip the contents, not the enclosing directory. This project uses a flat archive to avoid an extra directory when extracting into an existing plugin folder.
- The package name is `wc-dynamic-notice-toasts`; the ZIP is `dist/wc-dynamic-notice-toasts.zip` and the release staging directory is `dist/wc-dynamic-notice-toasts/`.
- Keep the display name, PHP filenames, option names, and code identifiers unchanged unless the user explicitly requests changes to those identifiers.
- The WordPress.org submission slug and translation domain are `dynamic-notice-toasts-for-woocommerce`. Keep all translation calls, the plugin header, POT filename, WPCS rules and Plugin Check slug aligned with it. The external ZIP name is independent of this slug.
- Use the release builder's runtime allowlist. Exclude development dependencies, tests, tools, reports, credentials, Git files, and other archives.
- Run `npm run build` and `npm run verify:release` after packaging changes. The verifier must enforce the flat archive structure and compare the packaged files with their sources.
