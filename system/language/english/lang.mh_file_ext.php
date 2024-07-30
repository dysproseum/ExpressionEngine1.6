<?php

$L = array(

'file' =>
'File',

'remove' =>
'Remove',

'allow_multiple' =>
'Allow Multiple Files?  (Requires JS)',

'show_preview' =>
'Show image previews?',

'select_location' =>
'Select an upload location (or leave it blank to use the defaults)',

'server_path' =>
'Server Path (/...)',

'url' =>
'Site Path (http://...)',

'store_thumbs_separately' =>
'Store Thumbs in Separate Folder',

'server_path_thumb' =>
'Server Path to Thumbnails',

'site_path_thumb' =>
'Site Path to Thumbnails',

'max_size' =>
'Max File Size (in bytes)',

'allowed_types' =>
'Allowed File Types',

'max_width' =>
'Max Width',

'max_height' =>
'Max Height',

'thumb_width' =>
'Thumb Width',

'thumb_height' =>
'Thumb Height',

'provide_quicksave' =>
'Provide \'Quick Save\' Button by Field?',

// ERRORS

'error_filesize' =>
'<strong>%{field}</strong> &ndash; The file size of &lsquo;%{file}&rsquo; is too big.',

'error_filesize_ini' =>
'<strong>%{field}</strong> &ndash; The file size of &lsquo;%{file}&rsquo; is larger than what is allowed in your php.ini.',

'error_filetype' =>
'<strong>%{field}</strong> &ndash; The filetype &lsquo;%{file}&rsquo; is not allowed.',

'error_filedimensions' =>
'<strong>%{field}</strong> &ndash; The file dimensions of &lsquo;%{file}&rsquo; is too big.  You may try resizing your image.',

'error_transfer' =>
'<strong>%{field}</strong> &ndash; There was a problem uploading &lsquo;%{file}&rsquo;.',

'error_thumbnail' =>
'<strong>%{field}</strong> &ndash; There was a problem creating a thumbnail for &lsquo;%f&rsquo;.',

'error_empty_filename' =>
'<strong>%{field}</strong> &ndash; There was a problem during uploading because the file&rsquo;s name was empty.',

'error_no_server_path' =>
'You have not defined a server path in the <a href="'.BASE.AMP.'C=admin'.AMP.'M=blog_admin'.AMP.'P=upload_prefs" title="File Upload Settings">Upload Settings</a>.',

'error_file_exists' =>
'<strong>%{field}</strong> &ndash; The file &lsquo;%{file}&rsquo; already exists, please rename your file before uploading it to the server. (<input type="checkbox" name="auto_rename[]" value="%{file}" /> Automagically rename this file the next time it&rsquo;s uploaded?)',

'error_filesize_width' =>
'<strong>%{field}</strong> &ndash; The file &lsquo;%{file}&rsquo; is too wide.  You may try resizing your image.',

'error_filesize_height' =>
'<strong>%{field}</strong> &ndash; The file &lsquo;%{file}&rsquo; is too tall.  You may try resizing your image.',

// END
''=>''
);
?>