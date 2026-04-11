# MovieMania notes

This version application is based on the existing databse MovieLens schema:

- movies(movieid, title, genres)
- ratings(userid, movieid, rating, timestamp)
- tags(userid, movieid, tag, timestamp)
- links(movieid, imdbid, tmdbid)

## Important setup

1. Put the project folder inside your MAMP document root (htdocs).
2. In `api/config.php`, set the real PostgreSQL password and confirm the database name.
3. Open the site through your local server, for example:
   - `http://localhost/MovieMania/MovieManiaRebrand_wired_real_schema/MovieManiaRebrand_wired/index.html`
4. In the frontend, the **Reviewer Name** field is used as a **numeric user ID**.
5. Movie titles must match titles in the `movies` table.

## What each page does

- **Data Entry**: inserts or updates a rating in `ratings` and optionally inserts a tag in `tags`.
- **Delete Data**: searches by user ID and optional movie title, then deletes the matching rating and related tags.
- **Update Data**: searches by user ID and optional movie title, then updates the rating and replaces the tag.
- **Summary**: loads totals from `ratings` and shows top-rated movies.
