# Tinglayan fixes - 22 October 2026

## What changed

- Administrator and Editor roles are retained. Only Administrators can view, create, or delete accounts. Editors retain their existing content-management access; their sidebar hides Admin Users and direct account-management requests are rejected server-side.
- Audio uploads validate the recording, create the storage folder when missing, and stop the save if storage fails. A database failure removes the newly uploaded file instead of leaving an orphan.
- Both dictionary API endpoints generate HTTPS audio links behind Render's proxy.
- Android feedback labels, dropdown items, and action buttons use theme-aware colors. Feedback remains available from cards and word details.
- The website update also includes the previous Tinglayan naming and restored feedback changes.

## Deploy the website

1. Back up the existing database and any uploaded audio recordings.
2. No database migration is needed for these fixes: the earlier admin-only update was never deployed. Do not run the withdrawn admin-only SQL, import `database.sql`, or reset the database. Existing account roles remain unchanged.
3. Extract `releases/Tinglayan-Fixes-20261022.zip`. This replacement archive supersedes the earlier package with the same name. Upload its files to the existing GitHub repository, preserving their folders, then commit. Do not replace the repository's existing database connection configuration or Docker files. Neither is included in this package.
4. Wait for the connected Render deployment to finish. Sign out and sign back in with the appropriate account.
5. Verify the public dictionary, feedback, and Admin Users page as Administrator. As Editor, verify content management works and direct access to Admin Users is denied. Test an audio recording on a legitimate entry and check playback on both the website and Android.

The website admin login cannot publish source changes to GitHub or Render. These server fixes are not live until the deployment completes.

## Audio persistence

The missing-folder fix makes uploads work on a writable server filesystem. Render's ephemeral filesystem does not retain uploaded recordings through restarts or deployments. Durable recordings require persistent storage (for the Docker service, mount it at `/var/www/html/uploads/audio`) or an external object-storage integration. That hosting change is not included here. Keep backups of recordings; deploying code alone does not recover files from earlier failed uploads.

## Android

The updated debug APK is `AndroidApp/app/build/outputs/apk/debug/app-debug.apk`. It keeps the existing application ID so it updates the same installed app. The corrected build was installed on the connected phone. Its dark feedback dialog and empty-comment validation were visually checked without creating a live feedback record.

## Verification

- All ten local PHP/JavaScript regression test suites passed, including both login flows, Administrator/Editor permissions, isolated HTTP upload, rollback, and API URL tests. Editor tests verify content saves succeed while account creation/deletion is denied without modifying accounts. Test fixtures use a temporary SQLite database and temporary audio folder, removed at completion.
- PHP syntax checks passed for 52 files.
- Android `:app:assembleDebug :app:testDebugUnitTest :app:lintDebug` passed: 12 unit tests, no failures; lint reported 0 errors and 35 warnings. The warnings remain for a separate focused review.
- A final read-only comparison against the live baseline confirmed the original words, dialects, categories, parts of speech, accounts, and feedback were unchanged and no earlier test records remained.
- Live playback using the corrected server URL cannot be verified until the website update is deployed. Phone visual verification covered the current dark dialog, not a separate light-mode run.
