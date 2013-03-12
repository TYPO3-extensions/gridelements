<?php
//this is just a dummy JSON object
header('application/json');
echo json_encode(array('a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5));
?>