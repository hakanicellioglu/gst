# GST Project

This project is a PHP-based quoting system. Product images are stored directly in the database using a BLOB `image_data` column with its MIME type in `image_type`. Images are only shown on the optimization page using base64 data URLs.

See `image_upload_example.php` for a minimal example of handling an image upload and saving the binary data.
