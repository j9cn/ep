php_value auto_append_file none
php_value auto_prepend_file none
php_value include_path "."
DirectoryIndex index.php

<IfModule mod_rewrite.c>
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} -s [OR]
RewriteCond %{REQUEST_FILENAME} -l [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^.*$ - [NC,L]

RewriteRule ^(.*)\.(ico)$ - [NC,L]
RewriteCond %{REQUEST_URI}::$1 ^(/.+)(.+)::\2$
RewriteRule ^(.*) - [E=BASE:%1]
RewriteRule ^(.*)$ %{ENV:BASE}index.php/$1 [NC,L]
</IfModule>

<IfModule mod_headers.c>
<FilesMatch ".(js|css|xml|gz)$">
Header append Vary Accept-Encoding
</FilesMatch>
</IfModule>