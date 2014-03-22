<?php

require_once 'gemstone.php';

G::init('mysql:host=localhost;dbname=test;charset=utf8', 'localhost', '');

$book = G::mine('books');

$book   ->fname("Douglas")
		->lname("Adams")
		->title("Hitchhiker's Guide to the Galaxy")
		->price(34.99)
		->stock(12)
		->region('Canada', 'United States')
		->rating('PG')
		// ->extra('test')
;

$booktwo = G::mine('books');

$booktwo   ->fname("Douglas")
		->lname("Adams")
		->title("Derk Gently's Holistic Detective Agency")
		->price(34.99)
		->stock(12)
		->region('Canada')
		->rating('PG')
		->extra('test')
;

$book->sync($booktwo);

G::deposit($book);

?>