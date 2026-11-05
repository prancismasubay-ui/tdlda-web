# Persistent audio storage with your existing TiDB database

The website can store pronunciation files in your existing TiDB database. This avoids a separate object-storage account and uses the TiDB connection the hosted website already has. The application imposes no audio file size cap. It splits each file into 256 KiB database chunks so the file does not need to fit in one TiDB row. Render and PHP still enforce their own request and upload limits, which may need adjustment for unusually large recordings; the recordings you described are under 50 KB each.

## One-time setup

1. Open the TiDB cluster used by the website and select the database that contains the `words` table. The SQL Editor in the supplied screenshot previously showed `tdlda`; confirm that this is still the app's database.
2. Run the SQL in `migrations/20261105_audio_storage.sql`. It creates two audio-only tables and does not alter or delete dictionary records.
3. Upload the contents of `Tinglayan-Audio-Storage-TiDB-20261105.zip` to the root of the GitHub repository, preserving folder paths, then deploy the website.
4. In Render, open the `tdlda-web` service's **Environment** settings and add:

   ```text
   AUDIO_STORAGE_DRIVER=tidb
   ```

   Save the change and wait for Render to redeploy. The feature uses the website's existing TiDB database connection; it needs no separate storage keys.
5. In the admin dictionary, edit a word and upload its recording. The same returned audio URL works on the website and Android app.

## Restore recordings

Restarted Render instances already lost the old audio files, so the old database filenames alone cannot restore playback. Re-upload the originals or backups after setup. The sample `yes.aac` matches the existing Itongrayan entry **Wen — Yes** (ID `120013`).

At under 50 KB each, 500 recordings would use under about 25 MB, well within the cluster's 5 GiB Starter row-storage quota. The plan also includes 50 million request units per month; usage beyond the free quota can restrict new connections until the quota resets or the plan changes. Check the cluster's Usage page periodically. [TiDB Starter quotas](https://docs.pingcap.com/tidbcloud/select-cluster-tier/)

TiDB's default maximum row size is 6 MiB. Chunking keeps each stored audio piece below that row limit. [TiDB BLOB and row-size limits](https://docs.pingcap.com/tidb/v8.1/data-type-string/)
