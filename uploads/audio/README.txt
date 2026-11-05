Pronunciation recordings use this directory in local development. On Render,
configure AUDIO_STORAGE_DRIVER=tidb so recordings are stored in the website's
TiDB database and survive restarts and deployments. See AUDIO_STORAGE_SETUP.md
for the one-time table and Render setup. Keep this directory in the repository
so local development can use the filesystem fallback.
