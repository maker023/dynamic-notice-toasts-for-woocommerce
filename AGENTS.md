# Project packaging instructions

- Every release ZIP must contain the plugin directory's **contents directly at the archive root**: `assets/`, `includes/`, `languages/`, `templates/`, `woocommerce-toasts.php`, and the other approved runtime files.
- Never add a containing plugin/project folder inside the ZIP. Zip the contents, not the enclosing directory. This project uses a flat archive to avoid an extra directory when extracting into an existing plugin folder.
- The package name is `wc-dynamic-notice-toasts`; the ZIP is `dist/wc-dynamic-notice-toasts.zip` and the release staging directory is `dist/wc-dynamic-notice-toasts/`.
- Keep the display name, PHP filenames, option names, and code identifiers unchanged unless the user explicitly requests changes to those identifiers.
- The WordPress.org submission slug and translation domain are `dynamic-notice-toasts-for-woocommerce`. Keep all translation calls, the plugin header, POT filename, WPCS rules and Plugin Check slug aligned with it. The external ZIP name is independent of this slug.
- Use the release builder's runtime allowlist. Exclude development dependencies, tests, tools, reports, credentials, Git files, and other archives.
- Run `npm run build` and `npm run verify:release` after packaging changes. The verifier must enforce the flat archive structure and compare the packaged files with their sources.

# WordPress.org publishing instructions

- The plugin is live on WordPress.org. Publish only through `tools/deploy-svn.mjs`, following "Publishing to WordPress.org" in `README.md`. Never hand-copy files into SVN.
- Run `npm run test:flows` before every release and after any change to notice capture, the frontend script, or the directory blueprint.
- Publishing is public and permanent. Show the user the dry run and get explicit approval before running with `--commit`.
- Never ask for, print, log, or store the SVN password. The user sets `SVN_PASSWORD` in their own terminal; delete it from the user environment after the commit and confirm it is gone.
- In PowerShell, call `node tools/deploy-svn.mjs --commit` directly. `npm run deploy:svn -- --commit` drops the flag and stays a dry run.
- Never reuse or overwrite an SVN tag. Every code change ships under a new version; use `--assets-only` for directory assets.
