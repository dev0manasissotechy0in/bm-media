<?php
$response = file_get_contents('http://localhost/api/articles/by-category.php?category_id=1');
echo $response;
