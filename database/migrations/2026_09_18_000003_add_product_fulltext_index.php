<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Generated columns give a stable, indexable text target for each
        // locale. A JSON column cannot be FULLTEXT-indexed directly, and the
        // translatable fields are stored as `{"id": "...", "en": "..."}`.
        //
        // `JSON_UNQUOTE(JSON_EXTRACT(...))` rather than the `->>` operator:
        // MariaDB 13 rejects `->>` inside a generated column with a syntax
        // error, while this form is accepted. Verified against the real
        // server, both spellings.
        DB::statement('ALTER TABLE products ADD COLUMN name_id_text TEXT
            GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(name, "$.id"))) STORED');
        DB::statement('ALTER TABLE products ADD COLUMN name_en_text TEXT
            GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(name, "$.en"))) STORED');

        // Two single-column indexes, not one composite index.
        //
        // A MATCH clause must name exactly the columns of one FULLTEXT index.
        // With a composite `(name_id_text, name_en_text)` index, the
        // locale-selected `MATCH(name_id_text)` raises
        // `ERROR 1191: Can't find FULLTEXT index matching the column list` —
        // so every search request would be a 500. Verified against MariaDB
        // 13.0.2: composite index plus a single-column MATCH fails; two
        // single-column indexes both resolve.
        DB::statement('ALTER TABLE products ADD FULLTEXT products_name_id_fulltext (name_id_text)');
        DB::statement('ALTER TABLE products ADD FULLTEXT products_name_en_fulltext (name_en_text)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE products DROP INDEX products_name_id_fulltext');
        DB::statement('ALTER TABLE products DROP INDEX products_name_en_fulltext');
        DB::statement('ALTER TABLE products DROP COLUMN name_id_text');
        DB::statement('ALTER TABLE products DROP COLUMN name_en_text');
    }
};
